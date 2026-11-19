<?php

namespace Tests\Feature;

use App\Legacy\MediaCopier;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Ola 4: experiencia de compra traída del sitio actual. */
class ShoppingExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_add_to_cart_returns_real_totals_for_the_sheet(): void
    {
        $p = Product::visible()->where('stock', '>', 2)->first();
        $this->postJson('/carrito', ['product_id' => $p->id, 'quantity' => 2])->assertOk()
            ->assertJson(['ok' => true, 'count' => 2, 'subtotal' => cop($p->price * 2)]);
        $this->get('/producto/'.$p->slug)->assertSee('data-add-to-cart', false)->assertSee('mc-cart-sheet', false);

        $p->update(['stock' => 0]);
        $this->postJson('/carrito', ['product_id' => $p->id])->assertStatus(422);
    }

    public function test_installment_filter_offers_the_same_amounts_as_the_live_site(): void
    {
        $this->get('/shop')->assertSee('$ 20.000 / mes')->assertSee('$ 50.000 / mes')->assertSee('$ 100.000 / mes');
    }

    public function test_new_stores_do_not_promise_free_shipping(): void
    {
        $user = User::create(['name' => 'Nueva', 'email' => 'nueva@example.com', 'password' => 'secreto123', 'role' => 'seller']);
        $store = Store::create(['user_id' => $user->id, 'name' => 'Nueva tienda', 'slug' => 'nueva-tienda', 'status' => 'active']);
        $this->assertFalse($store->fresh()->free_shipping);
        $this->assertSame(15000, (int) $store->fresh()->shipping_fee);
    }

    public function test_legacy_photos_are_copied_once_and_broken_ones_are_skipped(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Http::fake([
            'marketcity.co/wp-content/uploads/a.png' => Http::response($png, 200, ['Content-Type' => 'image/png']),
            'marketcity.co/wp-content/uploads/roto.png' => Http::response('no', 404),
            'marketcity.co/wp-content/uploads/falso.png' => Http::response('<html>', 200, ['Content-Type' => 'image/png']),
        ]);
        [$a, $b, $c, $d] = Product::take(4)->get()->all();
        $a->update(['image_path' => 'https://marketcity.co/wp-content/uploads/a.png']);
        $b->update(['image_path' => 'https://marketcity.co/wp-content/uploads/a.png']);
        $c->update(['image_path' => 'https://marketcity.co/wp-content/uploads/roto.png']);
        $d->update(['image_path' => 'https://marketcity.co/wp-content/uploads/falso.png']);

        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin)->get('/backoffice/importar')->assertOk()->assertSee('Fotos del sitio anterior');
        $this->actingAs($admin)->post('/backoffice/importar/fotos')->assertRedirect('/backoffice/importar');

        $this->assertStringStartsWith('productos/legado/', $a->fresh()->image_path);
        $this->assertSame($a->fresh()->image_path, $b->fresh()->image_path); // una sola copia
        Storage::disk('public')->assertExists($a->fresh()->image_path);
        $this->assertStringStartsWith('https://', $c->fresh()->image_path);
        $this->assertStringStartsWith('https://', $d->fresh()->image_path);
        $this->assertSame(0, app(MediaCopier::class)->pendingQuery()->whereIn('id', [$c->id, $d->id])->count());
    }
}
