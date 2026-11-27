<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/** Foto adicional de la galería de un producto. */
class ProductImage extends Model
{
    public const MAX_PER_PRODUCT = 8;

    protected $fillable = ['product_id', 'path', 'position'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function url(): string
    {
        return str_starts_with($this->path, 'http') || str_starts_with($this->path, '/')
            ? $this->path
            : Storage::disk('public')->url($this->path);
    }
}
