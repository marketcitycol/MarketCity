<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function index()
    {
        return view('admin.stores', [
            'stores' => Store::with('user')->withCount('products')->orderByRaw("case status when 'pending' then 0 when 'active' then 1 else 2 end")->orderBy('name')->get(),
        ]);
    }

    public function status(Request $request, Store $store)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'suspended', 'pending'])]]);
        $store->update($data);

        return back()->with('success', "Tienda «{$store->name}»: ".['active' => 'aprobada', 'suspended' => 'suspendida', 'pending' => 'en revisión'][$data['status']].'.');
    }

    public function products(Request $request)
    {
        $q = Product::with('store', 'category')->latest('id');
        if ($term = trim((string) $request->input('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"));
        }
        if ($store = $request->input('tienda')) {
            $q->where('store_id', $store);
        }

        return view('admin.products', ['products' => $q->paginate(30)->withQueryString(), 'stores' => Store::orderBy('name')->get()]);
    }

    public function productStatus(Request $request, Product $product)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'paused'])]]);
        $product->update($data);

        return back()->with('success', 'Producto actualizado.');
    }
}
