<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\Campaigns\CampaignService;
use App\Services\Campaigns\EmailPriceService;
use App\Services\Catalog\StoreDirectory;
use App\Services\Content\BrandAssets;
use App\Services\Orders\Cart;
use App\Services\Payments\PaymentRegistry;
use App\Support\SearchQuery;
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
            'stores' => Store::where('status', 'active')->where('directory_hidden', false)->withCount(['products' => fn ($q) => $q->visible()])->orderByDesc('featured')->orderByDesc('products_count')->get(),
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
        // La búsqueda entiende precios («audífonos menos de 80 mil») y busca cada palabra por separado.
        $search = SearchQuery::parse(mb_substr((string) $request->input('q'), 0, 120));
        foreach (array_slice(array_filter(explode(' ', str_replace(['%', '_'], '', $search['term'])), fn ($w) => mb_strlen($w) >= 2), 0, 6) as $word) {
            $like = '%'.str_replace(['%', '_'], '', $word).'%';
            $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like)
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like))
                ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like)));
        }
        if ($search['min'] !== null) {
            $q->where('price', '>=', $search['min']);
        }
        if ($search['max'] !== null) {
            $q->where('price', '<=', $search['max']);
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
            'search' => $search,
            'category' => $category,
            'products' => $q->paginate(12)->withQueryString(),
            'brands' => Brand::whereHas('products', fn ($p) => $p->where('status', 'active'))->orderBy('name')->get(),
        ]);
    }

    public function product(Request $request, Product $product, CampaignService $campaigns, EmailPriceService $emailPrices, Cart $cart, PaymentRegistry $payments)
    {
        abort_unless($product->status === 'active' && $product->price >= Product::MIN_PRICE && $product->store->isActive(), 404);
        // Enlace del correo de precio con correo (?cupon=CODIGO): se aplica al carrito y lleva allá.
        if ($request->filled('cupon')) {
            $error = $cart->applyCoupon((string) $request->query('cupon'));

            return redirect()->route($error ? 'product' : 'cart', $error ? $product : [])->with($error ? 'error' : 'success', $error ?? 'Aplicamos tu precio con correo a 1 unidad.');
        }
        $campaign = $campaigns->activeFor($product);

        return view('shop.product', [
            'product' => $product->load('store', 'category', 'brand'),
            'campaign' => $campaign,
            'remaining' => $campaign ? $campaigns->remaining($campaign) : null,
            'emailOffer' => $emailPrices->offerFor($product),
            'cutoff' => $campaign ? null : CampaignService::dispatchCutoff(),
            'maxQty' => $cart->maxFor($product),
            'isFavorite' => (bool) $request->user()?->hasFavorite($product),
            // «Lo que debes saber» (#14): solo los medios que esta tienda acepta hoy.
            'paymentLabels' => collect($payments->forStores([$product->store]))->except('sandbox')->map->label()->values(),
            'related' => Product::visible()->with('store')->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)->take(4)->get(),
        ]);
    }

    public function stores(Request $request, StoreDirectory $directory)
    {
        $stores = $directory->activeStores();
        $categories = $directory->categoriesFor($stores);
        $active = $request->filled('categoria') ? $categories->firstWhere('slug', $request->string('categoria')->toString()) : null;

        return view('shop.stores', [
            'stores' => $directory->filter($stores, $request->input('q'), $active, $request->input('orden')),
            'categories' => $categories,
            'activeCategory' => $active,
            'totalStores' => $stores->count(),
            'totalProducts' => $stores->sum('products_count'),
        ]);
    }

    public function store(Store $store, StoreDirectory $directory)
    {
        abort_unless($store->isActive(), 404);

        return view('shop.store', [
            'store' => $store,
            'mainCategory' => $directory->mainCategory($store),
            'products' => $store->products()->visible()->with('store')->orderByDesc('featured')->paginate(12),
        ]);
    }
}
