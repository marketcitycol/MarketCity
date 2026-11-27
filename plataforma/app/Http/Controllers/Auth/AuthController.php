<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required']);
        // El correo se compara sin mayúsculas (SQLite distingue mayúsculas).
        $credentials['email'] = User::findByEmail($credentials['email'])?->email ?? $credentials['email'];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // #47: se ofrece crear la cuenta con ese correo. El mensaje es igual exista o no la cuenta,
            // para no revelar qué correos están registrados.
            $request->session()->put('register_email', mb_strtolower(trim($credentials['email'])));

            return back()->withInput($request->only('email'))->with('login_failed', true)->with('error', 'El correo o la contraseña no coinciden.');
        }

        $user = $request->user();
        if ($user->hasTwoFactor()) {
            // La contraseña es correcta, pero falta el código de la app: se cierra la sesión hasta verificarlo.
            Auth::logout();
            $request->session()->regenerate();
            $request->session()->put([
                TwoFactorController::SESSION_USER => $user->id,
                TwoFactorController::SESSION_REMEMBER => $request->boolean('remember'),
                TwoFactorController::SESSION_AT => now()->timestamp,
            ]);

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        return redirect()->intended(self::home($user));
    }

    public static function home(User $user): string
    {
        return match ($user->role) {
            'admin' => route('admin.dashboard'),
            'seller' => route('seller.dashboard'),
            default => route('account'),
        };
    }

    public function registerForm(Request $request)
    {
        return view('auth.register', ['suggestedEmail' => $request->session()->pull('register_email')]);
    }

    public function register(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:120|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'accepted',
        ], ['terms.accepted' => 'Debes aceptar la política de tratamiento de datos.']);

        $user = User::create($data + ['role' => 'customer']);
        Mailer::send($user->email, new WelcomeMail($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('account')->with('success', 'Tu cuenta quedó creada.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
