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
        return ['pending_deduction' => 'boolean', 'restocked' => 'boolean', 'amount' => 'integer'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sellerOrder(): BelongsTo
    {
        return $this->belongsTo(SellerOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
