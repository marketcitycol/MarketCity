<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
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
        Product::create($data);

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
        $product->update($this->validated($request, $product));

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
        ], ['regular_price.gt' => 'El precio antes debe ser mayor que el precio de venta.']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('productos', 'public');
        }
        unset($data['image']);

        return $data;
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
