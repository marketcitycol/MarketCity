<?php

namespace App\Services\Content;

use Illuminate\Support\Collection;

/**
 * Piezas de marca que v1 comparte con marketcity.co (lectura del 2026-11-19).
 * Logos, medios de pago y transportadoras viven en public/marca/ para no depender de WordPress.
 * Las fotos de categoría siguen apuntando a las imágenes del sitio actual, igual que las de producto,
 * hasta que el copiador de medios las traiga (Backoffice → Importar).
 */
class BrandAssets
{
    public const LOGO = '/marca/logo.webp';

    public const ISOTIPO = '/marca/isotipo.webp';

    /** Foto redonda de cada categoría en «Compra por categoría» (la misma del inicio actual). */
    public const CATEGORY_IMAGES = [
        'tecnologia' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2023/11/425358.png?resize=150%2C150&ssl=1',
        'hogar' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2025/08/ARMARIO.png?resize=150%2C150&ssl=1',
        'belleza' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2025/08/encrespador.png?resize=150%2C150&ssl=1',
        'gaming' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2025/08/DIADEMA-G9000.jpg?resize=150%2C150&ssl=1',
        'cocina' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2025/08/abridor-huevos-1.png?resize=150%2C150&ssl=1',
        'salud' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2025/07/altaf.1.png?resize=150%2C150&ssl=1',
        'gadgets' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2025/06/proyector-hy300.png?resize=150%2C150&ssl=1',
        'moda' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2025/07/CAMARA-INSTA.1.png?resize=150%2C150&ssl=1',
        'herramientas' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2025/08/pulidora.png?resize=150%2C150&ssl=1',
        'vehiculos' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2025/05/68215f6a53af4500094d5c4a_1579444868134791430-1.jpg?resize=150%2C150&ssl=1',
        'jugueteria' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2025/05/68215f6a53af4500094d5c4a_16616388145418947124.jpg?resize=150%2C150&ssl=1',
        'mascotas' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2025/06/maquina-perros.png?resize=150%2C150&ssl=1',
    ];

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
        return self::CATEGORY_IMAGES[$slug] ?? null;
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
