<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Revisa que cada URL del sitio anterior (sitemap de WordPress) responda en la plataforma propia:
 * directo (200), con redirección 301 que termina en 200, o retirada a propósito (410).
 *
 * Trabaja por lotes y guarda el avance en storage/app/revision-urls.json, para poder correrlo
 * desde el backoffice (sin consola) o con `php artisan mc:revisar-urls`.
 */
class LegacyUrlChecker
{
    private const FILE = 'revision-urls.json';

    /** Sub-sitemaps que no son contenido público (plantillas del tema, usuarios). */
    private const SKIP = ['wolmart_template', 'users', 'elementor'];

    public function state(): array
    {
        $raw = Storage::disk('local')->get(self::FILE);
        $data = $raw ? json_decode($raw, true) : null;

        return is_array($data) ? $data : ['source' => null, 'base' => null, 'urls' => [], 'results' => [], 'updated_at' => null];
    }

    private function save(array $state): void
    {
        $state['updated_at'] = now()->toIso8601String();
        Storage::disk('local')->put(self::FILE, json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** Lee un sitemap (índice o simple) y devuelve las rutas relativas (/producto/x?…) sin repetir. */
    public function readSitemap(string $url, int $depth = 0): array
    {
        $res = Http::timeout(30)->withHeaders(['User-Agent' => 'MarketCity-revision-urls/1.0'])->get($url);
        if (! $res->successful()) {
            throw new \RuntimeException("No se pudo leer {$url} (HTTP {$res->status()}).");
        }
        preg_match_all('#<loc>\s*(.*?)\s*</loc>#si', $res->body(), $m);
        $paths = [];
        foreach ($m[1] as $loc) {
            $loc = html_entity_decode($loc, ENT_QUOTES | ENT_XML1);
            if (preg_match('#\.xml($|\?)#', $loc)) {
                if ($depth < 2 && ! $this->skipSitemap($loc)) {
                    $paths = array_merge($paths, $this->readSitemap($loc, $depth + 1));
                }

                continue;
            }
            $paths[] = $this->toPath($loc);
        }

        return array_values(array_unique(array_filter($paths)));
    }

    private function skipSitemap(string $loc): bool
    {
        foreach (self::SKIP as $s) {
            if (str_contains($loc, $s)) {
                return true;
            }
        }

        return false;
    }

    public function toPath(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    /** Empieza una revisión nueva con la lista dada. */
    public function start(array $paths, string $base, string $source): array
    {
        $state = ['source' => $source, 'base' => rtrim($base, '/'), 'urls' => array_values(array_unique(array_filter(array_map([$this, 'toPath'], $paths)))), 'results' => []];
        $this->save($state);

        return $state;
    }

    /** @return array{checked:int, remaining:int} */
    public function checkBatch(int $limit = 25): array
    {
        $state = $this->state();
        $pending = array_values(array_filter($state['urls'], fn ($p) => ! isset($state['results'][$p])));
        $checked = 0;
        foreach (array_slice($pending, 0, $limit) as $path) {
            $state['results'][$path] = $this->check($state['base'], $path);
            $checked++;
        }
        $this->save($state);

        return ['checked' => $checked, 'remaining' => count($pending) - $checked];
    }

    /** @return array{result:string, status:int, final:string, final_status:int, hops:int} */
    public function check(string $base, string $path): array
    {
        $host = parse_url($base, PHP_URL_HOST);
        $url = $base.$path;
        $first = null;
        $status = 0;
        for ($hops = 0; $hops <= 4; $hops++) {
            try {
                $res = Http::withOptions(['allow_redirects' => false])->timeout(20)
                    ->withHeaders(['User-Agent' => 'MarketCity-revision-urls/1.0'])->get($url);
                $status = $res->status();
            } catch (\Throwable $e) {
                $status = 0;
            }
            $first ??= $status;
            if ($status < 300 || $status >= 400) {
                break;
            }
            $location = $res->header('Location');
            if (! $location) {
                break;
            }
            $url = str_starts_with($location, 'http') ? $location : $base.'/'.ltrim($location, '/');
            if (parse_url($url, PHP_URL_HOST) !== $host) {
                break; // redirige fuera del sitio
            }
        }

        $result = match (true) {
            $status === 200 && $first === 200 => 'ok',
            $status === 200 => 'redirige',
            $status === 410 => 'retirada',
            $status === 0 => 'error',
            default => 'falta',
        };

        return ['result' => $result, 'status' => (int) $first, 'final' => $this->toPath($url) ?? $path, 'final_status' => $status, 'hops' => $hops];
    }

    /** @return array<string,int> conteo por resultado */
    public function summary(?array $state = null): array
    {
        $state ??= $this->state();
        $out = ['total' => count($state['urls']), 'revisadas' => count($state['results']), 'ok' => 0, 'redirige' => 0, 'retirada' => 0, 'falta' => 0, 'error' => 0];
        foreach ($state['results'] as $r) {
            $out[$r['result']] = ($out[$r['result']] ?? 0) + 1;
        }

        return $out;
    }

    public function csv(): string
    {
        $rows = ["url_anterior,resultado,codigo,destino,codigo_final"];
        foreach ($this->state()['results'] as $path => $r) {
            $rows[] = implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', [$path, $r['result'], $r['status'], $r['final'], $r['final_status']]));
        }

        return implode("\n", $rows)."\n";
    }
}
