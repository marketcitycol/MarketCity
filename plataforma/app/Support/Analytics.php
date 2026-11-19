<?php

namespace App\Support;

use App\Models\OrderItem;
use App\Models\Product;

/**
 * Medición del embudo (GA4 / Google Tag Manager / píxel de Meta) con consentimiento previo.
 *
 * Nada de terceros carga hasta que el visitante pulsa «Aceptar» en el aviso de cookies.
 * Los eventos de la página se encolan en window.mcEvents y resources/js/app.js los envía
 * solo si hay consentimiento. Los identificadores se validan aquí para que un valor raro
 * en Backoffice → Plugins no termine dentro de un <script>.
 */
class Analytics
{
    /** @return array{gtm:?string, ga4:?string, meta:?string} */
    public static function ids(): array
    {
        if (! Plugins::enabled('analytics')) {
            return ['gtm' => null, 'ga4' => null, 'meta' => null];
        }
        $c = Plugins::config('analytics');
        $match = fn (string $re, ?string $v) => preg_match($re, trim((string) $v)) ? strtoupper(trim((string) $v)) : null;

        return [
            'gtm' => $match('/^GTM-[A-Z0-9]{4,12}$/i', $c['gtm_id'] ?? null),
            'ga4' => $match('/^G-[A-Z0-9]{4,16}$/i', $c['ga4_id'] ?? null),
            'meta' => $match('/^\d{6,20}$/', $c['meta_pixel_id'] ?? null),
        ];
    }

    public static function active(): bool
    {
        return (bool) array_filter(self::ids());
    }

    public static function product(Product $p, int $quantity = 1): array
    {
        return array_filter([
            'item_id' => $p->sku ?: 'P'.$p->id,
            'item_name' => $p->name,
            'item_brand' => $p->brand?->name,
            'item_category' => $p->category?->name,
            'affiliation' => $p->store?->name,
            'price' => (int) $p->price,
            'quantity' => $quantity,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public static function orderItem(OrderItem $i, ?string $store = null): array
    {
        return array_filter([
            'item_id' => $i->sku ?: ($i->product_id ? 'P'.$i->product_id : null),
            'item_name' => $i->name,
            'affiliation' => $store,
            'price' => (int) $i->unit_price,
            'quantity' => (int) $i->quantity,
        ], fn ($v) => $v !== null && $v !== '');
    }
}
