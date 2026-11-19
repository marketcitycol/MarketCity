@extends('layouts.shop')

@section('content')
@php($P = \App\Services\Content\Plugins::class)
@php($B = \App\Services\Content\BrandAssets::class)
@php($hero = $P::config('hero'))

{{-- Portada: igual a la de marketcity.co (azul noche, texto con degradado, buscador, botones y medios de pago). --}}
<section class="relative overflow-hidden rounded-[26px] px-[18px] pt-7 pb-6 text-white shadow-[inset_0_0_0_1px_rgba(252,209,22,.2),0_28px_60px_-28px_rgba(5,13,36,.75)] md:px-10 md:py-12 [background:radial-gradient(120%_70%_at_100%_0,rgba(28,72,170,.55),transparent_60%),radial-gradient(90%_60%_at_0_100%,rgba(252,209,22,.07),transparent_60%),linear-gradient(165deg,#0b1d4a_0%,#081536_55%,#050d24_100%)]">
    @if ($P::enabled('hero') && $hero['image_url'])
        <img src="{{ $hero['image_url'] }}" alt="" class="pointer-events-none absolute inset-y-0 right-0 hidden h-full w-1/2 object-cover opacity-90 [mask-image:linear-gradient(90deg,transparent,#000_35%)] lg:block" fetchpriority="high">
    @endif
    <div class="relative max-w-xl">
        <p class="inline-flex items-center rounded-full border border-[rgba(252,209,22,.4)] px-3 py-1 text-[10px] font-semibold tracking-wide text-[#f6e6a6] uppercase md:text-xs">{{ $hero['badge'] ?: 'Marketplace colombiano · Paga a cuotas' }}</p>
        <h1 class="mt-4 text-[33px] leading-[1.08] font-bold tracking-[-0.67px] md:text-5xl">
            {{ $hero['title'] }} <span class="bg-[linear-gradient(90deg,#9ec5ff,#ffd166)] bg-clip-text text-transparent">{{ $hero['title_soft'] }}</span><br>{{ $hero['title_end'] }}
        </h1>
        <span class="mt-4 block h-[3px] w-12 rounded-full bg-accent-400" aria-hidden="true"></span>
        <p class="mt-4 text-[15px] leading-relaxed text-white/80 md:text-lg">{{ $hero['subtitle'] }}</p>
        <form action="{{ route('shop') }}" method="get" role="search" class="mt-5 flex items-center gap-1.5 rounded-[14px] bg-white p-1.5 shadow-[0_12px_30px_-12px_rgba(0,0,0,.45)]">
            <label for="q-hero" class="sr-only">¿Qué buscas hoy?</label>
            <span class="pl-2 text-[#8a90a6]">{!! icon('buscar', 'h-5 w-5') !!}</span>
            <input id="q-hero" name="q" placeholder="¿Qué buscas hoy?" enterkeyhint="search" class="h-[46px] min-w-0 flex-1 bg-transparent px-1 text-[15px] text-slate-900 focus:outline-none">
            <button type="button" class="hidden h-[46px] w-[46px] shrink-0 place-items-center rounded-[11px] bg-[#e8f0ff] text-[#0b5cff]" data-voice aria-label="Buscar por voz">{!! icon('microfono', 'h-5 w-5') !!}</button>
            <button class="h-[46px] shrink-0 rounded-[11px] bg-[#0b5cff] px-4 text-sm font-bold text-white hover:bg-brand-700">Buscar</button>
        </form>
        <div class="mt-4 grid grid-cols-2 gap-2.5 sm:flex">
            @foreach ($P::lines($hero['buttons']) as $i => $b)
                @php($label = preg_replace('/^[^\p{L}\p{N}]+/u', '', $b[0]))
                <a href="{{ $P::url($b[1] ?? '#') }}" class="{{ $i === 0 ? 'bg-accent-400 text-[#1a1300] hover:bg-accent-500' : 'border border-white/34 text-white hover:bg-white/10' }} inline-flex h-[52px] items-center justify-center gap-1.5 rounded-[14px] px-5 text-[15px] font-bold">
                    @if ($i === 0){!! icon('ofertas', 'h-[18px] w-[18px]') !!}@endif{{ $label }}
                </a>
            @endforeach
        </div>
        <ul class="mt-4 flex flex-wrap gap-2 text-[11px] font-semibold text-white/85">
            @foreach (['Addi a cuotas', 'Sistecrédito', 'Tarjeta y PSE'] as $pill)
                <li class="inline-flex h-7 items-center gap-1.5 rounded-full border border-white/14 bg-white/5 px-3">{!! icon($loop->last ? 'protegido' : 'credito', 'h-3.5 w-3.5 text-accent-400') !!}{{ $pill }}</li>
            @endforeach
        </ul>
    </div>
</section>

{{-- ¿Cuánto quieres gastar? --}}
<section class="mt-7">
    @include('partials.section-head', ['title' => '¿Cuánto quieres gastar?', 'sub' => 'Encuentra lo que te alcanza'])
    <div class="mt-3 grid grid-cols-2 gap-2.5 md:grid-cols-4">
        @foreach ([
            ['Menos de', '$50.000', ['max_price' => 50000]],
            ['Entre', '$50.000 y $100.000', ['min_price' => 50000, 'max_price' => 100000]],
            ['Entre', '$100.000 y $250.000', ['min_price' => 100000, 'max_price' => 250000]],
            ['Más de', '$250.000', ['min_price' => 250000]],
        ] as [$small, $amount, $query])
            <a href="{{ route('shop', $query) }}" class="flex flex-col rounded-2xl border border-[#e3e8f2] bg-white px-3.5 py-4 hover:border-brand-500">
                <small class="text-xs text-[#6b7385]">{{ $small }}</small>
                <b class="mt-0.5 text-[17px] leading-tight font-bold text-[#0f172a]">{{ $amount }}</b>
                <em class="mt-2 text-xs font-semibold text-[#0b5cff] not-italic">Ver productos →</em>
            </a>
        @endforeach
    </div>
</section>

{{-- Compra por categoría --}}
@if ($categories->isNotEmpty())
<section class="mt-8">
    @include('partials.section-head', ['title' => 'Compra por categoría', 'sub' => 'Encuentra rápido lo que necesitas', 'href' => route('shop')])
    <div class="-mx-3.5 mt-3 flex gap-3 overflow-x-auto px-3.5 pb-1 [scrollbar-width:none] md:mx-0 md:flex-wrap md:justify-between md:px-0 [&::-webkit-scrollbar]:hidden">
        @foreach ($categories as $c)
            <a href="{{ route('category', $c) }}" class="group flex w-[76px] shrink-0 flex-col items-center gap-1.5 text-center md:w-[92px]">
                <span class="grid h-[72px] w-[72px] place-items-center overflow-hidden rounded-full bg-[#f7f8fa] ring-1 ring-[#e3e6ec] transition group-hover:ring-brand-500 md:h-20 md:w-20">
                    @if ($img = $B::categoryImage($c->slug))
                        <img src="{{ $img }}" alt="" class="h-full w-full object-cover" loading="lazy" width="80" height="80">
                    @else
                        <span class="text-brand-600">{!! icon(\App\Services\Content\Icons::forCategory($c->slug), 'h-7 w-7') !!}</span>
                    @endif
                </span>
                <span class="text-[13px] leading-tight font-semibold text-[#0f172a]">{{ $c->name }}</span>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- Beneficios --}}
<section class="mt-8 grid gap-2.5 md:grid-cols-3">
    @foreach ([
        ['credito', 'Paga a cuotas', 'Con Addi o Sistecrédito, sujeto a aprobación'],
        ['envio', 'Envío a toda Colombia', 'Con seguimiento de tu pedido'],
        ['protegido', 'Compra protegida', 'Pago seguro y derecho de retracto'],
    ] as [$ic, $t, $d])
        <div class="flex items-center gap-3 rounded-2xl border border-[#e3e6ec] bg-white p-3">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-credit-50 text-credit-600">{!! icon($ic, 'h-[22px] w-[22px]') !!}</span>
            <span><b class="block text-[15px] font-bold text-[#0f172a]">{{ $t }}</b><span class="block text-[13px] text-[#6b7385]">{{ $d }}</span></span>
        </div>
    @endforeach
</section>

{{-- Ofertas relámpago --}}
@if ($offers->isNotEmpty())
<section class="mt-9">
    @include('partials.section-head', ['title' => 'Ofertas relámpago', 'sub' => 'Descuentos reales de 5 % o más', 'href' => route('offers'), 'link' => 'Ver ofertas'])
    @include('partials.product-rail', ['products' => $offers])
</section>
@endif

{{-- Compra a cuotas --}}
@php($inst = installments())
@php($minCuota = (int) ceil((\App\Models\Product::visible()->min('price') ?: 25200) / $inst / 100) * 100)
<section class="mt-9 rounded-[20px] bg-credit-50 p-5 md:p-7">
    <h2 class="text-xl leading-tight font-bold text-[#0f172a] md:text-2xl">Compra a cuotas desde {{ cop(max(1000, $minCuota)) }} al mes</h2>
    <p class="mt-1 text-sm text-[#4a5068]">Elige cuánto quieres pagar por cuota y te mostramos lo que te alcanza.</p>
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach ([20000, 50000, 100000] as $c)
            <a href="{{ route('shop', ['cuota' => $c]) }}" class="inline-flex h-11 items-center rounded-full border border-[#d5ccf5] bg-white px-4 text-sm font-bold text-credit-600 hover:bg-credit-600 hover:text-white">Hasta {{ cop($c) }} al mes</a>
        @endforeach
    </div>
    <p class="mt-3 text-xs text-[#6b7385]">Cuotas aproximadas en {{ $inst }} pagos. El crédito lo otorga Addi o Sistecrédito y está sujeto a aprobación.</p>
</section>

{{-- Más vendidos --}}
@if ($bestsellers->isNotEmpty())
<section class="mt-9">
    @include('partials.section-head', ['title' => 'Más vendidos', 'sub' => 'Lo que más se llevan nuestros clientes', 'href' => route('shop')])
    @include('partials.product-rail', ['products' => $bestsellers, 'ranked' => true])
</section>
@endif

{{-- Destacados (si el equipo marcó productos) --}}
@if ($featured->isNotEmpty())
<section class="mt-9">
    @include('partials.section-head', ['title' => 'Destacados', 'sub' => 'Elegidos por el equipo de MarketCity', 'href' => route('shop')])
    @include('partials.product-rail', ['products' => $featured])
</section>
@endif

{{-- Tiendas destacadas --}}
@if ($stores->isNotEmpty())
<section class="mt-9">
    @include('partials.section-head', ['title' => 'Tiendas destacadas', 'sub' => 'Vendedores colombianos verificados en MarketCity', 'href' => route('stores'), 'link' => 'Ver tiendas'])
    <div class="mt-3 grid grid-cols-2 gap-2.5 md:grid-cols-3 lg:grid-cols-6">
        @foreach ($stores->take(6) as $store)
            <a href="{{ route('store', $store) }}" class="flex flex-col items-center rounded-2xl border border-[#e3e6ec] bg-white px-3 pt-4 pb-3 text-center hover:border-brand-500">
                <span class="grid h-16 w-16 place-items-center overflow-hidden rounded-full bg-brand-900 text-lg font-extrabold text-white">
                    @if ($logo = $store->logoUrl())<img src="{{ $logo }}" alt="" class="h-full w-full bg-white object-contain" loading="lazy">@else{{ $store->initials() }}@endif
                </span>
                <b class="mt-2 line-clamp-1 text-[15px] font-bold text-[#0f172a]">{{ $store->name }}</b>
                <span class="text-xs text-[#6b7385]">{{ $store->products_count }} productos</span>
                <span class="mt-2.5 inline-flex h-9 w-full items-center justify-center rounded-full border border-brand-600 text-[13px] font-bold text-brand-600">Ver tienda</span>
            </a>
        @endforeach
    </div>
</section>
@endif

{{-- Vende en MarketCity --}}
<section class="mt-9 overflow-hidden rounded-[20px] bg-brand-900 p-6 text-white md:flex md:items-center md:justify-between md:gap-8 md:p-8">
    <div>
        <p class="text-xs font-bold tracking-wider text-[#c8a15a] uppercase">Para vendedores</p>
        <h2 class="mt-1 text-[22px] leading-tight font-extrabold md:text-[26px]">Vende en MarketCity</h2>
        <p class="mt-2 max-w-lg text-sm text-white/80">Crea tu tienda gratis, sube tus productos y vende a todo el país con pagos a cuotas para tus clientes.</p>
    </div>
    <a href="{{ route('sell') }}" class="mt-4 inline-flex h-12 shrink-0 items-center justify-center rounded-xl bg-white px-6 text-[15px] font-bold text-brand-900 hover:bg-brand-50 md:mt-0">Crear mi tienda</a>
</section>

{{-- Recién llegados --}}
@if ($newest->isNotEmpty())
<section class="mt-9">
    @include('partials.section-head', ['title' => 'Recién llegados', 'sub' => 'Lo nuevo en MarketCity', 'href' => route('shop', ['orderby' => 'date'])])
    @include('partials.product-rail', ['products' => $newest])
</section>
@endif

{{-- Cobertura --}}
<section class="mt-9 overflow-hidden rounded-[20px] p-6 text-white md:p-8 [background:radial-gradient(90%_80%_at_100%_0,#163a8f,transparent_60%),linear-gradient(160deg,#0a1b45,#06122e)]">
    <p class="text-xs font-bold tracking-wider text-[#f6e6a6] uppercase">Envíos nacionales</p>
    <h2 class="mt-1 text-[22px] leading-tight font-extrabold md:text-[26px]">Explora nuestra cobertura</h2>
    <p class="mt-2 max-w-xl text-sm text-white/80">Enviamos a los 32 departamentos y Bogotá con transportadoras aliadas. Cada pedido sale con guía para que lo sigas.</p>
    <p class="mt-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/8 px-3 py-1.5 text-xs font-bold">{!! icon('pin', 'h-4 w-4 text-accent-400') !!} Envíos a 32 departamentos y Bogotá</p>
    <ul class="mt-4 flex flex-wrap gap-1.5">
        @foreach (['Bogotá', 'Antioquia', 'Valle del Cauca', 'Cundinamarca', 'Atlántico', 'Santander', 'Bolívar', 'Nariño', 'Tolima', 'Meta', 'Boyacá', 'Huila'] as $d)
            <li class="inline-flex h-8 items-center rounded-full border border-white/12 bg-white/6 px-3 text-xs font-semibold text-white/85">{{ $d }}</li>
        @endforeach
        <li class="inline-flex h-8 items-center rounded-full px-2 text-xs font-semibold text-white/60">y más</li>
    </ul>
</section>

{{-- Envíos seguros --}}
<section class="mt-9">
    @include('partials.section-head', ['title' => 'Envíos seguros', 'sub' => 'Trabajamos con las transportadoras de confianza del país'])
    <ul class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-5">
        @foreach ($B::CARRIERS as $name => $file)
            <li class="grid h-14 place-items-center rounded-xl border border-[#e3e6ec] bg-white px-2"><img src="{{ $B::asset($file) }}" alt="{{ $name }}" class="max-h-8 max-w-full object-contain" loading="lazy"></li>
        @endforeach
    </ul>
    <a href="{{ route('track') }}" class="mt-3 flex items-center gap-3 rounded-2xl border border-[#e3e6ec] bg-white p-3.5 hover:border-brand-500">
        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600">{!! icon('caja', 'h-[22px] w-[22px]') !!}</span>
        <span class="min-w-0 flex-1 text-sm text-[#4a5068]"><b class="block text-[#0f172a]">¿Ya hiciste tu pedido?</b>Rastréalo aquí en segundos con tu número de guía o de pedido.</span>
        <span class="shrink-0 text-sm font-bold text-[#0b5cff]">Rastrear →</span>
    </a>
</section>
@endsection
