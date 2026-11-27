<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Ingreso con Google (en WordPress: «Nextend Social Login»), con OAuth 2.0 directo, sin paquetes.
 * - Cuenta ya vinculada (google_id) o mismo correo verificado por Google: entra y queda vinculada.
 * - Correo nuevo: se crea la cuenta de cliente (al continuar acepta la política de datos).
 * - Si la cuenta tiene verificación en dos pasos, igual se pide el código.
 */
class GoogleController extends Controller
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(Request $request)
    {
        abort_unless(self::enabled(), 404);
        $state = Str::random(40);
        $request->session()->put('google.state', $state);
        $request->session()->put('google.remember', $request->boolean('remember'));

        return redirect()->away(self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => route('login.google.callback'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]));
    }

    public function callback(Request $request)
    {
        abort_unless(self::enabled(), 404);
        $state = $request->session()->pull('google.state');
        if (! $state || ! hash_equals($state, (string) $request->query('state')) || ! $request->filled('code')) {
            return redirect()->route('login')->with('error', $request->query('error') === 'access_denied'
                ? 'Cancelaste el ingreso con Google.'
                : 'No pudimos completar el ingreso con Google. Intenta de nuevo.');
        }

        try {
            $token = Http::asForm()->timeout(15)->post(self::TOKEN_URL, [
                'code' => $request->query('code'),
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => route('login.google.callback'),
                'grant_type' => 'authorization_code',
            ])->throw()->json('access_token');
            $info = Http::withToken((string) $token)->timeout(15)->get(self::USERINFO_URL)->throw()->json();
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('login')->with('error', 'Google no respondió. Intenta de nuevo o ingresa con tu correo y contraseña.');
        }

        $sub = (string) ($info['sub'] ?? '');
        $email = mb_strtolower(trim((string) ($info['email'] ?? '')));
        if ($sub === '' || $email === '' || ! ($info['email_verified'] ?? false)) {
            return redirect()->route('login')->with('error', 'Tu cuenta de Google no tiene un correo verificado.');
        }

        $user = User::where('google_id', $sub)->first() ?? User::findByEmail($email);
        $created = false;
        if (! $user) {
            $user = User::create([
                'name' => Str::limit(trim((string) ($info['name'] ?? '')) ?: Str::before($email, '@'), 120, ''),
                'email' => $email,
                'password' => Str::random(40), // puede crear una con «¿Olvidaste tu contraseña?»
                'role' => User::ROLE_CUSTOMER,
            ]);
            $created = true;
        }
        $user->forceFill(['google_id' => $sub, 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        $remember = (bool) $request->session()->pull('google.remember');
        if ($user->hasTwoFactor()) {
            $request->session()->regenerate();
            $request->session()->put([
                TwoFactorController::SESSION_USER => $user->id,
                TwoFactorController::SESSION_REMEMBER => $remember,
                TwoFactorController::SESSION_AT => now()->timestamp,
            ]);

            return redirect()->route('two-factor.challenge');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();
        if ($created) {
            Mailer::send($user->email, new WelcomeMail($user));

            return redirect()->route('account')->with('success', 'Tu cuenta quedó creada con Google.');
        }

        return redirect()->intended(AuthController::home($user));
    }
}
