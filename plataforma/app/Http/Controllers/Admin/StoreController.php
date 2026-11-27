<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Store;
use App\Services\Orders\CommissionCalculator;
use App\Services\Payments\PaymentRegistry;
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

    public function edit(Store $store, PaymentRegistry $payments)
    {
        return view('admin.store-edit', ['store' => $store, 'gateways' => $payments->all(), 'general' => app(CommissionCalculator::class)->rule()]);
    }

    /** Condiciones negociadas con el comercio. Vacío = usar los valores generales. */
    public function update(Request $request, Store $store, PaymentRegistry $payments)
    {
        $data = $request->validate([
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'commission_fixed' => 'nullable|integer|min:0|max:1000000',
            'payments_mode' => ['required', Rule::in(['all', 'custom'])],
            'payment_methods' => 'nullable|array',
            'payment_methods.*' => [Rule::in(array_keys($payments->all()))],
        ]);

        if ($data['payments_mode'] === 'custom' && empty($data['payment_methods'])) {
            return back()->withInput()->with('error', 'Elige al menos un medio de pago o deja "todos los activos".');
        }

        $store->update([
            'commission_percent' => $data['commission_percent'] ?? null,
            'commission_fixed' => $data['commission_fixed'] ?? null,
            'payment_methods' => $data['payments_mode'] === 'custom' ? implode(',', $data['payment_methods']) : null,
        ]);

        return redirect()->route('admin.stores')->with('success', "Condiciones de «{$store->name}» guardadas. Aplican a pedidos nuevos.");
    }

    /** Directorio /tiendas (#68): destacar la tienda o sacarla del directorio (p. ej. una tienda de prueba). */
    public function directory(Request $request, Store $store)
    {
        $store->update(['featured' => $request->boolean('featured'), 'directory_hidden' => $request->boolean('directory_hidden')]);

        return back()->with('success', "Directorio: «{$store->name}» ".($store->directory_hidden ? 'queda oculta' : ($store->featured ? 'sale destacada' : 'sale normal')).'.');
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
