<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Store;

/** Sitemap XML con las mismas rutas públicas del sitio actual (MC-FR-033). */
class SitemapController extends Controller
{
    public function __invoke()
    {
        $urls = collect([route('home'), route('shop'), route('stores'), route('offers')])
            ->map(fn ($u) => ['loc' => $u, 'lastmod' => null]);

        $urls = $urls
            ->concat(Category::all()->map(fn ($c) => ['loc' => route('category', $c), 'lastmod' => $c->updated_at]))
            ->concat(Store::where('status', 'active')->get()->map(fn ($s) => ['loc' => route('store', $s), 'lastmod' => $s->updated_at]))
            ->concat(Page::where('status', 'published')->get()->map(fn ($p) => ['loc' => route('page', $p), 'lastmod' => $p->updated_at]))
            ->concat(Product::visible()->select('id', 'slug', 'updated_at')->get()->map(fn ($p) => ['loc' => route('product', $p), 'lastmod' => $p->updated_at]));

        return response()->view('sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /** Feed de productos (RSS 2.0 con g:) para Google Merchant Center y el catálogo de Meta. */
    public function feed()
    {
        $products = Product::visible()->with('store', 'category', 'brand')->orderBy('id')->get();

        return response()->view('feed', ['products' => $products], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
