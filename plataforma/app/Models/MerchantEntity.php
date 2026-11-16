<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Titular del comercio (quien cobra ante las pasarelas y factura).
 * Hoy: el fundador como persona natural. Mañana: una sociedad.
 * Cambiarlo es activar otro registro; los pedidos guardan cuál estaba vigente.
 */
class MerchantEntity extends Model
{
    protected $fillable = ['legal_name', 'person_type', 'document_type', 'document_number', 'email', 'phone', 'address', 'city', 'is_active', 'active_from', 'active_until'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'active_from' => 'datetime', 'active_until' => 'datetime'];
    }

    public static function current(): ?self
    {
        return static::where('is_active', true)->latest('active_from')->first();
    }

    public function activate(): void
    {
        static::where('is_active', true)->where('id', '!=', $this->id)
            ->update(['is_active' => false, 'active_until' => now()]);
        $this->update(['is_active' => true, 'active_from' => now(), 'active_until' => null]);
    }

    public function personTypeLabel(): string
    {
        return $this->person_type === 'juridica' ? 'Persona jurídica (sociedad)' : 'Persona natural';
    }
}
