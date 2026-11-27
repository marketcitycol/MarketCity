<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Campaigns\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    private function own(Request $request, Product $product): void
    {
        abort_unless($product->store_id === $request->user()->store->id, 404);
    }

    public function index(Request $request)
    {
        $q = $request->user()->store->products()->with('category')->latest('id');
        if ($term = $request->input('q')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
        }

        return view('seller.products.index', ['products' => $q->paginate(20)->withQueryString()]);
    }

    public function create()
    {
        return view('seller.products.form', ['product' => new Product(['status' => 'active', 'stock' => 1]), 'categories' => Category::orderBy('position')->get(), 'brands' => Brand::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['store_id'] = $request->user()->store->id;
        $this->syncGallery($request, Product::create($data));

        return redirect()->route('seller.products.index')->with('success', 'Producto creado.');
    }

    public function edit(Request $request, Product $product)
    {
        $this->own($request, $product);

        return view('seller.products.form', ['product' => $product, 'categories' => Category::orderBy('position')->get(), 'brands' => Brand::orderBy('name')->get()]);
    }

    public function update(Request $request, Product $product)
    {
        $this->own($request, $product);
        // Durante una campaña, el precio que escribe el vendedor queda como precio normal (#77).
        $product->update(app(CampaignService::class)->protectBasePrice($product, $this->validated($request, $product)));
        $this->syncGallery($request, $product);

        return redirect()->route('seller.products.index')->with('success', 'Producto actualizado.');
    }

    public function destroy(Request $request, Product $product)
    {
        $this->own($request, $product);
        // No se borra: los pedidos lo referencian. Se pausa.
        $product->update(['status' => 'paused']);

        return back()->with('success', 'Producto pausado: ya no aparece en la tienda.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        // Precios en pesos (#74): «1.200.000» o «$ 1,200,000» se guardan como 1200000, nunca como 1,2.
        foreach (['price', 'regular_price'] as $k) {
            if (is_string($request->input($k)) && preg_match('/^\s*\$?\s*\d{1,3}([.,]\d{3})+\s*$/', $request->input($k))) {
                $request->merge([$k => preg_replace('/\D/', '', $request->input($k))]);
            }
        }
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'sku' => 'nullable|string|max:64',
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',
            'short_description' => 'nullable|string|max:300',
            'description' => 'nullable|string|max:5000',
            'price' => 'required|integer|min:1000|max:100000000',
            'regular_price' => 'nullable|integer|gt:price|max:100000000',
            'stock' => 'required|integer|min:0|max:100000',
            'color' => 'nullable|string|max:40',
            'status' => ['required', Rule::in(['active', 'draft', 'paused'])],
            'image' => 'nullable|image|max:4096',
            'gallery' => 'nullable|array|max:'.ProductImage::MAX_PER_PRODUCT,
            'gallery.*' => 'image|max:4096',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer',
        ], ['regular_price.gt' => 'El precio antes debe ser mayor que el precio de venta.']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('productos', 'public');
        }
        unset($data['image'], $data['gallery'], $data['remove_images']);

        return $data;
    }

    /** Galería: borra las fotos marcadas y agrega las nuevas, hasta 8 adicionales por producto. */
    private function syncGallery(Request $request, Product $product): void
    {
        $remove = array_map('intval', (array) $request->input('remove_images', []));
        if ($remove) {
            $product->images()->whereIn('id', $remove)->delete();
        }
        $room = ProductImage::MAX_PER_PRODUCT - $product->images()->count();
        $next = (int) $product->images()->max('position') + 1;
        foreach (array_slice((array) $request->file('gallery', []), 0, max(0, $room)) as $file) {
            $product->images()->create(['path' => $file->store('productos', 'public'), 'position' => $next++]);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $i = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
