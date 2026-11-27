<?php

namespace Tests\Feature;

use App\Legacy\MediaCopier;
use App\Legacy\WooCommerceCsvImporter;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Galería de fotos por producto: Seller Center, ficha, importación y copia de fotos del sitio anterior. */
class ProductGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    public function test_seller_adds_and_removes_gallery_photos_up_to_the_limit(): void
    {
        Storage::fake('public');
        $seller = User::where('email', 'vendedor1@marketcity.test')->first();
        $product = $seller->store->products()->first();
        $base = ['name' => $product->name, 'category_id' => $product->category_id, 'price' => $product->price, 'stock' => $product->stock, 'status' => 'active'];
        $files = array_map(fn ($i) => UploadedFile::fake()->image("f{$i}.jpg"), range(1, 3));

        $this->actingAs($seller)->put("/seller-center/productos/{$product->slug}", $base + ['gallery' => $files])->assertRedirect();
        $this->assertSame(3, $product->images()->count());
        $first = $product->images()->first();
        Storage::disk('public')->assertExists($first->path);

        $this->actingAs($seller)->put("/seller-center/productos/{$product->slug}", $base + ['remove_images' => [$first->id]])->assertRedirect();
        $this->assertSame(2, $product->images()->count());

        $many = array_map(fn ($i) => UploadedFile::fake()->image("m{$i}.jpg"), range(1, 8));
        $this->actingAs($seller)->put("/seller-center/productos/{$product->slug}", $base + ['gallery' => $many])->assertRedirect();
        $this->assertSame(ProductImage::MAX_PER_PRODUCT, $product->images()->count(), 'nunca pasa de 8 adicionales');

        // Otra tienda no puede tocarlas.
        $other = User::where('role', 'seller')->where('id', '!=', $seller->id)->whereHas('store')->first();
        if ($other) {
            $this->actingAs($other)->put("/seller-center/productos/{$product->slug}", $base + ['remove_images' => [$product->images()->value('id')]])->assertNotFound();
        }
    }

    public function test_product_page_shows_gallery_thumbnails_and_json_ld_images(): void
    {
        $p = Product::visible()->first();
        $p->update(['image_path' => '/marca/portada.webp']);
        $p->images()->create(['path' => '/marca/logo.webp', 'position' => 1]);
        $this->get('/producto/'.$p->slug)->assertOk()
            ->assertSee('data-gallery-main', false)
            ->assertSee('Ver foto 2 de 2')
            ->assertSee(json_encode(url('/marca/logo.webp'), JSON_UNESCAPED_SLASHES), false);
    }

    public function test_csv_import_fills_gallery_and_copier_brings_the_photos(): void
    {
        Storage::fake('public');
        $csv = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($csv, "ID,Tipo,SKU,Nombre,Publicado,Inventario,\"Precio normal\",Categorías,Imágenes\n"
            .'901,simple,GAL-1,"Lámpara con galería",1,5,90000,Hogar,"https://marketcity.co/wp-content/uploads/1.png, https://marketcity.co/wp-content/uploads/2.png, https://marketcity.co/wp-content/uploads/3.png"'."\n");
        $store = Store::where('status', 'active')->first();
        app(WooCommerceCsvImporter::class)->import($csv, $store);
        $p = Product::where('sku', 'GAL-1')->firstOrFail();
        $this->assertSame('https://marketcity.co/wp-content/uploads/1.png', $p->image_path);
        $this->assertSame(['https://marketcity.co/wp-content/uploads/2.png', 'https://marketcity.co/wp-content/uploads/3.png'], $p->images()->pluck('path')->all());

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Http::fake(['marketcity.co/wp-content/uploads/*' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);
        Product::where('id', '!=', $p->id)->where('image_path', 'like', 'http%')->update(['image_path' => null]);
        app(MediaCopier::class)->copyBatch(15);
        $this->assertStringStartsWith('productos/legado/', $p->fresh()->image_path);
        $this->assertSame(0, $p->images()->where('path', 'like', 'http%')->count());

        // Reimportar no vuelve a poner las URL viejas sobre las fotos ya copiadas.
        app(WooCommerceCsvImporter::class)->import($csv, $store);
        $this->assertSame(0, $p->images()->where('path', 'like', 'http%')->count());
    }
}
