<?php

namespace App\Services\Orders;

use App\Mail\OrderNoticeMail;
use App\Models\Order;
use App\Models\Refund;
use App\Models\SellerOrder;
use App\Models\User;
use App\Support\Mailer;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Reembolsos (en WooCommerce: botón «Reembolso» del pedido y correo «Pedido reembolsado»).
 *
 * Registra la devolución de dinero que el equipo hizo en la pasarela o por transferencia y ajusta
 * las cuentas del vendedor:
 * - Subpedido sin entregar: se cancela (vuelven las unidades) y no se le liquida al vendedor.
 * - Subpedido entregado (devolución, garantía o retracto) a cargo de la tienda: MarketCity devuelve
 *   la comisión de lo reembolsado y el resto se descuenta del neto del vendedor. Si ya se le había
 *   liquidado, queda como descuento pendiente para la próxima liquidación.
 * - A cargo de MarketCity: el vendedor no cambia.
 *
 * No mueve dinero por sí solo: la devolución se hace en el panel de la pasarela o en el banco y aquí
 * se anota su referencia.
 */
class RefundService
{
    public function __construct(private OrderService $orders) {}

    public function refund(Order $order, ?SellerOrder $so, int $amount, string $reason, string $chargedTo, bool $restock, ?string $reference, User $by): Refund
    {
        $refund = DB::transaction(function () use ($order, $so, $amount, $reason, $chargedTo, $restock, $reference, $by) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            $so = $so ? SellerOrder::with('items.product', 'store', 'order')->lockForUpdate()->findOrFail($so->id) : null;
            if ($so && $so->order_id !== $order->id) {
                throw new RuntimeException('Ese subpedido no es de este pedido.');
            }
            if ($order->payment_status !== 'paid') {
                throw new RuntimeException('Solo se reembolsan pedidos pagados.');
            }
            $max = $so ? min($so->refundable(), $order->refundable()) : $order->refundable();
            if ($amount < 1 || $amount > $max) {
                throw new RuntimeException('El valor a reembolsar debe estar entre $1 y '.cop($max).'.');
            }
            // Un reembolso sin subpedido (compensación general) lo asume MarketCity.
            $chargedTo = $so ? $chargedTo : 'marketcity';

            $data = ['commission_reversed' => 0, 'seller_debit' => 0, 'pending_deduction' => false, 'restocked' => false];
            if ($so && $restock && ! in_array($so->status, ['entregado', 'cancelado'], true)) {
                // Aún no se entregaba: se cancela el subpedido; el vendedor no cobra ni MarketCity comisiona.
                $data['commission_reversed'] = (int) $so->commission_amount;
                if ($so->settled_at) {
                    $data['seller_debit'] = (int) $so->seller_net;
                    $data['pending_deduction'] = true;
                }
                $this->orders->cancel($so, null, 'reembolso: '.$reason);
                $data['restocked'] = true;
                $so->refresh();
            } else {
                if ($so && $restock && $so->status === 'entregado') {
                    if ($amount < $so->refundable()) {
                        throw new RuntimeException('Para devolver las unidades al inventario el reembolso debe ser por todo el subpedido.');
                    }
                    foreach ($so->items as $item) {
                        $item->product?->increment('stock', $item->quantity);
                    }
                    $data['restocked'] = true;
                }
                if ($so && $chargedTo === 'tienda' && $so->status !== 'cancelado') {
                    $productPart = min($amount, max(0, $so->subtotal - ($so->discount_funded_by === 'tienda' ? (int) $so->discount_total : 0)));
                    $percent = (float) ($so->commission_rule['percent'] ?? 0);
                    $data['commission_reversed'] = min((int) $so->commission_amount, (int) round($productPart * $percent / 100));
                    $data['seller_debit'] = $amount - $data['commission_reversed'];
                    if ($so->settled_at) {
                        $data['pending_deduction'] = true;
                    } else {
                        $so->commission_amount -= $data['commission_reversed'];
                        $so->seller_net = max(0, $so->seller_net - $data['seller_debit']);
                    }
                }
            }
            if ($so) {
                $so->refunded_total = (int) $so->refunded_total + $amount;
                $so->save();
            }

            $refund = $order->refunds()->create($data + [
                'seller_order_id' => $so?->id,
                'user_id' => $by->id,
                'amount' => $amount,
                'reason' => $reason,
                'charged_to' => $chargedTo,
                'reference' => $reference,
            ]);

            $order->refunded_total = (int) $order->refunded_total + $amount;
            if ($order->refunded_total >= $order->total) {
                $order->payment_status = 'refunded';
            }
            $order->save();
            $order->log('reembolso', 'Reembolso de '.cop($amount).($so ? ' ('.$so->store->name.')' : '').": {$reason}. A cargo de ".($chargedTo === 'tienda' ? 'la tienda' : 'MarketCity')
                .($reference ? " · ref. {$reference}" : '').($data['pending_deduction'] ? ' · ya estaba liquidado: descontar '.cop($data['seller_debit']).' en la próxima liquidación' : '').'.', $so, $by);

            return $refund;
        });

        Mailer::send($order->email, new OrderNoticeMail($order->fresh(), 'refunded', $reason, $so?->fresh(), $refund->amount));

        return $refund;
    }
}
