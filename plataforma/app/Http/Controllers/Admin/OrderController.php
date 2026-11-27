<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderNoticeMail;
use App\Models\Order;
use App\Models\SellerOrder;
use App\Services\Orders\OrderService;
use App\Services\Orders\RefundService;
use App\Support\Mailer;
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
        return view('admin.orders.show', ['order' => $order->load('sellerOrders.store', 'sellerOrders.items', 'events.user', 'payments', 'merchantEntity', 'refunds.user', 'refunds.sellerOrder.store')]);
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

    /** Nota al cliente (en WooCommerce: «Nota para el cliente»): queda en el historial y le llega por correo. */
    public function note(Request $request, Order $order)
    {
        $data = $request->validate(['note' => 'required|string|max:1000']);
        $order->log('nota', 'Nota al cliente: '.$data['note'], null, $request->user());
        $sent = Mailer::send($order->email, new OrderNoticeMail($order, 'note', $data['note']));

        return back()->with($sent ? 'success' : 'error', $sent ? 'Nota enviada al cliente.' : 'La nota quedó en el historial, pero el correo no salió. Revisa el correo del servidor.');
    }

    /** Reembolso total o parcial, de un subpedido o del pedido, con su referencia en la pasarela o el banco. */
    public function refund(Request $request, Order $order, RefundService $refunds)
    {
        $data = $request->validate([
            'seller_order_id' => ['nullable', 'integer', Rule::exists('seller_orders', 'id')->where('order_id', $order->id)],
            'amount' => 'required|integer|min:1',
            'reason' => 'required|string|max:300',
            'charged_to' => ['required', Rule::in(['tienda', 'marketcity'])],
            'restock' => 'nullable|boolean',
            'reference' => 'nullable|string|max:120',
        ]);
        $so = isset($data['seller_order_id']) ? SellerOrder::find($data['seller_order_id']) : null;
        try {
            $refund = $refunds->refund($order, $so, (int) $data['amount'], $data['reason'], $data['charged_to'], (bool) ($data['restock'] ?? false), $data['reference'] ?? null, $request->user());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Reembolso de '.cop($refund->amount).' registrado y avisado al cliente.'
            .($refund->pending_deduction ? ' El subpedido ya estaba liquidado: descuenta '.cop($refund->seller_debit).' en la próxima liquidación.' : ''));
    }
}
