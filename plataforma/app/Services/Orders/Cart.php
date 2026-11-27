<?php

namespace App\Services\Orders;

use App\Models\Coupon;
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

    private const CODE = 'cart.code';

    public function __construct(private CampaignService $campaigns, private EmailPriceService $emailPrices, private CouponService $couponRules) {}

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
        $max = $left === null ? $max : max(0, min($max, $left));
        // Límite por cliente de la oferta: con sesión se descuenta lo que ya compró; sin sesión se revisa al pagar con el correo.
        $perCustomer = $this->campaigns->customerAllowance($product, auth()->user());

        return $perCustomer === null ? $max : max(0, min($max, $perCustomer));
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

    /** Cupón general: se guarda en el carrito si hoy aplica. Devuelve el error o null. */
    public function applyGeneralCoupon(Coupon $coupon): ?string
    {
        $groups = $this->groups();
        $result = $this->couponRules->evaluate($coupon, $groups, $this->shippingByStore($groups));
        if ($result['error']) {
            return $result['error'];
        }
        session([self::CODE => $coupon->code]);

        return null;
    }

    public function generalCoupon(): ?Coupon
    {
        $code = session(self::CODE);

        return $code ? Coupon::where('code', $code)->first() : null;
    }

    public function removeGeneralCoupon(): void
    {
        session()->forget(self::CODE);
    }

    /**
     * Descuento del cupón general con el carrito de hoy (o el motivo por el que ya no aplica).
     *
     * @return array{coupon:?Coupon, error:?string, total:int, stores:array<int,int>}
     */
    public function couponDiscount(?Collection $groups = null, ?string $email = null): array
    {
        $coupon = $this->generalCoupon();
        if (! $coupon) {
            return ['coupon' => null, 'error' => null, 'total' => 0, 'stores' => []];
        }
        $groups ??= $this->groups();

        return ['coupon' => $coupon] + $this->couponRules->evaluate($coupon, $groups, $this->shippingByStore($groups), $email);
    }

    /** @return array<int,int> envío por tienda con lo elegido en el carrito */
    public function shippingByStore(?Collection $groups = null): array
    {
        $out = [];
        foreach ($groups ?? $this->groups() as $g) {
            $method = $this->shippingChoice($g['store']->id);
            if ($method === 'recogida' && ! $g['store']->pickup_enabled) {
                $method = 'envio';
            }
            $out[$g['store']->id] = self::shippingCost($g['store'], $method);
        }

        return $out;
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
        session()->forget([self::KEY, 'cart.shipping', self::COUPONS, self::CODE]);
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
