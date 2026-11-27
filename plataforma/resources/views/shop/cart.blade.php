@extends('layouts.shop', ['title' => 'Carrito'])

@section('content')
<h1 class="text-2xl font-bold">Tu carrito</h1>

@if ($groups->isEmpty())
    <div class="card mt-5 p-10 text-center">
        <p class="text-4xl">🛒</p>
        <p class="mt-2 font-semibold">Tu carrito está vacío</p>
        <a href="{{ route('shop') }}" class="btn-primary mt-4">Ir a la tienda</a>
    </div>
@else
<form method="post" action="{{ route('cart.update') }}" class="mt-5 grid gap-6 lg:grid-cols-[1fr_340px]">
    @csrf @method('patch')
    <div class="space-y-4">
        @foreach ($groups as $g)
            @php($store = $g['store'])
            <section class="card">
                <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3 text-sm">
                    <p>Vendido por <a href="{{ route('store', $store) }}" class="font-semibold text-brand-700 hover:underline">{{ $store->name }}</a></p>
                    <p class="text-slate-500">Despacha desde {{ $store->city }}</p>
                </header>
                <ul class="divide-y divide-slate-100">
                    @foreach ($g['lines'] as $line)
                        @php($p = $line['product'])
                        <li class="flex gap-4 p-4">
                            <a href="{{ route('product', $p) }}" class="h-20 w-20 shrink-0 overflow-hidden rounded-lg">@include('partials.product-image', ['product' => $p, 'size' => 'text-3xl'])</a>
                            <div class="flex-1">
                                <a href="{{ route('product', $p) }}" class="font-medium hover:text-brand-600">{{ $p->name }}</a>
                                <p class="text-sm text-slate-500">{{ cop($p->price) }} c/u</p>
                                @if ($line['requested'] > $line['quantity'])<p class="text-xs text-amber-700">{{ $line['quantity'] < $p->stock ? 'Quedan '.$line['quantity'].' unidades a precio de campaña' : 'Solo hay '.$p->stock.' unidades' }}: ajustamos la cantidad.</p>@endif
                                @if ($line['discount'])<p class="text-xs font-semibold text-emerald-700">Precio con correo en 1 unidad (código {{ $line['coupon']->code }}): −{{ cop($line['discount']) }}</p>@endif
                                <div class="mt-2 flex items-center gap-2">
                                    <label for="qty-{{ $p->id }}" class="sr-only">Cantidad</label>
                                    <input id="qty-{{ $p->id }}" type="number" name="qty[{{ $p->id }}]" value="{{ $line['quantity'] }}" min="0" max="{{ $p->stock }}" class="input w-20">
                                    <button name="remove" value="{{ $p->id }}" class="text-xs text-red-600 hover:underline">Quitar</button>
                                </div>
                            </div>
                            <p class="font-semibold">{{ cop($line['line_total']) }}</p>
                        </li>
                    @endforeach
                </ul>
                <footer class="border-t border-slate-100 bg-slate-50 px-4 py-3 text-sm">
                    <p class="mb-2 font-medium">Entrega de esta tienda</p>
                    <label class="flex items-center gap-2"><input type="radio" name="shipping[{{ $store->id }}]" value="envio" @checked($cart->shippingChoice($store->id) === 'envio')> Envío a domicilio — {{ $store->free_shipping ? 'gratis' : cop($store->shipping_fee) }}</label>
                    @if ($store->pickup_enabled)
                        <label class="mt-1 flex items-center gap-2"><input type="radio" name="shipping[{{ $store->id }}]" value="recogida" @checked($cart->shippingChoice($store->id) === 'recogida')> Recoger en la tienda ({{ $store->address }}, {{ $store->city }}) — {{ $store->pickup_fee ? cop($store->pickup_fee) : 'gratis' }}</label>
                    @endif
                </footer>
            </section>
        @endforeach
        <button class="btn-ghost">Actualizar carrito</button>
    </div>
    @push('after-cart')
    <form method="post" action="{{ route('cart.coupon') }}" class="card mt-4 flex flex-wrap items-end gap-2 p-4 text-sm">
        @csrf
        <div class="flex-1"><label for="code" class="label">¿Tienes un código de precio con correo?</label><input id="code" name="code" class="input" maxlength="20" autocomplete="off" placeholder="MCXXXXXXXX"></div>
        <button class="btn-ghost">Aplicar</button>
    </form>
    @endpush

    <aside class="card h-fit p-5">
        @php($shipping = $groups->sum(fn ($g) => \App\Services\Orders\Cart::shippingCost($g['store'], $cart->shippingChoice($g['store']->id))))
        <h2 class="font-semibold">Resumen</h2>
        <dl class="mt-3 space-y-2 text-sm">
            <div class="flex justify-between"><dt>Productos</dt><dd>{{ cop($groups->sum('subtotal')) }}</dd></div>
            <div class="flex justify-between"><dt>Envío ({{ $groups->count() }} {{ $groups->count() === 1 ? 'tienda' : 'tiendas' }})</dt><dd>{{ $shipping ? cop($shipping) : 'Gratis' }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold"><dt>Total</dt><dd>{{ cop($groups->sum('subtotal') + $shipping) }}</dd></div>
        </dl>
        <button name="next" value="checkout" class="btn-primary mt-4 w-full">Continuar con la compra</button>
        <a href="{{ route('shop') }}" class="mt-2 block text-center text-sm text-brand-600 hover:underline">Seguir comprando</a>
    </aside>
</form>
@endif
@stack('after-cart')
@endsection
