<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CutoverReadiness;
use App\Services\LegacyUrlChecker;
use Illuminate\Http\Request;

/** Backoffice → Cambio de dominio: lista sí/no y revisión de las URL de WordPress. */
class CutoverController extends Controller
{
    public function index(CutoverReadiness $readiness, LegacyUrlChecker $urls)
    {
        $state = $urls->state();
        $problems = collect($state['results'])->filter(fn ($r) => in_array($r['result'], ['falta', 'error'], true));

        return view('admin.cutover', [
            'checks' => $readiness->checks(),
            'ready' => $readiness->ready(),
            'summary' => $urls->summary($state),
            'state' => $state,
            'problems' => $problems->take(200),
        ]);
    }

    public function start(Request $request, LegacyUrlChecker $urls)
    {
        $data = $request->validate([
            'sitemap' => 'nullable|url|starts_with:https://|max:300',
            'list' => 'nullable|string|max:200000',
        ]);
        $base = rtrim($request->getSchemeAndHttpHost(), '/');

        if (filled($data['list'] ?? null)) {
            $paths = preg_split('/\s+/', trim($data['list']));
            $source = 'lista pegada';
        } else {
            try {
                $paths = $urls->readSitemap($data['sitemap'] ?: 'https://marketcity.co/wp-sitemap.xml');
            } catch (\Throwable $e) {
                return back()->with('error', 'No se pudo leer el sitemap: '.$e->getMessage().' Pega la lista de URL en su lugar.');
            }
            $source = $data['sitemap'] ?: 'https://marketcity.co/wp-sitemap.xml';
        }
        $state = $urls->start($paths, $base, $source);

        return redirect()->route('admin.cutover')->with('success', count($state['urls']).' URL listas para revisar.')->with('continue', true);
    }

    public function batch(LegacyUrlChecker $urls)
    {
        $r = $urls->checkBatch(20);

        return redirect()->route('admin.cutover')
            ->with('success', "Revisadas {$r['checked']}; faltan {$r['remaining']}.")
            ->with('continue', $r['remaining'] > 0 && $r['checked'] > 0);
    }

    public function csv(LegacyUrlChecker $urls)
    {
        return response($urls->csv(), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="revision-urls.csv"',
        ]);
    }
}
