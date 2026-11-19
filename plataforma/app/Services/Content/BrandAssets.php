<?php

namespace App\Services\Content;

use Illuminate\Support\Collection;

/**
 * Piezas de marca que v1 comparte con marketcity.co (lectura del 2026-11-19).
 * Todas viven en public/marca/ (logos, portada, fotos de categoría, logos de tiendas, medios de pago
 * y transportadoras) para que nada dependa de WordPress.
 */
class BrandAssets
{
    public const LOGO = '/marca/logo.webp';

    public const ISOTIPO = '/marca/isotipo.webp';

    /** Categorías con foto redonda en «Compra por categoría» (public/marca/categorias/<slug>.webp, 160×160). */
    public const CATEGORY_IMAGES = ['tecnologia', 'hogar', 'belleza', 'gaming', 'cocina', 'salud', 'gadgets', 'moda', 'herramientas', 'vehiculos', 'jugueteria', 'mascotas'];

    /** Orden de las categorías en el inicio y el menú, como en marketcity.co. */
    public const CATEGORY_ORDER = ['tecnologia', 'hogar', 'belleza', 'gaming', 'cocina', 'salud', 'gadgets', 'moda', 'herramientas', 'vehiculos', 'jugueteria', 'mascotas'];

    /** Logos de «Paga con» del pie de página. */
    public const PAYMENTS = [
        'Addi' => 'pago-addi', 'Sistecrédito' => 'pago-sistecredito', 'Nequi' => 'pago-nequi', 'Daviplata' => 'pago-daviplata',
        'PSE' => 'pago-pse', 'Visa' => 'pago-visa', 'Mastercard' => 'pago-mastercard', 'American Express' => 'pago-american-express',
        'Diners Club' => 'pago-diners-club',
    ];

    /** Transportadoras de la franja «Envíos seguros». */
    public const CARRIERS = [
        'Servientrega' => 'envio-servientrega', 'Coordinadora' => 'envio-coordinadora', 'Envía' => 'envio-envia',
        'Inter Rapidísimo' => 'envio-interrapidisimo', 'TCC' => 'envio-tcc',
    ];

    public static function asset(string $file): string
    {
        return asset('marca/'.$file.'.webp');
    }

    /** Logo de un medio de pago por su nombre (o null si no hay archivo). */
    public static function paymentLogo(string $name): ?string
    {
        $key = self::PAYMENTS[$name] ?? null;

        return $key ? self::asset($key) : null;
    }

    public static function categoryImage(?string $slug): ?string
    {
        return in_array($slug, self::CATEGORY_IMAGES, true) ? asset("marca/categorias/{$slug}.webp") : null;
    }

    /**
     * Ordena categorías como en marketcity.co: primero las conocidas en su orden, luego el resto por nombre.
     *
     * @template T of \App\Models\Category
     *
     * @param  Collection<int, T>  $categories
     * @return Collection<int, T>
     */
    public static function sortCategories($categories)
    {
        $order = array_flip(self::CATEGORY_ORDER);

        return $categories->sortBy(fn ($c) => [$order[$c->slug] ?? 99, $c->name])->values();
    }

    /** Degradado de la portada de una tienda en /tiendas, según su categoría principal. */
    public static function storeCover(?string $slug): string
    {
        return match ($slug) {
            'cocina', 'hogar' => 'linear-gradient(135deg,#123b2a,#0e7a3d)',
            'belleza', 'moda', 'salud' => 'linear-gradient(135deg,#4a1d5c,#a2347a)',
            'gaming' => 'linear-gradient(135deg,#1b1446,#5b43c0)',
            default => 'linear-gradient(135deg,#0b2a6f,#0a4bff)',
        };
    }
}
