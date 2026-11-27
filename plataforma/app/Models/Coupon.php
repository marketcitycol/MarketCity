<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cupón general (en WooCommerce: Marketing → Cupones). Sin tienda lo asume MarketCity (no baja el
 * neto del vendedor); con tienda solo vale para sus productos y lo asume esa tienda.
 */
class Coupon extends Model
{
    public const TYPES = [
        'percent' => 'Porcentaje de los productos',
        'fixed' => 'Valor fijo en pesos',
        'free_shipping' => 'Envío gratis',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer', 'max_discount' => 'integer', 'min_subtotal' => 'integer',
            'usage_limit' => 'integer', 'usage_limit_per_customer' => 'integer',
            'exclude_sale_items' => 'boolean', 'active' => 'boolean',
            'starts_at' => 'datetime', 'ends_at' => 'datetime',
        ];
    }

    public static function normalize(string $code): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($code)));
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function fundedBy(): string
    {
        return $this->store_id ? 'tienda' : 'marketcity';
    }

    /** Usos que cuentan: pedidos pagados o esperando el pago (los cancelados liberan el uso). */
    public function usesQuery(?string $email = null)
    {
        return Order::where('coupon_id', $this->id)->whereIn('payment_status', ['pending', 'paid'])
            ->when($email, fn ($q) => $q->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))]));
    }

    public function summary(): string
    {
        return match ($this->type) {
            'percent' => $this->amount.' %'.($this->max_discount ? ' (hasta '.cop($this->max_discount).')' : ''),
            'fixed' => cop($this->amount),
            default => 'Envío gratis',
        };
    }
}
