@extends('layouts.seller', ['title' => 'Pedido '.$so->order->number])

@section('content')
@php($o = $so->order)
<a href="{{ route('seller.orders') }}" class="text-sm text-brand-600 hover:underline">← Pedidos</a>
<div class="mt-2 flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-bold">Pedido {{ $o->number }}</h1>
    @include('partials.status-badge')
    <span class="badge bg-white text-slate-600">{{ $o->paymentStatusLabel() }} · {{ $o->payment_method === 'contraentrega' ? 'Contraentrega' : 'Pago en línea' }}</span>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-[1fr_320px]">
    <div class="space-y-5">
        <section class="card p-5">
            <h2 class="font-semibold">Productos</h2>
            <table class="table-std mt-2">
                <thead><tr><th>Producto</th><th>SKU</th><th>Cant.</th><th class="text-right">Total</th></tr></thead>
                <tbody>@foreach ($so->items as $i)<tr><td>{{ $i->name }}</td><td>{{ $i->sku }}</td><td>{{ $i->quantity }}</td><td class="text-right">{{ cop($i->line_total) }}</td></tr>@endforeach</tbody>
            </table>
            <dl class="mt-4 ml-auto max-w-xs space-y-1 text-sm">
                <div class="flex justify-between"><dt>Productos</dt><dd>{{ cop($so->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt>{{ $so->shipping_method === 'recogida' ? 'Recogida' : 'Envío' }}</dt><dd>{{ cop($so->shipping_total) }}</dd></div>
                <div class="flex justify-between text-red-700"><dt>Comisión MarketCity</dt><dd>− {{ cop($so->commission_amount) }}</dd></div>
                <div class="flex justify-between border-t border-slate-200 pt-1 font-bold"><dt>Tu neto</dt><dd>{{ cop($so->seller_net) }}</dd></div>
                @if ($so->settlement_due_on)<div class="flex justify-between text-slate-500"><dt>Liquidación</dt><dd>{{ $so->settled_at ? 'Pagada '.fecha($so->settled_at, 'd/m/Y') : 'Programada '.$so->settlement_due_on->format('d/m/Y') }}</dd></div>@endif
            </dl>
        </section>
        <section class="card p-5">
            <h2 class="font-semibold">Siguiente paso</h2>
            <div class="mt-3">
                @if ($so->status === 'cancelado')
                    <p class="text-sm text-slate-500">Este pedido está cancelado.</p>
                @elseif ($so->nextStatus())
                    @include('partials.advance-form', ['action' => route('seller.orders.advance', $so)])
                @else
                    <p class="text-sm text-emerald-700">Pedido entregado. ¡Listo!</p>
                @endif
                @if ($so->tracking_number)<p class="mt-3 text-sm">Guía: {{ $so->carrierName() }} {{ $so->tracking_number }}</p>@endif
            </div>
        </section>
    </div>
    <aside class="card h-fit p-5 text-sm">
        <h2 class="font-semibold">{{ $so->shipping_method === 'recogida' ? 'Recoge en tienda' : 'Enviar a' }}</h2>
        <p class="mt-2">{{ $o->customerName() }}<br>{{ $o->document_type }} {{ $o->document_number }}<br>{{ $o->phone }}</p>
        @if ($so->shipping_method === 'envio')
            <p class="mt-2">{{ $o->address }}@if ($o->address_2), {{ $o->address_2 }}@endif<br>{{ $o->city }}, {{ $o->department }}</p>
        @endif
        @if ($o->notes)<p class="mt-2 rounded bg-slate-50 p-2 text-slate-600">Nota: {{ $o->notes }}</p>@endif
    </aside>
</div>
@endsection
