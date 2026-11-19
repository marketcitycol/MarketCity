<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreBankAccount extends Model
{
    public const TYPES = ['Ahorros', 'Corriente', 'Depósito electrónico'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['account_number' => 'encrypted', 'holder_document_number' => 'encrypted', 'reviewed_at' => 'datetime'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function maskedNumber(): string
    {
        return mask_document($this->account_number);
    }

    public function statusLabel(): string
    {
        return ['pending' => 'En revisión', 'approved' => 'Aprobada', 'rejected' => 'Rechazada', 'replaced' => 'Reemplazada'][$this->status] ?? $this->status;
    }
}
