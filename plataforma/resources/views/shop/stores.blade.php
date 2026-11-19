@extends('layouts.shop', ['title' => 'Tiendas'])

@section('content')
@php($B = \App\Support\Brand::class)
{{-- Portada de /tiendas, igual a la de marketcity.co. --}}
<section class="overflow-hidden rounded-[22px] px-[18px] pt-[22px] pb-5 text-white md:px-10 md:py-10 [background:radial-gradient(120%_90%_at_100%_0,rgba(10,75,255,.55),transparent_60%),radial-gradient(80%_70%_at_0_100%,rgba(200,161,90,.35),transparent_60%),#0b2a6f]">
    <nav class="text-xs text-white/70" aria-label="Ruta"><a href="{{ route('home') }}" class="hover:text-white">Inicio</a> <span aria-hidden="true">/</span> <span class="text-white">Tiendas</span></nav>
    <p class="mt-3 inline-flex items-center gap-1.5 text-[13px] font-bold tracking-wide text-[#f3d9a4] uppercase">{!! icon('verificado', 'h-4 w-4') !!} Explora todas las tiendas</p>
    <h1 class="mt-1.5 text-[26px] leading-tight font-extrabold md:text-4xl">Encuentra tiendas para todo lo que necesitas</h1>
    <p class="mt-2 max-w-2xl text-[15px] text-white/88">Vendedores colombianos, pago seguro dentro de MarketCity y envíos a todo el país. Compra local, con confianza.</p>
    <form action="{{ route('stores') }}" method="get" role="search" class="mt-4 flex h-14 max-w-xl items-center rounded-full bg-white p-1 pl-4">
        <label for="q-stores" class="sr-only">Buscar una tienda</label>
        <span class="text-[#8a90a6]">{!! icon('tienda', 'h-5 w-5') !!}</span>
        <input id="q-stores" name="q" value="{{ request('q') }}" placeholder="Buscar una tienda" class="h-full min-w-0 flex-1 bg-transparent px-2 text-[15px] text-slate-900 focus:outline-none">
        @if ($activeCategory)<input type="hidden" name="categoria" value="{{ $activeCategory->slug }}">@endif
        <button class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-brand-600 text-white hover:bg-brand-700" aria-label="Buscar">{!! icon('buscar', 'h-5 w-5') !!}</button>
    </form>
    <ul class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-white/90">
        <li class="flex items-center gap-1.5">{!! icon('tienda', 'h-4 w-4 text-[#f3d9a4]') !!}<b>{{ $totalStores }}</b> tiendas</li>
        <li class="flex items-center gap-1.5">{!! icon('bolsa', 'h-4 w-4 text-[#f3d9a4]') !!}<b>{{ number_format($totalProducts, 0, ',', '.') }}</b> productos</li>
        <li class="flex items-center gap-1.5">{!! icon('envio', 'h-4 w-4 text-[#f3d9a4]') !!}Envíos a toda Colombia</li>
    </ul>
    <a href="{{ route('sell') }}" class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-white underline underline-offset-4 hover:text-[#f3d9a4]">Quiero vender en MarketCity →</a>
</section>

{{-- Comprar por categoría --}}
@if ($categories->isNotEmpty())
<section class="mt-7">
    @include('partials.section-head', ['title' => 'Comprar por categoría', 'sub' => 'Toca una categoría para ver las tiendas que la venden.'])
    <div class="-mx-3.5 mt-3 flex gap-3 overflow-x-auto px-3.5 pb-1 [scrollbar-width:none] md:mx-0 md:flex-wrap md:px-0 [&::-webkit-scrollbar]:hidden">
        @foreach ($categories as $c)
            @php($on = $activeCategory?->id === $c->id)
            <a href="{{ route('stores', array_filter(['categoria' => $on ? null : $c->slug, 'q' => request('q')])) }}" class="flex w-[78px] shrink-0 flex-col items-center gap-1 text-center" @if ($on) aria-current="true" @endif>
                <span class="grid h-16 w-16 place-items-center rounded-full {{ $on ? 'bg-brand-600 text-white' : 'bg-[#eef1f5] text-brand-600' }}">{!! icon(\App\Support\Icons::forCategory($c->slug), 'h-7 w-7') !!}</span>
                <span class="text-[13px] leading-tight font-semibold text-[#0f172a]">{{ $c->name }}</span>
                <span class="text-[11px] text-[#6b7385]">{{ $c->stores_count }} {{ $c->stores_count === 1 ? 'tienda' : 'tiendas' }}</span>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- Todas las tiendas --}}
<section class="mt-7">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-[#0f172a] md:text-2xl">{{ $activeCategory ? 'Tiendas de '.$activeCategory->name : 'Todas las tiendas' }}</h2>
            <p class="text-[13px] text-[#6b7385]">{{ $stores->count() }} {{ $stores->count() === 1 ? 'tienda' : 'tiendas' }}@if (request('q')) para «{{ request('q') }}»@endif</p>
        </div>
        <form action="{{ route('stores') }}" method="get" class="flex items-center gap-2">
            @foreach (['q', 'categoria'] as $k)@if (request($k))<input type="hidden" name="{{ $k }}" value="{{ request($k) }}">@endif @endforeach
            <label for="orden" class="text-[13px] text-[#4a5068]">Ordenar por</label>
            <select id="orden" name="orden" onchange="this.form.submit()" class="h-11 rounded-xl border border-[#e3e6ec] bg-white px-3 text-sm font-semibold text-[#0f172a]">
                @foreach (['' => 'Recomendadas', 'recientes' => 'Más recientes', 'productos' => 'Más productos', 'alfabetico' => 'Alfabético'] as $v => $l)
                    <option value="{{ $v }}" @selected(request('orden', '') === $v)>{{ $l }}</option>
                @endforeach
            </select>
            <noscript><button class="h-11 rounded-xl border border-brand-600 px-3 text-sm font-bold text-brand-600">Aplicar</button></noscript>
        </form>
    </div>

    @if ($stores->isEmpty())
        <div class="mt-4 rounded-2xl border border-[#e3e6ec] bg-white p-6 text-center text-sm text-[#4a5068]">
            No encontramos tiendas con ese filtro. <a href="{{ route('stores') }}" class="font-semibold text-brand-600 hover:underline">Ver todas</a>
        </div>
    @else
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($stores as $store) @include('partials.store-card') @endforeach
        </div>
    @endif
</section>

<section class="mt-8 flex flex-wrap items-center justify-between gap-4 rounded-[20px] bg-brand-900 p-6 text-white">
    <div>
        <p class="text-xs font-bold tracking-wider text-[#c8a15a] uppercase">Para vendedores</p>
        <p class="mt-1 text-xl font-extrabold">¿Tienes una tienda?</p>
        <p class="text-sm text-white/80">Vende en MarketCity y llega a todo el país.</p>
    </div>
    <a href="{{ route('sell') }}" class="inline-flex h-12 items-center rounded-xl bg-white px-6 text-[15px] font-bold text-brand-900 hover:bg-brand-50">Quiero vender</a>
</section>
@endsection
