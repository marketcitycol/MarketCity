<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\Marketing\MessagingPermissions;
use App\Services\Orders\Cart;
use App\Services\Orders\OrderService;
use App\Services\Payments\BankTransferGateway;
use App\Services\Payments\PaymentRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /** Solo quien hizo el pedido (enlace con token) o su dueño con sesión puede verlo. */
    private function authorizeView(Request $request, Order $order): void
    {
        $ok = hash_equals($order->access_token, (string) $request->query('t'))
            || ($request->user() && ($request->user()->id === $order->user_id || $request->user()->isAdmin()));
        abort_unless($ok, 404);
    }

    /** El pago de prueba solo existe en modo demostración y solo para pedidos hechos con ese medio. */
    private function authorizeSandbox(Order $order): void
    {
        abort_unless(PaymentRegistry::sandboxAllowed() && $order->payment_method === 'sandbox', 404);
    }

    public function sandbox(Request $request, Order $order)
    {
        $this->authorizeView($request, $order);
        $this->authorizeSandbox($order);
        if ($order->payment_status !== 'pending') {
            return redirect()->route('orders.thanks', [$order, 't' => $order->access_token]);
        }

        return view('shop.sandbox', ['order' => $order, 'payment' => $order->payments()->latest('id')->first()]);
    }

    public function sandboxDecide(Request $request, Order $order, OrderService $orders)
    {
        $this->authorizeView($request, $order);
        $this->authorizeSandbox($order);
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

        return view('shop.thanks', [
            'order' => $order->load('sellerOrders.store', 'sellerOrders.items'),
            'transfer' => $order->awaitingTransfer() ? BankTransferGateway::accountDetails() : null,
            'offerAccount' => $this->canOfferAccount($request, $order) ? ! User::findByEmail($order->email) : null,
        ]);
    }

    /** «Crear cuenta con un toque» (#62): solo a quien hizo el pedido como invitado, en este mismo navegador. */
    private function canOfferAccount(Request $request, Order $order): bool
    {
        return ! $request->user() && ! $order->user_id
            && in_array($order->number, (array) $request->session()->get('my_orders', []), true);
    }

    /**
     * Crea la cuenta con los datos del pedido y envía «crea tu contraseña» al correo. No inicia sesión:
     * abrir el enlace del correo demuestra que el correo es de quien compró.
     */
    public function createAccount(Request $request, Order $order)
    {
        $this->authorizeView($request, $order);
        abort_unless($this->canOfferAccount($request, $order), 404);
        $back = redirect()->route('orders.thanks', [$order, 't' => $order->access_token]);
        if (User::findByEmail($order->email)) {
            return $back->with('error', 'Ya hay una cuenta con ese correo. Ingresa para ver tus pedidos.');
        }

        $user = User::create([
            'name' => $order->customerName(), 'email' => mb_strtolower(trim($order->email)), 'password' => Str::random(40),
            'role' => User::ROLE_CUSTOMER, 'phone' => $order->phone,
            'document_type' => $order->document_type, 'document_number' => $order->document_number,
        ]);
        Order::where('email', $order->email)->whereNull('user_id')->whereIn('number', (array) $request->session()->get('my_orders', []))
            ->update(['user_id' => $user->id]);
        AccountController::rememberAddress($user, $order);
        try {
            Password::sendResetLink(['email' => $user->email]);
        } catch (\Throwable $e) {
            report($e);
        }

        return $back->with('success', 'Creamos tu cuenta. Te enviamos un correo a '.$user->email.' para que elijas tu contraseña.');
    }

    /**
     * Plan B del pago rechazado (#87): vuelve a armar el carrito con lo que quedó disponible
     * y lleva al pago para elegir otro medio. El pedido rechazado no se toca.
     */
    public function retry(Request $request, Order $order, Cart $cart)
    {
        $this->authorizeView($request, $order);
        abort_unless($order->payment_status === 'failed', 404);
        $items = $order->items()->whereNotNull('product_id')->get()
            ->groupBy('product_id')->map(fn ($rows) => (int) $rows->sum('quantity'))->all();
        $cart->restore($items);
        if ($cart->isEmpty()) {
            return redirect()->route('shop')->with('error', 'Los productos de ese pedido ya no están disponibles.');
        }

        return redirect()->route('checkout')->with('success', 'Volvimos a armar tu carrito. Elige otro medio de pago.');
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

    /**
     * Rastreo seguro (traslado del snippet #65): número de pedido + correo o celular de la compra.
     * Mismo mensaje si el pedido no existe o si el dato no coincide. Límites: 15 fallos por IP en
     * 15 minutos y 5 fallos con celular por pedido en 24 horas (con el correo se puede seguir).
     */
    public function track(Request $request)
    {
        $data = $request->validate([
            'number' => 'required|string|max:20',
            'contact' => 'required_without:email|nullable|string|max:120',
            'email' => 'nullable|string|max:120',
            'website' => 'nullable|max:0',
        ]);
        $ipKey = 'track-ip:'.sha1((string) $request->ip());
        if (RateLimiter::tooManyAttempts($ipKey, 15)) {
            return back()->withInput()->with('error', 'Hiciste muchos intentos. Espera 15 minutos y vuelve a probar.');
        }

        $digits = preg_replace('/\D+/', '', $data['number']);
        $number = $digits === '' ? '' : 'MC-'.str_pad(ltrim($digits, '0') ?: '0', 6, '0', STR_PAD_LEFT);
        $order = $number ? Order::where('number', $number)->first() : null;
        $contact = trim((string) ($data['contact'] ?? $data['email']));
        $isEmail = str_contains($contact, '@');
        $phoneKey = $order ? 'track-phone:'.$order->id : null;

        $match = false;
        if ($order && $isEmail) {
            $match = hash_equals(mb_strtolower(trim($order->email)), mb_strtolower($contact));
        } elseif ($order && ! RateLimiter::tooManyAttempts($phoneKey, 5)) {
            $given = MessagingPermissions::normalizePhone($contact);
            $match = $given !== null && $given === MessagingPermissions::normalizePhone($order->phone);
            if (! $match) {
                RateLimiter::hit($phoneKey, 24 * 3600);
            }
        }

        if (! $match) {
            RateLimiter::hit($ipKey, 15 * 60);

            return back()->withInput()->with('error', 'No encontramos un pedido con ese número y ese correo o celular.');
        }

        return redirect()->route('orders.show', [$order, 't' => $order->access_token]);
    }
}
