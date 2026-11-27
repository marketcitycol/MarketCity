<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Ingreso con Google (OAuth 2.0 directo). */
class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private array $info = [];

    private function fakeGoogle(array $info): void
    {
        $this->info = $info;
        Http::fake(fn ($request) => str_contains($request->url(), 'oauth2.googleapis.com/token')
            ? Http::response(['access_token' => 'tok'])
            : Http::response($this->info));
    }

    private function roundTrip(): TestResponse
    {
        $to = $this->get('/ingresar/google')->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $to);
        parse_str(parse_url($to, PHP_URL_QUERY), $q);
        $this->assertSame('openid email profile', $q['scope']);

        return $this->get('/ingresar/google/regreso?code=abc&state='.$q['state']);
    }

    public function test_button_hidden_without_keys(): void
    {
        $this->get('/ingresar')->assertOk()->assertDontSee('Continuar con Google');
        $this->get('/ingresar/google')->assertNotFound();
    }

    public function test_new_and_existing_accounts_and_two_factor(): void
    {
        Mail::fake();
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        $this->get('/ingresar')->assertSee('Continuar con Google');
        $this->get('/registro')->assertSee('Continuar con Google');

        // Estado falso: no entra.
        $this->get('/ingresar/google');
        $this->get('/ingresar/google/regreso?code=abc&state=otro')->assertRedirect('/ingresar');
        $this->assertGuest();

        // Correo sin verificar en Google: no entra.
        $this->fakeGoogle(['sub' => 'g-1', 'email' => 'nuevo@gmail.com', 'email_verified' => false, 'name' => 'Nuevo']);
        $this->roundTrip()->assertRedirect('/ingresar');
        $this->assertGuest();

        // Cuenta nueva.
        $this->fakeGoogle(['sub' => 'g-1', 'email' => 'Nuevo@Gmail.com', 'email_verified' => true, 'name' => 'Nuevo Cliente']);
        $this->roundTrip()->assertRedirect(route('account'));
        $u = User::where('email', 'nuevo@gmail.com')->first();
        $this->assertSame('g-1', $u->google_id);
        $this->assertSame('customer', $u->role);
        $this->assertNotNull($u->email_verified_at);
        $this->assertAuthenticatedAs($u);

        // Cuenta existente con el mismo correo: se vincula.
        auth()->logout();
        $seller = User::where('role', 'seller')->first();
        $this->fakeGoogle(['sub' => 'g-2', 'email' => strtoupper($seller->email), 'email_verified' => true]);
        $this->roundTrip()->assertRedirect(route('seller.dashboard'));
        $this->assertSame('g-2', $seller->fresh()->google_id);

        // Con verificación en dos pasos, Google no la salta.
        auth()->logout();
        $seller->forceFill(['two_factor_secret' => Totp::secret(), 'two_factor_confirmed_at' => now()])->save();
        $this->roundTrip()->assertRedirect('/ingresar/verificacion');
        $this->assertGuest();
    }
}
