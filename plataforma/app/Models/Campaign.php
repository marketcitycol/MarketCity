<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Campaña con precio programado (Black City, Hot Sale…): reemplaza los snippets #16, #77 y #78. */
class Campaign extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'active' => 'boolean', 'email_price_enabled' => 'boolean', 'unit_cap' => 'integer', 'per_customer_limit' => 'integer'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function items(): HasMany
    {
        return $this->hasMany(CampaignProduct::class);
    }

    public function isRunning(): bool
    {
        return $this->active && now()->between($this->starts_at, $this->ends_at);
    }

    public function scopeRunning(Builder $q): Builder
    {
        return $q->where('active', true)->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }
}
