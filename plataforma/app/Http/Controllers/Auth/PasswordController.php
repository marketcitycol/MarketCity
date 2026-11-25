<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * "Olvidé mi contraseña", "Crear mi contraseña" (clientes copiados del sitio anterior)
 * y "Cambiar contraseña" desde Mi cuenta.
 * La respuesta a "olvidé" es la misma exista o no el correo, para no revelar quién tiene cuenta.
 */
class PasswordController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => 'required|email|max:190']);
        $user = User::findByEmail($request->input('email'));
        if ($user) {
            try {
                // Con el correo tal como está guardado, para que el enlace funcione aunque lo escriban con mayúsculas.
                Password::sendResetLink(['email' => $user->email]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return back()->with('success', 'Si ese correo tiene una cuenta, te enviamos un enlace para crear una contraseña nueva. Vence en '.config('auth.passwords.users.expire', 60).' minutos. Revisa también la carpeta de spam.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|max:128|confirmed',
        ]);
        $email = User::findByEmail($request->input('email'))?->email ?? (string) $request->input('email');

        $status = Password::reset(['email' => $email] + $request->only('password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)]);
            // Quien abre el enlace demostró que el correo es suyo.
            $user->email_verified_at ??= now();
            $user->save();
            // Quien tuviera la sesión abierta en otro equipo queda fuera.
            self::closeSessions($user);
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->with('error', 'El enlace ya no es válido o venció. Pide uno nuevo.');
        }

        return redirect()->route('login')->withInput(['email' => $email])->with('success', 'Listo. Ya puedes ingresar con tu contraseña nueva.');
    }

    /** Mi cuenta → Cambiar contraseña (con la sesión abierta). */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|max:128|confirmed|different:current_password',
        ]);
        $user = $request->user();
        $user->forceFill(['password' => $request->input('password'), 'remember_token' => Str::random(60)])->save();
        self::closeSessions($user, $request->session()->getId());
        // Nueva id de sesión; si este equipo usaba "recordarme", su cookie sigue con el token nuevo.
        $request->session()->regenerate(true);
        if ($request->hasCookie(Auth::guard()->getRecallerName())) {
            Auth::guard()->login($user, true);
        }

        return redirect()->route('account')->with('success', 'Cambiaste tu contraseña. Cerramos la sesión en los demás equipos.');
    }

    /** Borra las sesiones del usuario guardadas en la base (menos la actual, si se indica). */
    private static function closeSessions(User $user, ?string $keep = null): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }
        DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->when($keep, fn ($q) => $q->where('id', '!=', $keep))
            ->delete();
    }
}
