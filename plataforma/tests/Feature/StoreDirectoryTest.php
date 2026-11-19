<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Store;
use App\Services\Catalog\StoreDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Directorio de tiendas (/tiendas) y página de cada tienda. */
class StoreDirectoryTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_lists_only_active_stores_with_their_visible_products(): void
    {
        $response = $this->get('/tiendas')->assertOk();

        foreach (Store::where('status', 'active')->get() as $store) {
            $response->assertSee($store->name);
        }
        $response->assertDontSee('Boutique Nueva'); // solicitud pendiente
        $response->assertSee(Store::where('status', 'active')->count().' tiendas');
    }

    public function test_every_store_gets_its_main_category_categories_and_up_to_three_thumbnails(): void
    {
        $stores = app(StoreDirectory::class)->activeStores();

        $this->assertNotEmpty($stores);
        foreach ($stores as $store) {
            $this->assertNotNull($store->mainCategory);
            $this->assertContains($store->mainCategory->id, $store->categoryIds);
            $this->assertLessThanOrEqual(3, $store->thumbs->count());
            $this->assertFalse($store->relationLoaded('products'));
        }
    }

    public function test_search_matches_store_name_or_city_ignoring_case(): void
    {
        $this->get('/tiendas?q=medellín')->assertOk()->assertSee('Ruta Sport')->assertDontSee('TecnoAndes')->assertSee('1 tienda');
        $this->get('/tiendas?q=TECNO')->assertOk()->assertSee('TecnoAndes')->assertDontSee('Ruta Sport');
    }

    public function test_category_filter_keeps_only_stores_selling_in_it_and_counts_them(): void
    {
        $directory = app(StoreDirectory::class);
        $stores = $directory->activeStores();
        $categories = $directory->categoriesFor($stores);
        $category = $categories->first();

        $expected = $stores->filter(fn ($s) => in_array($category->id, $s->categoryIds));
        $this->assertSame($expected->count(), $category->stores_count);

        $filtered = $directory->filter($stores, null, $category, null);
        $this->assertEqualsCanonicalizing($expected->pluck('id')->all(), $filtered->pluck('id')->all());

        $this->get('/tiendas?categoria='.$category->slug)->assertOk()->assertSee('aria-current="true"', false);
    }

    public function test_categories_without_active_stores_are_not_offered(): void
    {
        $empty = Category::create(['name' => 'Sin tiendas', 'slug' => 'sin-tiendas', 'position' => 99]);

        $categories = app(StoreDirectory::class)->categoriesFor(app(StoreDirectory::class)->activeStores());

        $this->assertNull($categories->firstWhere('id', $empty->id));
        $this->assertTrue($categories->every(fn ($c) => $c->stores_count > 0));
    }

    public function test_orders_verified_stores_first_by_default_and_supports_other_orders(): void
    {
        $directory = app(StoreDirectory::class);
        $verified = Store::where('slug', 'casa-caribe')->firstOrFail();
        $verified->update(['verified_at' => now()]);
        $stores = $directory->activeStores();

        $this->assertSame($verified->id, $directory->filter($stores, null, null, null)->first()->id);

        $alphabetic = $directory->filter($stores, null, null, 'alfabetico')->pluck('name')->all();
        $sorted = $alphabetic;
        usort($sorted, fn ($a, $b) => strcmp(mb_strtolower($a), mb_strtolower($b)));
        $this->assertSame($sorted, $alphabetic);

        $this->assertSame($stores->max('id'), $directory->filter($stores, null, null, 'recientes')->first()->id);
        $this->assertSame($stores->max('products_count'), $directory->filter($stores, null, null, 'productos')->first()->products_count);
    }

    public function test_store_page_shows_active_stores_and_hides_the_rest(): void
    {
        $active = Store::where('status', 'active')->firstOrFail();
        $this->get('/tienda/'.$active->slug)->assertOk()->assertSee($active->name);

        $this->get('/tienda/boutique-nueva')->assertNotFound();
    }

    public function test_store_main_category_is_the_one_with_most_visible_products(): void
    {
        $store = Store::where('status', 'active')->firstOrFail();
        $counts = $store->products()->visible()->get()->countBy('category_id');

        $main = app(StoreDirectory::class)->mainCategory($store);

        $this->assertSame($counts->max(), $counts[$main->id]);
    }
}
