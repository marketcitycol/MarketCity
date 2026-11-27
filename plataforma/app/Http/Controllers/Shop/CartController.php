<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\AbandonedCart;
use App\Models\Product;
use App\Services\Marketing\AbandonedCarts;
use App\Services\Orders\Cart;
use App\Support\Analytics;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private Cart $cart) {}

    public function index()
    {
        return view('shop.cart', ['groups' => $this->cart->groups(), 'cart' => $this->cart]);
    }

    public function add(Request $request)
    {
        $data = $request->validate(['product_id' => 'required|integer', 'quantity' => 'nullable|integer|min:1|max:99']);
        $product = Product::visible()->findOrFail($data['product_id']);

        if (! $product->inStock()) {
            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => 'Este producto está agotado.'], 422)
                : back()->with('error', 'Este producto está agotado.');
        }

        if ($this->cart->maxFor($product) < 1) {
            $msg = 'Este producto ya vendió todas sus unidades a precio de campaña. Vuelve en unos minutos.';

            return $request->expectsJson() ? response()->json(['ok' => false, 'message' => $msg], 422) : back()->with('error', $msg);
        }

        $this->cart->add($product, $data['quantity'] ?? 1);
        app(AbandonedCarts::class)->refresh($request->session()->getId());

        if ($request->input('buy_now')) {
            return redirect()->route('checkout');
        }

        if ($request->expectsJson()) {
            // Para el panel de "Agregado al carrito": mismos totales que la página del carrito.
            return response()->json([
                'ok' => true,
                'message' => "Agregaste «{$product->name}» al carrito.",
                'count' => $this->cart->count(),
                'subtotal' => cop($this->cart->groups()->sum('subtotal')),
                'product' => ['name' => $product->name, 'price' => cop($product->price), 'image' => $product->imageUrl()],
                'item' => Analytics::product($product->loadMissing('store', 'category', 'brand'), (int) ($data['quantity'] ?? 1)),
                'value' => (int) $product->price * (int) ($data['quantity'] ?? 1),
            ]);
        }

        return back()->with('success', "Agregaste «{$product->name}» al carrito.");
    }

    public function update(Request $request)
    {
        foreach ((array) $request->input('qty', []) as $productId => $qty) {
            $this->cart->update((int) $productId, (int) $qty);
        }
        if ($request->filled('remove')) {
            $this->cart->update((int) $request->input('remove'), 0);
        }
        app(AbandonedCarts::class)->refresh($request->session()->getId());
        if ($request->has('shipping')) {
            $this->cart->setShippingChoices(array_map(fn ($v) => $v === 'recogida' ? 'recogida' : 'envio', (array) $request->input('shipping')));
        }

        return redirect()->route($request->input('next') === 'checkout' ? 'checkout' : 'cart');
    }

    /** Código de precio con correo escrito en el carrito o traído desde el correo (#76). */
    public function coupon(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:20']);
        $error = $this->cart->applyCoupon($data['code']);

        return $error ? back()->with('error', $error) : redirect()->route('cart')->with('success', 'Aplicamos tu precio con correo a 1 unidad. Paga con el mismo correo con el que lo pediste.');
    }

    /** Enlace de un recordatorio (#83): rehace el carrito en cualquier dispositivo y lleva al pago. */
    public function recover(Request $request, string $token)
    {
        $row = AbandonedCart::where('token', $token)->first();
        if (! $row || in_array($row->status, ['stopped', 'recovered'], true)) {
            return redirect()->route('cart')->with('error', 'Este enlace ya no está disponible.');
        }
        $order = $row->order_id ? $row->order()->first() : null;
        if ($order && $order->payment_status === 'pending') {
            return redirect()->route('orders.show', [$order, 't' => $order->access_token]);
        }
        $this->cart->restore((array) $row->items);
        if (! $request->user()?->isAdmin()) {
            $row->update(['clicked_at' => $row->clicked_at ?? now()]);
        }

        return redirect()->route($this->cart->isEmpty() ? 'cart' : 'checkout');
    }
}
