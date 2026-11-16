<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function home()
    {
        return view('shop.home', [
            'featured' => Product::visible()->with('store')->where('featured', true)->latest('id')->take(8)->get(),
            'offers' => Product::visible()->with('store')->whereColumn('regular_price', '>', 'price')->where('stock', '>', 0)->orderByDesc('sales_count')->take(4)->get(),
            'newest' => Product::visible()->with('store')->latest('id')->take(8)->get(),
            'stores' => Store::where('status', 'active')->withCount(['products' => fn ($q) => $q->where('status', 'active')])->get(),
        ]);
    }

    public function index(Request $request)
    {
        return $this->listing($request, null, 'Tienda');
    }

    public function category(Request $request, Category $category)
    {
        return $this->listing($request, $category, $category->name);
    }

    public function offers(Request $request)
    {
        $request->merge(['ofertas' => 1]);

        return $this->listing($request, null, 'Ofertas');
    }

    private function listing(Request $request, ?Category $category, string $title)
    {
        $q = Product::visible()->with('store');

        if ($category) {
            $q->where('category_id', $category->id);
        } elseif ($request->filled('categoria')) {
            $q->whereHas('category', fn ($c) => $c->where('slug', $request->string('categoria')));
        }
        if ($term = trim((string) $request->input('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%")
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$term}%")));
        }
        if ($request->filled('marca')) {
            $q->whereHas('brand', fn ($b) => $b->where('slug', $request->string('marca')));
        }
        if ($request->filled('min_price')) {
            $q->where('price', '>=', (int) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $q->where('price', '<=', (int) $request->input('max_price'));
        }
        // "Cuota de hasta": filtra por el valor real de la cuota (precio / número de cuotas), no por el precio total.
        if ($request->filled('cuota')) {
            $q->where('price', '<=', (int) $request->input('cuota') * installments());
        }
        if ($request->boolean('ofertas')) {
            $q->whereColumn('regular_price', '>', 'price');
        }
        if ($request->boolean('disponibles')) {
            $q->where('stock', '>', 0);
        }

        match ($request->input('orderby')) {
            'price' => $q->orderBy('price'),
            'price-desc' => $q->orderByDesc('price'),
            'date' => $q->latest('id'),
            default => $q->orderByDesc('featured')->orderByDesc('sales_count'),
        };

        return view('shop.listing', [
            'title' => $title,
            'category' => $category,
            'products' => $q->paginate(12)->withQueryString(),
            'brands' => Brand::whereHas('products', fn ($p) => $p->where('status', 'active'))->orderBy('name')->get(),
        ]);
    }

    public function product(Product $product)
    {
        abort_unless($product->status === 'active' && $product->store->isActive(), 404);

        return view('shop.product', [
            'product' => $product->load('store', 'category', 'brand'),
            'related' => Product::visible()->with('store')->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)->take(4)->get(),
        ]);
    }

    public function stores()
    {
        return view('shop.stores', [
            'stores' => Store::where('status', 'active')->withCount(['products' => fn ($q) => $q->where('status', 'active')])->orderBy('name')->get(),
        ]);
    }

    public function store(Store $store)
    {
        abort_unless($store->isActive(), 404);

        return view('shop.store', [
            'store' => $store,
            'products' => $store->products()->visible()->with('store')->orderByDesc('featured')->paginate(12),
        ]);
    }
}
