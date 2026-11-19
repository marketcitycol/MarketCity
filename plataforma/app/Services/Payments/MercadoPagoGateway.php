<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Mercado Pago (Checkout Pro): tarjeta, PSE y efectivo. Las llaves van solo en el .env del servidor
 * (MP_ACCESS_TOKEN y MP_WEBHOOK_SECRET); con llaves de prueba funciona en modo de prueba.
 */
class MercadoPagoGateway implements OnlineGateway
{
    private const API = 'https://api.mercadopago.com';

    public function key(): string
    {
        return 'mercadopago';
    }

    public function label(): string
    {
        return 'Mercado Pago';
    }

    public function description(): string
    {
        return 'Tarjeta de crédito o débito, PSE o efectivo. Te llevamos a Mercado Pago para pagar de forma segura.';
    }

    public static function isConfigured(): bool
    {
        return filled(config('services.mercadopago.access_token')) && filled(config('services.mercadopago.webhook_secret'));
    }

    private function http()
    {
        return Http::withToken((string) config('services.mercadopago.access_token'))->acceptJson()->timeout(20);
    }

    public function start(Order $order, Payment $payment): string
    {
        $res = $this->http()->withHeaders(['X-Idempotency-Key' => 'mc-pref-'.$payment->id])
            ->post(self::API.'/checkout/preferences', [
                'items' => [[
                    'id' => $order->number,
                    'title' => 'Pedido '.$order->number.' en MarketCity',
                    'quantity' => 1,
                    'currency_id' => 'COP',
                    'unit_price' => (int) $order->total,
                ]],
                'payer' => ['email' => $order->email, 'name' => $order->first_name, 'surname' => $order->last_name],
                'external_reference' => $order->number,
                'statement_descriptor' => 'MARKETCITY',
                'back_urls' => [
                    'success' => route('payments.return', ['gateway' => $this->key(), 'order' => $order, 't' => $order->access_token]),
                    'pending' => route('payments.return', ['gateway' => $this->key(), 'order' => $order, 't' => $order->access_token]),
                    'failure' => route('payments.return', ['gateway' => $this->key(), 'order' => $order, 't' => $order->access_token]),
                ],
                'auto_return' => 'approved',
                'notification_url' => route('payments.webhook', $this->key()),
                'expires' => true,
                'expiration_date_to' => now()->addHours(24)->toIso8601String(),
            ]);

        if (! $res->successful() || ! $res->json('init_point')) {
            throw new RuntimeException('Mercado Pago no respondió. Intenta de nuevo o elige otro medio de pago.');
        }
        $payment->update(['provider_reference' => (string) $res->json('id')]);

        return (string) $res->json('init_point');
    }

    /**
     * Firma de Mercado Pago: cabecera x-signature "ts=…,v1=…"; v1 = HMAC-SHA256 del texto
     * "id:{data.id};request-id:{x-request-id};ts:{ts};" con la clave secreta de avisos.
     */
    public function verifyWebhook(Request $request): bool
    {
        $secret = (string) config('services.mercadopago.webhook_secret');
        $header = (string) $request->header('x-signature');
        if ($secret === '' || $header === '') {
            return false;
        }
        $parts = [];
        foreach (explode(',', $header) as $piece) {
            [$k, $v] = array_pad(explode('=', trim($piece), 2), 2, '');
            $parts[$k] = $v;
        }
        if (empty($parts['ts']) || empty($parts['v1'])) {
            return false;
        }
        $id = (string) $this->providerPaymentId($request);
        $id = ctype_alnum($id) ? strtolower($id) : $id;
        $manifest = '';
        if ($id !== '') {
            $manifest .= "id:{$id};";
        }
        if ($rid = $request->header('x-request-id')) {
            $manifest .= "request-id:{$rid};";
        }
        $manifest .= "ts:{$parts['ts']};";

        return hash_equals(hash_hmac('sha256', $manifest, $secret), $parts['v1']);
    }

    public function webhookEventKey(Request $request): ?string
    {
        $id = $this->providerPaymentId($request);

        return $id ? ($request->header('x-request-id') ?: 'pago-'.$id.'-'.$request->input('action', 'aviso')) : null;
    }

    public function providerPaymentId(Request $request): ?string
    {
        $id = $request->query('data_id') ?? $request->query('data.id') ?? $request->input('data.id') ?? $request->query('payment_id') ?? $request->query('collection_id');

        return $id !== null && preg_match('/^[A-Za-z0-9\-]{1,40}$/', (string) $id) ? (string) $id : null;
    }

    public function findPaymentForOrder(Order $order): ?string
    {
        $res = $this->http()->get(self::API.'/v1/payments/search', [
            'external_reference' => $order->number, 'sort' => 'date_created', 'criteria' => 'desc', 'limit' => 1,
        ]);
        $id = $res->successful() ? $res->json('results.0.id') : null;

        return $id !== null ? (string) $id : null;
    }

    public function fetchPayment(string $providerPaymentId): array
    {
        $res = $this->http()->get(self::API.'/v1/payments/'.rawurlencode($providerPaymentId));
        if (! $res->successful()) {
            throw new RuntimeException('No se pudo consultar el pago en Mercado Pago.');
        }
        $status = match ($res->json('status')) {
            'approved' => 'approved',
            'rejected', 'cancelled', 'refunded', 'charged_back' => 'declined',
            default => 'pending',
        };

        return [
            'status' => $status,
            'order_number' => $res->json('external_reference'),
            'amount' => $res->json('transaction_amount') !== null ? (int) round((float) $res->json('transaction_amount')) : null,
            'currency' => $res->json('currency_id'),
            'raw' => array_intersect_key((array) $res->json(), array_flip(['id', 'status', 'status_detail', 'payment_method_id', 'payment_type_id', 'transaction_amount', 'currency_id', 'external_reference', 'date_approved'])),
        ];
    }
}
