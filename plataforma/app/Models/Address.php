<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Dirección guardada en «Mi cuenta» (#79). La principal rellena el pago. */
class Address extends Model
{
    protected $fillable = ['user_id', 'label', 'recipient', 'phone', 'department', 'city', 'address', 'address_2', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function oneLine(): string
    {
        return collect([$this->address, $this->address_2, $this->city, $this->department])->filter()->join(', ');
    }
}
