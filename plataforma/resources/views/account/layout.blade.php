@extends('layouts.shop', ['title' => $pageTitle])

{{-- Mi cuenta (#48, #49, #79): misma navegación en todas las secciones. --}}
@section('content')
@php($tabs = [
    ['Inicio', 'account'], ['Pedidos', 'account.orders'], ['Direcciones', 'account.addresses'], ['Mis datos', 'account.details'],
    ['Favoritos', 'account.favorites'], ['Notificaciones', 'account.notifications'], ['Consultas', 'account.inquiries'], ['Seguridad', 'account.security'],
])
<div class="flex flex-wrap items-center justify-between gap-3">
    <div><p class="text-xs font-semibold tracking-wide text-brand-600 uppercase">Mi cuenta</p><h1 class="text-2xl font-bold">{{ $pageTitle }}</h1></div>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn-ghost btn-sm">Cerrar sesión</button></form>
</div>
<div class="mt-5 grid gap-6 lg:grid-cols-[220px_1fr]">
    <nav aria-label="Secciones de Mi cuenta" class="flex gap-1 overflow-x-auto text-sm lg:flex-col">
        @foreach ($tabs as [$label, $route])
            <a href="{{ route($route) }}" @if (request()->routeIs($route)) aria-current="page" @endif
               class="rounded-lg px-3 py-2 whitespace-nowrap {{ request()->routeIs($route) ? 'bg-brand-50 font-semibold text-brand-700' : 'text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="min-w-0">@yield('account')</div>
</div>
@endsection
