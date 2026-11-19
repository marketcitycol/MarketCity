<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Página de contenido (políticas, contacto, preguntas frecuentes…), editable desde el Backoffice. */
class Page extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'status', 'legacy_id'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}
