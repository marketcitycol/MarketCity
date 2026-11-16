<!DOCTYPE html>
<html lang="es-CO">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title ?? 'Panel' }} · {{ $area }} · MarketCity</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">
<div class="lg:flex lg:min-h-screen">
    <aside class="bg-brand-900 text-brand-100 lg:w-60 lg:shrink-0">
    <div class="lg:sticky lg:top-0">
        <div class="flex items-center justify-between px-5 py-4">
            <a href="{{ route('home') }}" class="text-lg font-extrabold text-white">Market<span class="text-accent-400">City</span></a>
            <span class="text-xs text-brand-100/70">{{ $area }}</span>
        </div>
        <nav class="flex gap-1 overflow-x-auto px-3 pb-3 text-sm lg:flex-col lg:overflow-visible">
            @foreach ($menu as [$label, $route])
                <a href="{{ route($route) }}" class="rounded-lg px-3 py-2 whitespace-nowrap {{ request()->routeIs($route.'*') ? 'bg-white/10 font-semibold text-white' : 'hover:bg-white/5' }}">{{ $label }}</a>
            @endforeach
            <form method="post" action="{{ route('logout') }}" class="lg:mt-4">@csrf<button class="w-full rounded-lg px-3 py-2 text-left whitespace-nowrap hover:bg-white/5">Cerrar sesión</button></form>
        </nav>
    </div>
    </aside>
    <main class="flex-1 px-4 py-6 lg:px-8">
        @include('partials.flash')
        @yield('content')
    </main>
</div>
</body>
</html>
