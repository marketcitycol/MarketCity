<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\LegacyRedirects;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['role' => EnsureRole::class]);
        // Los avisos de las pasarelas no traen token CSRF: se validan con su firma.
        // Cookies que escriben scripts del navegador (aviso de cookies y píxel de Meta): no van cifradas.
        $middleware->encryptCookies(except: ['mc_consent', '_fbp', '_fbc']);
        // Baja de mensajes con un clic (List-Unsubscribe-Post): la firma de la URL reemplaza el token CSRF.
        $middleware->validateCsrfTokens(except: ['pagos/aviso/*', 'mensajes/baja']);
        $middleware->append(SecurityHeaders::class);
        // URL de WordPress → URL propias (301), antes del enrutador.
        $middleware->prepend(LegacyRedirects::class);
        $middleware->redirectUsersTo(fn () => route('account'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
