<?php

namespace App\Legacy;

use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Copia clientes y pedidos desde la API REST de WooCommerce (wc/v3) a v1.
 *
 * - Repetible: clientes por legacy_id/correo, pedidos por legacy_id (se rehacen sus líneas).
 * - No toca stock ni comisiones: los pedidos históricos guardan regla "legado" y neto = subtotal.
 * - Los clientes quedan sin contraseña usable (aleatoria): entran con "olvidé mi contraseña".
 */
class WooOrderImporter
{
    /** Estado de WooCommerce → [estado de pago, estado del subpedido]. */
    private const STATUS = [
        'completed' => ['paid', 'entregado'],
        'processing' => ['paid', 'validado'],
        'addi-approved' => ['paid', 'validado'],
        'wava-confirm' => ['paid', 'validado'],
        'epayco-completed' => ['paid', 'validado'],
        'epayco-processing' => ['paid', 'validado'],
        'epayco-on-hold' => ['pending', 'recibido'],
        'epayco-cancelled' => ['failed', 'cancelado'],
        'epayco-failed' => ['failed', 'cancelado'],
        'on-hold' => ['pending', 'recibido'],
        'pending' => ['pending', 'recibido'],
        'checkout-draft' => ['pending', 'recibido'],
        'cancelled' => ['failed', 'cancelado'],
        'failed' => ['failed', 'cancelado'],
        'refunded' => ['refunded', 'cancelado'],
    ];

    /** @return array{customers_created:int, customers_updated:int, orders_created:int, orders_updated:int} */
    public function import(array $customers, array $orders): array
    {
        $stats = ['customers_created' => 0, 'customers_updated' => 0, 'orders_created' => 0, 'orders_updated' => 0];

        DB::transaction(function () use ($customers, $orders, &$stats) {
            foreach ($customers as $c) {
                $this->customer($c, $stats);
            }
            $fallback = Store::where('slug', 'marketcity')->first() ?? Store::orderBy('id')->firstOrFail();
            foreach ($orders as $o) {
                $this->order($o, $fallback, $stats);
            }
        });

        return $stats;
    }

    private function customer(array $c, array &$stats): ?User
    {
        $email = Str::lower(trim((string) ($c['email'] ?? '')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $user = User::where('legacy_id', $c['id'] ?? 0)->where('legacy_id', '>', 0)->first() ?? User::where('email', $email)->first();
        $name = trim(($c['first_name'] ?? '').' '.($c['last_name'] ?? '')) ?: (trim($c['billing']['first_name'] ?? '').' '.trim($c['billing']['last_name'] ?? ''));
        $data = [
            'name' => Str::limit(trim($name) ?: Str::before($email, '@'), 180, ''),
            'phone' => Str::limit(trim((string) ($c['billing']['phone'] ?? '')), 30, '') ?: null,
        ];
        if ($user) {
            // Nunca se cambia el rol ni la contraseña de una cuenta que ya existe en v1.
            if ($user->role === 'customer') {
                $user->fill(array_filter($data))->save();
            }
            if (! $user->legacy_id && ! empty($c['id'])) {
                $user->legacy_id = (int) $c['id'];
                $user->save();
            }
            $stats['customers_updated']++;

            return $user;
        }
        $user = new User($data + ['email' => $email, 'password' => Str::random(48), 'role' => 'customer']);
        $user->legacy_id = (int) ($c['id'] ?? 0) ?: null;
        $user->save();
        $stats['customers_created']++;

        return $user;
    }

    private function order(array $o, Store $fallback, array &$stats): void
    {
        $b = $o['billing'] ?? [];
        $s = $o['shipping'] ?? [];
        [$payStatus, $soStatus] = self::STATUS[$o['status'] ?? ''] ?? ['pending', 'recibido'];
        $created = $this->date($o['date_created_gmt'] ?? null) ?? now();
        $meta = collect($o['meta_data'] ?? [])->pluck('value', 'key');

        $user = null;
        if (! empty($o['customer_id'])) {
            $user = User::where('legacy_id', $o['customer_id'])->first()
                ?? $this->customer(['id' => $o['customer_id'], 'email' => $b['email'] ?? '', 'first_name' => $b['first_name'] ?? '', 'last_name' => $b['last_name'] ?? '', 'billing' => $b], $stats);
        }

        $items = collect($o['line_items'] ?? []);
        $subtotal = (int) round($items->sum(fn ($i) => (float) ($i['subtotal'] ?? $i['total'] ?? 0)));
        $shipping = (int) round((float) ($o['shipping_total'] ?? 0));
        $total = (int) round((float) ($o['total'] ?? 0));

        $attrs = [
            'user_id' => $user?->id,
            'first_name' => Str::limit(trim($b['first_name'] ?? '') ?: '—', 180, ''),
            'last_name' => Str::limit(trim($b['last_name'] ?? '') ?: '—', 180, ''),
            'document_number' => Str::limit(trim((string) ($meta['cedula'] ?? $meta['_billing_cedula'] ?? '')) ?: '—', 30, ''),
            'email' => Str::lower(trim($b['email'] ?? '')) ?: 'sin-correo@marketcity.invalid',
            'phone' => Str::limit(trim((string) ($b['phone'] ?? '')) ?: '—', 30, ''),
            'department' => Str::limit(trim($s['state'] ?? '') ?: (trim($b['state'] ?? '') ?: '—'), 180, ''),
            'city' => Str::limit(trim($s['city'] ?? '') ?: (trim($b['city'] ?? '') ?: '—'), 180, ''),
            'address' => Str::limit(trim($s['address_1'] ?? '') ?: (trim($b['address_1'] ?? '') ?: '—'), 250, ''),
            'address_2' => Str::limit(trim($s['address_2'] ?? '') ?: (trim($b['address_2'] ?? '') ?: ''), 250, '') ?: null,
            'notes' => trim((string) ($o['customer_note'] ?? '')) ?: null,
            'subtotal' => max(0, $subtotal),
            'shipping_total' => max(0, $shipping),
            'total' => max(0, $total),
            'payment_method' => Str::limit(($o['payment_method'] ?? '') ?: 'legado', 40, ''),
            'payment_status' => $payStatus,
            'paid_at' => $payStatus === 'paid' ? ($this->date($o['date_paid_gmt'] ?? null) ?? $created) : null,
            'terms_version' => 'woocommerce',
            'terms_accepted_at' => $created,
            'ip_address' => Str::limit((string) ($o['customer_ip_address'] ?? ''), 45, '') ?: null,
        ];

        $order = Order::where('legacy_id', $o['id'])->first();
        if ($order) {
            $order->fill($attrs)->save();
            $order->sellerOrders()->delete(); // borra en cascada sus líneas y eventos
            $order->items()->delete();
            $order->events()->delete();
            $order->payments()->delete();
            $stats['orders_updated']++;
        } else {
            $order = new Order($attrs + [
                'number' => $this->number((string) ($o['number'] ?? $o['id'])),
                'access_token' => Str::random(40),
            ]);
            $order->legacy_id = (int) $o['id'];
            $order->created_at = $created;
            $order->save();
            $stats['orders_created']++;
        }
        $order->created_at = $created;
        $order->save();

        // Un subpedido por tienda, según el producto ya importado (legacy_id).
        $products = Product::whereIn('legacy_id', $items->pluck('product_id')->filter()->all())->get()->keyBy('legacy_id');
        $groups = $items->groupBy(fn ($i) => $products[$i['product_id'] ?? 0]->store_id ?? $fallback->id);
        $first = true;
        foreach ($groups as $storeId => $lines) {
            $soSubtotal = (int) round($lines->sum(fn ($i) => (float) ($i['subtotal'] ?? $i['total'] ?? 0)));
            $so = $order->sellerOrders()->create([
                'store_id' => $storeId,
                'subtotal' => $soSubtotal,
                'shipping_method' => 'envio',
                'shipping_total' => $first ? $attrs['shipping_total'] : 0,
                'commission_amount' => 0,
                'seller_net' => $soSubtotal,
                'commission_rule' => ['origen' => 'woocommerce', 'nota' => 'Pedido histórico: comisión no recalculada'],
                'status' => $soStatus,
                // Lo histórico se liquidó (o no) por fuera de v1: no debe aparecer como deuda con el vendedor.
                'settled_at' => $created,
                'settlement_reference' => 'Histórico WooCommerce (liquidación fuera de v1)',
                'created_at' => $created,
            ]);
            $first = false;
            foreach ($lines as $i) {
                $qty = max(1, (int) ($i['quantity'] ?? 1));
                $line = (int) round((float) ($i['total'] ?? 0));
                $order->items()->create([
                    'seller_order_id' => $so->id,
                    'product_id' => $products[$i['product_id'] ?? 0]->id ?? null,
                    'name' => Str::limit(trim(strip_tags((string) ($i['name'] ?? 'Producto'))), 250, ''),
                    'sku' => Str::limit((string) ($i['sku'] ?? ''), 64, '') ?: null,
                    'unit_price' => (int) round((float) ($i['price'] ?? ($line / $qty))),
                    'quantity' => $qty,
                    'line_total' => $line,
                    'created_at' => $created,
                ]);
            }
        }

        $order->events()->create([
            'type' => 'creado',
            'message' => 'Copiado desde WooCommerce: pedido #'.($o['number'] ?? $o['id']).' ('.($o['status'] ?? '¿?').', '.Arr::get($o, 'payment_method_title', '').').',
            'created_at' => $created,
        ]);
        $order->payments()->create([
            'provider' => Str::limit(($o['payment_method'] ?? '') ?: 'legado', 40, ''),
            'provider_reference' => ($o['transaction_id'] ?? '') ?: null,
            'amount' => $attrs['total'],
            'status' => ['paid' => 'approved', 'pending' => 'pending', 'failed' => 'declined', 'refunded' => 'declined'][$payStatus],
            'payload' => ['origen' => 'woocommerce', 'estado' => $o['status'] ?? null, 'medio' => $o['payment_method_title'] ?? null],
            'created_at' => $created,
        ]);
    }

    private function number(string $wc): string
    {
        $base = 'WC-'.preg_replace('/[^A-Za-z0-9]/', '', $wc);
        $n = $base;
        $i = 2;
        while (Order::where('number', $n)->exists()) {
            $n = $base.'-'.$i++;
        }

        return Str::limit($n, 20, '');
    }

    private function date(?string $gmt): ?Carbon
    {
        return $gmt ? rescue(fn () => Carbon::parse($gmt, 'UTC'), null, false) : null;
    }
}
