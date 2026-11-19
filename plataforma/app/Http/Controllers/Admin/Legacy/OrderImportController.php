<?php

namespace App\Http\Controllers\Admin\Legacy;

use App\Http\Controllers\Controller;
use App\Legacy\WooOrderImporter;
use Illuminate\Http\Request;

/** Recibe clientes y pedidos leídos de la API de WooCommerce (desde el navegador del administrador). */
class OrderImportController extends Controller
{
    public function orders(Request $request, WooOrderImporter $importer)
    {
        $data = $request->validate([
            'customers' => 'present|array|max:5000',
            'customers.*.id' => 'required|integer',
            'customers.*.email' => 'nullable|string|max:190',
            'orders' => 'present|array|max:5000',
            'orders.*.id' => 'required|integer',
            'orders.*.status' => 'required|string|max:40',
            'orders.*.line_items' => 'present|array',
        ]);
        @set_time_limit(300);

        return response()->json($importer->import($request->input('customers', []), $request->input('orders', [])));
    }
}
