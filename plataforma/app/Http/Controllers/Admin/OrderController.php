<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerOrder;
use App\Services\Orders\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $q = Order::with('sellerOrders.store')->latest('id');
        if ($term = trim((string) $request->input('q'))) {
            $q->where(fn ($w) => $w->where('number', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")
                ->orWhere('document_number', 'like', "%{$term}%")->orWhere('last_name', 'like', "%{$term}%"));
        }
        if ($pay = $request->input('pago')) {
            $q->where('payment_status', $pay);
        }

        return view('admin.orders.index', ['orders' => $q->paginate(25)->withQueryString()]);
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load('sellerOrders.store', 'sellerOrders.items', 'events.user', 'payments', 'merchantEntity')]);
    }

    /** Para transferencia: el operador registra que el dinero llegó, con la referencia del banco. */
    public function markPaid(Request $request, Order $order, OrderService $orders)
    {
        $payment = $order->payments()->where('status', 'pending')->latest('id')->firstOrFail();
        $payment->update(['provider_reference' => $request->input('reference') ?: 'MANUAL-'.now()->format('YmdHis')]);
        $orders->markPaid($order, $payment, ['manual_by' => $request->user()->id]);

        return back()->with('success', 'Pago registrado.');
    }

    public function advance(Request $request, SellerOrder $sellerOrder, OrderService $orders)
    {
        $data = $request->validate([
            'to' => 'required|string',
            'carrier' => ['nullable', Rule::in(array_keys(SellerOrder::CARRIERS))],
            'tracking_number' => 'nullable|string|max:60',
        ]);
        try {
            $orders->advance($sellerOrder, $data['to'], $request->user(), $data['carrier'] ?? null, $data['tracking_number'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Subpedido actualizado.');
    }

    public function cancel(Request $request, SellerOrder $sellerOrder, OrderService $orders)
    {
        $data = $request->validate(['reason' => 'required|string|max:200']);
        $orders->cancel($sellerOrder, $request->user(), $data['reason']);

        return back()->with('success', 'Subpedido cancelado y unidades devueltas al inventario.');
    }
}
