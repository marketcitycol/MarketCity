<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageController extends Controller
{
    public function index()
    {
        return view('admin.pages.index', ['pages' => Page::orderBy('title')->get()]);
    }

    public function create()
    {
        return view('admin.pages.edit', ['page' => new Page(['status' => 'published'])]);
    }

    public function store(Request $request)
    {
        $page = Page::create($this->validated($request));

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Página creada.');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', ['page' => $page]);
    }

    public function update(Request $request, Page $page)
    {
        $page->update($this->validated($request, $page));

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Página guardada.');
    }

    /**
     * Recibe las páginas leídas de WordPress (REST pública) como JSON:
     * {"pages":[{"legacy_id":6103,"slug":"politica-de-privacidad","title":"…","html":"…"}]}.
     * Es repetible: actualiza por legacy_id.
     */
    public function import(Request $request)
    {
        $data = $request->validate([
            'pages' => 'required|array|max:100',
            'pages.*.legacy_id' => 'required|integer',
            'pages.*.slug' => 'required|string|max:150',
            'pages.*.title' => 'required|string|max:200',
            'pages.*.html' => 'nullable|string|max:500000',
        ]);
        $stats = ['created' => 0, 'updated' => 0];

        DB::transaction(function () use ($data, &$stats) {
            foreach ($data['pages'] as $p) {
                $html = self::relativeLinks((string) ($p['html'] ?? ''));
                $attrs = [
                    'title' => Str::limit(trim(html_entity_decode(strip_tags($p['title']))), 190, ''),
                    'body' => HtmlSanitizer::clean($html),
                ];
                $page = Page::where('legacy_id', $p['legacy_id'])->first();
                if ($page) {
                    $page->update($attrs);
                    $stats['updated']++;
                } else {
                    $slug = $this->uniqueSlug(Str::slug($p['slug']) ?: 'pagina');
                    Page::create($attrs + ['slug' => $slug, 'legacy_id' => $p['legacy_id'], 'status' => 'published']);
                    $stats['created']++;
                }
            }
        });

        return response()->json($stats);
    }

    /** Enlaces absolutos a marketcity.co → rutas relativas de v1. */
    public static function relativeLinks(string $html): string
    {
        $map = ['/cart-2/' => '/carrito', '/cart/' => '/carrito', '/my-account-2/pedidos/' => '/mi-cuenta', '/my-account/' => '/mi-cuenta', '/vendor-membership/' => '/vender', '/?page_id=6103' => '/politica-de-privacidad'];

        return preg_replace_callback('#href=(["\'])https?://(?:www\.)?marketcity\.co(/[^"\']*)?\1#i', function ($m) use ($map) {
            $path = $m[2] ?? '/';
            if (str_starts_with($path, '/wp-content/')) {
                return 'href='.$m[1].'https://marketcity.co'.$path.$m[1];
            }
            $path = $map[$path] ?? $path;

            return 'href='.$m[1].($path === '' ? '/' : $path).$m[1];
        }, $html);
    }

    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:190',
            'slug' => ['required', 'string', 'max:150', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('pages', 'slug')->ignore($page?->id), Rule::notIn($this->reserved())],
            'body' => 'nullable|string|max:500000',
            'status' => ['required', Rule::in(['published', 'draft'])],
        ], ['slug.not_in' => 'Esa dirección ya la usa la tienda. Elige otra.', 'slug.regex' => 'Usa solo minúsculas, números y guiones.']);
        $data['body'] = HtmlSanitizer::clean($data['body'] ?? '');

        return $data;
    }

    /** Primeros segmentos que ya usa la aplicación (no pueden ser páginas). */
    private function reserved(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->map(fn ($r) => explode('/', trim($r->uri(), '/'))[0])
            ->filter(fn ($s) => $s !== '' && ! str_starts_with($s, '{'))
            ->unique()->values()->all();
    }

    private function uniqueSlug(string $slug): string
    {
        $reserved = $this->reserved();
        $base = $slug;
        $i = 2;
        while (in_array($slug, $reserved, true) || Page::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
