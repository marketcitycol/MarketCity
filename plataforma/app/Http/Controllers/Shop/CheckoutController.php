<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\OrderPlacedMail;
use App\Services\Orders\Cart;
use App\Services\Orders\OrderService;
use App\Services\Payments\PaymentRegistry;
use App\Support\Mailer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(private Cart $cart, private PaymentRegistry $payments) {}

    public function show(Request $request)
    {
        if ($this->cart->isEmpty()) {
            return redirect()->route('cart');
        }

        return view('shop.checkout', [
            'groups' => $this->cart->groups(),
            'cart' => $this->cart,
            'gateways' => $this->payments->forStores($this->cart->groups()->pluck('store')),
            'user' => $request->user(),
        ]);
    }

    public function place(Request $request, OrderService $orders)
    {
        $allowed = array_keys($this->payments->forStores($this->cart->groups()->pluck('store')));
        $data = $request->validate([
            'first_name' => 'required|string|max:80',
            'last_name' => 'required|string|max:80',
            'document_type' => ['required', Rule::in(array_keys(config('colombia.document_types')))],
            'document_number' => 'required|string|regex:/^[0-9A-Za-z\-]{5,20}$/',
            'email' => 'required|email|max:120',
            'phone' => 'required|string|regex:/^[0-9 +]{7,15}$/',
            'department' => ['required', Rule::in(array_keys(config('colombia.departments')))],
            'city' => 'required|string|max:80',
            'address' => 'required|string|max:150',
            'address_2' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
            'payment_method' => ['required', Rule::in($allowed)],
            'terms' => 'accepted',
            'marketing_optin' => 'nullable|boolean',
            'shipping' => 'nullable|array',
        ], [
            'terms.accepted' => 'Debes aceptar los términos y la política de tratamiento de datos.',
            'document_number.regex' => 'Revisa el número de documento.',
            'phone.regex' => 'Revisa el número de celular.',
            'payment_method.in' => 'Ese medio de pago no está disponible para las tiendas de tu carrito.',
        ]);

        if (isset($data['shipping'])) {
            $this->cart->setShippingChoices(array_map(fn ($v) => $v === 'recogida' ? 'recogida' : 'envio', $data['shipping']));
        }

        try {
            $order = $orders->place($data, $data['payment_method'], $request->user(), $request->ip());
        } catch (RuntimeException $e) {
            return redirect()->route('cart')->with('error', $e->getMessage());
        }

        session()->push('my_orders', $order->number);

        $payment = $orders->createPayment($order);
        try {
            $next = $this->payments->get($order->payment_method)->start($order, $payment);
        } catch (RuntimeException $e) {
            // La pasarela no respondió: el pedido se cancela y las unidades vuelven, para no dejar stock tomado.
            $orders->markFailed($order, $payment, ['error' => $e->getMessage()], 'La pasarela no respondió');

            return redirect()->route('cart')->with('error', $e->getMessage());
        }
        $this->cart->clear();
        Mailer::send($order->email, new OrderPlacedMail($order->fresh()));

        return redirect()->to($next);
    }
}
