@extends('layouts.shop')

@section('content')
<section class="overflow-hidden rounded-2xl bg-gradient-to-br from-brand-700 to-brand-900 px-6 py-10 text-white sm:px-10 sm:py-14">
    <div class="max-w-2xl">
        <p class="text-sm font-semibold tracking-wide text-accent-400 uppercase">Muchos vendedores, un solo pedido</p>
        <h1 class="mt-2 text-3xl font-extrabold sm:text-4xl">Compra en todo Colombia y paga como prefieras</h1>
        <p class="mt-3 text-brand-100">Tecnología, hogar, moda y más, de tiendas verificadas. Paga de contado, contraentrega o en cuotas.</p>
        <div class="mt-6 flex flex-wrap gap-3">
            <a href="{{ route('shop') }}" class="btn-accent">Ver todos los productos</a>
            <a href="{{ route('offers') }}" class="btn border border-white/30 text-white hover:bg-white/10">Ver ofertas</a>
        </div>
    </div>
</section>

<section class="mt-8">
    <h2 class="text-lg font-bold">Categorías</h2>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">
        @foreach (\App\Models\Category::orderBy('position')->get() as $c)
            <a href="{{ route('category', $c) }}" class="card flex items-center gap-3 p-3 text-sm font-medium hover:border-brand-500 hover:text-brand-700">
                <span class="text-2xl" aria-hidden="true">{{ $c->icon }}</span>{{ $c->name }}
            </a>
        @endforeach
    </div>
</section>

@if ($offers->isNotEmpty())
<section class="mt-10">
    <div class="flex items-end justify-between">
        <h2 class="text-lg font-bold">🔥 Ofertas de la semana</h2>
        <a href="{{ route('offers') }}" class="text-sm font-medium text-brand-600 hover:underline">Ver todas</a>
    </div>
    <div class="mt-3 grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($offers as $product) @include('partials.product-card') @endforeach
    </div>
</section>
@endif

<section class="mt-10">
    <h2 class="text-lg font-bold">Destacados</h2>
    <div class="mt-3 grid grid-cols-2 gap-4 md:grid-cols-4">
        @foreach ($featured as $product) @include('partials.product-card') @endforeach
    </div>
</section>

<section class="mt-10">
    <div class="flex items-end justify-between">
        <h2 class="text-lg font-bold">Tiendas en MarketCity</h2>
        <a href="{{ route('stores') }}" class="text-sm font-medium text-brand-600 hover:underline">Ver todas</a>
    </div>
    <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-4">
        @foreach ($stores as $store) @include('partials.store-card') @endforeach
    </div>
</section>

<section class="mt-10 grid gap-4 sm:grid-cols-3">
    <div class="card p-5"><p class="text-2xl">🚚</p><p class="mt-2 font-semibold">Envíos a todo el país</p><p class="text-sm text-slate-500">Cada tienda despacha y te damos la guía para rastrearlo.</p></div>
    <div class="card p-5"><p class="text-2xl">💳</p><p class="mt-2 font-semibold">Paga como quieras</p><p class="text-sm text-slate-500">Tarjeta, PSE, billeteras, crédito y contraentrega (según disponibilidad).</p></div>
    <div class="card p-5"><p class="text-2xl">🛡️</p><p class="mt-2 font-semibold">Compra protegida</p><p class="text-sm text-slate-500">Garantía legal y derecho de retracto según el Estatuto del Consumidor.</p></div>
</section>
@endsection
