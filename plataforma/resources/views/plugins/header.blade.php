@php($h = \App\Support\Plugins::config('header'))
@php($P = \App\Support\Plugins::class)
@php($sell = $P::lines($h['sell'])[0] ?? null)
@php($count = app(\App\Services\Cart::class)->count())
@php($u = auth()->user())
@php($accountUrl = $u ? ($u->isAdmin() ? route('admin.dashboard') : ($u->isSeller() ? route('seller.dashboard') : route('account'))) : route('login'))
@php($accountLabel = $u ? ($u->isAdmin() ? 'Backoffice' : ($u->isSeller() ? 'Seller Center' : 'Hola, '.\Illuminate\Support\Str::of($u->name)->before(' '))) : 'Ingresar')

@if ($strip = $P::lines($h['strip']))
<div class="bg-brand-900 text-white">
    <p class="mx-auto flex max-w-7xl items-center justify-center gap-3 px-4 py-1.5 text-center text-xs font-bold">
        @foreach ($strip as $i => $s)@if ($i)<span aria-hidden="true" class="hidden text-white/50 sm:inline">·</span>@endif<span class="{{ $i ? 'hidden sm:inline' : '' }}">{{ $s[0] }}</span>@endforeach
    </p>
</div>
@endif

<header class="sticky top-0 z-30 border-b border-slate-200 bg-white" id="mc-header">
    <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-3">
        <button type="button" class="rounded-lg p-2 text-slate-700 hover:bg-slate-100" data-drawer-open aria-controls="mc-drawer" aria-label="Abrir menú">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <a href="{{ route('home') }}" class="shrink-0" aria-label="MarketCity, inicio">
            @if ($h['logo_url'])
                <img src="{{ $h['logo_url'] }}" alt="MarketCity" class="h-9 w-auto sm:h-10" width="120" height="40">
            @else
                <span class="text-xl font-extrabold text-brand-700">Market<span class="text-accent-500">City</span></span>
            @endif
        </a>
        <form action="{{ route('shop') }}" method="get" class="relative hidden flex-1 md:flex" role="search">
            <label for="q" class="sr-only">Buscar productos</label>
            <svg class="pointer-events-none absolute top-1/2 left-4 h-4 w-4 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="{{ $h['search_placeholder'] }}" class="w-full rounded-full border border-slate-300 bg-white py-2.5 pr-28 pl-10 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-100 focus:outline-none">
            <button class="absolute top-1 right-1 bottom-1 rounded-full bg-brand-600 px-5 text-sm font-bold text-white hover:bg-brand-700">Buscar</button>
        </form>
        <div class="ml-auto flex items-center gap-1 md:ml-0">
            @if ($sell)
                <a href="{{ $P::url($sell[1] ?? '/vender') }}" class="hidden rounded-full bg-brand-50 px-4 py-2 text-sm font-bold text-brand-700 hover:bg-brand-100 lg:inline-flex">{{ $sell[0] }}</a>
            @endif
            <a href="{{ $accountUrl }}" class="hidden items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold hover:bg-slate-100 sm:inline-flex">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>{{ $accountLabel }}
            </a>
            <a href="{{ route('cart') }}" class="relative rounded-lg p-2 hover:bg-slate-100" aria-label="Carrito, {{ $count }} productos">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 4h2l2.4 11h11l2-8H6.2"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg>
                @if ($count)<span class="absolute -top-0.5 -right-0.5 rounded-full bg-accent-400 px-1.5 text-xs font-bold text-brand-900">{{ $count }}</span>@endif
            </a>
        </div>
    </div>
    <form action="{{ route('shop') }}" method="get" class="relative mx-4 mb-3 flex md:hidden" role="search">
        <label for="q-m" class="sr-only">Buscar productos</label>
        <input id="q-m" name="q" value="{{ request('q') }}" placeholder="{{ $h['search_placeholder'] }}" class="w-full rounded-full border border-slate-300 bg-white py-2.5 pr-24 pl-4 text-sm focus:border-brand-500 focus:outline-none">
        <button class="absolute top-1 right-1 bottom-1 rounded-full bg-brand-600 px-4 text-sm font-bold text-white">Buscar</button>
    </form>
    <nav class="border-t border-slate-100" aria-label="Categorías">
        <div class="mx-auto flex max-w-7xl items-center gap-2 px-4 py-2">
            <div class="flex flex-1 gap-2 overflow-x-auto text-sm whitespace-nowrap [scrollbar-width:none]">
                @foreach ($P::lines($h['nav']) as $n)
                    @php($style = $n[2] ?? '')
                    <a href="{{ $P::url($n[1] ?? '#') }}" class="rounded-full border px-3 py-1.5 font-semibold {{ $style === 'oferta' ? 'border-orange-200 bg-orange-50 text-offer-600' : ($style === 'credito' ? 'border-credit-50 bg-credit-50 text-credit-600' : 'border-slate-200 text-slate-700 hover:bg-slate-50') }}">{{ $n[0] }}</a>
                @endforeach
            </div>
            <div class="hidden shrink-0 items-center gap-4 pl-3 text-sm font-semibold text-slate-600 lg:flex">
                @foreach ($P::lines($h['help']) as $l)
                    <a href="{{ $P::url($l[1] ?? '#') }}" class="hover:text-brand-700">{{ $l[0] }}</a>
                @endforeach
            </div>
        </div>
    </nav>
</header>

{{-- Menú lateral --}}
<div id="mc-drawer" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-label="Menú">
    <div class="absolute inset-0 bg-slate-900/50" data-drawer-close></div>
    <div class="absolute inset-y-0 left-0 flex w-80 max-w-[88vw] flex-col overflow-y-auto bg-white shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 p-4">
            <div>
                <p class="font-bold">{{ $u ? 'Hola, '.\Illuminate\Support\Str::of($u->name)->before(' ') : 'Hola' }}</p>
                <p class="text-xs text-slate-500">{{ $u ? 'Tus pedidos y tu carrito están a un toque' : 'Ingresa para ver tus pedidos y comprar más rápido' }}</p>
            </div>
            <button type="button" class="rounded-lg p-2 hover:bg-slate-100" data-drawer-close aria-label="Cerrar menú">✕</button>
        </div>
        <div class="grid grid-cols-2 gap-2 p-4">
            <a href="{{ $accountUrl }}" class="rounded-lg bg-brand-600 px-3 py-2 text-center text-sm font-bold text-white">Quiero comprar</a>
            <a href="{{ route('sell') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-center text-sm font-bold">Quiero vender</a>
        </div>
        <p class="px-4 pt-2 text-xs font-bold tracking-wide text-slate-500 uppercase">Categorías</p>
        <ul class="grid grid-cols-2 gap-1 p-3 text-sm">
            @foreach (\App\Models\Category::whereHas('products', fn ($q) => $q->visible())->orderBy('name')->get() as $c)
                <li><a href="{{ route('category', $c) }}" class="block rounded-lg px-2 py-1.5 hover:bg-slate-100">{{ $c->name }}</a></li>
            @endforeach
        </ul>
        <p class="px-4 pt-2 text-xs font-bold tracking-wide text-slate-500 uppercase">Tu compra</p>
        <ul class="p-3 text-sm">
            @foreach ($P::lines($h['drawer_buy']) as $l)
                <li><a href="{{ $P::url($l[2] ?? '#') }}" class="block rounded-lg px-2 py-2 hover:bg-slate-100"><span class="font-semibold">{{ $l[0] }}</span><span class="block text-xs text-slate-500">{{ $l[1] ?? '' }}</span></a></li>
            @endforeach
        </ul>
        @if ($h['drawer_payments'])
            <p class="px-4 pt-2 text-xs font-bold tracking-wide text-slate-500 uppercase">Formas de pago</p>
            <p class="px-5 py-2 text-sm text-slate-600">{{ $h['drawer_payments'] }}</p>
        @endif
        <div class="m-4 rounded-xl bg-brand-50 p-4">
            <p class="font-bold text-brand-900">Vende en MarketCity</p>
            <ul class="mt-2 space-y-2 text-sm">
                @foreach ($P::lines($h['drawer_sell']) as $l)
                    <li><span class="font-semibold">{{ $l[0] }}</span> <span class="block text-xs text-slate-600">{{ $l[1] ?? '' }}</span></li>
                @endforeach
            </ul>
            <div class="mt-3 flex flex-col gap-2">
                <a href="{{ route('sell') }}" class="btn-primary">Crear mi tienda</a>
                <a href="{{ route('seller.dashboard') }}" class="btn-ghost">Ya vendo · Seller Center</a>
            </div>
        </div>
    </div>
</div>

{{-- Barra inferior en celular --}}
<nav class="fixed inset-x-0 bottom-0 z-40 grid grid-cols-5 border-t border-slate-200 bg-white text-[11px] font-semibold text-slate-600 sm:hidden" aria-label="Accesos rápidos">
    <a href="{{ route('home') }}" class="flex flex-col items-center gap-0.5 py-2 {{ request()->routeIs('home') ? 'text-brand-700' : '' }}"><span aria-hidden="true">⌂</span>Inicio</a>
    <a href="{{ route('shop') }}" class="flex flex-col items-center gap-0.5 py-2"><span aria-hidden="true">⌕</span>Buscar</a>
    <a href="{{ route('offers') }}" class="flex flex-col items-center gap-0.5 py-2"><span aria-hidden="true">ϟ</span>Ofertas</a>
    <a href="{{ route('cart') }}" class="flex flex-col items-center gap-0.5 py-2"><span aria-hidden="true">🛒</span>Carrito{{ $count ? ' '.$count : '' }}</a>
    <a href="{{ $accountUrl }}" class="flex flex-col items-center gap-0.5 py-2"><span aria-hidden="true">◯</span>Cuenta</a>
</nav>
