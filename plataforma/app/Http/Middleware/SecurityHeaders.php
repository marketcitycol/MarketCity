<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad básicas (MC-NFR-010). La CSP completa queda para cuando se conecten
 * las pasarelas reales, porque cada una exige dominios propios (ver 08-integraciones-pagos.md).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $h = $response->headers;

        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(self)');
        $h->set('Content-Security-Policy', "upgrade-insecure-requests; frame-ancestors 'self'; base-uri 'self'; object-src 'none'");
        if ($request->isSecure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000');
        }
        $h->remove('X-Powered-By');
        header_remove('X-Powered-By');

        // Paneles y páginas con datos personales no se guardan en cachés compartidas.
        if ($request->is('backoffice*', 'seller-center*', 'mi-cuenta*', 'pedido/*', 'checkout*')) {
            $h->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
