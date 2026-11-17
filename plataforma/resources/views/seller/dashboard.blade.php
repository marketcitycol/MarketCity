@extends('layouts.seller', ['title' => 'Inicio'])

@section('content')
<h1 class="text-2xl font-bold">{{ $store->name }}</h1>
@if ($store->status === 'pending')
    <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Tu tienda está <strong>en revisión</strong>. Puedes cargar productos; se publican cuando MarketCity la apruebe.</div>
@elseif ($store->status === 'suspended')
    <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">Tu tienda está <strong>suspendida</strong>: tus productos no se muestran. Escríbenos para revisarlo.</div>
@else
    <p class="text-sm text-slate-500">Tienda pública: <a href="{{ route('store', $store) }}" class="text-brand-600 hover:underline">{{ route('store', $store) }}</a></p>
@endif

<div class="mt-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @include('partials.stat', ['label' => 'Ventas pagadas', 'value' => cop($stats['ventas'])])
    @include('partials.stat', ['label' => 'Pedidos por atender', 'value' => $stats['pedidos_pendientes'], 'class' => $stats['pedidos_pendientes'] ? 'text-amber-600' : ''])
    @include('partials.stat', ['label' => 'Por liquidarte', 'value' => cop($stats['por_liquidar'])])
    @include('partials.stat', ['label' => 'Productos activos', 'value' => $stats['productos'], 'hint' => $stats['agotados'] ? $stats['agotados'].' agotados' : null])
</div>

<div class="card mt-6">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
        <h2 class="font-semibold">Últimos pedidos</h2>
        <a href="{{ route('seller.orders') }}" class="text-sm text-brand-600 hover:underline">Ver todos</a>
    </div>
    @include('seller.orders._table', ['orders' => $recent])
</div>
@endsection
