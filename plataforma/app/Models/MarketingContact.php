<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Mensaje publicitario enviado (para no escribir más de una vez al día ni cambiar de canal en 7 días, Ley 2300). */
class MarketingContact extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }
}
