<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Store extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'slug', 'description', 'logo_path', 'department', 'city', 'address', 'phone', 'document_type', 'document_number', 'legal_name', 'rut_path', 'verified_at', 'free_shipping', 'shipping_fee', 'pickup_enabled', 'pickup_fee', 'dispatch_days', 'carriers', 'notify_email', 'notify_new_order', 'featured', 'directory_hidden', 'status', 'commission_percent', 'commission_fixed', 'payment_methods'];

    protected function casts(): array
    {
        return ['verified_at' => 'datetime', 'free_shipping' => 'boolean', 'pickup_enabled' => 'boolean', 'featured' => 'boolean', 'directory_hidden' => 'boolean', 'notify_new_order' => 'boolean', 'dispatch_days' => 'integer'];
    }

    /** Tarifa nacional de MarketCity: la que aplica a las tiendas nuevas mientras no configuren la suya. */
    public const NATIONAL_SHIPPING_FEE = 15000;

    protected static function booted(): void
    {
        static::creating(function (Store $store) {
            // Una tienda nueva no promete envío gratis por omisión (Ley 1480: solo se promete lo real).
            if (! array_key_exists('free_shipping', $store->getAttributes())) {
                $store->free_shipping = false;
                $store->shipping_fee ??= (int) Setting::get('shipping_national_fee', self::NATIONAL_SHIPPING_FEE);
            }
        });
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

    /** Medios de pago permitidos para esta tienda; null = todos los activos en la plataforma. */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(StoreBankAccount::class)->latest('id');
    }

    /** Cuenta vigente a la que se le paga (la última aprobada). */
    public function approvedBankAccount(): ?StoreBankAccount
    {
        return $this->bankAccounts()->where('status', 'approved')->first();
    }

    public function pendingBankAccount(): ?StoreBankAccount
    {
        return $this->bankAccounts()->where('status', 'pending')->first();
    }

    /**
     * Motivo por el que hoy no se le puede pagar a la tienda, o null si se puede.
     * Se retiene si no hay cuenta aprobada o si hay un cambio de cuenta en revisión.
     */
    public function settlementHoldReason(): ?string
    {
        if ($this->pendingBankAccount()) {
            return 'Hay un cambio de cuenta bancaria en revisión.';
        }
        if (! $this->approvedBankAccount()) {
            return 'La tienda no tiene una cuenta bancaria aprobada.';
        }

        return null;
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /** Correo al que se avisan los pedidos y las PQRS de la tienda: el elegido en su perfil o el de la cuenta (#69). */
    public function notificationEmail(): ?string
    {
        return $this->notify_email ?: $this->user?->email;
    }

    /** ¿Se le avisa por correo cada pedido pagado? Las PQRS siempre se avisan: tienen plazos legales. */
    public function wantsNewOrderEmail(): bool
    {
        return $this->notify_new_order !== false;
    }

    public function allowedPaymentMethods(): ?array
    {
        return $this->payment_methods === null ? null : array_values(array_filter(explode(',', $this->payment_methods)));
    }

    public function hasCustomCommission(): bool
    {
        return $this->commission_percent !== null || $this->commission_fixed !== null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Logo de la tienda: URL completa, ruta del sitio o archivo subido a public/uploads. */
    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        return str_starts_with($this->logo_path, 'http') || str_starts_with($this->logo_path, '/')
            ? $this->logo_path
            : Storage::disk('public')->url($this->logo_path);
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr(preg_replace('/[^\pL\pN ]/u', '', $this->name), 0, 2));
    }
}
