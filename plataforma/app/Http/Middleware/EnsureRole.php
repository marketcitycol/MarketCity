<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        abort_unless($user && $user->role === $role, 403, 'No tienes acceso a esta sección.');

        if ($role === 'seller') {
            abort_unless($user->store, 403, 'Tu usuario no tiene una tienda asociada.');
        }

        return $next($request);
    }
}
