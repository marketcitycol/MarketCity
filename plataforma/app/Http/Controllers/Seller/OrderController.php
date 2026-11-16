<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\SellerOrder;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class OrderController extends Controller
{
    private function own(Request $request, SellerOrder $so): void
    {
        abort_unless($so->store_id === $request->user()->store->id, 404);
    }

    public function index(Request $request)
    {
        $q = $request->user()->store->sellerOrders()->with('order')->latest('id');
        if ($status = $request->input('estado')) {
            $q->where('status', $status);
        }

        return view('seller.orders.index', ['orders' => $q->paginate(20)->withQueryString()]);
    }

    public function show(Request $request, SellerOrder $sellerOrder)
    {
        $this->own($request, $sellerOrder);

        return view('seller.orders.show', ['so' => $sellerOrder->load('order', 'items', 'store')]);
    }

    public function advance(Request $request, SellerOrder $sellerOrder, OrderService $orders)
    {
        $this->own($request, $sellerOrder);
        $data = $request->validate([
            'to' => 'required|string',
            'carrier' => ['nullable', Rule::in(array_keys(SellerOrder::CARRIERS))],
            'tracking_number' => 'nullable|string|max:60',
        ]);

        if ($sellerOrder->order->payment_status === 'failed') {
            return back()->with('error', 'El pago de este pedido fue rechazado.');
        }
        if ($sellerOrder->order->payment_status === 'pending' && $sellerOrder->order->payment_method !== 'contraentrega') {
            return back()->with('error', 'Espera a que el pago se confirme antes de avanzar el pedido.');
        }

        try {
            $orders->advance($sellerOrder, $data['to'], $request->user(), $data['carrier'] ?? null, $data['tracking_number'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pedido actualizado a «'.$sellerOrder->statusLabel().'».');
    }
}
