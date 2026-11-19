<?php

namespace App\Support;

/**
 * Iconos de línea de MarketCity (los mismos trazos del sprite de marketcity.co, 24×24, trazo 1.8).
 * Uso en Blade: {!! icon('carrito', 'h-6 w-6') !!}
 */
class Icons
{
    public const PATHS = [
        'atras' => '<path d="m15 5-7 7 7 7"/>',
        'belleza' => '<path d="M9 21h6M10 21V11h4v10M10.5 11V6.5L12 3l1.5 3.5V11"/>',
        'bolsa' => '<path d="M5 8h14l-1 12H6z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/>',
        'buscar' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'caja' => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        'camara' => '<path d="M4 8h3l2-3h6l2 3h3v11H4z"/><circle cx="12" cy="13" r="3.5"/>',
        'carrito' => '<path d="M3 4h2l2.4 11h10.2L20 7H6.2"/><circle cx="9" cy="19.5" r="1.3"/><circle cx="17" cy="19.5" r="1.3"/>',
        'carro' => '<path d="M5 16V11l2-5h10l2 5v5M3 16h18v3H3z"/><circle cx="7" cy="16" r="1.5"/><circle cx="17" cy="16" r="1.5"/>',
        'categorias' => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
        'cerrar' => '<path d="m6 6 12 12M18 6 6 18"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'cocina' => '<path d="M5 11h14v3a6 6 0 0 1-6 6h-2a6 6 0 0 1-6-6zM3 11h18M9 7c0-1 1-1 1-2M13 7c0-1 1-1 1-2"/>',
        'correo' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'credito' => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h4"/>',
        'cuenta' => '<circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-3.5 4.5-5 8-5s6.5 1.5 8 5"/>',
        'envio' => '<path d="M3 7h11v9H3zM14 10h4l3 3v3h-7"/><circle cx="7" cy="17.5" r="1.5"/><circle cx="17" cy="17.5" r="1.5"/>',
        'escudo' => '<path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6z"/><path d="m9 12 2 2 4-4"/>',
        'estrella' => '<path d="m12 4 2.4 5 5.4.6-4 3.7 1.1 5.4L12 16l-4.9 2.7 1.1-5.4-4-3.7 5.4-.6z"/>',
        'etiqueta' => '<path d="M3 12V4h8l10 10-8 8z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'facebook' => '<path fill="currentColor" stroke="none" d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>',
        'flecha' => '<path d="m9 5 7 7-7 7"/>',
        'gadget' => '<rect x="5" y="5" width="14" height="14" rx="4"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3"/>',
        'gaming' => '<path d="M7 9h10a4 4 0 0 1 4 4v1a3 3 0 0 1-5.4 1.8L14.5 14h-5l-1.1 1.8A3 3 0 0 1 3 14v-1a4 4 0 0 1 4-4z"/><path d="M8 11v3M6.5 12.5h3M15.5 12h.01M17.5 13.5h.01"/>',
        'herramientas' => '<path d="M14.5 6.5a4 4 0 0 0 5 5l-8.8 8.8a2.1 2.1 0 0 1-3-3z"/><path d="M14.5 6.5 17 4"/>',
        'hogar' => '<path d="M4 20V10l8-6 8 6v10M9 20v-5h6v5"/>',
        'inicio' => '<path d="M4 11 12 4l8 7v9h-5v-6H9v6H4z"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/>',
        'luna' => '<path d="M20.5 14.6A8.5 8.5 0 0 1 9.4 3.5a8.5 8.5 0 1 0 11.1 11.1z"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'microfono' => '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0 0 14 0M12 18v3"/>',
        'moda' => '<path d="M9 4 4 6.5l1.5 4L8 9.5V20h8V9.5l2.5 1 1.5-4L15 4a3 3 0 0 1-6 0z"/>',
        'ofertas' => '<path d="M13 3 5 13h6l-1 8 8-10h-6z"/>',
        'ojo' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'protegido' => '<path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6z"/><path d="m9 12 2 2 4-4"/>',
        'regalo' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M19 12v9H5v-9M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/>',
        'salud' => '<path d="M9 4h6v5h5v6h-5v5H9v-5H4V9h5z"/>',
        'sol' => '<circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.2M12 19.3v2.2M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.2M19.3 12h2.2M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/>',
        'tecnologia' => '<rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/>',
        'tienda' => '<path d="M3 9 5 4h14l2 5"/><path d="M3 9a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0"/><path d="M5 12v8h14v-8"/><path d="M10 20v-5h4v5"/>',
        'tiktok' => '<path fill="currentColor" stroke="none" d="M14 3v10.5a3.5 3.5 0 1 1-3-3.46V7.1A6.5 6.5 0 1 0 17 13.5V8.6a7 7 0 0 0 4 1.3V6.9A4 4 0 0 1 17 3z"/>',
        'verificado' => '<path d="m12 2 2.4 1.8 3-.2.9 2.9 2.5 1.7-1 2.8 1 2.8-2.5 1.7-.9 2.9-3-.2L12 22l-2.4-1.8-3 .2-.9-2.9-2.5-1.7 1-2.8-1-2.8 2.5-1.7.9-2.9 3 .2z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
        'whatsapp' => '<path d="M4.5 19.5 5.6 16A8 8 0 1 1 8.4 18.6z"/><path d="M9.2 8.8c.2 2.6 3 5.3 5.8 5.8l1-1.4-1.8-.9-.8.7c-1-.4-1.9-1.3-2.4-2.4l.7-.8-.9-1.8z"/>',
    ];

    public static function svg(string $name, string $class = 'h-5 w-5'): string
    {
        $paths = self::PATHS[$name] ?? self::PATHS['categorias'];

        return '<svg class="'.e($class).'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.$paths.'</svg>';
    }

    /** Icono de una categoría por su slug (tecnologia, hogar…); las que no tienen uno propio usan la cuadrícula. */
    public static function forCategory(?string $slug): string
    {
        return match ($slug) {
            'tecnologia' => 'tecnologia', 'hogar' => 'hogar', 'belleza' => 'belleza', 'gaming' => 'gaming',
            'cocina' => 'cocina', 'salud' => 'salud', 'gadgets' => 'gadget', 'moda' => 'moda',
            'herramientas' => 'herramientas', 'vehiculos' => 'carro', 'jugueteria' => 'regalo', 'mascotas' => 'estrella',
            'bisuteria' => 'estrella',
            default => 'categorias',
        };
    }
}
