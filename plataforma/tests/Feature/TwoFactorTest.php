<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** Verificación en dos pasos con app autenticadora (reemplaza el «login secreto» #35). */
class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_totp_matches_rfc_6238_vectors(): void
    {
        $secret = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // «12345678901234567890»
        $this->assertSame('287082', Totp::code($secret, intdiv(59, 30)));
        $this->assertSame('081804', Totp::code($secret, intdiv(1111111109, 30)));
        $this->assertSame('005924', Totp::code($secret, intdiv(1234567890, 30)));
        $this->assertSame(32, strlen(Totp::secret()));
    }

    public function test_admin_enables_two_factor_and_login_requires_code_or_recovery_code(): void
    {
        $admin = User::where('role', 'admin')->first();
        $admin->forceFill(['password' => 'clave-segura-1'])->save();

        $this->actingAs($admin)->get('/backoffice')->assertOk()->assertSee('Activa la verificación en dos pasos');
        $this->post('/seguridad/dos-pasos', ['current_password' => 'mala'])->assertSessionHasErrors('current_password');
        $this->post('/seguridad/dos-pasos', ['current_password' => 'clave-segura-1'])->assertSessionHas('2fa.pending_secret');
        $secret = session('2fa.pending_secret');
        $this->get('/mi-cuenta/seguridad')->assertOk()->assertSee('data-qr="otpauth://totp/', false);
        $this->post('/seguridad/dos-pasos/confirmar', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/seguridad/dos-pasos/confirmar', ['code' => Totp::code($secret, Totp::step() - 1)])->assertSessionHas('recovery_codes');
        $codes = session('recovery_codes');
        $this->assertCount(8, $codes);
        $this->assertTrue($admin->fresh()->hasTwoFactor());
        $this->assertNotSame($secret, $admin->fresh()->getRawOriginal('two_factor_secret')); // cifrada en la base

        // Ingresar: la contraseña sola no basta.
        auth()->logout();
        $this->post('/ingresar', ['email' => $admin->email, 'password' => 'clave-segura-1'])->assertRedirect('/ingresar/verificacion');
        $this->assertGuest();
        $this->get('/backoffice')->assertRedirect('/ingresar');
        $this->post('/ingresar/verificacion', ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();
        // El código ya usado al activar no se puede repetir.
        $this->post('/ingresar/verificacion', ['code' => Totp::code($secret, Totp::step() - 1)])->assertSessionHasErrors('code');
        $this->post('/ingresar/verificacion', ['code' => Totp::code($secret, Totp::step())])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        // Código de recuperación: sirve una sola vez.
        Cache::flush(); // el límite de intentos es por IP; aquí todo viene de la misma
        auth()->logout();
        $this->post('/ingresar', ['email' => $admin->email, 'password' => 'clave-segura-1']);
        $this->post('/ingresar/verificacion', ['code' => strtoupper($codes[0])])->assertRedirect(route('admin.dashboard'));
        $this->assertCount(7, $admin->fresh()->two_factor_recovery_codes);
        auth()->logout();
        $this->post('/ingresar', ['email' => $admin->email, 'password' => 'clave-segura-1']);
        $this->post('/ingresar/verificacion', ['code' => $codes[0]])->assertSessionHasErrors('code');
    }

    public function test_backoffice_can_require_two_factor_for_admins(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->put('/backoffice/configuracion', [
            'commission_percent' => 15, 'commission_fixed' => 0, 'settlement_business_days' => 10, 'installments' => 4,
            'terms_version' => '2026-11', 'payment_methods' => ['transferencia'], 'admin_requires_2fa' => '1',
        ])->assertSessionHas('error');
        $this->assertNotSame('1', Setting::get('admin_requires_2fa'));

        Setting::put('admin_requires_2fa', '1');
        $this->get('/backoffice')->assertRedirect(route('account.security').'#segundo-factor');
        $admin->forceFill(['two_factor_secret' => Totp::secret(), 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($admin->fresh())->get('/backoffice')->assertOk();
    }
}
