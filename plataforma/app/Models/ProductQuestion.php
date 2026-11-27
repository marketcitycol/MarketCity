<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pregunta de un cliente al vendedor sobre un producto y su respuesta pública. */
class ProductQuestion extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['answered_at' => 'datetime', 'hidden' => 'boolean'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublic(Builder $q): Builder
    {
        return $q->whereNotNull('answered_at')->where('hidden', false);
    }

    public function scopeUnanswered(Builder $q): Builder
    {
        return $q->whereNull('answered_at')->where('hidden', false);
    }

    /** Nombre que se muestra en la página: solo el primer nombre. */
    public function publicName(): string
    {
        return strtok(trim($this->name), ' ') ?: 'Cliente';
    }
}
