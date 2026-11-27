<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    /** Precio mínimo para vender: nada a $0, $1 o precios mal escritos (lección del sitio anterior). */
    public const MIN_PRICE = 1000;

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
        return $q->where('status', 'active')->where('price', '>=', self::MIN_PRICE)->whereHas('store', fn ($s) => $s->where('status', 'active'));
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

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Fotos para la galería de la ficha: la principal primero y luego las adicionales, sin repetir.
     *
     * @return list<string>
     */
    public function galleryUrls(): array
    {
        return collect([$this->imageUrl()])->merge($this->images->map->url())->filter()->unique()->values()->all();
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
