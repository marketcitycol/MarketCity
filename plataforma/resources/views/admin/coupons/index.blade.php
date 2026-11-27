@extends('layouts.admin', ['title' => 'Cupones'])

@section('content')
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Cupones</h1>
    <a href="{{ route('admin.coupons.create') }}" class="btn-primary">Nuevo cupón</a>
</div>
<p class="mt-1 text-sm text-slate-500">Un cupón sin tienda vale para todo el sitio y el descuento lo pone MarketCity (el neto del vendedor no cambia). Un cupón de una tienda solo vale para sus productos y sale de su neto. Los pedidos cancelados liberan el uso.</p>
<div class="card mt-4 overflow-x-auto">
<table class="table-std">
    <thead><tr><th>Código</th><th>Descuento</th><th>Aplica a</th><th>Fechas</th><th>Usos</th><th>Descontado</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse ($coupons as $c)
        <tr>
            <td class="font-mono font-semibold">{{ $c->code }}@if ($c->description)<br><span class="font-sans text-xs font-normal text-slate-500">{{ $c->description }}</span>@endif</td>
            <td>{{ $c->summary() }}@if ($c->min_subtotal)<br><span class="text-xs text-slate-500">desde {{ cop($c->min_subtotal) }}</span>@endif</td>
            <td>{{ $c->store?->name ?? 'Todo el sitio' }}@if ($c->exclude_sale_items)<br><span class="text-xs text-slate-500">sin productos en oferta</span>@endif</td>
            <td class="text-xs">{{ $c->starts_at ? fecha($c->starts_at, 'd/m/Y H:i') : 'Ya' }} → {{ $c->ends_at ? fecha($c->ends_at, 'd/m/Y H:i') : 'sin fin' }}</td>
            <td>{{ $c->uses }}{{ $c->usage_limit ? ' / '.$c->usage_limit : '' }}@if ($c->usage_limit_per_customer)<br><span class="text-xs text-slate-500">{{ $c->usage_limit_per_customer }} por cliente</span>@endif</td>
            <td>{{ cop((int) $c->discounted) }}</td>
            <td><span class="badge {{ $c->active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $c->active ? 'Activo' : 'Apagado' }}</span></td>
            <td class="text-right"><a href="{{ route('admin.coupons.edit', $c) }}" class="btn-ghost btn-sm">Editar</a></td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-center text-slate-500">Todavía no hay cupones.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $coupons->links() }}</div>
@endsection
