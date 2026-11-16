<?php

namespace App\Services;

use App\Models\MerchantEntity;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\Setting;
use App\Models\User;
use App\Support\BusinessDays;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    public function __construct(private Cart $cart, private CommissionCalculator $commissions) {}

    /**
     * Crea el pedido a partir del carrito: un subpedido por tienda, descuenta stock y guarda el titular vigente.
     *
     * @param  array<string,mixed>  $customer  datos validados del formulario
     */
    public function place(array $customer, string $paymentMethod, ?User $user, ?string $ip): Order
    {
        $groups = $this->cart->groups();
        if ($groups->isEmpty()) {
            throw new RuntimeException('El carrito está vacío.');
        }

        return DB::transaction(function () use ($groups, $customer, $paymentMethod, $user, $ip) {
            $merchant = MerchantEntity::current();

            $order = Order::create([
                'number' => $this->nextNumber(),
                'user_id' => $user?->id,
                'merchant_entity_id' => $merchant?->id,
                'first_name' => $customer['first_name'],
                'last_name' => $customer['last_name'],
                'document_type' => $customer['document_type'] ?? 'CC',
                'document_number' => $customer['document_number'],
                'email' => $customer['email'],
                'phone' => $customer['phone'],
                'department' => $customer['department'],
                'city' => $customer['city'],
                'address' => $customer['address'],
                'address_2' => $customer['address_2'] ?? null,
                'notes' => $customer['notes'] ?? null,
                'subtotal' => 0,
                'shipping_total' => 0,
                'total' => 0,
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                'terms_version' => (string) Setting::get('terms_version', '2026-11'),
                'terms_accepted_at' => now(),
                'marketing_optin' => (bool) ($customer['marketing_optin'] ?? false),
                'ip_address' => $ip,
                'access_token' => Str::random(40),
            ]);

            $subtotal = 0;
            $shippingTotal = 0;

            foreach ($groups as $group) {
                $store = $group['store'];
                $method = $this->cart->shippingChoice($store->id);
                if ($method === 'recogida' && ! $store->pickup_enabled) {
                    $method = 'envio';
                }
                $shipping = Cart::shippingCost($store, $method);
                $money = $this->commissions->forSellerOrder($group['subtotal'], $shipping);

                $sellerOrder = $order->sellerOrders()->create([
                    'store_id' => $store->id,
                    'subtotal' => $group['subtotal'],
                    'shipping_method' => $method,
                    'shipping_total' => $shipping,
                    'commission_amount' => $money['commission'],
                    'seller_net' => $money['seller_net'],
                    'commission_rule' => $money['rule'],
                    'status' => 'recibido',
                ]);

                foreach ($group['lines'] as $line) {
                    /** @var Product $product */
                    $product = Product::lockForUpdate()->find($line['product']->id);
                    if (! $product || $product->stock < $line['quantity']) {
                        throw new RuntimeException("No hay unidades suficientes de «{$line['product']->name}».");
                    }
                    $product->decrement('stock', $line['quantity']);
                    $product->increment('sales_count', $line['quantity']);

                    $order->items()->create([
                        'seller_order_id' => $sellerOrder->id,
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'unit_price' => $product->price,
                        'quantity' => $line['quantity'],
                        'line_total' => $product->price * $line['quantity'],
                    ]);
                }

                $subtotal += $group['subtotal'];
                $shippingTotal += $shipping;
            }

            $order->update(['subtotal' => $subtotal, 'shipping_total' => $shippingTotal, 'total' => $subtotal + $shippingTotal]);
            $order->log('creado', 'Pedido creado con '.$groups->count().' '.Str::plural('vendedor', $groups->count()).'.');

            return $order;
        });
    }

    public function createPayment(Order $order): Payment
    {
        return $order->payments()->create([
            'merchant_entity_id' => $order->merchant_entity_id,
            'provider' => $order->payment_method,
            'amount' => $order->total,
            'status' => 'pending',
        ]);
    }

    /** Pago confirmado: se fija la fecha de liquidación de cada vendedor a N días hábiles. */
    public function markPaid(Order $order, Payment $payment, array $payload = []): void
    {
        if ($order->payment_status === 'paid') {
            return;
        }

        DB::transaction(function () use ($order, $payment, $payload) {
            $payment->update(['status' => 'approved', 'payload' => $payload]);
            $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
            $due = BusinessDays::add(now(), (int) Setting::get('settlement_business_days', 10));
            $order->sellerOrders()->update(['settlement_due_on' => $due->toDateString()]);
            $order->log('pago', 'Pago aprobado por '.cop($payment->amount).' (ref. '.$payment->provider_reference.'). Liquidación a vendedores el '.$due->format('d/m/Y').'.');
        });
    }

    /** Pago rechazado: el pedido queda cancelado y el stock vuelve. */
    public function markFailed(Order $order, Payment $payment, array $payload = []): void
    {
        if ($order->payment_status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($order, $payment, $payload) {
            $payment->update(['status' => 'declined', 'payload' => $payload]);
            $order->update(['payment_status' => 'failed']);
            foreach ($order->sellerOrders as $so) {
                $this->cancel($so, null, 'Pago rechazado');
            }
            $order->log('pago', 'Pago rechazado. El pedido se canceló y las unidades volvieron al inventario.');
        });
    }

    public function advance(SellerOrder $so, string $to, ?User $by, ?string $carrier = null, ?string $tracking = null): void
    {
        if ($to !== $so->nextStatus()) {
            throw new RuntimeException('Ese cambio de estado no está permitido.');
        }
        if ($to === 'despachado' && (! $carrier || ! $tracking) && $so->shipping_method === 'envio') {
            throw new RuntimeException('Para despachar indica la transportadora y el número de guía.');
        }

        $from = $so->statusLabel();
        $so->fill(['status' => $to]);
        if ($carrier && $tracking) {
            $so->fill(['carrier' => $carrier, 'tracking_number' => $tracking]);
        }
        $so->save();

        $msg = "{$so->store->name}: {$from} → {$so->statusLabel()}";
        if ($to === 'despachado' && $so->tracking_number) {
            $msg .= " ({$so->carrierName()}, guía {$so->tracking_number})";
        }
        $so->order->log('estado', $msg.'.', $so, $by);
    }

    public function cancel(SellerOrder $so, ?User $by, string $reason): void
    {
        if (in_array($so->status, ['entregado', 'cancelado'], true)) {
            return;
        }

        foreach ($so->items as $item) {
            if ($item->product) {
                $item->product->increment('stock', $item->quantity);
                $item->product->decrement('sales_count', min($item->quantity, $item->product->sales_count));
            }
        }
        $so->update(['status' => 'cancelado', 'commission_amount' => 0, 'seller_net' => 0]);
        $so->order->log('estado', "{$so->store->name}: cancelado ({$reason}).", $so, $by);
    }

    public function settle(SellerOrder $so, User $by, string $reference): void
    {
        $so->update(['settled_at' => now(), 'settlement_reference' => $reference]);
        $so->order->log('liquidacion', "Liquidado a {$so->store->name}: ".cop($so->seller_net)." (ref. {$reference}).", $so, $by);
    }

    private function nextNumber(): string
    {
        $last = Order::max('id') ?? 0;

        return 'MC-'.str_pad((string) (10001 + $last), 6, '0', STR_PAD_LEFT);
    }
}
