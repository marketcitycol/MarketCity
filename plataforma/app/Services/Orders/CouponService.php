<?php

namespace App\Services\Orders;

use App\Models\Coupon;
use Illuminate\Support\Collection;

/**
 * Reglas de los cupones generales, como en WooCommerce: fechas, mínimo de compra, usos totales y
 * por cliente, productos en oferta excluidos y cupones de una sola tienda.
 *
 * El descuento se reparte por tienda (cada subpedido guarda el suyo) para que la comisión y el neto
 * del vendedor se calculen sobre lo que de verdad pagó el cliente cuando el cupón es de la tienda.
 */
class CouponService
{
    /**
     * @param  Collection  $groups  grupos del carrito (Cart::groups)
     * @param  array<int,int>  $shipping  envío por tienda (store_id => pesos)
     * @return array{error:?string, total:int, stores:array<int,int>}
     */
    public function evaluate(Coupon $coupon, Collection $groups, array $shipping, ?string $email = null): array
    {
        $fail = fn (string $msg) => ['error' => $msg, 'total' => 0, 'stores' => []];

        if (! $coupon->active || ($coupon->starts_at && $coupon->starts_at->isFuture())) {
            return $fail('Ese cupón no está activo.');
        }
        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            return $fail('Ese cupón ya venció.');
        }
        if ($coupon->usage_limit !== null && $coupon->usesQuery()->count() >= $coupon->usage_limit) {
            return $fail('Ese cupón ya alcanzó su número de usos.');
        }
        if ($email && $coupon->usage_limit_per_customer !== null && $coupon->usesQuery($email)->count() >= $coupon->usage_limit_per_customer) {
            return $fail('Ya usaste ese cupón con este correo.');
        }

        // Base elegible por tienda: productos de la tienda del cupón (o de todas), sin ofertas si así se pidió.
        $base = [];
        foreach ($groups as $g) {
            if ($coupon->store_id && $g['store']->id !== $coupon->store_id) {
                continue;
            }
            $sum = $g['lines']->filter(fn ($l) => ! ($coupon->exclude_sale_items && $this->onSale($l)))->sum('line_total');
            if ($sum > 0) {
                $base[$g['store']->id] = $sum;
            }
        }
        if (! $base) {
            return $fail($coupon->store_id ? 'Ese cupón es solo para productos de '.($coupon->store?->name ?? 'otra tienda').'.' : 'Ese cupón no aplica a los productos de tu carrito.');
        }
        $eligible = array_sum($base);
        if ($eligible < $coupon->min_subtotal) {
            return $fail('Ese cupón pide una compra mínima de '.cop($coupon->min_subtotal).' en productos que apliquen.');
        }

        $stores = match ($coupon->type) {
            'percent' => $this->spread(min($coupon->max_discount ?? PHP_INT_MAX, (int) round($eligible * min(100, $coupon->amount) / 100)), $base),
            'fixed' => $this->spread(min($coupon->amount, $eligible), $base),
            'free_shipping' => array_filter(array_intersect_key($shipping, $base)),
            default => [],
        };
        if (array_sum($stores) <= 0) {
            return $fail($coupon->type === 'free_shipping' ? 'Tu envío ya es gratis.' : 'Ese cupón no aplica a los productos de tu carrito.');
        }

        return ['error' => null, 'total' => array_sum($stores), 'stores' => $stores];
    }

    /** Producto en oferta: precio de campaña, precio con correo o precio tachado. */
    private function onSale(array $line): bool
    {
        return ! empty($line['campaign_id']) || ! empty($line['coupon']) || (int) $line['product']->regular_price > (int) $line['product']->price;
    }

    /** Reparte un descuento entre tiendas en proporción a su base; el redondeo va a la última. */
    private function spread(int $discount, array $base): array
    {
        $total = array_sum($base);
        $out = [];
        $left = $discount;
        $keys = array_keys($base);
        foreach ($keys as $i => $storeId) {
            $part = $i === count($keys) - 1 ? $left : (int) floor($discount * $base[$storeId] / $total);
            $out[$storeId] = min($part, $base[$storeId]);
            $left -= $out[$storeId];
        }

        return $out;
    }
}
