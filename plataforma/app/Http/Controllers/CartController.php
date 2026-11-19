<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
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

        $this->cart->add($product, $data['quantity'] ?? 1);

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
                'item' => \App\Support\Analytics::product($product->loadMissing('store', 'category', 'brand'), (int) ($data['quantity'] ?? 1)),
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
        if ($request->has('shipping')) {
            $this->cart->setShippingChoices(array_map(fn ($v) => $v === 'recogida' ? 'recogida' : 'envio', (array) $request->input('shipping')));
        }

        return redirect()->route($request->input('next') === 'checkout' ? 'checkout' : 'cart');
    }
}
