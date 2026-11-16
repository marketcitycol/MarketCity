<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Calcula la comisión de MarketCity sobre un subpedido.
 *
 * SUPUESTO (pendiente Q-031): en WCFM la regla 15 % + 900 COP está marcada "para el vendedor".
 * Mientras el propietario no lo confirme, la versión 1 la toma como lo que COBRA MarketCity,
 * sobre el subtotal de productos (el envío va al vendedor, como en WCFM).
 * Porcentaje, fijo y base se cambian en Backoffice → Configuración sin tocar código.
 */
class CommissionCalculator
{
    public function rule(): array
    {
        return [
            'percent' => (float) Setting::get('commission_percent', 15),
            'fixed' => (int) Setting::get('commission_fixed', 900),
            'base' => 'subtotal_productos',
            'source' => 'SUPUESTO Q-031',
        ];
    }

    /** @return array{commission:int, seller_net:int, rule:array} */
    public function forSellerOrder(int $subtotal, int $shipping): array
    {
        $rule = $this->rule();
        $commission = (int) round($subtotal * $rule['percent'] / 100) + $rule['fixed'];
        $commission = min($commission, $subtotal);

        return [
            'commission' => $commission,
            'seller_net' => $subtotal + $shipping - $commission,
            'rule' => $rule,
        ];
    }
}
