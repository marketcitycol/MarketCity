@extends('layouts.admin', ['title' => 'PQRS '.$pqr->radicado])

@section('content')
<a href="{{ route('admin.pqrs') }}" class="text-sm text-brand-600 hover:underline">← PQRS</a>
<div class="mt-2 flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-bold">{{ $pqr->radicado }}</h1>
    <span class="badge bg-white">{{ $pqr->typeLabel() }} · {{ $pqr->statusLabel() }}</span>
    <span class="text-sm {{ $pqr->isOverdue() ? 'font-semibold text-red-700' : 'text-slate-500' }}">Vence {{ fecha($pqr->due_at, 'd/m/Y') }}{{ $pqr->isOverdue() ? ' · VENCIDA' : '' }}</span>
</div>

<div class="mt-5 grid gap-5 lg:grid-cols-[1fr_340px]">
    <div class="space-y-5">
        <section class="card p-5 text-sm">
            <h2 class="font-semibold">Solicitud</h2>
            <p class="mt-2 whitespace-pre-line text-slate-700">{{ $pqr->message }}</p>
            @if ($pqr->type === 'retracto' && $pqr->within_term !== null)
                <p class="mt-3 rounded-lg px-3 py-2 {{ $pqr->within_term ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">
                    {{ $pqr->within_term ? 'Llegó dentro de los 5 días hábiles desde la entrega.' : 'Llegó después de los 5 días hábiles desde la entrega. Revisa si aplica igual (garantía o reclamo).' }}
                </p>
            @endif
            @if (in_array($pqr->type, \App\Models\Pqr::ORDER_TYPES) && $pqr->order_id && $pqr->isOpen())
                <p class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-red-800">Mientras esté abierta, la liquidación de este pedido a las tiendas queda retenida.</p>
            @endif
        </section>

        <section class="card p-5 text-sm">
            <h2 class="font-semibold">Responder</h2>
            <form method="post" action="{{ route('admin.pqrs.update', $pqr) }}" class="mt-3 space-y-3">
                @csrf @method('PUT')
                <div><label class="label" for="response">Respuesta al cliente</label><textarea class="input min-h-40" id="response" name="response" maxlength="5000">{{ old('response', $pqr->response) }}</textarea></div>
                <div class="flex flex-wrap items-end gap-3">
                    <div><label class="label" for="status">Estado</label>
                        <select class="input w-auto" id="status" name="status">@foreach (\App\Models\Pqr::STATUSES as $k => $v)<option value="{{ $k }}" @selected(old('status', $pqr->status) === $k)>{{ $v }}</option>@endforeach</select>
                    </div>
                    <label class="flex items-center gap-2 pb-2"><input type="hidden" name="notify" value="0"><input type="checkbox" name="notify" value="1" checked> Enviar la respuesta por correo</label>
                    <button class="btn-primary">Guardar</button>
                </div>
            </form>
            @if ($pqr->responded_at)<p class="mt-2 text-xs text-slate-500">Última respuesta: {{ fecha($pqr->responded_at) }} @if ($pqr->responder) por {{ $pqr->responder->name }}@endif</p>@endif
        </section>
    </div>

    <aside class="space-y-5 text-sm">
        <section class="card space-y-1 p-5">
            <h2 class="font-semibold">Cliente</h2>
            <p>{{ $pqr->name }}</p>
            <p><a href="mailto:{{ $pqr->email }}" class="text-brand-600">{{ $pqr->email }}</a></p>
            @if ($pqr->phone)<p>{{ $pqr->phone }}</p>@endif
            @if ($pqr->document_number)<p>Cédula {{ $pqr->maskedDocument() }}</p>@endif
            <p class="text-xs text-slate-500">Radicada {{ fecha($pqr->created_at) }} · autorizó datos {{ fecha($pqr->data_consent_at) }}</p>
        </section>
        @if ($pqr->order)
        <section class="card space-y-1 p-5">
            <h2 class="font-semibold">Pedido</h2>
            <p><a href="{{ route('admin.orders.show', $pqr->order) }}" class="text-brand-600 hover:underline">{{ $pqr->order->number }}</a> · {{ cop($pqr->order->total) }} · {{ $pqr->order->paymentStatusLabel() }}</p>
            @foreach ($pqr->order->sellerOrders as $so)
                <p class="text-slate-600">{{ $so->store->name }} · {{ $so->statusLabel() }}@if ($so->delivered_at) · entregado {{ fecha($so->delivered_at, 'd/m/Y') }}@endif</p>
            @endforeach
        </section>
        @elseif ($pqr->order_number)
            <p class="card p-5 text-amber-800">El cliente escribió el pedido {{ $pqr->order_number }}, pero no coincide con su correo.</p>
        @endif
    </aside>
</div>
@endsection
