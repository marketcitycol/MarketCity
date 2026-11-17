<!DOCTYPE html>
<html lang="es-CO">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($pageTitle = trim($__env->yieldContent('title')) ?: ($title ?? null))
    <title>{{ $pageTitle ? $pageTitle.' · ' : '' }}MarketCity</title>
    <meta name="description" content="@yield('description', 'MarketCity: el marketplace de Colombia. Compra a muchos vendedores en un solo pedido y paga como prefieras.')">
    @if (config('marketcity.demo'))<meta name="robots" content="noindex, nofollow">@endif
    <link rel="canonical" href="@hasSection('canonical')@yield('canonical')@else{{ url()->current() }}@endif">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
    @if (config('marketcity.demo'))
    <div class="bg-brand-900 px-4 py-1.5 text-center text-xs text-brand-100">
        Versión 1 de prueba de la plataforma propia · no se cobra dinero real
    </div>
    @endif
    <header class="sticky top-0 z-30 border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3">
            <a href="{{ route('home') }}" class="shrink-0 text-xl font-extrabold tracking-tight text-brand-700">Market<span class="text-accent-500">City</span></a>
            <form action="{{ route('shop') }}" method="get" class="flex flex-1" role="search">
                <label for="q" class="sr-only">Buscar productos</label>
                <input id="q" name="q" value="{{ request('q') }}" placeholder="Buscar productos, marcas o SKU" class="input rounded-r-none">
                <button class="btn-primary rounded-l-none" aria-label="Buscar">Buscar</button>
            </form>
            <nav class="flex items-center gap-1 text-sm">
                @auth
                    @php($u = auth()->user())
                    <a href="{{ $u->isAdmin() ? route('admin.dashboard') : ($u->isSeller() ? route('seller.dashboard') : route('account')) }}" class="hidden rounded-lg px-3 py-2 hover:bg-slate-100 sm:block">{{ $u->isAdmin() ? 'Backoffice' : ($u->isSeller() ? 'Seller Center' : 'Mi cuenta') }}</a>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg px-3 py-2 hover:bg-slate-100 sm:block">Ingresar</a>
                @endauth
                <a href="{{ route('cart') }}" class="relative rounded-lg px-3 py-2 hover:bg-slate-100" aria-label="Carrito">
                    🛒 <span class="hidden sm:inline">Carrito</span>
                    @php($count = app(\App\Services\Cart::class)->count())
                    @if ($count)<span class="absolute -top-0.5 -right-0.5 rounded-full bg-accent-400 px-1.5 text-xs font-bold text-brand-900">{{ $count }}</span>@endif
                </a>
            </nav>
        </div>
        <nav class="border-t border-slate-100" aria-label="Categorías">
            <div class="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 py-2 text-sm whitespace-nowrap">
                <a href="{{ route('offers') }}" class="rounded-full bg-red-50 px-3 py-1 font-medium text-red-700 hover:bg-red-100">🔥 Ofertas</a>
                @foreach (\App\Models\Category::orderBy('position')->get() as $c)
                    <a href="{{ route('category', $c) }}" class="rounded-full px-3 py-1 text-slate-600 hover:bg-slate-100 {{ request()->route('category')?->id === $c->id ? 'bg-brand-50 text-brand-700' : '' }}">{{ $c->name }}</a>
                @endforeach
                <a href="{{ route('stores') }}" class="rounded-full px-3 py-1 text-slate-600 hover:bg-slate-100">Tiendas</a>
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-6">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="mt-12 border-t border-slate-200 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-sm sm:grid-cols-3">
            <div>
                <p class="text-lg font-extrabold text-brand-700">Market<span class="text-accent-500">City</span></p>
                <p class="mt-2 text-slate-500">El marketplace de Colombia. Envíos a los 32 departamentos y Bogotá.</p>
            </div>
            <div>
                <p class="font-semibold">Compra</p>
                <ul class="mt-2 space-y-1 text-slate-500">
                    <li><a href="{{ route('track') }}" class="hover:text-brand-600">Rastrear mi pedido</a></li>
                    <li><a href="{{ route('stores') }}" class="hover:text-brand-600">Tiendas</a></li>
                    <li><a href="{{ route('account') }}" class="hover:text-brand-600">Mi cuenta</a></li>
                </ul>
            </div>
            <div>
                <p class="font-semibold">Vende</p>
                <ul class="mt-2 space-y-1 text-slate-500">
                    <li><a href="{{ route('sell') }}" class="hover:text-brand-600">Vende en MarketCity</a></li>
                    <li><a href="{{ route('seller.dashboard') }}" class="hover:text-brand-600">Seller Center</a></li>
                </ul>
            </div>
        </div>
        <p class="border-t border-slate-100 px-4 py-4 text-center text-xs text-slate-400">* Cuotas de referencia: el valor final depende del medio de pago y su aprobación. Precios en pesos colombianos con IVA incluido.</p>
    </footer>
</body>
</html>
