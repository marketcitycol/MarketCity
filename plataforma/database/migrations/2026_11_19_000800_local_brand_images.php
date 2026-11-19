<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Las imágenes de marca que v1 cargaba desde WordPress ahora viven en public/marca/.
 * Se cambian los logos de tienda que puso la migración 000700 y la imagen de la portada
 * si quedó guardada en el Backoffice. Un logo o una portada que alguien cambió a mano no se toca.
 */
return new class extends Migration
{
    private const STORE_LOGOS = [
        'impowinner' => ['https://i0.wp.com/marketcity.co/wp-content/uploads/2024/08/IMPOWINNER.png?resize=150%2C150&ssl=1', '/marca/tiendas/impowinner.webp'],
        'starktec' => ['https://i0.wp.com/marketcity.co/wp-content/uploads/2026/11/navidadstarktec.jpg?resize=150%2C150&ssl=1', '/marca/tiendas/starktec.webp'],
        'davshop' => ['https://marketcity.co/wp-content/uploads/2026/11/DAVSHOPLOGO.png', '/marca/tiendas/davshop.webp'],
        'davidshop' => ['https://marketcity.co/wp-content/uploads/2026/05/FD40193D-3C3D-4579-8343-EF67347DDBC6.png', '/marca/tiendas/davidshop.webp'],
        'alltronix' => ['https://i0.wp.com/marketcity.co/wp-content/uploads/2026/11/LOGOalltronix.webp?resize=150%2C150&ssl=1', '/marca/tiendas/alltronix.webp'],
    ];

    private const HERO_OLD = 'https://marketcity.co/wp-content/uploads/2026/10/marketcity-hero-final-web.jpg';

    private const HERO_NEW = '/marca/portada.webp';

    public function up(): void
    {
        foreach (self::STORE_LOGOS as $slug => [$old, $new]) {
            DB::table('stores')->where('slug', $slug)->where('logo_path', $old)->update(['logo_path' => $new, 'updated_at' => now()]);
        }
        $this->swapHero(self::HERO_OLD, self::HERO_NEW);
    }

    public function down(): void
    {
        foreach (self::STORE_LOGOS as $slug => [$old, $new]) {
            DB::table('stores')->where('slug', $slug)->where('logo_path', $new)->update(['logo_path' => $old, 'updated_at' => now()]);
        }
        $this->swapHero(self::HERO_NEW, self::HERO_OLD);
    }

    private function swapHero(string $from, string $to): void
    {
        $raw = DB::table('settings')->where('key', 'plugin.hero')->value('value');
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (($data['values']['image_url'] ?? null) !== $from) {
            return;
        }
        $data['values']['image_url'] = $to;
        DB::table('settings')->where('key', 'plugin.hero')->update(['value' => json_encode($data, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
    }
};
