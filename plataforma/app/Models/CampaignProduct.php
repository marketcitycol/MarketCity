<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignProduct extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['applied_at' => 'datetime', 'closed_at' => 'datetime', 'campaign_price' => 'integer', 'email_price' => 'integer', 'base_price' => 'integer', 'base_regular_price' => 'integer'];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isApplied(): bool
    {
        return $this->applied_at !== null;
    }
}
