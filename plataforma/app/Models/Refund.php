<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Reembolso registrado en un pedido (en WooCommerce: «Reembolso» en la pantalla del pedido). */
class Refund extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['pending_deduction' => 'boolean', 'restocked' => 'boolean', 'amount' => 'integer', 'deducted_amount' => 'integer', 'deducted_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sellerOrder(): BelongsTo
    {
        return $this->belongsTo(SellerOrder::class);
    }

    /** Lo que falta por restar de las liquidaciones de la tienda. */
    public function deductionLeft(): int
    {
        return $this->pending_deduction ? max(0, (int) $this->seller_debit - (int) $this->deducted_amount) : 0;
    }

    /** Reembolsos de subpedidos ya liquidados cuyo descuento todavía no se ha restado por completo. */
    public function scopeUndeductedFor($query, int $storeId)
    {
        return $query->where('pending_deduction', true)->whereColumn('deducted_amount', '<', 'seller_debit')
            ->whereHas('sellerOrder', fn ($q) => $q->where('store_id', $storeId))->orderBy('id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
