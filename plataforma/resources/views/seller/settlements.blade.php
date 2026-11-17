@extends('layouts.seller', ['title' => 'Liquidaciones'])

@section('content')
<h1 class="text-2xl font-bold">Liquidaciones</h1>
<p class="text-sm text-slate-500">MarketCity te paga el neto de cada venta {{ \App\Models\Setting::get('settlement_business_days', 10) }} días hábiles después de confirmado el pago (sin contar fines de semana ni festivos).</p>
<div class="card mt-4 overflow-x-auto">
    @if ($rows->isEmpty())
        <p class="p-8 text-center text-sm text-slate-500">Aún no hay ventas pagadas.</p>
    @else
    <table class="table-std">
        <thead><tr><th>Pedido</th><th>Venta</th><th>Envío</th><th>Comisión</th><th>Neto</th><th>Fecha de pago</th><th>Estado</th></tr></thead>
        <tbody>
        @foreach ($rows as $so)
            <tr>
                <td><a href="{{ route('seller.orders.show', $so) }}" class="text-brand-600 hover:underline">{{ $so->order->number }}</a></td>
                <td>{{ cop($so->subtotal) }}</td><td>{{ cop($so->shipping_total) }}</td>
                <td class="text-red-700">− {{ cop($so->commission_amount) }}</td>
                <td class="font-semibold">{{ cop($so->seller_net) }}</td>
                <td>{{ $so->settlement_due_on?->format('d/m/Y') }}</td>
                <td>@if ($so->settled_at)<span class="badge bg-emerald-100 text-emerald-700">Pagado · {{ $so->settlement_reference }}</span>@else<span class="badge bg-amber-100 text-amber-800">Programado</span>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
<div class="mt-4">{{ $rows->links() }}</div>
@endsection
