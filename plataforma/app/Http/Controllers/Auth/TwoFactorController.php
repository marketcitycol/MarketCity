<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Segundo factor con app autenticadora (reemplaza el «login secreto» #35 del sitio anterior).
 * Activarlo: Seguridad → escanear el QR → escribir el código. Al ingresar se pide el código de 6
 * dígitos o uno de los códigos de recuperación (cada uno sirve una vez).
 */
class TwoFactorController extends Controller
{
    public const SESSION_USER = 'login.2fa.id';

    public const SESSION_REMEMBER = 'login.2fa.remember';

    public const SESSION_AT = 'login.2fa.at';

    public function start(Request $request)
    {
        $request->validate(['current_password' => 'required|current_password']);
        $request->session()->put('2fa.pending_secret', Totp::secret());

        return back()->with('success', 'Escanea el código QR con tu app autenticadora y escribe el código de 6 dígitos que te muestra.');
    }

    public function confirm(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:10']);
        $secret = $request->session()->get('2fa.pending_secret');
        if (! $secret) {
            return back()->with('error', 'Empieza de nuevo la activación.');
        }
        $step = Totp::verify($secret, $data['code']);
        if ($step === null) {
            return back()->withErrors(['code' => 'El código no coincide. Revisa que la hora del celular esté en automático y escribe el código actual.']);
        }
        $codes = Totp::recoveryCodes();
        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $codes),
            'two_factor_confirmed_at' => now(),
            'two_factor_last_step' => $step,
        ])->save();
        $request->session()->forget('2fa.pending_secret');

        return back()->with('success', 'Segundo factor activado.')->with('recovery_codes', $codes);
    }

    public function regenerate(Request $request)
    {
        $request->validate(['current_password' => 'required|current_password']);
        abort_unless($request->user()->hasTwoFactor(), 404);
        $codes = Totp::recoveryCodes();
        $request->user()->forceFill(['two_factor_recovery_codes' => array_map(fn ($c) => Hash::make($c), $codes)])->save();

        return back()->with('success', 'Códigos de recuperación nuevos. Los anteriores ya no sirven.')->with('recovery_codes', $codes);
    }

    public function disable(Request $request)
    {
        $request->validate(['current_password' => 'required|current_password']);
        $request->user()->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null, 'two_factor_last_step' => null])->save();

        return back()->with('success', 'Segundo factor desactivado.');
    }

    // --- Al ingresar ---

    public function challengeForm(Request $request)
    {
        abort_unless($this->pendingUser($request), 404);

        return view('auth.two-factor');
    }

    public function challenge(Request $request)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->with('error', 'Tu verificación venció. Ingresa de nuevo.');
        }
        $data = $request->validate(['code' => 'required|string|max:20']);
        $code = trim($data['code']);

        $ok = false;
        if (preg_match('/^\d[\d ]{4,7}$/', $code)) {
            $step = Totp::verify($user->two_factor_secret, $code, $user->two_factor_last_step);
            if ($step !== null) {
                $user->forceFill(['two_factor_last_step' => $step])->save();
                $ok = true;
            }
        } else {
            $codes = $user->two_factor_recovery_codes ?? [];
            foreach ($codes as $i => $hash) {
                if (Hash::check(strtolower($code), $hash)) {
                    unset($codes[$i]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                    $ok = true;
                    break;
                }
            }
        }
        if (! $ok) {
            return back()->withErrors(['code' => 'El código no es válido o ya se usó.']);
        }

        $remember = (bool) $request->session()->pull(self::SESSION_REMEMBER);
        $request->session()->forget([self::SESSION_USER, self::SESSION_AT]);
        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(AuthController::home($user));
    }

    private function pendingUser(Request $request): ?User
    {
        $id = $request->session()->get(self::SESSION_USER);
        if (! $id || now()->timestamp - (int) $request->session()->get(self::SESSION_AT) > 600) {
            return null;
        }
        $user = User::find($id);

        return $user?->hasTwoFactor() ? $user : null;
    }
}
