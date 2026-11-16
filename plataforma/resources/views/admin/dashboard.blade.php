@extends('layouts.admin', ['title' => 'Resumen'])

@section('content')
<h1 class="text-2xl font-bold">Resumen</h1>
<p class="text-sm text-slate-500">Titular vigente del comercio: <strong>{{ $merchant?->legal_name ?? 'sin definir' }}</strong> ({{ $merchant?->personTypeLabel() }}) · <a href="{{ route('admin.settings') }}" class="text-brand-600 hover:underline">cambiar</a></p>

<div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @include('partials.stat', ['label' => 'Ventas pagadas (GMV)', 'value' => cop($stats['gmv']), 'hint' => $stats['pagados'].' de '.$stats['pedidos'].' pedidos pagados'])
    @include('partials.stat', ['label' => 'Comisión MarketCity', 'value' => cop($stats['comision']), 'hint' => 'General 15 %, ajustable por comercio'])
    @include('partials.stat', ['label' => 'Por liquidar a vendedores', 'value' => cop($stats['por_liquidar']), 'hint' => $stats['vencidas'] ? $stats['vencidas'].' vencidas hoy' : 'Ninguna vencida', 'class' => $stats['vencidas'] ? 'text-amber-600' : ''])
    @include('partials.stat', ['label' => 'Tiendas activas', 'value' => $stats['tiendas'], 'hint' => $stats['solicitudes'].' solicitudes · '.$stats['productos'].' productos'])
</div>

@if ($stats['solicitudes'])
    <a href="{{ route('admin.stores') }}" class="mt-4 block rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 hover:bg-amber-100">Hay {{ $stats['solicitudes'] }} {{ $stats['solicitudes'] === 1 ? 'tienda esperando' : 'tiendas esperando' }} aprobación →</a>
@endif

<div class="card mt-6">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3"><h2 class="font-semibold">Pedidos recientes</h2><a href="{{ route('admin.orders') }}" class="text-sm text-brand-600 hover:underline">Ver todos</a></div>
    @include('admin.orders._table', ['orders' => $recent])
</div>
@endsection
