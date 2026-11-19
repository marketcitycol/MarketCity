<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\OrderService;

/**
 * Aplica a un pedido el estado REAL de un pago, consultado al proveedor servidor a servidor.
 * Se usa desde el aviso firmado (webhook), desde el regreso del navegador y desde la conciliación.
 */
class PaymentReconciler
{
    public function __construct(private OrderService $orders) {}

    /** @return string resultado legible para la bitácora */
    public function apply(OnlineGateway $gateway, string $providerPaymentId, ?Order $expected = null): string
    {
        $info = $gateway->fetchPayment($providerPaymentId);
        $order = $info['order_number'] ? Order::where('number', $info['order_number'])->first() : null;
        if (! $order) {
            return 'Pago '.$providerPaymentId.' sin pedido de MarketCity.';
        }
        if ($expected && $expected->id !== $order->id) {
            return 'El pago '.$providerPaymentId.' es de otro pedido.';
        }
        if ($order->payment_method !== $gateway->key()) {
            return "El pedido {$order->number} no se pagaba con {$gateway->label()}.";
        }

        $payment = $order->payments()->where('provider', $gateway->key())->latest('id')->first();
        if (! $payment) {
            return "El pedido {$order->number} no tiene un intento de pago.";
        }
        $payload = ['proveedor' => $info['raw'], 'pago_proveedor' => $providerPaymentId];

        if ($info['status'] === 'approved') {
            // Nunca se aprueba un valor o una moneda distinta a la del pedido.
            if ($info['amount'] !== (int) $order->total || strtoupper((string) $info['currency']) !== 'COP') {
                if ($payment->status === 'pending') {
                    $payment->update(['status' => 'review', 'payload' => $payload]);
                    $order->log('pago', "{$gateway->label()} aprobó ".cop($info['amount'] ?? 0)." {$info['currency']} pero el pedido es de ".cop($order->total).' COP. Requiere revisión manual.');
                }

                return "Valor distinto en {$order->number}: queda en revisión.";
            }
            $payment->update(['provider_reference' => $providerPaymentId]);
            $this->orders->markPaid($order, $payment, $payload);

            return "Pago aprobado en {$order->number}.";
        }
        if ($info['status'] === 'declined') {
            $this->orders->markFailed($order, $payment, $payload, 'Pago rechazado por '.$gateway->label());

            return "Pago rechazado en {$order->number}.";
        }

        return "Pago pendiente en {$order->number}.";
    }
}
