<?php

namespace App\Models;

use App\Support\ColombiaCalendar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una PQRS radicada. Plazo de respuesta: 15 días hábiles (Ley 1755 de 2015 y Estatuto del Consumidor).
 * Retracto (art. 47, Ley 1480): 5 días hábiles desde la entrega. Reversión del pago (art. 51): 5 días hábiles
 * desde que el comprador supo del fraude, del producto no recibido o defectuoso.
 */
class Pqr extends Model
{
    protected $table = 'pqrs';

    protected $guarded = ['id'];

    public const TYPES = [
        'peticion' => 'Petición',
        'queja' => 'Queja',
        'reclamo' => 'Reclamo',
        'sugerencia' => 'Sugerencia',
        'retracto' => 'Derecho de retracto',
        'reversion' => 'Reversión del pago',
    ];

    /** Tipos que afectan un pedido y frenan la liquidación al vendedor mientras están abiertos. */
    public const ORDER_TYPES = ['reclamo', 'retracto', 'reversion'];

    public const STATUSES = [
        'recibida' => 'Recibida',
        'en_tramite' => 'En trámite',
        'respondida' => 'Respondida',
        'cerrada' => 'Cerrada',
    ];

    public const OPEN = ['recibida', 'en_tramite'];

    public const RESPONSE_DAYS = 15;

    public const RETRACT_DAYS = 5;

    public function getRouteKeyName(): string
    {
        return 'radicado';
    }

    protected function casts(): array
    {
        return [
            'document_number' => 'encrypted',
            'within_term' => 'boolean',
            'due_at' => 'datetime',
            'responded_at' => 'datetime',
            'data_consent_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function sellerOrder(): BelongsTo
    {
        return $this->belongsTo(SellerOrder::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->due_at && $this->due_at->isPast();
    }

    public function maskedDocument(): string
    {
        return mask_document($this->document_number);
    }

    public static function nextRadicado(): string
    {
        $year = now(ColombiaCalendar::TZ)->year;
        $prefix = "PQR-{$year}-";
        $last = static::where('radicado', 'like', $prefix.'%')->orderByDesc('id')->value('radicado');
        $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    /**
     * ¿El retracto está dentro de los 5 días hábiles? Si el pedido aún no se entrega, sí.
     * Se toma la entrega más reciente de los subpedidos (o del subpedido indicado).
     */
    public static function retractWithinTerm(Order $order, ?SellerOrder $only = null): bool
    {
        $orders = $only ? collect([$only]) : $order->sellerOrders;
        $delivered = $orders->pluck('delivered_at')->filter()->max();
        if (! $delivered) {
            return true;
        }

        return now()->lte(ColombiaCalendar::addBusinessDays($delivered, self::RETRACT_DAYS));
    }
}
