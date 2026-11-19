<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use App\Support\Plugins;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginsAndPagesTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function admin(): User
    {
        return User::where('role', 'admin')->firstOrFail();
    }

    public function test_home_uses_marketcity_header_and_footer_plugins(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Paga a cuotas con Addi y Sistecrédito')
            ->assertSee('Vende en MarketCity')
            ->assertSee('Sigue explorando')
            ->assertSee('Cra. 13 #12-58, Funza')
            ->assertSee('wa.me/573192134510', false)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_admin_can_disable_and_edit_a_plugin(): void
    {
        $this->actingAs($this->admin())->post(route('admin.plugins.toggle', 'footer'))->assertRedirect();
        $this->get('/')->assertDontSee('Sigue explorando');

        $this->actingAs($this->admin())->put(route('admin.plugins.update', 'header'), [
            'enabled' => 1, 'values' => ['strip' => "Envío gratis hoy", 'nav' => "Gaming | /categoria-producto/gaming\nMalo | javascript:alert(1)"],
        ])->assertRedirect();
        $this->get('/')->assertSee('Envío gratis hoy')->assertDontSee('javascript:alert', false);

        $this->actingAs($this->admin())->post(route('admin.plugins.reset', 'header'));
        $this->get('/')->assertSee('Paga a cuotas con Addi y Sistecrédito');
    }

    public function test_plugins_area_requires_admin(): void
    {
        $this->get(route('admin.plugins'))->assertRedirect(route('login'));
        $seller = User::where('role', 'seller')->firstOrFail();
        $this->actingAs($seller)->get(route('admin.plugins'))->assertForbidden();
    }

    public function test_page_import_sanitizes_and_rewrites_links(): void
    {
        $html = '<div class="elementor"><h2>Derecho de retracto</h2><p style="color:red" onclick="x()">Tienes 5 días hábiles. <a href="https://marketcity.co/contacto/">Contacto</a> <a href="javascript:alert(1)">mal</a></p><script>alert(1)</script><form><input name="x"></form></div>';
        $this->actingAs($this->admin())->postJson(route('admin.pages.import'), ['pages' => [
            ['legacy_id' => 6103, 'slug' => 'politica-de-privacidad', 'title' => 'Política de privacidad', 'html' => $html],
            ['legacy_id' => 9, 'slug' => 'shop', 'title' => 'Shop', 'html' => '<p>x</p>'],
        ]])->assertOk()->assertJson(['created' => 2, 'updated' => 0]);

        $page = Page::where('legacy_id', 6103)->firstOrFail();
        $this->assertStringContainsString('<a href="/contacto/">Contacto</a>', $page->body);
        $this->assertStringNotContainsString('script', $page->body);
        $this->assertStringNotContainsString('onclick', $page->body);
        $this->assertStringNotContainsString('javascript:', $page->body);
        $this->assertStringNotContainsString('<input', $page->body);
        $this->assertNotSame('shop', Page::where('legacy_id', 9)->value('slug'), 'una página no puede tapar una ruta de la tienda');

        $this->get('/politica-de-privacidad')->assertOk()->assertSee('Derecho de retracto')->assertSee('<title>Política de privacidad · MarketCity</title>', false);
        $this->get('/shop')->assertOk()->assertDontSee('<h1 class="text-3xl font-extrabold text-slate-900">Shop</h1>', false);

        // Repetible: actualiza, no duplica.
        $this->actingAs($this->admin())->postJson(route('admin.pages.import'), ['pages' => [
            ['legacy_id' => 6103, 'slug' => 'otra', 'title' => 'Política de privacidad', 'html' => '<p>Nueva versión</p>'],
        ]])->assertJson(['created' => 0, 'updated' => 1]);
        $this->get('/politica-de-privacidad')->assertSee('Nueva versión');
    }

    public function test_draft_pages_are_hidden_and_reserved_slugs_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), ['title' => 'Carrito', 'slug' => 'carrito', 'body' => '', 'status' => 'published'])
            ->assertSessionHasErrors('slug');
        $this->actingAs($this->admin())->post(route('admin.pages.store'), ['title' => 'FAQ', 'slug' => 'faq', 'body' => '<p>Hola</p>', 'status' => 'draft'])
            ->assertRedirect();
        auth()->logout();
        $this->get('/faq')->assertNotFound();
        $this->get('/no-existe')->assertNotFound();
    }
}
