<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

/** Si Configuración lo exige, el Backoffice solo abre con la verificación en dos pasos activa. */
class RequireTwoFactor
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && Setting::get('admin_requires_2fa', '0') === '1' && ! $user->hasTwoFactor()) {
            return redirect()->to(route('account.security').'#segundo-factor')
                ->with('error', 'Para entrar al Backoffice activa primero la verificación en dos pasos.');
        }

        return $next($request);
    }
}
