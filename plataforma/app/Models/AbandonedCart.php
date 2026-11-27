<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Copia del carrito de quien autorizó recordatorios (#83). */
class AbandonedCart extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['items' => 'array', 'next_reminder_at' => 'datetime', 'last_sent_at' => 'datetime', 'clicked_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function statusLabel(): string
    {
        return ['open' => 'Abierto', 'recovered' => 'Recuperado', 'stopped' => 'Detenido', 'done' => 'Secuencia terminada'][$this->status] ?? $this->status;
    }
}
