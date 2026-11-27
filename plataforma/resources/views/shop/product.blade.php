@extends('layouts.shop')
@section('title', $product->name)
@section('description', \Illuminate\Support\Str::limit($product->short_description, 150))
@section('canonical', route('product', $product))
@section('og_type', 'product')
@if ($product->imageUrl())
    @section('og_image', url($product->imageUrl()))
@endif
@push('head')
{{-- Open Graph de producto (#11): precio y disponibilidad para WhatsApp, Facebook y el catálogo de Meta. --}}
<meta property="product:price:amount" content="{{ (int) $product->price }}">
<meta property="product:price:currency" content="COP">
<meta property="product:availability" content="{{ $product->inStock() ? 'in stock' : 'out of stock' }}">
@if ($product->brand)<meta property="product:brand" content="{{ $product->brand->name }}">@endif
<script type="application/ld+json">{!! json_encode([
    '@'.'context' => 'https://schema.org', '@type' => 'Product', 'name' => $product->name, 'sku' => $product->sku,
    'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
    'offers' => ['@type' => 'Offer', 'priceCurrency' => 'COP', 'price' => $product->price, 'url' => route('product', $product),
        'availability' => $product->inStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'seller' => ['@type' => 'Organization', 'name' => $product->store->name]],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
@php($inst = installments())
<nav class="text-xs text-slate-500" aria-label="Ruta">
    <a href="{{ route('home') }}" class="hover:underline">Inicio</a> /
    @if ($product->category)<a href="{{ route('category', $product->category) }}" class="hover:underline">{{ $product->category->name }}</a> /@endif
    {{ $product->name }}
</nav>

<div class="mt-4 grid gap-8 lg:grid-cols-2">
    <div class="card aspect-square overflow-hidden">@include('partials.product-image', ['size' => 'text-9xl'])</div>

    <div>
        <h1 class="text-2xl font-bold sm:text-3xl">{{ $product->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">
            @if ($product->brand)Marca {{ $product->brand->name }} · @endif SKU {{ $product->sku ?? '—' }}
        </p>

        @if ($campaign)
            <p class="mt-3 inline-flex items-center gap-2 rounded-full bg-ink-900 px-3 py-1 text-xs font-bold tracking-wide text-white">{{ $campaign->campaign->badge ?: mb_strtoupper($campaign->campaign->name) }}
                @if ($remaining !== null && $remaining <= 5)<span class="text-accent-400">· quedan {{ $remaining }} a este precio</span>@endif</p>
            {{-- Cuenta regresiva real: termina cuando termina la campaña (#16). --}}
            <p class="mt-2 text-sm font-semibold text-offer-600" role="timer">⏱ La oferta termina en <span class="font-mono" data-countdown="{{ $campaign->campaign->ends_at->getTimestamp() }}">{{ (int) now()->diffInHours($campaign->campaign->ends_at) }} h</span></p>
        @elseif ($cutoff)
            <p class="mt-3 text-sm font-semibold text-emerald-700">🚚 Pide antes de las {{ $cutoff->format('g:i a') }} y, con el pago confirmado, lo despachamos hoy.</p>
        @endif

        <div class="mt-5">
            @if ($product->isOnSale())
                <p class="text-sm text-slate-500"><span class="line-through">{{ cop($product->regular_price) }}</span> <span class="badge bg-red-100 text-red-700">-{{ $product->discountPercent() }}%</span></p>
            @endif
            <p class="text-3xl font-extrabold">{{ cop($product->price) }}</p>
            <p class="mt-1 text-sm text-brand-700">o hasta {{ $inst }} cuotas de <strong>{{ cop(ceil($product->price / $inst)) }}</strong>*</p>
        </div>

        <p class="mt-4 text-sm {{ $product->inStock() ? 'text-emerald-700' : 'text-red-700' }}">
            {{ $product->inStock() ? ($product->stock <= 5 ? "¡Últimas {$product->stock} unidades!" : 'Disponible') : 'Agotado' }}
        </p>

        @if ($emailOffer && $maxQty > 0)
            {{-- Precio con correo (#76): cupón único para 1 unidad, ligado al correo. --}}
            <details class="card mt-4 border-ink-900 p-4 text-sm" @if ($errors->has('email') || $errors->has('consent')) open @endif>
                <summary class="cursor-pointer font-bold">Ver mi precio {{ $emailOffer->campaign->name }}: {{ cop($emailOffer->email_price) }}</summary>
                <form method="post" action="{{ route('product.email-price', $product) }}" class="mt-3 space-y-2">
                    @csrf
                    <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <label for="ep-email" class="label">Tu correo</label>
                    <input id="ep-email" name="email" type="email" class="input" required value="{{ old('email') }}" autocomplete="email">
                    @error('email')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <label class="flex items-start gap-2 text-xs text-slate-600"><input type="checkbox" name="consent" value="1" class="mt-0.5" required> <span>{{ \App\Services\Campaigns\EmailPriceService::CONSENT_TEXT }}</span></label>
                    @error('consent')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <button class="btn-primary w-full">Ver mi precio</button>
                    <p class="text-xs text-slate-500">1 unidad, una sola vez, pagando con este mismo correo, hasta el {{ fecha($emailOffer->campaign->ends_at, 'd/m/Y g:i a') }}.</p>
                </form>
            </details>
        @endif

        @if ($product->inStock() && $maxQty > 0)
        <form method="post" action="{{ route('cart.add') }}" class="mt-4 flex flex-wrap items-end gap-3" data-add-to-cart>
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <div>
                <label for="quantity" class="label">Cantidad</label>
                <input id="quantity" name="quantity" type="number" min="1" max="{{ min(99, $maxQty) }}" value="1" class="input w-24">
            </div>
            <button class="btn-primary">Agregar al carrito</button>
            <button name="buy_now" value="1" class="btn-accent">Comprar ahora</button>
        </form>
        @elseif ($product->inStock())
            <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Se vendieron todas las unidades a precio de campaña. Vuelve en unos minutos para comprarlo a su precio normal.</p>
        @endif

        @auth
            <form method="post" action="{{ route('favorites.toggle', $product) }}" class="mt-3">@csrf
                <button class="text-sm font-medium {{ $isFavorite ? 'text-red-600' : 'text-slate-600' }} hover:underline" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">{{ $isFavorite ? '♥ En tus favoritos (quitar)' : '♡ Guardar en favoritos' }}</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="mt-3 inline-block text-sm text-slate-600 hover:underline">♡ Ingresa para guardarlo en favoritos</a>
        @endauth

        @php($st = $product->store)
        {{-- «Lo que debes saber» (#14): vendedor, envío, despacho, pagos, garantía, retracto y reversión. --}}
        <section class="card mt-6 text-sm" aria-labelledby="debes-saber">
            <a href="{{ route('store', $st) }}" class="flex items-center gap-3 border-b border-slate-100 p-4 hover:bg-slate-50">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-100 font-bold text-brand-700">{{ $st->initials() }}</span>
                <span><span class="block text-xs text-slate-500">Vendido y despachado por</span><span class="font-semibold">{{ $st->name }}</span> · {{ $st->city }}@if ($st->isVerified()) · <span class="text-emerald-700">Tienda verificada</span>@endif</span>
            </a>
            <h2 id="debes-saber" class="px-4 pt-4 font-semibold">Lo que debes saber</h2>
            <ul class="space-y-2 p-4 pt-2 text-slate-600">
                <li>🚚 <strong>Envío:</strong> {{ $st->free_shipping ? 'gratis' : cop($st->shipping_fee) }} a los 32 departamentos y Bogotá.@if ($st->pickup_enabled) También puedes recogerlo en {{ $st->city }}{{ $st->pickup_fee ? ' ('.cop($st->pickup_fee).')' : ' sin costo' }}.@endif</li>
                <li>📦 <strong>Despacho:</strong> sale en máximo {{ $st->dispatch_days }} {{ $st->dispatch_days === 1 ? 'día hábil' : 'días hábiles' }} después de confirmado el pago{{ $st->carriers ? ', con '.$st->carriers : '' }}. Te avisamos por correo con la guía.</li>
                @if ($paymentLabels->isNotEmpty())<li>💳 <strong>Pagas con:</strong> {{ $paymentLabels->join(', ', ' o ') }}. Lo eliges al pagar.</li>@endif
                <li>🛡️ <strong>Garantía legal:</strong> la que indique el fabricante; si no la indica, 1 año para productos nuevos (Ley 1480 de 2011, art. 8).</li>
                <li>↩️ <strong>Retracto:</strong> tienes 5 días hábiles desde que lo recibes para devolverlo sin dar razones (art. 47). <a href="{{ route('pqrs.create', ['tipo' => 'retracto']) }}" class="text-brand-600 hover:underline">Cómo hacerlo</a></li>
                <li>🔁 <strong>Reversión del pago:</strong> si pagaste con tarjeta, PSE u otro medio electrónico y hubo fraude, no llegó o no es lo que pediste (art. 51). <a href="{{ route('pqrs.create', ['tipo' => 'reversion']) }}" class="text-brand-600 hover:underline">Solicitarla</a></li>
            </ul>
        </section>

        @if ($product->description)
            <div class="mt-6">
                <h2 class="font-semibold">Descripción</h2>
                <div class="mt-2 space-y-2 text-sm whitespace-pre-line text-slate-600">{{ $product->description }}</div>
            </div>
        @endif
    </div>
</div>

@if ($related->isNotEmpty())
<section class="mt-12">
    <h2 class="text-lg font-bold">También te puede interesar</h2>
    <div class="mt-3 grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($related as $item) @include('partials.product-card', ['product' => $item]) @endforeach
    </div>
</section>
@endif
@php($recent = json_encode(['name' => $product->name, 'price' => cop($product->price), 'url' => route('product', $product)]))
<div hidden data-recent-save="{{ $recent }}"></div>
@include('partials.track', ['event' => 'view_item', 'params' => ['currency' => 'COP', 'value' => (int) $product->price, 'items' => [\App\Support\Analytics::product($product)]]])
@endsection
