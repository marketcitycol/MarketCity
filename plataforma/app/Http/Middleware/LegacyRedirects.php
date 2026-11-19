<?php

namespace App\Http\Middleware;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirecciones 301 desde las URL de WordPress/WooCommerce de marketcity.co a las de la plataforma propia,
 * para no perder posiciones en Google ni enlaces guardados el día del cambio de dominio.
 *
 * Fuente: docs/archivo/fase-0/discovery/route-and-state-inventory.md, el sitemap wp-sitemap.xml y el prompt avanzado (2026-11-19).
 * Corre antes del enrutador y solo mira la ruta, así que cuesta casi nada en las demás visitas.
 * Las rutas que ya existen igual en v1 (/producto/{slug}, /tienda/{slug}, /categoria-producto/{slug}, /shop,
 * /mi-cuenta, /rastrear-pedido, /seller-center, /tiendas, /ofertas-relampago y las páginas copiadas) no se tocan.
 */
class LegacyRedirects
{
    /** Ruta exacta (sin barra final) → destino. null = 410 (retirada a propósito). */
    public const EXACT = [
        'cart' => '/carrito',
        'cart-2' => '/carrito',
        'checkout-2' => '/checkout',
        'finalizar-compra' => '/checkout',
        'my-account' => '/mi-cuenta',
        'my-account-2' => '/mi-cuenta',
        'my-account-2/pedidos' => '/mi-cuenta',
        'mi-cuenta/pedidos' => '/mi-cuenta',
        'mi-cuenta/orders' => '/mi-cuenta',
        'my-orders' => '/mi-cuenta',
        'mi-cuenta/lost-password' => '/olvide-mi-contrasena',
        'my-account/lost-password' => '/olvide-mi-contrasena',
        'rastrea-tu-pedido' => '/rastrear-pedido',
        'contact-us' => '/contacto',
        'pqr' => '/pqrs',
        'vendor-membership' => '/vender',
        'vender-2' => '/vender',
        'become-a-vendor' => '/vender',
        'vendor-onboarding' => '/vender',
        'store-registration' => '/vender',
        'store-listing' => '/tiendas',
        'vendors' => '/tiendas',
        'tienda' => '/tiendas',
        'store' => '/tiendas',
        'sellercenter' => '/seller-center',
        'dashboard' => '/seller-center',
        'vendor_dashboard' => '/seller-center',
        'seller-center/products-manage' => '/seller-center/productos',
        'seller-center/products' => '/seller-center/productos',
        'seller-center/orders' => '/seller-center/pedidos',
        'seller-center/perfil-tienda' => '/seller-center/tienda',
        'seller-center/settings' => '/seller-center/tienda',
        'seller-center/payments' => '/seller-center/liquidaciones',
        'home' => '/',
        'inicio' => '/',
        'inicio-marketcity' => '/',
        'black-city' => '/ofertas-relampago',
        'juegos' => '/',
        'blog' => '/',
        'about-us' => '/',
        'faq' => '/contacto',
        'coming-soon' => '/',
        'wishlist' => '/shop',
        'compare' => '/shop',
        'wp-login.php' => '/ingresar',
        'wp-admin' => '/ingresar',
        'admin' => '/ingresar',
        'wp-sitemap.xml' => '/sitemap.xml',
        'sitemap_index.xml' => '/sitemap.xml',
        'feed' => '/',
        // Páginas internas o de plantilla que no deben volver a existir.
        'estadisticas' => null,
        'estadisticas-ecommerce' => null,
        '7736-2' => null,
        'flas' => null,
    ];

    /** Páginas de WordPress por id (/?page_id=…). */
    public const PAGE_IDS = [
        '6103' => '/politica-de-privacidad',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $target = $this->resolve(trim($request->path(), '/'), $request->query());
        if ($target === false) {
            return $next($request);
        }
        if ($target === null) {
            abort(410);
        }

        return redirect($target, 301)->header('Cache-Control', 'public, max-age=86400');
    }

    /** @return string|false|null destino, false si no aplica, null si la URL se retiró (410). */
    public function resolve(string $path, array $query = []): string|false|null
    {
        $path = strtolower(rawurldecode($path));

        if ($path === '') {
            return $this->fromQuery($query);
        }

        if (array_key_exists($path, self::EXACT)) {
            // Si el equipo creó en v1 una página con ese mismo enlace (publicada o en borrador), gana la página.
            if (! str_contains($path, '/') && Page::where('slug', $path)->exists()) {
                return false;
            }

            return self::EXACT[$path];
        }

        // Paginación de WordPress: /shop/page/2 → /shop?page=2
        if (preg_match('#^(.*?)/page/(\d+)$#', $path, $m)) {
            $base = $m[1] === '' ? '/shop' : '/'.$m[1];
            $resolved = $this->resolve($m[1], []);
            if (is_string($resolved)) {
                $base = $resolved;
            }

            return $base.(str_contains($base, '?') ? '&' : '?').'page='.$m[2];
        }

        // Ficha en inglés y sitemaps de WordPress.
        if (preg_match('#^product/([a-z0-9-]+)$#', $path, $m)) {
            return '/producto/'.$m[1];
        }
        if (preg_match('#^wp-sitemap(-[a-z0-9_-]+)?\.xml$#', $path)) {
            return '/sitemap.xml';
        }

        // Tiendas WCFM con otra base: /store/{slug}, /vendor/{slug}, /tienda/{slug}/productos …
        if (preg_match('#^(?:store|vendor|tienda)/([a-z0-9-]+)(?:/.+)?$#', $path, $m) && ! preg_match('#^tienda/[a-z0-9-]+$#', $path)) {
            return '/tienda/'.$m[1];
        }

        // Categorías anidadas o en inglés: se usa la más específica que exista en v1.
        if (preg_match('#^(?:categoria-producto|product-category)/(.+)$#', $path, $m)) {
            $parts = array_values(array_filter(explode('/', $m[1])));
            if (str_starts_with($path, 'categoria-producto/') && count($parts) === 1) {
                return false; // ya existe igual en v1
            }
            foreach (array_reverse($parts) as $slug) {
                if (Category::where('slug', $slug)->exists()) {
                    return '/categoria-producto/'.$slug;
                }
            }

            return '/shop';
        }

        if (preg_match('#^(?:etiqueta-producto|product-tag)/([a-z0-9-]+)$#', $path, $m)) {
            return '/shop?q='.rawurlencode(str_replace('-', ' ', $m[1]));
        }
        if (preg_match('#^(?:marca|brand|pwb-brand|product-brand)/([a-z0-9-]+)$#', $path, $m)) {
            return '/shop?marca='.$m[1];
        }

        // Página de gracias de WooCommerce: los pedidos viejos se consultan en "Rastrear pedido".
        if (preg_match('#^(?:checkout|finalizar-compra)/(?:orden-recibida|order-received)/\d+$#', $path)) {
            return '/rastrear-pedido';
        }
        if (preg_match('#^(?:my-account|mi-cuenta)/(?:view-order|ver-pedido)/\d+$#', $path)) {
            return '/mi-cuenta';
        }
        if (str_starts_with($path, 'wp-admin/') || str_starts_with($path, 'wp-json')) {
            return null;
        }

        return false;
    }

    private function fromQuery(array $query): string|false
    {
        if (! empty($query['page_id']) && isset(self::PAGE_IDS[(string) $query['page_id']])) {
            return self::PAGE_IDS[(string) $query['page_id']];
        }
        if (! empty($query['product']) && is_string($query['product']) && preg_match('/^[a-z0-9-]+$/i', $query['product'])) {
            return '/producto/'.strtolower($query['product']);
        }
        if (! empty($query['p']) && ctype_digit((string) $query['p'])
            && $slug = Product::where('legacy_id', (int) $query['p'])->value('slug')) {
            return '/producto/'.$slug;
        }
        if (isset($query['s']) && is_string($query['s'])) {
            return '/shop?q='.rawurlencode(trim($query['s']));
        }
        if (! empty($query['add-to-cart'])) {
            return '/carrito';
        }

        return false;
    }
}
