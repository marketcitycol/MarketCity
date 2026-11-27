<?php

namespace App\Services\Orders;

use App\Mail\OrderShippedMail;
use App\Mail\PaymentConfirmedMail;
use App\Mail\SellerNewOrderMail;
use App\Models\EmailPriceCoupon;
use App\Models\MerchantEntity;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\Setting;
use App\Models\User;
use App\Services\Campaigns\CampaignService;
use App\Services\Marketing\MetaConversions;
use App\Support\BusinessDays;
use App\Support\Mailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OrderService
{
    public function __construct(private Cart $cart, private CommissionCalculator $commissions, private CampaignService $campaigns) {}

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

        $order = DB::transaction(function () use ($groups, $customer, $paymentMethod, $user, $ip) {
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
                'user_agent' => isset($customer['user_agent']) ? mb_substr((string) $customer['user_agent'], 0, 255) : null,
                'fbp' => $customer['fbp'] ?? null,
                'fbc' => $customer['fbc'] ?? null,
                'analytics_consent' => (bool) ($customer['analytics_consent'] ?? false),
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
                $money = $this->commissions->forSellerOrder($group['subtotal'], $shipping, $store);

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
                    // Tope de unidades a precio de campaña (#77): se revisa otra vez con el producto bloqueado.
                    $campaign = $this->campaigns->activeFor($product);
                    if ($campaign && ($left = $this->campaigns->remaining($campaign)) !== null && $left < $line['quantity']) {
                        throw new RuntimeException($left > 0
                            ? "Solo quedan {$left} unidades de «{$product->name}» a precio {$campaign->campaign->name}. Ajusta la cantidad."
                            : "«{$product->name}» ya vendió todas sus unidades a precio {$campaign->campaign->name}.");
                    }
                    $coupon = $this->useCoupon($line, $order, $product);
                    $discount = $coupon ? (int) $line['discount'] : 0;

                    $product->decrement('stock', $line['quantity']);
                    $product->increment('sales_count', $line['quantity']);

                    $order->items()->create([
                        'seller_order_id' => $sellerOrder->id,
                        'product_id' => $product->id,
                        'campaign_id' => $campaign?->campaign_id,
                        'coupon_id' => $coupon?->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'unit_price' => $product->price,
                        'quantity' => $line['quantity'],
                        'line_total' => $product->price * $line['quantity'] - $discount,
                        'discount_total' => $discount,
                    ]);
                }

                $subtotal += $group['subtotal'];
                $shippingTotal += $shipping;
            }

            $order->update(['subtotal' => $subtotal, 'shipping_total' => $shippingTotal, 'total' => $subtotal + $shippingTotal]);
            $order->log('creado', 'Pedido creado con '.$groups->count().' '.Str::plural('vendedor', $groups->count()).'.');

            return $order;
        });

        $this->syncCampaigns($order);

        return $order;
    }

    /** Marca usado el cupón de precio con correo de la línea; solo vale con el mismo correo con que se pidió (#76). */
    private function useCoupon(array $line, Order $order, Product $product): ?EmailPriceCoupon
    {
        if (empty($line['coupon'])) {
            return null;
        }
        $coupon = EmailPriceCoupon::lockForUpdate()->find($line['coupon']->id);
        if (! $coupon || ! $coupon->isUsable() || $coupon->product_id !== $product->id) {
            throw new RuntimeException("El código de precio con correo de «{$product->name}» ya se usó o venció. Quítalo del carrito para seguir.");
        }
        if (! hash_equals($coupon->email_hash, EmailPriceCoupon::hashEmail($order->email))) {
            throw new RuntimeException('El precio con correo solo sirve con el mismo correo con el que lo pediste. Usa ese correo en el pago.');
        }
        $coupon->update(['used_at' => now(), 'order_id' => $order->id]);

        return $coupon;
    }

    /** Tras crear o cancelar un pedido se revisa el tope de las campañas de sus productos. */
    private function syncCampaigns(Order $order): void
    {
        foreach ($order->items()->whereNotNull('campaign_id')->with('product')->get() as $item) {
            if ($item->product) {
                $this->campaigns->sync($item->product);
            }
        }
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
        $applied = DB::transaction(function () use ($order, $payment, $payload) {
            // Se relee con bloqueo: dos confirmaciones simultáneas (webhook + retorno) no deben aplicarse dos veces.
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if ($order->payment_status === 'paid') {
                return false;
            }
            if ($order->payment_status !== 'pending') {
                // Aprobación tardía de un pedido ya cancelado: no se revive el pedido ni se toca el stock.
                // Queda registrada para revisión manual (reembolso o reactivación), MC-PAY-006.
                $payment->update(['status' => 'review', 'payload' => $payload]);
                $order->log('pago', 'Llegó una aprobación de pago para un pedido que ya no estaba pendiente. Requiere revisión manual.');

                return false;
            }
            $payment->update(['status' => 'approved', 'payload' => $payload]);
            $order->update(['payment_status' => 'paid', 'paid_at' => now()]);
            $due = BusinessDays::add(now(), (int) Setting::get('settlement_business_days', 10));
            $order->sellerOrders()->update(['settlement_due_on' => $due->toDateString()]);
            $order->log('pago', 'Pago aprobado por '.cop($payment->amount).' (ref. '.$payment->provider_reference.'). Liquidación a vendedores el '.$due->format('d/m/Y').'.');

            return true;
        });

        if ($applied) {
            $order->refresh();
            app(MetaConversions::class)->purchase($order);
            Mailer::send($order->email, new PaymentConfirmedMail($order));
            foreach ($order->sellerOrders()->with('store.user')->get() as $so) {
                if ($so->store->wantsNewOrderEmail()) {
                    Mailer::send($so->store->notificationEmail(), new SellerNewOrderMail($so));
                }
            }
        }
    }

    /** Pago rechazado: el pedido queda cancelado y el stock vuelve. */
    public function markFailed(Order $order, Payment $payment, array $payload = [], string $reason = 'Pago rechazado'): void
    {
        DB::transaction(function () use ($order, $payment, $payload, $reason) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            if ($order->payment_status !== 'pending') {
                return;
            }
            $payment->update(['status' => 'declined', 'payload' => $payload]);
            $order->update(['payment_status' => 'failed']);
            foreach ($order->sellerOrders as $so) {
                $this->cancel($so, null, $reason);
            }
            $order->log('pago', $reason.'. El pedido se canceló y las unidades volvieron al inventario.');
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
        if ($to === 'entregado') {
            $so->delivered_at = now();
        }
        if ($carrier && $tracking) {
            $so->fill(['carrier' => $carrier, 'tracking_number' => $tracking]);
        }
        $so->save();

        $msg = "{$so->store->name}: {$from} → {$so->statusLabel()}";
        if ($to === 'despachado' && $so->tracking_number) {
            $msg .= " ({$so->carrierName()}, guía {$so->tracking_number})";
        }
        $so->order->log('estado', $msg.'.', $so, $by);

        if ($to === 'despachado') {
            Mailer::send($so->order->email, new OrderShippedMail($so));
        }
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
        // El cupón de precio con correo vuelve a servir mientras no venza.
        EmailPriceCoupon::whereIn('id', $so->items->pluck('coupon_id')->filter())->update(['used_at' => null, 'order_id' => null]);
        $so->order->log('estado', "{$so->store->name}: cancelado ({$reason}).", $so, $by);
        $this->syncCampaigns($so->order);
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
