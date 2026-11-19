<?php

namespace Tests\Feature;

use App\Legacy\CutoverReadiness;
use App\Models\Setting;
use App\Models\Store;
use App\Services\Content\BrandAssets;
use App\Services\Content\Plugins;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Las imágenes de marca viven en public/marca/: ninguna página depende de WordPress. */
class LocalBrandImagesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_every_brand_image_file_exists(): void
    {
        $files = [BrandAssets::LOGO, BrandAssets::ISOTIPO, Plugins::config('hero')['image_url']];
        foreach (BrandAssets::CATEGORY_IMAGES as $slug) {
            $files[] = "/marca/categorias/{$slug}.webp";
        }
        foreach (['impowinner', 'starktec', 'davshop', 'davidshop', 'alltronix'] as $slug) {
            $files[] = "/marca/tiendas/{$slug}.webp";
        }

        foreach ($files as $file) {
            $this->assertStringStartsWith('/marca/', $file);
            $this->assertFileExists(public_path(ltrim($file, '/')), "Falta {$file}");
        }
    }

    public function test_public_pages_load_nothing_from_wordpress(): void
    {
        $store = Store::where('status', 'active')->firstOrFail();

        foreach (['/', '/tiendas', '/shop', "/tienda/{$store->slug}"] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('marketcity.co/wp-content', $html, $url);
        }
        $this->assertStringContainsString('/marca/portada.webp', $this->get('/')->getContent());
    }

    public function test_migration_moves_saved_wordpress_images_and_keeps_manual_ones(): void
    {
        $oldLogo = 'https://marketcity.co/wp-content/uploads/2026/11/DAVSHOPLOGO.png';
        [$davshop, $alltronix] = Store::orderBy('id')->take(2)->get()->all();
        $davshop->update(['slug' => 'davshop', 'logo_path' => $oldLogo]);
        $alltronix->update(['slug' => 'alltronix', 'logo_path' => 'logos/propio.png']);
        Setting::put('plugin.hero', json_encode(['enabled' => true, 'values' => [
            'title' => 'Hola', 'image_url' => 'https://marketcity.co/wp-content/uploads/2026/10/marketcity-hero-final-web.jpg',
        ]]));

        $this->assertFalse($this->imagesCheck()['ok']);
        $this->assertStringContainsString('logos de tienda: 1', $this->imagesCheck()['detail']);

        $migration = require database_path('migrations/2026_11_19_000800_local_brand_images.php');
        $migration->up();

        $this->assertSame('/marca/tiendas/davshop.webp', $davshop->fresh()->logo_path);
        $this->assertSame('logos/propio.png', $alltronix->fresh()->logo_path);
        $hero = json_decode(Setting::get('plugin.hero'), true);
        $this->assertSame(['title' => 'Hola', 'image_url' => '/marca/portada.webp'], $hero['values']);
        $this->assertTrue($this->imagesCheck()['ok']);

        $migration->down();
        $this->assertSame($oldLogo, $davshop->fresh()->logo_path);
    }

    /** @return array{ok:bool, detail:string} */
    private function imagesCheck(): array
    {
        $check = collect(app(CutoverReadiness::class)->checks())->firstWhere('label', 'Fotos copiadas del sitio anterior');

        return ['ok' => $check['ok'], 'detail' => $check['detail']];
    }
}
