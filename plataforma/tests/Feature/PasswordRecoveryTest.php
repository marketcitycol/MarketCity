<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Recuperar, crear y cambiar la contraseña de los clientes. */
class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function tokenFor(User $user): string
    {
        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        return $token;
    }

    private function fakeSession(User $user, string $id): void
    {
        DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => '', 'last_activity' => time()]);
    }

    public function test_reset_link_works_with_any_capitalization_and_signs_out_other_devices(): void
    {
        Notification::fake();
        config(['session.driver' => 'database']);
        $user = User::where('email', 'cliente@marketcity.test')->first();
        $this->fakeSession($user, 'sesion-en-otro-equipo');

        $this->post('/olvide-mi-contrasena', ['email' => '  Cliente@MarketCity.TEST '])->assertSessionHas('success');
        $token = $this->tokenFor($user);

        $mail = (new ResetPasswordNotification($token))->toMail($user);
        $this->assertStringContainsString('/restablecer-contrasena/'.$token, $mail->render());

        $this->post('/restablecer-contrasena', ['token' => $token, 'email' => 'CLIENTE@marketcity.test', 'password' => 'clave-nueva-9', 'password_confirmation' => 'clave-nueva-9'])
            ->assertRedirect('/ingresar');
        $this->assertTrue(Hash::check('clave-nueva-9', $user->fresh()->password));
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());

        // Y entra con la clave nueva aunque escriba el correo con mayúsculas.
        $this->post('/ingresar', ['email' => 'Cliente@marketcity.test', 'password' => 'clave-nueva-9'])->assertRedirect(route('account'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_unknown_email_gets_the_same_answer_and_no_mail(): void
    {
        Notification::fake();
        $this->post('/olvide-mi-contrasena', ['email' => 'nadie@example.com'])->assertSessionHas('success');
        Notification::assertNothingSent();
    }

    public function test_validation_messages_are_in_spanish(): void
    {
        $this->from('/restablecer-contrasena/x')->post('/restablecer-contrasena', ['token' => 'x', 'email' => 'cliente@marketcity.test', 'password' => 'corta', 'password_confirmation' => 'otra'])
            ->assertSessionHasErrors(['password' => 'La contraseña debe tener al menos 8 caracteres.']);
        $this->post('/olvide-mi-contrasena', ['email' => 'no-es-correo'])
            ->assertSessionHasErrors(['email' => 'Escribe un correo válido.']);
    }

    public function test_expired_link_is_rejected(): void
    {
        Notification::fake();
        $user = User::where('email', 'cliente@marketcity.test')->first();
        $this->post('/olvide-mi-contrasena', ['email' => $user->email]);
        $token = $this->tokenFor($user);

        $this->travel(61)->minutes();
        $this->post('/restablecer-contrasena', ['token' => $token, 'email' => $user->email, 'password' => 'clave-nueva-9', 'password_confirmation' => 'clave-nueva-9'])
            ->assertSessionHas('error');
        $this->assertTrue(Hash::check('marketcity', $user->fresh()->password));
    }

    public function test_customer_changes_password_from_my_account(): void
    {
        config(['session.driver' => 'database']);
        $user = User::where('email', 'cliente@marketcity.test')->first();
        $this->fakeSession($user, 'sesion-en-otro-equipo');

        $this->actingAs($user)->get('/mi-cuenta')->assertSee('Cambiar contraseña');

        $this->actingAs($user)->from('/mi-cuenta')->put('/mi-cuenta/contrasena', ['current_password' => 'equivocada', 'password' => 'clave-nueva-9', 'password_confirmation' => 'clave-nueva-9'])
            ->assertSessionHasErrors(['current_password' => 'La contraseña actual no es correcta.']);
        $this->actingAs($user)->from('/mi-cuenta')->put('/mi-cuenta/contrasena', ['current_password' => 'marketcity', 'password' => 'marketcity', 'password_confirmation' => 'marketcity'])
            ->assertSessionHasErrors(['password' => 'La contraseña nueva debe ser distinta de la actual.']);

        $this->actingAs($user)->put('/mi-cuenta/contrasena', ['current_password' => 'marketcity', 'password' => 'clave-nueva-9', 'password_confirmation' => 'clave-nueva-9'])
            ->assertRedirect('/mi-cuenta')->assertSessionHas('success');
        $this->assertTrue(Hash::check('clave-nueva-9', $user->fresh()->password));
        $this->assertSame(0, DB::table('sessions')->where('id', 'sesion-en-otro-equipo')->count());
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_change_password_requires_login(): void
    {
        $this->put('/mi-cuenta/contrasena', ['current_password' => 'x', 'password' => 'clave-nueva-9', 'password_confirmation' => 'clave-nueva-9'])->assertRedirect('/ingresar');
    }
}
