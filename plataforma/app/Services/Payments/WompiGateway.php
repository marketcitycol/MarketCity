<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Wompi (Bancolombia): tarjeta, PSE, Nequi, botón Bancolombia y corresponsal. Checkout web de Wompi
 * con firma de integridad; el estado se confirma con el evento firmado y consultando la transacción.
 *
 * Llaves solo en el .env del servidor: WOMPI_PUBLIC_KEY, WOMPI_PRIVATE_KEY, WOMPI_INTEGRITY_SECRET y
 * WOMPI_EVENTS_SECRET. Con llaves pub_test_/prv_test_ trabaja contra el ambiente de pruebas (sandbox).
 */
class WompiGateway implements OnlineGateway
{
    private const CHECKOUT = 'https://checkout.wompi.co/p/';

    public function key(): string
    {
        return 'wompi';
    }

    public function label(): string
    {
        return 'Wompi';
    }

    public function description(): string
    {
        return 'Tarjeta, PSE, Nequi o botón Bancolombia. Te llevamos a Wompi (Bancolombia) para pagar de forma segura.';
    }

    public static function isConfigured(): bool
    {
        foreach (['public_key', 'private_key', 'integrity_secret', 'events_secret'] as $k) {
            if (blank(config('services.wompi.'.$k))) {
                return false;
            }
        }

        return true;
    }

    private static function api(): string
    {
        return str_starts_with((string) config('services.wompi.public_key'), 'pub_prod_')
            ? 'https://production.wompi.co/v1'
            : 'https://sandbox.wompi.co/v1';
    }

    private function http()
    {
        return Http::withToken((string) config('services.wompi.private_key'))->acceptJson()->timeout(20);
    }

    /** Referencia única por intento: el número del pedido más el intento («MC-000123-P45»). */
    public static function reference(Order $order, Payment $payment): string
    {
        return $order->number.'-P'.$payment->id;
    }

    public static function orderNumberFromReference(?string $reference): ?string
    {
        return $reference && preg_match('/^(.+)-P\d+$/', $reference, $m) ? $m[1] : null;
    }

    /** Firma de integridad: SHA-256 de referencia + valor en centavos + moneda + secreto de integridad. */
    public static function integritySignature(string $reference, int $amountInCents, string $currency = 'COP'): string
    {
        return hash('sha256', $reference.$amountInCents.$currency.config('services.wompi.integrity_secret'));
    }

    public function start(Order $order, Payment $payment): string
    {
        $reference = self::reference($order, $payment);
        $cents = (int) $order->total * 100;
        $payment->update(['provider_reference' => $reference]);

        // Sin datos personales en la dirección: el comprador los escribe en Wompi.
        return self::CHECKOUT.'?'.http_build_query([
            'public-key' => config('services.wompi.public_key'),
            'currency' => 'COP',
            'amount-in-cents' => $cents,
            'reference' => $reference,
            'signature:integrity' => self::integritySignature($reference, $cents),
            'redirect-url' => route('payments.return', ['gateway' => $this->key(), 'order' => $order, 't' => $order->access_token]),
        ]);
    }

    /**
     * Evento firmado de Wompi: checksum = SHA-256 de los valores de signature.properties (en orden,
     * tomados de data), más timestamp, más el secreto de eventos.
     */
    public function verifyWebhook(Request $request): bool
    {
        $secret = (string) config('services.wompi.events_secret');
        $props = $request->input('signature.properties');
        $checksum = (string) ($request->input('signature.checksum') ?: $request->header('X-Event-Checksum'));
        $timestamp = $request->input('timestamp');
        if ($secret === '' || ! is_array($props) || $props === [] || $checksum === '' || ! is_scalar($timestamp)) {
            return false;
        }
        $text = '';
        foreach ($props as $path) {
            $value = data_get($request->input('data'), (string) $path);
            if (! is_scalar($value)) {
                return false;
            }
            $text .= is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }
        $text .= $timestamp.$secret;

        return hash_equals(strtolower($checksum), hash('sha256', $text));
    }

    public function webhookEventKey(Request $request): ?string
    {
        $id = $this->providerPaymentId($request);
        $status = $request->input('data.transaction.status');

        return $id ? 'tx-'.$id.'-'.(is_string($status) ? $status : 'aviso').'-'.$request->input('timestamp') : null;
    }

    public function providerPaymentId(Request $request): ?string
    {
        // El aviso trae data.transaction.id; el regreso del navegador trae ?id=.
        $id = $request->input('data.transaction.id') ?? $request->query('id');

        return is_scalar($id) && preg_match('/^[A-Za-z0-9\-]{4,60}$/', (string) $id) ? (string) $id : null;
    }

    public function findPaymentForOrder(Order $order): ?string
    {
        $payment = $order->payments()->where('provider', $this->key())->latest('id')->first();
        if (! $payment) {
            return null;
        }
        $res = $this->http()->get(self::api().'/transactions', ['reference' => self::reference($order, $payment)]);
        $id = $res->successful() ? $res->json('data.0.id') : null;

        return $id !== null ? (string) $id : null;
    }

    public function fetchPayment(string $providerPaymentId): array
    {
        $res = $this->http()->get(self::api().'/transactions/'.rawurlencode($providerPaymentId));
        if (! $res->successful() || ! is_array($res->json('data'))) {
            throw new RuntimeException('No se pudo consultar el pago en Wompi.');
        }
        $tx = (array) $res->json('data');
        $status = match ($tx['status'] ?? null) {
            'APPROVED' => 'approved',
            'DECLINED', 'VOIDED', 'ERROR' => 'declined',
            default => 'pending',
        };
        $cents = $tx['amount_in_cents'] ?? null;

        return [
            'status' => $status,
            'order_number' => self::orderNumberFromReference($tx['reference'] ?? null),
            // Un valor con centavos no se redondea: así no coincide con el pedido y queda en revisión.
            'amount' => is_numeric($cents) ? ((int) $cents % 100 === 0 ? intdiv((int) $cents, 100) : -1) : null,
            'currency' => $tx['currency'] ?? null,
            'raw' => array_intersect_key($tx, array_flip(['id', 'status', 'status_message', 'payment_method_type', 'amount_in_cents', 'currency', 'reference', 'finalized_at'])),
        ];
    }
}
