<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Una fila por cada autorización o baja (registro inmutable, Ley 1581 y Decreto 1377). */
class MarketingConsent extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['granted' => 'boolean', 'created_at' => 'datetime'];
    }
}
