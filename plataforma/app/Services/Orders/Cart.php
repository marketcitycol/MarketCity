<?php

namespace App\Services\Orders;

use App\Models\EmailPriceCoupon;
use App\Models\Product;
use App\Models\Store;
use App\Services\Campaigns\CampaignService;
use App\Services\Campaigns\EmailPriceService;
use Illuminate\Support\Collection;

/**
 * Carrito en la sesión. Guarda solo ids y cantidades; precios y stock se leen siempre de la base.
 */
class Cart
{
    private const KEY = 'cart.items';

    private const COUPONS = 'cart.coupons';

    public function __construct(private CampaignService $campaigns, private EmailPriceService $emailPrices) {}

    public function add(Product $product, int $qty = 1): void
    {
        $items = $this->raw();
        $items[$product->id] = min(($items[$product->id] ?? 0) + max(1, $qty), $this->maxFor($product));
        session([self::KEY => $items]);
    }

    /** Máximo que se puede llevar: el inventario y, en campaña con tope, las unidades que quedan a ese precio (#77). */
    public function maxFor(Product $product): int
    {
        $max = max(1, $product->stock);
        $left = $this->campaigns->maxQuantity($product);

        return $left === null ? $max : max(0, min($max, $left));
    }

    /** Aplica un código de precio con correo (#76). Devuelve el mensaje de error o null si quedó aplicado. */
    public function applyCoupon(string $code): ?string
    {
        $coupon = EmailPriceCoupon::where('code', strtoupper(trim($code)))->first();
        $product = $coupon ? Product::visible()->find($coupon->product_id) : null;
        if (! $coupon || ! $product || ! $this->emailPrices->validFor($coupon->code, $product)) {
            return 'Ese código no existe, ya se usó, venció o el producto ya no está a precio de campaña.';
        }
        if (! isset($this->raw()[$product->id])) {
            $this->add($product, 1);
        }
        session([self::COUPONS => [$product->id => $coupon->code] + session(self::COUPONS, [])]);

        return null;
    }

    /** @return array<int,string> producto => código */
    public function coupons(): array
    {
        return session(self::COUPONS, []);
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
        session()->forget([self::KEY, 'cart.shipping', self::COUPONS]);
    }

    /** Rehace el carrito desde una copia guardada (enlace de un recordatorio). */
    public function restore(array $items): void
    {
        $clean = [];
        foreach (Product::visible()->whereIn('id', array_keys($items))->where('stock', '>', 0)->get() as $p) {
            $clean[$p->id] = max(1, min((int) $items[$p->id], $this->maxFor($p)));
        }
        session([self::KEY => $clean]);
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
     * @return Collection<int, array{store:Store, lines:Collection, subtotal:int}>
     */
    public function groups(): Collection
    {
        $raw = $this->raw();
        if (! $raw) {
            return collect();
        }

        $products = Product::visible()->with('store')->whereIn('id', array_keys($raw))->get();
        $coupons = $this->coupons();

        return $products
            ->map(function (Product $p) use ($raw, $coupons) {
                $qty = min($raw[$p->id], $p->stock, $this->maxFor($p));
                $campaign = $this->campaigns->activeFor($p);
                $coupon = isset($coupons[$p->id]) && $qty > 0 ? $this->emailPrices->validFor($coupons[$p->id], $p) : null;
                // El cupón cubre 1 unidad: esa unidad va al precio con correo, el resto al precio de hoy.
                $discount = $coupon ? max(0, $p->price - $this->emailPrices->unitPrice($coupon, $p)) : 0;

                return [
                    'product' => $p,
                    'quantity' => $qty,
                    'requested' => $raw[$p->id],
                    'campaign_id' => $campaign?->campaign_id,
                    'coupon' => $coupon,
                    'discount' => $discount,
                    'line_total' => $p->price * $qty - $discount,
                ];
            })
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

    public static function shippingCost(Store $store, string $method): int
    {
        if ($method === 'recogida') {
            return $store->pickup_enabled ? $store->pickup_fee : 0;
        }

        return $store->free_shipping ? 0 : $store->shipping_fee;
    }
}
