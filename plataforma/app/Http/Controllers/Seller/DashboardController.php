<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $store = $request->user()->store;
        $paid = $store->sellerOrders()->whereHas('order', fn ($o) => $o->where('payment_status', 'paid'))->where('status', '!=', 'cancelado');

        return view('seller.dashboard', [
            'store' => $store,
            'stats' => [
                'ventas' => (clone $paid)->sum('subtotal'),
                'pedidos_pendientes' => $store->sellerOrders()->whereIn('status', ['recibido', 'validado'])->where('status', '!=', 'cancelado')
                    ->whereHas('order', fn ($o) => $o->where('payment_status', '!=', 'failed'))->count(),
                'por_liquidar' => (clone $paid)->whereNull('settled_at')->sum('seller_net'),
                'productos' => $store->products()->where('status', 'active')->count(),
                'agotados' => $store->products()->where('stock', 0)->count(),
            ],
            'recent' => $store->sellerOrders()->with('order')->latest('id')->take(5)->get(),
        ]);
    }

    public function settlements(Request $request)
    {
        $store = $request->user()->store;

        return view('seller.settlements', [
            'rows' => $store->sellerOrders()->with('order')
                ->whereHas('order', fn ($o) => $o->where('payment_status', 'paid'))
                ->where('status', '!=', 'cancelado')->latest('id')->paginate(20),
        ]);
    }

    public function editStore(Request $request)
    {
        return view('seller.store', ['store' => $request->user()->store]);
    }

    public function updateStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'description' => 'nullable|string|max:1000',
            'department' => ['required', Rule::in(array_keys(config('colombia.departments')))],
            'city' => 'required|string|max:80',
            'address' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:20',
            'free_shipping' => 'boolean',
            'shipping_fee' => 'nullable|integer|min:0|max:1000000',
            'pickup_enabled' => 'boolean',
            'pickup_fee' => 'nullable|integer|min:0|max:1000000',
        ]);
        $data['free_shipping'] = $request->boolean('free_shipping');
        $data['pickup_enabled'] = $request->boolean('pickup_enabled');
        $data['shipping_fee'] = (int) ($data['shipping_fee'] ?? 0);
        $data['pickup_fee'] = (int) ($data['pickup_fee'] ?? 0);

        $request->user()->store->update($data);

        return back()->with('success', 'Guardamos los datos de tu tienda.');
    }
}
