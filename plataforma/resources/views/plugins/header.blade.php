@php($h = \App\Services\Content\Plugins::config('header'))
@php($P = \App\Services\Content\Plugins::class)
@php($sell = $P::lines($h['sell'])[0] ?? null)
@php($count = app(\App\Services\Orders\Cart::class)->count())
@php($u = auth()->user())
@php($firstName = $u ? \Illuminate\Support\Str::of($u->name)->before(' ') : null)
@php($accountUrl = $u ? ($u->isAdmin() ? route('admin.dashboard') : ($u->isSeller() ? route('seller.dashboard') : route('account'))) : route('login'))
@php($accountLabel = $u ? ($u->isAdmin() ? 'Backoffice' : ($u->isSeller() ? 'Seller Center' : 'Hola, '.$firstName)) : 'Ingresar')
@php($logo = $P::logo())
@php($cats = \App\Services\Content\BrandAssets::sortCategories(\App\Models\Category::whereHas('products', fn ($q) => $q->visible())->get()))
{{-- Icono de cada opción del menú de categorías según su enlace. --}}
@php($chipIcon = function (string $url, string $style): string {
    if ($style === 'oferta') return 'ofertas';
    if ($style === 'credito') return 'credito';
    if ($style === 'parati') return 'estrella';
    if (preg_match('#categoria-producto/([\w-]+)#', $url, $m)) return \App\Services\Content\Icons::forCategory($m[1]);
    return 'categorias';
})

{{-- Franja de avisos: en celular rota un mensaje a la vez; en escritorio se ven todos. --}}
@if ($strip = $P::lines($h['strip']))
<div class="relative bg-[#132238] text-white" data-promo>
    <div class="mx-auto flex h-[34px] max-w-7xl items-center justify-center gap-3 px-10 text-xs font-bold">
        @foreach ($strip as $i => $s)
            @if ($i)<span aria-hidden="true" class="hidden text-white/40 md:inline">·</span>@endif
            <span class="items-center gap-2 truncate {{ $i ? 'hidden md:inline-flex' : 'inline-flex' }}" data-promo-item>
                {!! icon(['escudo', 'envio', 'credito', 'regalo'][$i % 4], 'h-4 w-4 shrink-0 text-accent-400') !!}<span class="truncate">{{ $s[0] }}</span>
            </span>
        @endforeach
    </div>
    <button type="button" class="absolute top-1/2 right-2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-full text-white/70 hover:text-white md:hidden" data-promo-close aria-label="Ocultar avisos por hoy">{!! icon('cerrar', 'h-4 w-4') !!}</button>
</div>
@endif

<header class="sticky top-0 z-30 border-b border-line bg-white" id="mc-header">
    <div class="mx-auto flex max-w-7xl items-center gap-2 px-3 py-2.5 sm:gap-3 sm:px-4">
        <button type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-night-900 hover:bg-slate-100" data-drawer-open aria-controls="mc-drawer" aria-label="Abrir menú">
            {!! icon('menu', 'h-6 w-6') !!}
        </button>
        <a href="{{ route('home') }}" class="shrink-0" aria-label="MarketCity, inicio">
            <img src="{{ $logo }}" alt="MarketCity" class="h-[34px] w-auto lg:h-10" width="108" height="34" fetchpriority="high">
        </a>
        <form action="{{ route('shop') }}" method="get" class="relative hidden flex-1 md:flex" role="search" data-search>
            <label for="q" class="sr-only">Buscar productos</label>
            <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-ink-700">{!! icon('buscar', 'h-5 w-5') !!}</span>
            <input id="q" name="q" value="{{ request('q') }}" placeholder="{{ $h['search_placeholder'] }}" autocomplete="off" enterkeyhint="search" class="h-12 w-full rounded-full border border-[#8a90a6] bg-white pr-36 pl-12 text-sm text-night-900 placeholder:text-[#6b7189] focus:border-brand-600 focus:ring-2 focus:ring-brand-100 focus:outline-none">
            <button type="button" class="absolute top-1 right-[92px] hidden h-10 w-10 place-items-center rounded-full text-ink-700 hover:bg-slate-100" data-voice aria-label="Buscar por voz">{!! icon('microfono', 'h-5 w-5') !!}</button>
            <button class="absolute top-1 right-1 h-10 rounded-full bg-brand-600 px-5 text-[13px] font-semibold text-white hover:bg-brand-700">Buscar</button>
        </form>
        <div class="ml-auto flex items-center gap-1 md:ml-0">
            @if ($sell)
                <a href="{{ $P::url($sell[1] ?? '/vender') }}" class="hidden h-11 items-center gap-2 rounded-full bg-brand-50 px-4 text-sm font-bold text-brand-700 hover:bg-brand-100 lg:inline-flex">{!! icon('tienda', 'h-5 w-5') !!}{{ $sell[0] }}</a>
            @endif
            <a href="{{ $accountUrl }}" class="inline-flex h-11 min-w-11 items-center justify-center gap-2 rounded-xl px-2 text-sm font-semibold text-night-900 hover:bg-slate-100" aria-label="{{ $u ? $accountLabel : 'Mi cuenta' }}">
                {!! icon('cuenta', 'h-6 w-6') !!}<span class="hidden max-w-40 truncate xl:inline">{{ $accountLabel }}</span>
            </a>
            <a href="{{ route('cart') }}" class="relative grid h-11 w-11 place-items-center rounded-xl text-night-900 hover:bg-slate-100" aria-label="Carrito, {{ $count }} productos" data-cart-link>
                {!! icon('carrito', 'h-6 w-6') !!}
                <span class="absolute top-0.5 right-0.5 min-w-5 rounded-full bg-brand-600 px-1 text-center text-[11px] leading-5 font-bold text-white {{ $count ? '' : 'hidden' }}" data-cart-count>{{ $count }}</span>
            </a>
        </div>
    </div>
    <form action="{{ route('shop') }}" method="get" class="relative mx-3 mb-2.5 flex md:hidden" role="search" data-search>
        <label for="q-m" class="sr-only">Buscar productos</label>
        <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-ink-700">{!! icon('buscar', 'h-5 w-5') !!}</span>
        <input id="q-m" name="q" value="{{ request('q') }}" placeholder="{{ $h['search_placeholder'] }}" autocomplete="off" enterkeyhint="search" class="h-12 w-full rounded-full border border-[#8a90a6] bg-white pr-32 pl-11 text-[15px] text-night-900 placeholder:text-[#6b7189] focus:border-brand-600 focus:outline-none">
        <button type="button" class="absolute top-1 right-[88px] hidden h-10 w-9 place-items-center rounded-full text-ink-700" data-voice aria-label="Buscar por voz">{!! icon('microfono', 'h-5 w-5') !!}</button>
        <button class="absolute top-1 right-1 h-10 rounded-full bg-brand-600 px-4 text-[13px] font-semibold text-white">Buscar</button>
    </form>
    <nav class="border-t border-[#eef0f4]" aria-label="Categorías">
        <div class="mx-auto flex max-w-7xl items-center gap-3 px-3 py-[5px] sm:px-4">
            <div class="flex flex-1 gap-2 overflow-x-auto text-[13px] whitespace-nowrap [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($P::lines($h['nav']) as $n)
                    @php($style = $n[2] ?? '')
                    @php($href = $P::url($n[1] ?? '#'))
                    <a href="{{ $href }}" class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-full border px-3 {{ $style === 'oferta' ? 'border-[#f6c7b3] bg-offer-50 font-bold text-offer-600' : ($style === 'credito' ? 'border-[#d5ccf5] bg-credit-50 font-bold text-credit-600' : 'border-transparent bg-[#f3f5f8] font-medium text-ink-900 hover:bg-[#e9edf3]') }}">
                        {!! icon($chipIcon($n[1] ?? '', $style), 'h-4 w-4') !!}{{ $n[0] }}
                    </a>
                @endforeach
            </div>
            <div class="hidden shrink-0 items-center gap-5 pl-2 text-[13px] font-semibold text-ink-700 lg:flex">
                @foreach ($P::lines($h['help']) as $l)
                    <a href="{{ $P::url($l[1] ?? '#') }}" class="hover:text-brand-700">{{ $l[0] }}</a>
                @endforeach
            </div>
        </div>
    </nav>
</header>

{{-- Menú lateral (igual al de marketcity.co: cabecera azul noche, pestañas comprar/vender, categorías con icono). --}}
<div id="mc-drawer" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true" aria-label="Menú">
    <div class="absolute inset-0 bg-night-800/55" data-drawer-close></div>
    <aside class="absolute inset-y-0 left-0 flex w-[344px] max-w-[86vw] flex-col bg-white shadow-2xl">
        <div class="bg-night-800 px-3.5 pt-3.5 pb-3 text-white">
            <div class="flex items-center justify-between">
                <img src="{{ $logo }}" alt="" class="h-[30px] w-auto brightness-0 invert" width="95" height="30">
                <button type="button" class="grid h-11 w-11 place-items-center rounded-xl text-white/90 hover:bg-white/10" data-drawer-close aria-label="Cerrar menú">{!! icon('cerrar', 'h-5 w-5') !!}</button>
            </div>
            <p class="mt-2 text-[19px] font-bold">{{ $u ? 'Hola, '.$firstName : 'Hola' }}<small class="mt-0.5 block text-[13px] font-normal text-white/80">{{ $u ? 'Tus pedidos y tu carrito están a un toque' : 'Ingresa para ver tus pedidos y comprar más rápido' }}</small></p>
            <div class="mt-3 grid grid-cols-2 gap-1 rounded-xl bg-white/10 p-[3px]" role="tablist">
                <button type="button" role="tab" aria-selected="true" class="h-11 rounded-[9px] text-[13px] font-extrabold text-white/80 aria-selected:bg-white aria-selected:text-night-800" data-drawer-tab="buy">Quiero comprar</button>
                <button type="button" role="tab" aria-selected="false" class="h-11 rounded-[9px] text-[13px] font-extrabold text-white/80 aria-selected:bg-white aria-selected:text-night-800" data-drawer-tab="sell">Quiero vender</button>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto px-3.5 pt-3 pb-4">
            <div data-drawer-pane="buy">
                <a href="{{ route('sell') }}" class="flex h-11 items-center justify-center rounded-xl border border-brand-600 text-[15px] font-bold text-brand-600 hover:bg-brand-50">Vende en MarketCity · Crea tu tienda</a>
                <div class="hidden" data-recent>
                    <p class="mt-5 mb-2 text-[11px] font-black tracking-wider text-ink-400 uppercase">Retoma donde quedaste</p>
                    <a href="#" class="flex items-center gap-3 rounded-xl px-2 py-2 hover:bg-slate-50" data-recent-link>
                        <span class="grid h-[38px] w-[38px] shrink-0 place-items-center rounded-[10px] bg-[#eaf1ff] text-electric-600">{!! icon('ojo', 'h-5 w-5') !!}</span>
                        <span class="min-w-0 flex-1"><b class="block truncate text-sm text-night-900" data-recent-name></b><small class="block text-xs text-[#6b7189]" data-recent-price></small></span>
                        {!! icon('flecha', 'h-4 w-4 text-ink-400') !!}
                    </a>
                </div>
                <p class="mt-5 mb-2 text-[11px] font-black tracking-wider text-ink-400 uppercase">Categorías</p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach ($cats as $c)
                        <a href="{{ route('category', $c) }}" class="flex min-h-[61px] flex-col items-center justify-center gap-1 rounded-[14px] bg-[#f3f5f8] p-2 text-center text-xs font-bold text-night-900 hover:bg-[#e9edf3]">
                            {!! icon(\App\Services\Content\Icons::forCategory($c->slug), 'h-5 w-5') !!}<span class="[overflow-wrap:anywhere]">{{ $c->name }}</span>
                        </a>
                    @endforeach
                </div>
                <p class="mt-5 mb-1 text-[11px] font-black tracking-wider text-ink-400 uppercase">Tu compra</p>
                @foreach ($P::lines($h['drawer_buy']) as $i => $l)
                    <a href="{{ $P::url($l[2] ?? '#') }}" class="flex items-center gap-3 rounded-xl px-2 py-[9px] hover:bg-slate-50">
                        <span class="grid h-[38px] w-[38px] shrink-0 place-items-center rounded-[10px] bg-[#eaf1ff] text-electric-600">{!! icon(['caja', 'envio', 'carrito', 'cuenta'][$i % 4], 'h-5 w-5') !!}</span>
                        <span class="min-w-0 flex-1"><b class="block text-sm text-night-900">{{ $l[0] }}</b><small class="block text-xs text-[#6b7189]">{{ $l[1] ?? '' }}</small></span>
                        {!! icon('flecha', 'h-4 w-4 text-ink-400') !!}
                    </a>
                @endforeach
                @if ($h['drawer_payments'])
                    <p class="mt-5 mb-2 text-[11px] font-black tracking-wider text-ink-400 uppercase">Formas de pago</p>
                    <div class="flex gap-3 rounded-xl bg-credit-50 p-3 text-[13px] text-ink-700">
                        <span class="text-credit-600">{!! icon('credito', 'h-5 w-5') !!}</span><span>{{ $h['drawer_payments'] }}</span>
                    </div>
                @endif
            </div>
            <div class="hidden" data-drawer-pane="sell">
                <p class="mb-2 text-[11px] font-black tracking-wider text-ink-400 uppercase">Vende en MarketCity</p>
                <ol class="space-y-2">
                    @foreach ($P::lines($h['drawer_sell']) as $i => $l)
                        <li class="flex gap-3 rounded-xl bg-[#f3f5f8] p-3">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-brand-600 text-xs font-bold text-white">{{ $i + 1 }}</span>
                            <span class="text-[13px] text-ink-700"><b class="block text-sm text-night-900">{{ $l[0] }}</b>{{ $l[1] ?? '' }}</span>
                        </li>
                    @endforeach
                </ol>
                <a href="{{ route('sell') }}" class="mt-4 flex h-11 items-center justify-center gap-2 rounded-xl bg-brand-600 text-sm font-bold text-white hover:bg-brand-700">{!! icon('tienda', 'h-5 w-5') !!}Crear mi tienda</a>
                <a href="{{ route('seller.dashboard') }}" class="mt-2 flex h-11 items-center justify-center rounded-xl border border-[#d6dae3] text-sm font-bold text-night-900 hover:bg-slate-50">Ya vendo · Seller Center</a>
            </div>
        </div>
        <div class="border-t border-[#eef0f4] px-3.5 pt-3 pb-4">
            @php($wa = preg_replace('/\D/', '', \App\Services\Content\Plugins::config('whatsapp')['number'] ?? ''))
            @if ($wa)
                <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" data-track="whatsapp_click" class="flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#0f7a43] px-3 py-2 text-sm font-extrabold text-white hover:brightness-110">{!! icon('whatsapp', 'h-5 w-5') !!}<span>WhatsApp <small class="font-semibold text-white/85">· respondemos de lunes a sábado desde las 8 a.m.</small></span></a>
            @endif
            <div class="mt-3 flex justify-center gap-5 text-[13px] text-ink-700">
                <a href="{{ url('/politica-de-privacidad') }}" class="hover:text-brand-700">Términos</a>
                <a href="{{ url('/politica-de-privacidad') }}" class="hover:text-brand-700">Privacidad</a>
                <a href="{{ url('/contacto') }}" class="hover:text-brand-700">Contacto</a>
            </div>
        </div>
    </aside>
</div>

{{-- Barra inferior en celular (Inicio, Categorías, Ofertas, Carrito, Cuenta). --}}
@php($tab = request()->routeIs('home') ? 'home' : (request()->routeIs('offers') ? 'offers' : (request()->routeIs('cart', 'checkout*') ? 'cart' : (request()->routeIs('account', 'login', 'register') ? 'account' : (request()->routeIs('category', 'shop') ? 'categories' : '')))))
<nav class="fixed inset-x-0 bottom-0 z-40 grid h-14 grid-cols-5 border-t border-line bg-white pb-[env(safe-area-inset-bottom)] md:hidden" aria-label="Navegación rápida">
    @foreach ([
        ['home', route('home'), 'inicio', 'Inicio'],
        ['categories', null, 'categorias', 'Categorías'],
        ['offers', route('offers'), 'ofertas', 'Ofertas'],
        ['cart', route('cart'), 'carrito', 'Carrito'],
        ['account', $accountUrl, 'cuenta', 'Cuenta'],
    ] as [$key, $href, $ic, $label])
        @php($on = $tab === $key)
        @if ($href)
            <a href="{{ $href }}" class="relative flex flex-col items-center justify-center gap-0.5 text-[11px] font-semibold {{ $on ? 'text-brand-600' : 'text-ink-700' }}" @if ($on) aria-current="page" @endif @if ($key === 'cart') data-cart-link @endif>
        @else
            <button type="button" class="relative flex flex-col items-center justify-center gap-0.5 text-[11px] font-semibold {{ $on ? 'text-brand-600' : 'text-ink-700' }}" data-drawer-open aria-controls="mc-drawer">
        @endif
            @if ($on)<span class="absolute top-0 left-1/2 h-[3px] w-8 -translate-x-1/2 rounded-b-full bg-brand-600" aria-hidden="true"></span>@endif
            {!! icon($ic, 'h-6 w-6') !!}<span>{{ $label }}</span>
            @if ($key === 'cart')<i class="absolute top-1 left-1/2 ml-2 min-w-[18px] rounded-full bg-brand-600 px-1 text-center text-[10px] leading-[18px] font-bold text-white not-italic {{ $count ? '' : 'hidden' }}" data-cart-count>{{ $count }}</i>@endif
        @if ($href)</a>@else</button>@endif
    @endforeach
</nav>
