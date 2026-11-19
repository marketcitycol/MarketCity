<?php

namespace App\Services\Content;

use App\Models\Setting;

/**
 * Plugins de la tienda: piezas de la identidad (encabezado, pie, barra de anuncio, WhatsApp)
 * que se activan y editan desde Backoffice → Plugins, sin tocar código.
 *
 * Los valores por defecto copian lo que hoy muestra marketcity.co (lectura del 2026-11-17).
 * Lo editado se guarda en `settings` con la clave `plugin.<clave>` (JSON).
 *
 * Campos "lineas": una opción por línea, partes separadas por "|". Ej.: "Tienda | /shop".
 */
class Plugins
{
    public const LOGO = '/marca/logo.webp';

    /** Logo que usaba v1 desde WordPress; si quedó guardado, se cambia por la copia local. */
    public const LEGACY_LOGO = 'https://marketcity.co/wp-content/uploads/2026/05/mcLOGO.png';

    public static function logo(): string
    {
        $url = trim((string) (self::config('header')['logo_url'] ?? ''));

        return $url === '' || $url === self::LEGACY_LOGO ? asset(self::LOGO) : self::url($url);
    }

    /** @return array<string, array{name:string, description:string, enabled:bool, fields:array<string,array{0:string,1:string}>, defaults:array<string,string>}> */
    public static function definitions(): array
    {
        return [
            'announcement' => [
                'name' => 'Barra de anuncio',
                'description' => 'Franja negra superior con una campaña, cuenta regresiva y botón.',
                'enabled' => true,
                'fields' => [
                    'text' => ['Texto', 'text'],
                    'highlight' => ['Texto resaltado (opcional)', 'text'],
                    'button' => ['Texto del botón', 'text'],
                    'url' => ['Enlace del botón', 'text'],
                    'ends_at' => ['Termina (AAAA-MM-DD HH:MM, hora de Colombia; vacío = sin cuenta regresiva)', 'text'],
                ],
                'defaults' => [
                    'text' => 'BLACK CITY: ofertas desde $28.000',
                    'highlight' => 'o en 4 cuotas',
                    'button' => 'Ver ofertas',
                    'url' => '/ofertas-relampago',
                    'ends_at' => '2026-11-21 00:00',
                ],
            ],
            'header' => [
                'name' => 'Encabezado MarketCity',
                'description' => 'Logo, buscador, "Vende en MarketCity", cuenta, carrito, menú de categorías, menú lateral y barra inferior en celular.',
                'enabled' => true,
                'fields' => [
                    'logo_url' => ['Logo (URL de la imagen)', 'text'],
                    'strip' => ['Franja azul (una frase por línea)', 'lines'],
                    'search_placeholder' => ['Texto del buscador', 'text'],
                    'sell' => ['Botón para vendedores (Texto | URL)', 'text'],
                    'nav' => ['Menú (Texto | URL | estilo: oferta, credito o normal)', 'lines'],
                    'help' => ['Enlaces de ayuda a la derecha (Texto | URL)', 'lines'],
                    'drawer_buy' => ['Menú lateral · Tu compra (Texto | Subtítulo | URL)', 'lines'],
                    'drawer_payments' => ['Menú lateral · Formas de pago', 'textarea'],
                    'drawer_sell' => ['Menú lateral · Vende en MarketCity (Título | Subtítulo)', 'lines'],
                ],
                'defaults' => [
                    'logo_url' => self::LOGO,
                    'strip' => "Paga a cuotas con Addi y Sistecrédito\nEnvío a toda Colombia\nCompra protegida",
                    'search_placeholder' => 'Prueba: audífonos menos de 80 mil',
                    'sell' => 'Vende en MarketCity | /vender',
                    'nav' => implode("\n", [
                        'Ofertas relámpago | /ofertas-relampago | oferta',
                        'A crédito | /shop?max_price=200000 | credito',
                        'Gaming | /categoria-producto/gaming',
                        'Belleza | /categoria-producto/belleza',
                        'Tecnología | /categoria-producto/tecnologia',
                        'Hogar | /categoria-producto/hogar',
                        'Cocina | /categoria-producto/cocina',
                        'Salud | /categoria-producto/salud',
                        'Gadgets | /categoria-producto/gadgets',
                        'Moda | /categoria-producto/moda',
                        'Herramientas | /categoria-producto/herramientas',
                        'Vehículos | /categoria-producto/vehiculos',
                        'Ver todo | /shop',
                    ]),
                    'help' => "Rastrear pedido | /rastrear-pedido\nMis pedidos | /mi-cuenta\nAyuda | /contacto",
                    'drawer_buy' => implode("\n", [
                        'Mis pedidos | Estado y detalle de tus compras | /mi-cuenta',
                        'Rastrear pedido | Con tu número de orden | /rastrear-pedido',
                        'Carrito | Revisa y finaliza tu compra | /carrito',
                        'Mi cuenta | Datos y direcciones | /mi-cuenta',
                    ]),
                    'drawer_payments' => 'Crédito con Addi o Sistecrédito, Nequi, Daviplata o tarjeta. Lo eliges al pagar.',
                    'drawer_sell' => "Crea tu tienda | Regístrate con tu cédula o NIT.\nSube tus productos | Fotos, precio y stock desde tu panel.\nVende con crédito | Tus clientes pagan con Addi o Sistecrédito y más.",
                ],
            ],
            'footer' => [
                'name' => 'Pie de página MarketCity',
                'description' => 'Beneficios, categorías, enlaces, redes, contacto, medios de pago y enlaces legales.',
                'enabled' => true,
                'fields' => [
                    'trust' => ['Beneficios (Título | Subtítulo)', 'lines'],
                    'explore' => ['Sigue explorando (Texto | URL)', 'lines'],
                    'description' => ['Descripción bajo el logo', 'textarea'],
                    'social' => ['Redes (tiktok, instagram o facebook | URL)', 'lines'],
                    'buy' => ['Columna Comprar (Texto | URL)', 'lines'],
                    'help' => ['Columna Ayuda (Texto | URL)', 'lines'],
                    'sell' => ['Columna Vende y contacto (Texto | URL)', 'lines'],
                    'email' => ['Correo', 'text'],
                    'whatsapp' => ['WhatsApp (número con indicativo, solo dígitos)', 'text'],
                    'hours' => ['Horario de atención', 'text'],
                    'payments' => ['Paga con (uno por línea)', 'lines'],
                    'company' => ['Línea de la empresa (dirección y contacto)', 'text'],
                    'legal' => ['Enlaces legales (Texto | URL)', 'lines'],
                ],
                'defaults' => [
                    'trust' => implode("\n", [
                        'Compra ahora, paga después | Financia con Addi o Sistecrédito',
                        'Entrega express | Rastrea tu pedido en tiempo real',
                        'Compra protegida | Retracto y reversión según la ley',
                        'Te ayudamos por WhatsApp | Lun a sáb · 8:00 a. m. – 6:00 p. m.',
                    ]),
                    'explore' => "Gaming | /categoria-producto/gaming\nTecnología | /categoria-producto/tecnologia\nBelleza | /categoria-producto/belleza\nSalud | /categoria-producto/salud",
                    'description' => 'Marketplace colombiano para comprar productos, pagar fácil y conectar con tiendas aliadas.',
                    'social' => "tiktok | https://www.tiktok.com/@marketcity.coo\ninstagram | https://www.instagram.com/marketcity.coo/\nfacebook | https://www.facebook.com/marketcity.coo",
                    'buy' => "Tienda | /shop\nOfertas relámpago | /ofertas-relampago\nCarrito | /carrito\nMi cuenta | /mi-cuenta",
                    'help' => "Rastrear pedido | /rastrear-pedido\nMis pedidos | /mi-cuenta\nPQRS, retracto y reversión | /pqrs\nCompra ahora, paga después | /contacto",
                    'sell' => "Vende en MarketCity | /vender\nSeller Center | /seller-center",
                    'email' => 'somos@marketcity.co',
                    'whatsapp' => '573192134510',
                    'hours' => 'Lun a sáb · 8:00 a. m. – 6:00 p. m.',
                    'payments' => "Addi\nSistecrédito\nNequi\nDaviplata\nPSE\nVisa\nMastercard\nAmerican Express\nDiners Club",
                    'company' => 'MarketCity · Cra. 13 #12-58, Funza, Cundinamarca · somos@marketcity.co · 319 213 4510',
                    'legal' => "Términos y condiciones | /politica-de-privacidad\nTratamiento de datos | /politica-de-privacidad\nDerecho de retracto | /pqrs?tipo=retracto\nReversión del pago | /pqrs?tipo=reversion\nSuperintendencia de Industria y Comercio | https://www.sic.gov.co/",
                ],
            ],
            'hero' => [
                'name' => 'Portada de inicio',
                'description' => 'Bloque principal de la página de inicio: mensaje, imagen y botones.',
                'enabled' => true,
                'fields' => [
                    'badge' => ['Etiqueta superior', 'text'],
                    'title' => ['Título', 'text'],
                    'title_soft' => ['Título · parte resaltada', 'text'],
                    'title_end' => ['Título · segunda línea', 'text'],
                    'subtitle' => ['Texto', 'textarea'],
                    'image_url' => ['Imagen (URL)', 'text'],
                    'buttons' => ['Botones (Texto | URL)', 'lines'],
                ],
                'defaults' => [
                    'badge' => 'Marketplace colombiano · Paga a cuotas',
                    'title' => 'Lo que quieres,',
                    'title_soft' => 'hoy mismo.',
                    'title_end' => 'Págalo después.',
                    'subtitle' => 'Tecnología, hogar, belleza y más de tiendas colombianas, con envío a todo el país y crédito con Addi o Sistecrédito.',
                    'image_url' => '/marca/portada.webp',
                    'buttons' => "⚡ Ver ofertas | /ofertas-relampago\nExplorar la tienda | /shop",
                ],
            ],
            'analytics' => [
                'name' => 'Medición y aviso de cookies',
                'description' => 'Google Tag Manager, Google Analytics 4 y píxel de Meta con el embudo de compra (ver producto, agregar al carrito, iniciar pago, medio de pago, compra y clic en WhatsApp). Nada carga hasta que el visitante acepta las cookies de medición. Usa GTM o GA4, no los dos, para no contar doble.',
                'enabled' => false,
                'fields' => [
                    'gtm_id' => ['Google Tag Manager (GTM-XXXXXXX, opcional)', 'text'],
                    'ga4_id' => ['Google Analytics 4 (G-XXXXXXXXXX, opcional; se ignora si hay GTM)', 'text'],
                    'meta_pixel_id' => ['Píxel de Meta (solo números, opcional)', 'text'],
                    'banner_text' => ['Texto del aviso de cookies', 'textarea'],
                    'policy_url' => ['Enlace a la política de cookies y datos', 'text'],
                ],
                'defaults' => [
                    'gtm_id' => '',
                    'ga4_id' => '',
                    'meta_pixel_id' => '',
                    'banner_text' => 'Usamos cookies necesarias para que la tienda funcione. Si aceptas, también usamos cookies de medición de Google y Meta para entender cómo compras y mostrarte anuncios útiles. Puedes cambiar tu decisión cuando quieras.',
                    'policy_url' => '/politica-de-privacidad',
                ],
            ],
            'whatsapp' => [
                'name' => 'Botón de WhatsApp',
                'description' => 'Botón flotante para escribir por WhatsApp desde cualquier página.',
                'enabled' => true,
                'fields' => [
                    'number' => ['Número (con indicativo, solo dígitos)', 'text'],
                    'message' => ['Mensaje inicial', 'text'],
                ],
                'defaults' => [
                    'number' => '573192134510',
                    'message' => 'Hola MarketCity, necesito ayuda con una compra.',
                ],
            ],
        ];
    }

    /** @var array<string, array>|null */
    private static ?array $cache = null;

    /** Configuración guardada (sin defaults): ['enabled' => bool, 'values' => [...]]. */
    private static function stored(string $key): array
    {
        $raw = Setting::get('plugin.'.$key);
        $data = is_string($raw) ? json_decode($raw, true) : null;

        return is_array($data) ? $data : [];
    }

    public static function enabled(string $key): bool
    {
        $def = self::definitions()[$key] ?? null;
        if (! $def) {
            return false;
        }

        return (bool) (self::stored($key)['enabled'] ?? $def['enabled']);
    }

    /** Valores efectivos del plugin (guardados sobre los de fábrica). */
    public static function config(string $key): array
    {
        self::$cache ??= [];
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        $def = self::definitions()[$key];
        $values = array_merge($def['defaults'], array_intersect_key(self::stored($key)['values'] ?? [], $def['fields']));

        return self::$cache[$key] = $values;
    }

    public static function save(string $key, bool $enabled, array $values): void
    {
        $def = self::definitions()[$key];
        $clean = [];
        foreach ($def['fields'] as $field => [, $type]) {
            $clean[$field] = trim(str_replace("\r\n", "\n", (string) ($values[$field] ?? '')));
        }
        Setting::put('plugin.'.$key, json_encode(['enabled' => $enabled, 'values' => $clean], JSON_UNESCAPED_UNICODE));
        self::$cache = null;
    }

    public static function reset(string $key): void
    {
        Setting::where('key', 'plugin.'.$key)->delete();
        self::$cache = null;
    }

    /**
     * Convierte un campo de líneas en filas: "A | B | C" → ['A', 'B', 'C'].
     *
     * @return list<list<string>>
     */
    public static function lines(?string $value): array
    {
        $rows = [];
        foreach (preg_split('/\n+/', (string) $value) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rows[] = array_map('trim', explode('|', $line));
        }

        return $rows;
    }

    /** Solo enlaces relativos del sitio o https:// (evita javascript: y similares). */
    public static function url(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '' || str_starts_with($url, '//')) {
            return '#';
        }
        if (str_starts_with($url, '/') || preg_match('#^(https://|mailto:|tel:)#i', $url)) {
            return $url;
        }

        return '#';
    }
}
