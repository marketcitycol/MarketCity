<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Mail\OrderPlacedMail;
use App\Services\Marketing\AbandonedCarts;
use App\Services\Marketing\MessagingPermissions;
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
            'messages' => 'nullable|array',
            'messages.email' => 'nullable|boolean',
            'messages.whatsapp' => 'nullable|boolean',
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

        $channels = ['email' => (bool) ($data['messages']['email'] ?? false), 'whatsapp' => (bool) ($data['messages']['whatsapp'] ?? false)];
        $data['marketing_optin'] = in_array(true, $channels, true);
        // Para la compra de Meta por la API de conversiones (#85), solo con consentimiento de medición.
        $data['user_agent'] = $request->userAgent();
        $data['analytics_consent'] = $request->cookie('mc_consent') === 'granted';
        $data['fbp'] = $data['analytics_consent'] ? $this->cookieValue($request, '_fbp', 120) : null;
        $data['fbc'] = $data['analytics_consent'] ? $this->cookieValue($request, '_fbc', 255) : null;

        try {
            $order = $orders->place($data, $data['payment_method'], $request->user(), $request->ip());
        } catch (RuntimeException $e) {
            return redirect()->route('cart')->with('error', $e->getMessage());
        }

        session()->push('my_orders', $order->number);
        // Se guarda lo que quedó marcado o desmarcado al pagar (lo desmarcado se retira).
        app(MessagingPermissions::class)->record($order->email, $order->phone, $channels, [
            'source' => 'checkout', 'ip' => $request->ip(), 'user_agent' => $request->userAgent(), 'order_id' => $order->id, 'user_id' => $request->user()?->id, 'session_only' => true,
        ]);
        app(AbandonedCarts::class)->attachOrder($request->session()->getId(), $order);

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

    /**
     * Casillas de mensajes marcadas o desmarcadas antes de pagar (#82 y #83): se registra el permiso
     * y, si hay alguno, se guarda una copia del carrito para recordárselo si no termina la compra.
     */
    public function messages(Request $request, MessagingPermissions $permissions, AbandonedCarts $carts)
    {
        $data = $request->validate([
            'email' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:20',
            'first_name' => 'nullable|string|max:80',
            'messages' => 'nullable|array',
            'messages.email' => 'nullable|boolean',
            'messages.whatsapp' => 'nullable|boolean',
        ]);
        $channels = ['email' => (bool) ($data['messages']['email'] ?? false), 'whatsapp' => (bool) ($data['messages']['whatsapp'] ?? false)];
        $email = MessagingPermissions::normalizeEmail($data['email'] ?? null);
        $phone = MessagingPermissions::normalizePhone($data['phone'] ?? null);
        if (($channels['email'] && ! $email) || ($channels['whatsapp'] && ! $phone)) {
            return response()->json(['ok' => false, 'message' => $channels['email'] && ! $email ? 'Escribe tu correo para recibir mensajes por correo.' : 'Escribe un celular válido para recibir mensajes por WhatsApp.'], 422);
        }
        $permissions->record($email, $phone, $channels, ['source' => 'checkout', 'ip' => $request->ip(), 'user_agent' => $request->userAgent(), 'user_id' => $request->user()?->id, 'session_only' => true]);
        $carts->capture($email, $phone, $data['first_name'] ?? null, $channels, $request->session()->getId(), $request->user()?->id);

        return response()->json(['ok' => true]);
    }

    private function cookieValue(Request $request, string $name, int $max): ?string
    {
        $v = (string) $request->cookie($name, '');

        return preg_match('/^fb\.\d\.\d+\.[\w\-]+$/', $v) ? mb_substr($v, 0, $max) : null;
    }
}
