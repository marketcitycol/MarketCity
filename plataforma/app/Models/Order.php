<?php

namespace App\Models;

use App\Services\Payments\PaymentRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'terms_accepted_at' => 'datetime', 'marketing_optin' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(SellerOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(OrderEvent::class)->latest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function merchantEntity(): BelongsTo
    {
        return $this->belongsTo(MerchantEntity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customerName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function paymentStatusLabel(): string
    {
        return [
            'pending' => 'Pago pendiente',
            'paid' => 'Pagado',
            'failed' => 'Pago rechazado',
            'refunded' => 'Reembolsado',
        ][$this->payment_status] ?? $this->payment_status;
    }

    public function paymentMethodLabel(): string
    {
        return PaymentRegistry::labelFor($this->payment_method);
    }

    /** Pedido por transferencia que todavía espera el dinero. */
    public function awaitingTransfer(): bool
    {
        return $this->payment_method === 'transferencia' && $this->payment_status === 'pending';
    }

    /** Documento del comprador enmascarado para pantallas y correos (Ley 1581). */
    public function maskedDocument(): string
    {
        return trim($this->document_type.' '.mask_document($this->document_number));
    }

    public function log(string $type, string $message, ?SellerOrder $sellerOrder = null, ?User $user = null): void
    {
        $this->events()->create([
            'type' => $type,
            'message' => $message,
            'seller_order_id' => $sellerOrder?->id,
            'user_id' => $user?->id,
        ]);
    }
}
