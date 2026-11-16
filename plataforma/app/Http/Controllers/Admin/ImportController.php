<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\WooCommerceCsvImporter;
use Illuminate\Http\Request;
use RuntimeException;

class ImportController extends Controller
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
}
