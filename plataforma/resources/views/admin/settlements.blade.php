@extends('layouts.admin', ['title' => 'Liquidaciones'])

@section('content')
<h1 class="text-2xl font-bold">Liquidaciones a vendedores</h1>
<p class="text-sm text-slate-500">Cada venta pagada se liquida {{ \App\Models\Setting::get('settlement_business_days', 10) }} días hábiles después del pago (festivos de Colombia incluidos). Registra aquí cada transferencia. Si a una tienda se le reembolsó una venta que ya se le había pagado, ese valor se resta de su siguiente liquidación.</p>

<div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @forelse ($byStore as $row)
        @include('partials.stat', ['label' => $row['store']->name, 'value' => cop($row['total']), 'hint' => cop($row['due']).' ya vencido'.($row['deduction'] ? ' · − '.cop($row['deduction']).' por reembolsos' : '').(($hold = $row['store']->settlementHoldReason()) ? ' · RETENIDA: '.$hold : '')])
    @empty
        <p class="text-sm text-slate-500">No hay nada pendiente por liquidar.</p>
    @endforelse
</div>

@if ($pending->isNotEmpty())
<div class="card mt-6 overflow-x-auto">
    <h2 class="border-b border-slate-100 px-4 py-3 font-semibold">Pendientes</h2>
    <table class="table-std">
        <thead><tr><th>Fecha de pago</th><th>Tienda</th><th>Pedido</th><th>Estado</th><th>Neto</th><th>A transferir si liquidas esta</th><th>Registrar transferencia</th></tr></thead>
        <tbody>
        @foreach ($pending as $so)
            @php($overdue = $so->settlement_due_on && $so->settlement_due_on->lte(now('America/Bogota')))
            <tr>
                <td class="{{ $overdue ? 'font-semibold text-amber-700' : '' }}">{{ $so->settlement_due_on?->format('d/m/Y') }}{{ $overdue ? ' · vencida' : '' }}</td>
                <td>{{ $so->store->name }}</td>
                <td><a href="{{ route('admin.orders.show', $so->order) }}" class="text-brand-600 hover:underline">{{ $so->order->number }}</a></td>
                <td>@include('partials.status-badge')@if ($claim = $so->openClaim())<br><a href="{{ route('admin.pqrs.show', $claim) }}" class="text-xs font-semibold text-red-700 hover:underline">RETENIDA · {{ $claim->typeLabel() }} {{ $claim->radicado }}</a>@endif</td>
                <td>{{ cop($so->seller_net) }}</td>
                @php($less = min((int) $so->seller_net, $deductions[$so->store_id] ?? 0))
                <td class="font-semibold">{{ cop($so->seller_net - $less) }}@if ($less)<br><span class="text-xs font-normal text-red-700">− {{ cop($less) }} de reembolsos ya liquidados</span>@endif</td>
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
        <thead><tr><th>Pagada</th><th>Tienda</th><th>Pedido</th><th>Neto</th><th>Descuento</th><th>Transferido</th><th>Referencia</th></tr></thead>
        <tbody>@foreach ($done as $so)<tr><td>{{ fecha($so->settled_at, 'd/m/Y') }}</td><td>{{ $so->store->name }}</td><td>{{ $so->order->number }}</td><td>{{ cop($so->seller_net) }}</td><td>{{ $so->settlement_deduction ? '− '.cop($so->settlement_deduction) : '—' }}</td><td class="font-semibold">{{ cop($so->seller_net - $so->settlement_deduction) }}</td><td>{{ $so->settlement_reference }}</td></tr>@endforeach</tbody>
    </table>
</div>
@endif
@endsection
