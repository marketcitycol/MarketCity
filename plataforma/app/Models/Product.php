<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'category_id', 'brand_id', 'name', 'slug', 'sku', 'short_description', 'description', 'price', 'regular_price', 'stock', 'color', 'image_path', 'status', 'featured'];

    protected function casts(): array
    {
        return ['featured' => 'boolean', 'price' => 'integer', 'regular_price' => 'integer', 'stock' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** Productos que se pueden ver y comprar: activos y de una tienda activa. */
    public function scopeVisible(Builder $q): Builder
    {
        return $q->where('status', 'active')->whereHas('store', fn ($s) => $s->where('status', 'active'));
    }

    public function isOnSale(): bool
    {
        return $this->regular_price && $this->regular_price > $this->price;
    }

    public function discountPercent(): int
    {
        return $this->isOnSale() ? (int) round(100 - $this->price * 100 / $this->regular_price) : 0;
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return str_starts_with($this->image_path, 'http') || str_starts_with($this->image_path, '/')
            ? $this->image_path
            : Storage::disk('public')->url($this->image_path);
    }
}
