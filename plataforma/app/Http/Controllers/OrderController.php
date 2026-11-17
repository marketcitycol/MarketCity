<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /** Solo quien hizo el pedido (enlace con token) o su dueño con sesión puede verlo. */
    private function authorizeView(Request $request, Order $order): void
    {
        $ok = hash_equals($order->access_token, (string) $request->query('t'))
            || ($request->user() && ($request->user()->id === $order->user_id || $request->user()->isAdmin()));
        abort_unless($ok, 404);
    }

    public function sandbox(Request $request, Order $order)
    {
        $this->authorizeView($request, $order);
        if ($order->payment_status !== 'pending') {
            return redirect()->route('orders.thanks', [$order, 't' => $order->access_token]);
        }

        return view('shop.sandbox', ['order' => $order, 'payment' => $order->payments()->latest('id')->first()]);
    }

    public function sandboxDecide(Request $request, Order $order, OrderService $orders)
    {
        $this->authorizeView($request, $order);
        $payment = $order->payments()->where('status', 'pending')->latest('id')->firstOrFail();

        if ($request->input('decision') === 'approve') {
            $orders->markPaid($order, $payment, ['sandbox' => 'approved']);
        } else {
            $orders->markFailed($order, $payment, ['sandbox' => 'declined']);
        }

        return redirect()->route('orders.thanks', [$order, 't' => $order->access_token]);
    }

    public function thanks(Request $request, Order $order)
    {
        $this->authorizeView($request, $order);

        return view('shop.thanks', ['order' => $order->load('sellerOrders.store', 'sellerOrders.items')]);
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeView($request, $order);

        return view('shop.order', ['order' => $order->load('sellerOrders.store', 'sellerOrders.items', 'events')]);
    }

    public function trackForm()
    {
        return view('shop.track');
    }

    public function track(Request $request)
    {
        $data = $request->validate(['number' => 'required|string|max:20', 'email' => 'required|email']);
        $order = Order::where('number', strtoupper(trim($data['number'])))->where('email', $data['email'])->first();

        if (! $order) {
            return back()->withInput()->with('error', 'No encontramos un pedido con ese número y ese correo.');
        }

        return redirect()->route('orders.show', [$order, 't' => $order->access_token]);
    }
}
