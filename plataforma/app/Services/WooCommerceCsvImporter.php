<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Importa el catálogo desde la exportación estándar de WooCommerce
 * (Productos → Exportar → CSV). Acepta encabezados en español o en inglés.
 *
 * - Solo datos de catálogo (públicos): no toca clientes ni pedidos.
 * - Es repetible: actualiza por `legacy_id` (el ID de WooCommerce), no duplica.
 * - Productos variables: toma el menor precio y la suma del stock de sus variaciones.
 * - Las fotos se enlazan por URL; descargarlas es un paso posterior.
 */
class WooCommerceCsvImporter
{
    /** Nombre interno => encabezados posibles (minúsculas, sin tildes). */
    private const COLUMNS = [
        'id' => ['id'],
        'type' => ['type', 'tipo'],
        'sku' => ['sku'],
        'name' => ['name', 'nombre'],
        'published' => ['published', 'publicado'],
        'short' => ['short description', 'descripcion corta'],
        'description' => ['description', 'descripcion'],
        'sale' => ['sale price', 'precio rebajado'],
        'regular' => ['regular price', 'precio normal'],
        'categories' => ['categories', 'categorias'],
        'images' => ['images', 'imagenes'],
        'stock' => ['stock', 'inventario'],
        'in_stock' => ['in stock?', 'en inventario?', '¿en inventario?'],
        'parent' => ['parent', 'superior'],
        'brand' => ['brands', 'marcas', 'brand', 'marca'],
        'vendor' => ['vendor', 'vendedor', 'store', 'tienda', 'vendido por'],
        'featured' => ['is featured?', 'esta destacado?', '¿esta destacado?', 'destacado?', 'destacado'],
    ];

    /** @return array{created:int, updated:int, skipped:int, stores:int} */
    public function import(string $path, Store $defaultStore): array
    {
        $rows = $this->read($path);
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'stores' => 0];

        // Agrupa variaciones por su producto padre ("id:123").
        $variations = [];
        foreach ($rows as $r) {
            if (($r['type'] ?? '') === 'variation' && ($r['parent'] ?? '') !== '') {
                $variations[preg_replace('/^id:/', '', $r['parent'])][] = $r;
            }
        }

        DB::transaction(function () use ($rows, $variations, $defaultStore, &$stats) {
            foreach ($rows as $r) {
                $type = $r['type'] ?? 'simple';
                if ($type === 'variation' || trim($r['name'] ?? '') === '' || str_contains($type, 'grouped') || str_contains($type, 'external')) {
                    $stats['skipped']++;
                    continue;
                }

                $regular = $this->money($r['regular'] ?? '');
                $sale = $this->money($r['sale'] ?? '');
                $stock = $this->stock($r);

                if (str_contains($type, 'variable') && isset($variations[$r['id']])) {
                    $prices = [];
                    $stock = 0;
                    foreach ($variations[$r['id']] as $v) {
                        $vp = $this->money($v['sale'] ?? '') ?: $this->money($v['regular'] ?? '');
                        if ($vp) {
                            $prices[] = ['price' => $vp, 'regular' => $this->money($v['regular'] ?? '')];
                        }
                        $stock += $this->stock($v);
                    }
                    usort($prices, fn ($a, $b) => $a['price'] <=> $b['price']);
                    $regular = $prices[0]['regular'] ?? null;
                    $sale = $prices[0]['price'] ?? null;
                }

                $price = $sale ?: $regular;
                if (! $price) {
                    $stats['skipped']++;
                    continue;
                }

                $store = $defaultStore;
                if ($vendor = trim($r['vendor'] ?? '')) {
                    $store = Store::where('name', $vendor)->first();
                    if (! $store) {
                        $store = $this->createStore($vendor, $defaultStore);
                        $stats['stores']++;
                    }
                }

                $data = [
                    'store_id' => $store->id,
                    'category_id' => $this->category($r['categories'] ?? '')?->id,
                    'brand_id' => $this->brand($r['brand'] ?? '')?->id,
                    'name' => trim($r['name']),
                    'sku' => ($r['sku'] ?? '') !== '' ? $r['sku'] : null,
                    'short_description' => $this->text($r['short'] ?? ''),
                    'description' => $this->text($r['description'] ?? ''),
                    'price' => $price,
                    'regular_price' => $regular && $regular > $price ? $regular : null,
                    'stock' => $stock,
                    'image_path' => $this->firstImage($r['images'] ?? ''),
                    // Un precio por debajo del mínimo entra como borrador para revisarlo, nunca a la venta.
                    'status' => in_array(trim($r['published'] ?? '1'), ['1', 'yes', 'si', 'sí'], true) && $price >= Product::MIN_PRICE ? 'active' : 'draft',
                    'featured' => in_array(trim($r['featured'] ?? '0'), ['1', 'yes', 'si', 'sí'], true),
                ];

                $product = ($r['id'] ?? '') !== '' ? Product::where('legacy_id', $r['id'])->first() : null;
                if ($product) {
                    $product->update($data);
                    $stats['updated']++;
                } else {
                    $product = new Product($data + ['slug' => $this->slug($data['name'])]);
                    $product->legacy_id = ($r['id'] ?? '') !== '' ? (int) $r['id'] : null;
                    $product->save();
                    $stats['created']++;
                }
            }
        });

        return $stats;
    }

    /** @return list<array<string,string>> */
    private function read(string $path): array
    {
        $fh = fopen($path, 'r');
        if (! $fh) {
            throw new RuntimeException('No se pudo leer el archivo.');
        }
        $header = fgetcsv($fh, 0, ',', '"', '');
        if (! $header) {
            throw new RuntimeException('El archivo está vacío.');
        }
        $map = [];
        foreach ($header as $i => $h) {
            $key = $this->normalize($h);
            foreach (self::COLUMNS as $name => $aliases) {
                if (in_array($key, array_map(fn ($a) => $this->normalize($a), $aliases), true) && ! in_array($name, $map, true)) {
                    $map[$i] = $name;
                }
            }
        }
        if (! in_array('name', $map, true) || ! in_array('regular', $map, true)) {
            throw new RuntimeException('No parece una exportación de productos de WooCommerce: faltan las columnas Nombre y Precio normal.');
        }

        $rows = [];
        while (($line = fgetcsv($fh, 0, ',', '"', '')) !== false) {
            $row = [];
            foreach ($map as $i => $name) {
                $row[$name] = trim((string) ($line[$i] ?? ''));
            }
            $rows[] = $row;
        }
        fclose($fh);

        return $rows;
    }

    private function normalize(string $s): string
    {
        $s = preg_replace('/^\xEF\xBB\xBF/', '', $s); // BOM

        return trim(mb_strtolower(Str::ascii($s)));
    }

    private function money(string $v): ?int
    {
        $v = preg_replace('/[^0-9.,]/', '', $v);
        if ($v === '') {
            return null;
        }
        // "1.234.567" o "1234567.00" → entero COP
        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $v)) {
            $v = str_replace('.', '', $v);
        }

        return (int) round((float) str_replace(',', '.', $v));
    }

    private function stock(array $r): int
    {
        if (($r['stock'] ?? '') !== '') {
            return max(0, (int) $r['stock']);
        }
        // Sin control de inventario: si está "en inventario" se deja disponible.
        $in = $r['in_stock'] ?? '1';

        return in_array($in, ['1', 'yes', 'si', 'sí', 'instock'], true) || $in === '' ? 999 : 0;
    }

    private function text(string $html): ?string
    {
        $t = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br />', '</p>', '\n'], "\n", $html))));

        return $t === '' ? null : Str::limit(preg_replace("/\n{3,}/", "\n\n", $t), 4900, '');
    }

    private function firstImage(string $images): ?string
    {
        $first = trim(explode(',', $images)[0] ?? '');

        return filter_var($first, FILTER_VALIDATE_URL) && str_starts_with($first, 'https://') ? $first : null;
    }

    private function category(string $value): ?Category
    {
        $first = trim(explode(',', $value)[0] ?? '');
        if ($first === '') {
            return null;
        }
        $name = trim(collect(explode('>', $first))->last());
        $slug = Str::slug($name);

        return Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'position' => 100]);
    }

    private function brand(string $value): ?Brand
    {
        $name = trim(explode(',', $value)[0] ?? '');
        if ($name === '') {
            return null;
        }

        return Brand::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
    }

    private function createStore(string $name, Store $template): Store
    {
        $user = \App\Models\User::create([
            'name' => "Vendedor {$name}",
            'email' => 'importado+'.Str::slug($name).'-'.Str::lower(Str::random(4)).'@marketcity.invalid',
            'password' => Str::random(40),
            'role' => 'seller',
        ]);

        return Store::create([
            'user_id' => $user->id, 'name' => $name, 'slug' => $this->uniqueStoreSlug($name),
            'status' => 'active', 'city' => $template->city, 'department' => $template->department,
        ]);
    }

    private function uniqueStoreSlug(string $name): string
    {
        $slug = Str::slug($name) ?: 'tienda';
        $base = $slug;
        $i = 2;
        while (Store::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function slug(string $name): string
    {
        $slug = Str::slug($name) ?: 'producto';
        $base = $slug;
        $i = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
