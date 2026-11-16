<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'slug', 'description', 'logo_path', 'department', 'city', 'address', 'phone', 'document_type', 'document_number', 'free_shipping', 'shipping_fee', 'pickup_enabled', 'pickup_fee', 'status'];

    protected function casts(): array
    {
        return ['free_shipping' => 'boolean', 'pickup_enabled' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(SellerOrder::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr(preg_replace('/[^\pL\pN ]/u', '', $this->name), 0, 2));
    }
}
