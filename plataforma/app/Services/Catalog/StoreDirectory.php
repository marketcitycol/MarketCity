<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Store;
use App\Services\Content\BrandAssets;
use Illuminate\Support\Collection;

/**
 * Directorio de tiendas (/tiendas): qué tiendas se muestran, con qué datos y en qué orden.
 *
 * Cada tienda llega con tres datos calculados que usa la tarjeta (partials/store-card):
 * - mainCategory: la categoría con más productos visibles de la tienda;
 * - categoryIds: todas las categorías en las que tiene productos visibles;
 * - thumbs: hasta tres productos con foto, los más vendidos primero.
 */
class StoreDirectory
{
    /** @return Collection<int, Store> */
    public function activeStores(): Collection
    {
        // Las tiendas marcadas «Ocultar del directorio» en Backoffice (p. ej. de prueba) no salen (#68).
        return Store::where('status', 'active')->where('directory_hidden', false)
            ->withCount(['products' => fn ($q) => $q->visible()])
            ->with(['products' => fn ($q) => $q->visible()->with('category')->orderByDesc('sales_count')])
            ->get()
            ->each(function (Store $store) {
                $products = $store->products;
                $store->mainCategory = $products->groupBy('category_id')->sortByDesc->count()->first()?->first()?->category;
                $store->categoryIds = $products->pluck('category_id')->filter()->unique()->values()->all();
                $store->thumbs = $products->filter(fn ($p) => $p->imageUrl())->take(3)->values();
                $store->unsetRelation('products');
            });
    }

    /**
     * Categorías con al menos una de las tiendas dadas, en el orden de marketcity.co y con su número de tiendas.
     *
     * @param  Collection<int, Store>  $stores
     * @return Collection<int, Category>
     */
    public function categoriesFor(Collection $stores): Collection
    {
        return BrandAssets::sortCategories(Category::whereHas('products', fn ($q) => $q->visible())->get())
            ->each(fn (Category $c) => $c->stores_count = $stores->filter(fn ($s) => in_array($c->id, $s->categoryIds))->count())
            ->filter(fn (Category $c) => $c->stores_count > 0)
            ->values();
    }

    /**
     * Aplica la búsqueda (nombre o ciudad), la categoría y el orden elegidos por el visitante.
     * Órdenes: «recientes», «productos», «alfabetico»; cualquier otro valor usa el orden por defecto.
     *
     * @param  Collection<int, Store>  $stores
     * @return Collection<int, Store>
     */
    public function filter(Collection $stores, ?string $term, ?Category $category, ?string $order): Collection
    {
        $term = mb_strtolower(trim((string) $term));
        if ($term !== '') {
            $stores = $stores->filter(fn ($s) => str_contains(mb_strtolower($s->name.' '.$s->city), $term));
        }
        if ($category) {
            $stores = $stores->filter(fn ($s) => in_array($category->id, $s->categoryIds));
        }

        $sorted = match ($order) {
            'recientes' => $stores->sortByDesc('id'),
            'productos' => $stores->sortByDesc('products_count'),
            'alfabetico' => $stores->sortBy(fn ($s) => mb_strtolower($s->name)),
            // Por defecto: primero las destacadas (#68), luego las verificadas y, entre ellas, las de más productos.
            default => $stores->sortByDesc(fn ($s) => [$s->featured ? 1 : 0, $s->isVerified() ? 1 : 0, $s->products_count]),
        };

        return $sorted->values();
    }

    /** Categoría con más productos visibles de una tienda (colorea la portada de su página). */
    public function mainCategory(Store $store): ?Category
    {
        return $store->products()->visible()->with('category')->get()
            ->groupBy('category_id')->sortByDesc->count()->first()?->first()?->category;
    }
}
