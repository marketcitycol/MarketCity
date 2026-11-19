<?php

namespace App\Http\Controllers\Admin\Legacy;

use App\Http\Controllers\Controller;
use App\Legacy\MediaCopier;
use App\Legacy\WooCommerceCsvImporter;
use App\Models\Store;
use Illuminate\Http\Request;
use RuntimeException;

class CatalogImportController extends Controller
{
    public function show()
    {
        return view('admin.import', ['stores' => Store::orderBy('name')->get()]);
    }

    public function store(Request $request, WooCommerceCsvImporter $importer)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:20480',
            'store_id' => 'required|exists:stores,id',
        ]);
        @set_time_limit(300);

        try {
            $stats = $importer->import($request->file('file')->getRealPath(), Store::findOrFail($data['store_id']));
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Importación lista: {$stats['created']} productos nuevos, {$stats['updated']} actualizados, {$stats['skipped']} omitidos (variaciones, sin precio o sin nombre), {$stats['stores']} tiendas nuevas.");
    }

    /** Copia un lote de fotos del sitio anterior; la página sigue sola hasta terminar. */
    public function media(Request $request, MediaCopier $copier)
    {
        if ($request->boolean('reintentar')) {
            $copier->retryFailed();
        }
        $r = $copier->copyBatch(15);

        return redirect()->route('admin.import')->with('media', $r)->with('success', "Fotos copiadas en este lote: {$r['copied']}. Faltan {$r['remaining']}.");
    }
}
