<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;

/** Verificación de correo: no bloquea compras; habilita ver en "Mi cuenta" los pedidos hechos como invitado. */
class EmailVerificationController extends Controller
{
    public function send(Request $request)
    {
        $user = $request->user();
        if (! $user->email_verified_at) {
            try {
                $user->notify(new VerifyEmailNotification);
            } catch (\Throwable $e) {
                report($e);

                return back()->with('error', 'No pudimos enviar el correo ahora. Inténtalo en unos minutos.');
            }
        }

        return back()->with('success', 'Te enviamos un enlace a '.$user->email.' para confirmar tu correo.');
    }

    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403);
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect()->route($request->user()?->id === $user->id ? 'account' : 'login')->with('success', 'Tu correo quedó confirmado.');
    }
}
