<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerOrder extends Model
{
    /** Estados que ve el comprador (mismos del sitio actual) y el orden en que avanzan. */
    public const STATUSES = [
        'recibido' => 'Recibido',
        'validado' => 'Validado',
        'despachado' => 'Despachado',
        'entregado' => 'Entregado',
        'cancelado' => 'Cancelado',
    ];

    public const FLOW = ['recibido', 'validado', 'despachado', 'entregado'];

    public const CARRIERS = [
        'coordinadora' => ['Coordinadora', 'https://coordinadora.com/rastreo/rastreo-de-guia/detalle-de-rastreo-de-guia/?guia='],
        'servientrega' => ['Servientrega', 'https://www.servientrega.com/wps/portal/rastreo-envio/detalle?id='],
        'tcc' => ['TCC', 'https://tcc.com.co/courier/mensajeria/rastrear-envio/?guia='],
        'envia' => ['Envía', 'https://envia.co/rastreo?guia='],
        'interrapidisimo' => ['Interrapidísimo', 'https://www.interrapidisimo.com/sigue-tu-envio/?guia='],
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['commission_rule' => 'array', 'settlement_due_on' => 'date', 'settled_at' => 'datetime', 'delivered_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Siguiente estado permitido (los vendedores solo avanzan; cancelar es del backoffice). */
    public function nextStatus(): ?string
    {
        $i = array_search($this->status, self::FLOW, true);

        return $i === false ? null : (self::FLOW[$i + 1] ?? null);
    }

    public function carrierName(): ?string
    {
        return $this->carrier ? (self::CARRIERS[$this->carrier][0] ?? $this->carrier) : null;
    }

    public function trackingUrl(): ?string
    {
        if (! $this->carrier || ! $this->tracking_number || ! isset(self::CARRIERS[$this->carrier])) {
            return null;
        }

        return self::CARRIERS[$this->carrier][1].urlencode($this->tracking_number);
    }

    /** Lo que pagó el cliente por este subpedido (con el descuento del cupón). */
    public function total(): int
    {
        return max(0, $this->subtotal + $this->shipping_total - (int) $this->discount_total);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function refundable(): int
    {
        return max(0, $this->total() - (int) $this->refunded_total);
    }

    public function pqrs(): HasMany
    {
        return $this->hasMany(Pqr::class);
    }

    /** Un reclamo, retracto o reversión abierto sobre este subpedido (o sobre todo el pedido) frena la liquidación. */
    public function openClaim(): ?Pqr
    {
        return Pqr::whereIn('type', Pqr::ORDER_TYPES)->whereIn('status', Pqr::OPEN)
            ->where('order_id', $this->order_id)
            ->where(fn ($q) => $q->whereNull('seller_order_id')->orWhere('seller_order_id', $this->id))
            ->first();
    }
}
