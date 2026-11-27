<?php

namespace App\Services\Orders;

use App\Models\Setting;
use App\Models\Store;

/**
 * Calcula la comisión que cobra MarketCity sobre un subpedido.
 *
 * Regla del propietario (2026-11-16): 15 % por venta, sin valor fijo, ajustable por comercio
 * según lo negociado. Se aplica sobre el valor de los productos; el envío va al vendedor.
 * Cada subpedido guarda la regla con la que se calculó.
 */
class CommissionCalculator
{
    public function rule(?Store $store = null): array
    {
        $custom = $store?->hasCustomCommission();

        return [
            'percent' => (float) ($store?->commission_percent ?? Setting::get('commission_percent', 15)),
            'fixed' => (int) ($store?->commission_fixed ?? Setting::get('commission_fixed', 0)),
            'base' => 'subtotal_productos',
            'source' => $custom ? 'negociada con el comercio' : 'general',
        ];
    }

    /**
     * Un cupón de MarketCity no toca al vendedor: la comisión va sobre el valor de lista y el descuento
     * lo pone MarketCity. Un cupón de la tienda baja la base de la comisión y sale del neto del vendedor.
     *
     * @return array{commission:int, seller_net:int, rule:array}
     */
    public function forSellerOrder(int $subtotal, int $shipping, ?Store $store = null, int $discount = 0, string $fundedBy = 'marketcity', bool $onShipping = false): array
    {
        $rule = $this->rule($store);
        $byStore = $discount > 0 && $fundedBy === 'tienda';
        $base = $byStore && ! $onShipping ? max(0, $subtotal - $discount) : $subtotal;
        $commission = (int) round($base * $rule['percent'] / 100) + $rule['fixed'];
        $commission = min($commission, $base);
        if ($discount > 0) {
            $rule['descuento'] = ['valor' => $discount, 'asume' => $fundedBy, 'sobre' => $onShipping ? 'envio' : 'productos'];
        }

        return [
            'commission' => $commission,
            'seller_net' => max(0, $subtotal + $shipping - $commission - ($byStore ? $discount : 0)),
            'rule' => $rule,
        ];
    }
}
