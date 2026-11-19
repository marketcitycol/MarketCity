<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * "Olvidé mi contraseña" y "Crear mi contraseña" (clientes copiados del sitio anterior).
 * La respuesta es la misma exista o no el correo, para no revelar quién tiene cuenta.
 */
class PasswordController extends Controller
{
    public function requestForm()
    {
        return view('auth.forgot');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        try {
            Password::sendResetLink($request->only('email'));
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', 'Si ese correo tiene una cuenta, te enviamos un enlace para crear una contraseña nueva. Revisa también la carpeta de spam.');
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
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)]);
            // Quien abre el enlace demostró que el correo es suyo.
            $user->email_verified_at ??= now();
            $user->save();
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->with('error', 'El enlace ya no es válido o venció. Pide uno nuevo.');
        }

        return redirect()->route('login')->with('success', 'Listo. Ya puedes ingresar con tu contraseña nueva.');
    }
}
