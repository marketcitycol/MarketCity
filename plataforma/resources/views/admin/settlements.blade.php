@extends('layouts.admin', ['title' => 'Liquidaciones'])

@section('content')
<h1 class="text-2xl font-bold">Liquidaciones a vendedores</h1>
<p class="text-sm text-slate-500">Cada venta pagada se liquida {{ \App\Models\Setting::get('settlement_business_days', 10) }} días hábiles después del pago (festivos de Colombia incluidos). Registra aquí cada transferencia.</p>

<div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @forelse ($byStore as $row)
        @include('partials.stat', ['label' => $row['store']->name, 'value' => cop($row['total']), 'hint' => cop($row['due']).' ya vencido'.(($hold = $row['store']->settlementHoldReason()) ? ' · RETENIDA: '.$hold : '')])
    @empty
        <p class="text-sm text-slate-500">No hay nada pendiente por liquidar.</p>
    @endforelse
</div>

@if ($pending->isNotEmpty())
<div class="card mt-6 overflow-x-auto">
    <h2 class="border-b border-slate-100 px-4 py-3 font-semibold">Pendientes</h2>
    <table class="table-std">
        <thead><tr><th>Fecha de pago</th><th>Tienda</th><th>Pedido</th><th>Estado</th><th>Neto</th><th>Registrar transferencia</th></tr></thead>
        <tbody>
        @foreach ($pending as $so)
            @php($overdue = $so->settlement_due_on && $so->settlement_due_on->lte(now('America/Bogota')))
            <tr>
                <td class="{{ $overdue ? 'font-semibold text-amber-700' : '' }}">{{ $so->settlement_due_on?->format('d/m/Y') }}{{ $overdue ? ' · vencida' : '' }}</td>
                <td>{{ $so->store->name }}</td>
                <td><a href="{{ route('admin.orders.show', $so->order) }}" class="text-brand-600 hover:underline">{{ $so->order->number }}</a></td>
                <td>@include('partials.status-badge')</td>
                <td class="font-semibold">{{ cop($so->seller_net) }}</td>
                <td>
                    <form method="post" action="{{ route('admin.settlements.settle', $so) }}" class="flex gap-2">@csrf
                        <input class="input" name="reference" placeholder="Ref. transferencia" required>
                        <button class="btn-primary btn-sm">Liquidar</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif

@if ($done->isNotEmpty())
<div class="card mt-6 overflow-x-auto">
    <h2 class="border-b border-slate-100 px-4 py-3 font-semibold">Últimas liquidaciones</h2>
    <table class="table-std">
        <thead><tr><th>Pagada</th><th>Tienda</th><th>Pedido</th><th>Neto</th><th>Referencia</th></tr></thead>
        <tbody>@foreach ($done as $so)<tr><td>{{ fecha($so->settled_at, 'd/m/Y') }}</td><td>{{ $so->store->name }}</td><td>{{ $so->order->number }}</td><td>{{ cop($so->seller_net) }}</td><td>{{ $so->settlement_reference }}</td></tr>@endforeach</tbody>
    </table>
</div>
@endif
@endsection
