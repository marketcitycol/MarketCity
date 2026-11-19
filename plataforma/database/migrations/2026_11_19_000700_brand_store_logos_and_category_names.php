<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ola visual: las tiendas copiadas del sitio anterior quedaron sin logo y dos categorías sin tilde.
 * Se ponen los mismos logos que muestra marketcity.co en /tiendas y en el inicio,
 * solo donde la tienda no tiene uno propio, y se corrigen los nombres sin tilde.
 */
return new class extends Migration
{
    private const LOGOS = [
        'impowinner' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2024/08/IMPOWINNER.png?resize=150%2C150&ssl=1',
        'starktec' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2026/11/navidadstarktec.jpg?resize=150%2C150&ssl=1',
        'davshop' => 'https://marketcity.co/wp-content/uploads/2026/11/DAVSHOPLOGO.png',
        'davidshop' => 'https://marketcity.co/wp-content/uploads/2026/05/FD40193D-3C3D-4579-8343-EF67347DDBC6.png',
        'alltronix' => 'https://i0.wp.com/marketcity.co/wp-content/uploads/2026/11/LOGOalltronix.webp?resize=150%2C150&ssl=1',
        'marketcity' => '/marca/isotipo.webp',
    ];

    private const NAMES = ['tecnologia' => 'Tecnología', 'jugueteria' => 'Juguetería', 'vehiculos' => 'Vehículos', 'bisuteria' => 'Bisutería'];

    public function up(): void
    {
        foreach (self::LOGOS as $slug => $url) {
            DB::table('stores')->where('slug', $slug)->where(fn ($q) => $q->whereNull('logo_path')->orWhere('logo_path', ''))
                ->update(['logo_path' => $url, 'updated_at' => now()]);
        }
        foreach (self::NAMES as $slug => $name) {
            DB::table('categories')->where('slug', $slug)->update(['name' => $name, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('stores')->whereIn('logo_path', array_values(self::LOGOS))->update(['logo_path' => null]);
    }
};
