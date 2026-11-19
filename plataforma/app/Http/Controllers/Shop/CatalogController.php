<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\Content\BrandAssets;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function home()
    {
        return view('shop.home', [
            'featured' => Product::visible()->with('store')->where('featured', true)->latest('id')->take(8)->get(),
            'offers' => Product::visible()->with('store')->whereColumn('regular_price', '>', 'price')->where('stock', '>', 0)->orderByDesc('sales_count')->take(10)->get(),
            'bestsellers' => Product::visible()->with('store')->where('stock', '>', 0)->orderByDesc('sales_count')->latest('id')->take(10)->get(),
            'newest' => Product::visible()->with('store')->latest('id')->take(10)->get(),
            'stores' => Store::where('status', 'active')->withCount(['products' => fn ($q) => $q->visible()])->orderByDesc('products_count')->get(),
            'categories' => BrandAssets::sortCategories(Category::whereHas('products', fn ($q) => $q->visible())->get()),
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
        abort_unless($product->status === 'active' && $product->price >= Product::MIN_PRICE && $product->store->isActive(), 404);

        return view('shop.product', [
            'product' => $product->load('store', 'category', 'brand'),
            'related' => Product::visible()->with('store')->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)->take(4)->get(),
        ]);
    }

    public function stores(Request $request)
    {
        $stores = Store::where('status', 'active')->withCount(['products' => fn ($q) => $q->visible()])
            ->with(['products' => fn ($q) => $q->visible()->with('category')->orderByDesc('sales_count')])->get()
            ->each(function (Store $store) {
                // Categoría principal (la que más productos tiene) y tres miniaturas, como en marketcity.co.
                $store->mainCategory = $store->products->groupBy('category_id')->sortByDesc->count()->first()?->first()?->category;
                $store->categoryIds = $store->products->pluck('category_id')->filter()->unique()->values()->all();
                $store->thumbs = $store->products->filter(fn ($p) => $p->imageUrl())->take(3)->values();
                $store->unsetRelation('products');
            });

        $categories = BrandAssets::sortCategories(Category::whereHas('products', fn ($q) => $q->visible())->get())
            ->each(fn ($c) => $c->stores_count = $stores->filter(fn ($s) => in_array($c->id, $s->categoryIds))->count())
            ->filter(fn ($c) => $c->stores_count > 0)->values();

        $list = $stores;
        if ($term = trim((string) $request->input('q'))) {
            $list = $list->filter(fn ($s) => str_contains(mb_strtolower($s->name.' '.$s->city), mb_strtolower($term)));
        }
        $active = $request->filled('categoria') ? $categories->firstWhere('slug', $request->string('categoria')->toString()) : null;
        if ($active) {
            $list = $list->filter(fn ($s) => in_array($active->id, $s->categoryIds));
        }
        $list = match ($request->input('orden')) {
            'recientes' => $list->sortByDesc('id'),
            'productos' => $list->sortByDesc('products_count'),
            'alfabetico' => $list->sortBy(fn ($s) => mb_strtolower($s->name)),
            default => $list->sortByDesc(fn ($s) => [$s->isVerified() ? 1 : 0, $s->products_count]),
        };

        return view('shop.stores', [
            'stores' => $list->values(),
            'categories' => $categories,
            'activeCategory' => $active,
            'totalStores' => $stores->count(),
            'totalProducts' => $stores->sum('products_count'),
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
