<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Mensaje del formulario de contacto (#66). */
class ContactMessage extends Model
{
    public const TOPICS = [
        'compra' => 'Una compra o un pedido',
        'pagos' => 'Pagos y crédito (Addi, Sistecrédito)',
        'vender' => 'Quiero vender en MarketCity',
        'alianzas' => 'Alianzas, prensa o publicidad',
        'otro' => 'Otro tema',
    ];

    public const SOURCES = ['Instagram', 'Facebook', 'TikTok', 'Google', 'WhatsApp', 'Recomendación', 'Otro'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['handled_at' => 'datetime'];
    }

    public function topicLabel(): string
    {
        return self::TOPICS[$this->topic] ?? $this->topic;
    }
}
