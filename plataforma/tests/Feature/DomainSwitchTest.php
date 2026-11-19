<?php

namespace Tests\Feature;

use App\Legacy\CutoverReadiness;
use App\Legacy\UrlChecker;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Ola 6: URL de WordPress con 301 y lista sí/no para el cambio de dominio. */
class DomainSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_wordpress_urls_redirect_permanently(): void
    {
        $cat = Category::first();
        $p = Product::visible()->first();
        $p->forceFill(['legacy_id' => 4321])->save();
        $store = Store::where('status', 'active')->first();

        $cases = [
            '/cart-2/' => '/carrito',
            '/cart' => '/carrito',
            '/checkout-2' => '/checkout',
            '/my-account-2/pedidos/' => '/mi-cuenta',
            '/rastrea-tu-pedido/' => '/rastrear-pedido',
            '/vendor-membership/' => '/vender',
            '/store-listing/' => '/tiendas',
            '/tienda/' => '/tiendas',
            '/contact-us/' => '/contacto',
            '/black-city/' => '/ofertas-relampago',
            '/wp-login.php' => '/ingresar',
            '/wp-sitemap.xml' => '/sitemap.xml',
            '/wp-sitemap-posts-product-1.xml' => '/sitemap.xml',
            '/seller-center/products-manage/' => '/seller-center/productos',
            "/product/{$p->slug}/" => "/producto/{$p->slug}",
            "/store/{$store->slug}/" => "/tienda/{$store->slug}",
            "/categoria-producto/tecnologia-vieja/{$cat->slug}/" => "/categoria-producto/{$cat->slug}",
            '/categoria-producto/no-existe/tampoco/' => '/shop',
            '/shop/page/3/' => '/shop?page=3',
            '/checkout/orden-recibida/5512/' => '/rastrear-pedido',
            '/?page_id=6103' => '/politica-de-privacidad',
            "/?product={$p->slug}" => "/producto/{$p->slug}",
            '/?p=4321' => "/producto/{$p->slug}",
            '/?s=audifonos&post_type=product' => '/shop?q=audifonos',
            '/etiqueta-producto/gamer-pro/' => '/shop?q=gamer%20pro',
        ];
        foreach ($cases as $from => $to) {
            $r = $this->get($from);
            $this->assertSame(301, $r->status(), $from);
            $r->assertRedirect($to);
        }

        // Retiradas a propósito.
        $this->get('/estadisticas/')->assertStatus(410);
        $this->get('/wp-json/wp/v2/users')->assertStatus(410);

        // Lo que ya existe igual no se toca.
        $this->get("/producto/{$p->slug}")->assertOk();
        $this->get("/categoria-producto/{$cat->slug}")->assertOk();
        $this->get("/tienda/{$store->slug}")->assertOk();
        $this->get('/')->assertOk();
        // Un POST nunca se redirige (formularios y avisos de pago).
        $this->post('/cart')->assertStatus(405);
    }

    public function test_url_checker_reads_sitemap_and_classifies(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://marketcity.co/wp-sitemap.xml' => Http::response('<sitemapindex><sitemap><loc>https://marketcity.co/wp-sitemap-posts-page-1.xml</loc></sitemap><sitemap><loc>https://marketcity.co/wp-sitemap-posts-wolmart_template-1.xml</loc></sitemap></sitemapindex>'),
            'https://marketcity.co/wp-sitemap-posts-page-1.xml' => Http::response('<urlset><url><loc>https://marketcity.co/shop/</loc></url><url><loc>https://marketcity.co/cart-2/</loc></url><url><loc>https://marketcity.co/estadisticas/</loc></url><url><loc>https://marketcity.co/pagina-perdida/</loc></url></urlset>'),
            'https://v1.test/shop/' => Http::response('', 301, ['Location' => 'https://v1.test/shop']),
            'https://v1.test/shop' => Http::response('ok', 200),
            'https://v1.test/cart-2/' => Http::response('', 301, ['Location' => '/carrito']),
            'https://v1.test/carrito' => Http::response('ok', 200),
            'https://v1.test/estadisticas/' => Http::response('', 410),
            'https://v1.test/pagina-perdida/' => Http::response('', 404),
        ]);

        $checker = app(UrlChecker::class);
        $paths = $checker->readSitemap('https://marketcity.co/wp-sitemap.xml');
        $this->assertSame(['/shop/', '/cart-2/', '/estadisticas/', '/pagina-perdida/'], $paths);

        $checker->start($paths, 'https://v1.test', 'prueba');
        $this->assertSame(['checked' => 3, 'remaining' => 1], $checker->checkBatch(3));
        $checker->checkBatch(3);
        $s = $checker->summary();
        $this->assertSame([4, 2, 1, 1], [$s['revisadas'], $s['redirige'], $s['retirada'], $s['falta']]);
        $this->assertStringContainsString('"/pagina-perdida/","falta","404"', $checker->csv());
    }

    public function test_cutover_page_shows_go_no_go(): void
    {
        Storage::fake('local');
        $admin = User::where('role', 'admin')->first();
        $this->get('/backoffice/cambio-de-dominio')->assertRedirect('/ingresar');
        $this->actingAs($admin)->get('/backoffice/cambio-de-dominio')->assertOk()
            ->assertSee('TODAVÍA NO')
            ->assertSee('Modo de prueba apagado')
            ->assertSee('Base de datos MySQL');

        $this->assertFalse(app(CutoverReadiness::class)->ready());
        $this->actingAs($admin)->post('/backoffice/cambio-de-dominio/revisar', ['list' => "https://marketcity.co/shop/\n/cart-2/"])
            ->assertRedirect('/backoffice/cambio-de-dominio');
        $this->assertCount(2, app(UrlChecker::class)->state()['urls']);
    }

    public function test_product_feed_for_google_and_meta(): void
    {
        $p = Product::visible()->first();
        $xml = $this->get('/catalogo/productos.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
        $this->assertSame(Product::visible()->count(), count($doc->channel->item));
        $this->assertStringContainsString(route('product', $p), $xml);
        $this->assertStringContainsString(' COP</g:price>', $xml);
        $this->assertStringNotContainsString('<g:price>1 COP</g:price>', $xml);
    }
}
