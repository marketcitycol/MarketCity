<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Carrito en la sesión. Guarda solo ids y cantidades; precios y stock se leen siempre de la base.
 */
class Cart
{
    private const KEY = 'cart.items';

    public function add(Product $product, int $qty = 1): void
    {
        $items = $this->raw();
        $items[$product->id] = min(($items[$product->id] ?? 0) + max(1, $qty), max(1, $product->stock));
        session([self::KEY => $items]);
    }

    public function update(int $productId, int $qty): void
    {
        $items = $this->raw();
        if ($qty <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $qty;
        }
        session([self::KEY => $items]);
    }

    public function clear(): void
    {
        session()->forget([self::KEY, 'cart.shipping']);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /** @return array<int,int> */
    public function raw(): array
    {
        return session(self::KEY, []);
    }

    /**
     * Líneas agrupadas por tienda, listas para mostrar y para crear el pedido.
     *
     * @return Collection<int, array{store:\App\Models\Store, lines:Collection, subtotal:int}>
     */
    public function groups(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }

        $products = Product::visible()->with('store')->whereIn('id', array_keys($raw))->get();

        return $products
            ->map(fn (Product $p) => [
                'product' => $p,
                'quantity' => min($raw[$p->id], $p->stock),
                'requested' => $raw[$p->id],
                'line_total' => $p->price * min($raw[$p->id], $p->stock),
            ])
            ->filter(fn ($l) => $l['quantity'] > 0)
            ->groupBy(fn ($l) => $l['product']->store_id)
            ->map(fn ($lines) => [
                'store' => $lines->first()['product']->store,
                'lines' => $lines->values(),
                'subtotal' => $lines->sum('line_total'),
            ])
            ->values();
    }

    public function subtotal(): int
    {
        return $this->groups()->sum('subtotal');
    }

    /** Envío elegido por tienda: 'envio' o 'recogida'. */
    public function shippingChoice(int $storeId): string
    {
        return session('cart.shipping', [])[$storeId] ?? 'envio';
    }

    public function setShippingChoices(array $choices): void
    {
        session(['cart.shipping' => $choices]);
    }

    public static function shippingCost(\App\Models\Store $store, string $method): int
    {
        if ($method === 'recogida') {
            return $store->pickup_enabled ? $store->pickup_fee : 0;
        }

        return $store->free_shipping ? 0 : $store->shipping_fee;
    }
}
