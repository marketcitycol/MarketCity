<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_SELLER = 'seller';

    public const ROLE_ADMIN = 'admin';

    protected $fillable = ['name', 'email', 'password', 'role', 'phone', 'document_type', 'document_number'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function hasTwoFactor(): bool
    {
        return $this->two_factor_confirmed_at !== null && $this->two_factor_secret;
    }

    /**
     * Busca por correo sin importar mayúsculas: SQLite compara con distinción y hay cuentas
     * creadas antes de guardar los correos en minúscula.
     */
    public static function findByEmail(?string $email): ?self
    {
        $email = mb_strtolower(trim((string) $email));

        return $email === '' ? null : static::whereRaw('lower(email) = ?', [$email])->orderBy('id')->first();
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class)->orderByDesc('is_default')->latest('id');
    }

    public function defaultAddress(): ?Address
    {
        return $this->addresses()->first();
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class)->latest('id');
    }

    public function hasFavorite(Product $product): bool
    {
        return $this->favorites()->where('product_id', $product->id)->exists();
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSeller(): bool
    {
        return $this->role === self::ROLE_SELLER;
    }
}
