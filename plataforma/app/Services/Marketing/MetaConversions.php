<?php

namespace App\Services\Marketing;

use App\Models\Order;
use App\Support\Analytics;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Compra (Purchase) de Meta solo con pedido pagado (traslado del snippet #85).
 *
 * El navegador solo envía «purchase» en la página de gracias cuando el pedido ya está pagado.
 * Si el pago llega después (aviso de la pasarela, transferencia aprobada a mano), se envía por la
 * API de conversiones con el mismo event_id (número de pedido) para que Meta no la cuente dos veces.
 * Solo se envía si el comprador aceptó la medición en el aviso de cookies.
 * El token va en el .env del servidor (META_CAPI_TOKEN), nunca en el repositorio.
 */
class MetaConversions
{
    public static function configured(): bool
    {
        return (bool) (Analytics::ids()['meta'] && config('services.meta.capi_token'));
    }

    public function purchase(Order $order): bool
    {
        if (! self::configured() || ! $order->analytics_consent || $order->meta_purchase_sent_at || $order->payment_status !== 'paid') {
            return false;
        }
        $order->loadMissing('items');
        $hash = fn (?string $v) => $v ? [hash('sha256', $v)] : null;

        $event = [
            'event_name' => 'Purchase',
            'event_time' => ($order->paid_at ?? now())->getTimestamp(),
            'event_id' => $order->number,
            'action_source' => 'website',
            'event_source_url' => route('orders.thanks', $order),
            'user_data' => array_filter([
                'em' => $hash(mb_strtolower(trim($order->email))),
                'ph' => $hash(MessagingPermissions::normalizePhone($order->phone)),
                'client_ip_address' => $order->ip_address,
                'client_user_agent' => $order->user_agent,
                'fbp' => $order->fbp,
                'fbc' => $order->fbc,
            ]),
            'custom_data' => [
                'currency' => 'COP',
                'value' => (int) $order->total,
                'order_id' => $order->number,
                'content_type' => 'product',
                'contents' => $order->items->map(fn ($i) => ['id' => $i->sku ?: 'P'.$i->product_id, 'quantity' => (int) $i->quantity])->values()->all(),
            ],
        ];

        try {
            $pixel = Analytics::ids()['meta'];
            $response = Http::timeout(10)->asJson()
                ->post("https://graph.facebook.com/v21.0/{$pixel}/events?access_token=".urlencode((string) config('services.meta.capi_token')), ['data' => [$event]]);
            if (! $response->successful()) {
                report(new \RuntimeException('Meta CAPI respondió '.$response->status()));

                return false;
            }
        } catch (Throwable $e) {
            report($e);

            return false;
        }

        $order->update(['meta_purchase_sent_at' => now()]);
        $order->log('medicion', 'Compra enviada a Meta por la API de conversiones.');

        return true;
    }
}
