@extends('layouts.shop')

@section('content')
@if (\App\Support\Plugins::enabled('hero'))
@php($hero = \App\Support\Plugins::config('hero'))
<section class="relative overflow-hidden rounded-3xl bg-[linear-gradient(120deg,#061e42_0%,#0b2a8a_55%,#1d4ed8_100%)] text-white">
    @if ($hero['image_url'])
        <img src="{{ $hero['image_url'] }}" alt="" class="absolute inset-0 h-full w-full object-cover object-right opacity-40 lg:left-auto lg:w-3/5 lg:opacity-100" fetchpriority="high">
        <div class="absolute inset-0 bg-gradient-to-r from-[#061e42] via-[#061e42]/85 to-transparent lg:via-[#061e42]/60"></div>
    @endif
    <div class="relative max-w-xl px-6 py-10 sm:px-10 sm:py-14">
        @if ($hero['badge'])<p class="inline-flex rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-bold">⚡ {{ $hero['badge'] }}</p>@endif
        <h1 class="mt-4 text-4xl leading-tight font-extrabold sm:text-5xl">{{ $hero['title'] }} <span class="text-brand-100/80">{{ $hero['title_soft'] }}</span><br>{{ $hero['title_end'] }}</h1>
        <p class="mt-4 text-base text-white/85 sm:text-lg">{{ $hero['subtitle'] }}</p>
        <form action="{{ route('shop') }}" method="get" role="search" class="mt-6 flex gap-2 rounded-2xl bg-white p-1.5">
            <label for="q-hero" class="sr-only">¿Qué buscas hoy?</label>
            <input id="q-hero" name="q" placeholder="¿Qué buscas hoy?" class="min-w-0 flex-1 rounded-xl px-3 text-slate-900 focus:outline-none">
            <button class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-brand-700">Buscar</button>
        </form>
        <div class="mt-5 flex flex-wrap gap-3">
            @foreach (\App\Support\Plugins::lines($hero['buttons']) as $i => $b)
                <a href="{{ \App\Support\Plugins::url($b[1] ?? '#') }}" class="{{ $i === 0 ? 'btn-accent' : 'btn border border-white/30 text-white hover:bg-white/10' }}">{{ $b[0] }}</a>
            @endforeach
        </div>
    </div>
</section>
@else
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
@endif

<section class="mt-8">
    <h2 class="text-lg font-bold">Categorías</h2>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">
        @foreach (\App\Models\Category::whereHas('products', fn ($q) => $q->visible())->withCount(['products' => fn ($q) => $q->visible()])->orderByDesc('products_count')->get() as $c)
            <a href="{{ route('category', $c) }}" class="card flex min-w-0 items-center gap-3 p-3 text-sm font-medium hover:border-brand-500 hover:text-brand-700">
                <span class="text-2xl" aria-hidden="true">{{ $c->icon }}</span><span class="min-w-0 [overflow-wrap:anywhere]">{{ $c->name }}</span>
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
