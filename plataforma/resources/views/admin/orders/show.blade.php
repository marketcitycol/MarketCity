@extends('layouts.admin', ['title' => 'Pedido '.$order->number])

@section('content')
<a href="{{ route('admin.orders') }}" class="text-sm text-brand-600 hover:underline">← Pedidos</a>
<div class="mt-2 flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-bold">Pedido {{ $order->number }}</h1>
    <span class="badge bg-white">{{ $order->paymentStatusLabel() }} · {{ $order->paymentMethodLabel() }}</span>
    <span class="text-sm text-slate-500">{{ fecha($order->created_at) }}</span>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-[1fr_340px]">
    <div class="space-y-5">
        @foreach ($order->sellerOrders as $so)
        <section class="card p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="font-semibold">{{ $so->store->name }} <span class="font-normal text-slate-500">· {{ $so->shipping_method === 'recogida' ? 'Recogida en tienda' : 'Envío' }}</span></p>
                @include('partials.status-badge')
            </div>
            <ul class="mt-2 text-sm text-slate-600">@foreach ($so->items as $i)<li>{{ $i->quantity }} × {{ $i->name }} ({{ $i->sku }}) — {{ cop($i->line_total) }}</li>@endforeach</ul>
            <dl class="mt-3 grid grid-cols-2 gap-x-6 gap-y-1 text-sm sm:grid-cols-4">
                <div><dt class="text-xs text-slate-500">Productos</dt><dd>{{ cop($so->subtotal) }}</dd></div>
                <div><dt class="text-xs text-slate-500">Envío</dt><dd>{{ cop($so->shipping_total) }}</dd></div>
                <div><dt class="text-xs text-slate-500">Comisión</dt><dd>{{ cop($so->commission_amount) }}</dd></div>
                <div><dt class="text-xs text-slate-500">Neto vendedor</dt><dd class="font-semibold">{{ cop($so->seller_net) }}</dd></div>
            </dl>
            @if (isset($so->commission_rule['percent']))<p class="mt-1 text-xs text-slate-500">Regla aplicada: {{ $so->commission_rule['percent'] }} % + {{ cop($so->commission_rule['fixed'] ?? 0) }} ({{ $so->commission_rule['source'] ?? '' }})</p>@elseif ($so->commission_rule)<p class="mt-1 text-xs text-slate-500">{{ $so->commission_rule['nota'] ?? 'Pedido histórico' }}</p>@endif
            @if ($so->settlement_due_on)<p class="mt-1 text-xs text-slate-500">Liquidación: {{ $so->settled_at ? 'pagada '.fecha($so->settled_at, 'd/m/Y').' · '.$so->settlement_reference : 'programada '.$so->settlement_due_on->format('d/m/Y') }}</p>@endif
            @if ($so->tracking_number)<p class="mt-1 text-sm">Guía: {{ $so->carrierName() }} {{ $so->tracking_number }}</p>@endif
            @if (! in_array($so->status, ['entregado', 'cancelado']))
            <div class="mt-4 flex flex-wrap items-end justify-between gap-3 border-t border-slate-100 pt-4">
                @include('partials.advance-form', ['action' => route('admin.orders.advance', $so)])
                <form method="post" action="{{ route('admin.orders.cancel', $so) }}" class="flex gap-2">
                    @csrf
                    <input class="input" name="reason" placeholder="Motivo de la cancelación" required>
                    <button class="btn-danger">Cancelar</button>
                </form>
            </div>
            @endif
        </section>
        @endforeach

        <section class="card p-5 text-sm">
            <h2 class="font-semibold">Historial</h2>
            <ul class="mt-2 space-y-1 text-slate-600">
                @foreach ($order->events as $e)<li><span class="text-slate-500">{{ fecha($e->created_at) }}</span> · {{ $e->message }} @if ($e->user)<span class="text-xs text-slate-500">({{ $e->user->name }})</span>@endif</li>@endforeach
            </ul>
        </section>
    </div>

    <aside class="space-y-5">
        <section class="card p-5 text-sm">
            <h2 class="font-semibold">Cliente</h2>
            <p class="mt-2">{{ $order->customerName() }}<br>{{ $order->document_type }} {{ $order->document_number }}<br>{{ $order->email }}<br>{{ $order->phone }}</p>
            <p class="mt-2">{{ $order->address }}@if ($order->address_2), {{ $order->address_2 }}@endif<br>{{ $order->city }}, {{ $order->department }}</p>
            <p class="mt-2 text-xs text-slate-500">Aceptó términos v{{ $order->terms_version }} el {{ fecha($order->terms_accepted_at) }} · Marketing: {{ $order->marketing_optin ? 'sí' : 'no' }}</p>
        </section>
        <section class="card p-5 text-sm">
            <h2 class="font-semibold">Pago</h2>
            <dl class="mt-2 space-y-1">
                <div class="flex justify-between"><dt>Productos</dt><dd>{{ cop($order->subtotal) }}</dd></div>
                <div class="flex justify-between"><dt>Envíos</dt><dd>{{ cop($order->shipping_total) }}</dd></div>
                <div class="flex justify-between font-bold"><dt>Total</dt><dd>{{ cop($order->total) }}</dd></div>
            </dl>
            <p class="mt-2 text-xs text-slate-500">Cobrado a nombre de: {{ $order->merchantEntity?->legal_name ?? '—' }}</p>
            @foreach ($order->payments as $p)<p class="mt-1 text-xs">{{ $p->provider }} · {{ $p->status }} · {{ $p->provider_reference ?? 'sin referencia' }}</p>@endforeach
            @if ($order->payment_status === 'pending')
                <form method="post" action="{{ route('admin.orders.paid', $order) }}" class="mt-3 space-y-2">
                    @csrf
                    <input class="input" name="reference" placeholder="Referencia del recaudo (opcional)">
                    <button class="btn-primary w-full">Registrar pago recibido</button>
                </form>
            @endif
        </section>
    </aside>
</div>
@endsection
