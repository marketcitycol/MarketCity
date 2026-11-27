@extends('layouts.admin', ['title' => 'Condiciones · '.$store->name])

@section('content')
<a href="{{ route('admin.stores') }}" class="text-sm text-brand-600 hover:underline">← Vendedores</a>
<h1 class="mt-2 text-2xl font-bold">Condiciones de {{ $store->name }}</h1>
<p class="text-sm text-slate-500">Lo negociado con este comercio. Si dejas un campo vacío se usa el valor general ({{ $general['percent'] }} %{{ $general['fixed'] ? ' + '.cop($general['fixed']) : '' }}). Los cambios aplican a pedidos nuevos.</p>

<form method="post" action="{{ route('admin.stores.update', $store) }}" class="card mt-5 max-w-2xl space-y-5 p-5">
    @csrf @method('put')
    <fieldset>
        <legend class="font-semibold">Comisión de MarketCity</legend>
        <div class="mt-3 grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="commission_percent">Porcentaje</label><input class="input" type="number" step="0.1" min="0" max="100" id="commission_percent" name="commission_percent" value="{{ old('commission_percent', $store->commission_percent !== null ? (float) $store->commission_percent : '') }}" placeholder="{{ $general['percent'] }} (general)"></div>
            <div><label class="label" for="commission_fixed">Valor fijo por pedido (COP)</label><input class="input" type="number" min="0" id="commission_fixed" name="commission_fixed" value="{{ old('commission_fixed', $store->commission_fixed) }}" placeholder="{{ $general['fixed'] }} (general)"></div>
        </div>
    </fieldset>

    @php($allowed = $store->allowedPaymentMethods())
    <fieldset>
        <legend class="font-semibold">Medios de pago</legend>
        <label class="mt-3 flex items-center gap-2 text-sm"><input type="radio" name="payments_mode" value="all" @checked($allowed === null)> Todos los activos en la plataforma</label>
        <label class="mt-1 flex items-center gap-2 text-sm"><input type="radio" name="payments_mode" value="custom" @checked($allowed !== null)> Solo estos:</label>
        <div class="mt-2 ml-6 space-y-1">
            @foreach ($gateways as $key => $g)
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="payment_methods[]" value="{{ $key }}" @checked($allowed !== null && in_array($key, $allowed))> {{ $g->label() }}</label>
            @endforeach
        </div>
        <p class="mt-2 text-xs text-slate-500">Si un cliente lleva productos de varias tiendas, solo ve los medios de pago que todas aceptan.</p>
    </fieldset>
    <button class="btn-primary">Guardar condiciones</button>
</form>

<form method="post" action="{{ route('admin.stores.directory', $store) }}" class="card mt-6 max-w-2xl space-y-2 p-5 text-sm">
    @csrf
    <h2 class="font-semibold">Directorio de tiendas</h2>
    <label class="flex items-center gap-2"><input type="checkbox" name="featured" value="1" @checked($store->featured)> Destacar en /tiendas y en el inicio (sale primero)</label>
    <label class="flex items-center gap-2"><input type="checkbox" name="directory_hidden" value="1" @checked($store->directory_hidden)> Ocultar del directorio, del inicio y del mapa del sitio (p. ej. tienda de prueba). Su página y sus productos siguen visibles.</label>
    <button class="btn-ghost btn-sm">Guardar directorio</button>
</form>

@php($approved = $store->approvedBankAccount())
@php($pending = $store->pendingBankAccount())
<section class="card mt-6 max-w-2xl p-5">
    <h2 class="font-semibold">Datos legales y cuenta bancaria</h2>
    <p class="mt-2 text-sm">Titular: <strong>{{ $store->legal_name ?: 'sin registrar' }}</strong> · {{ $store->document_type }} {{ $store->document_number }}
        @if ($store->rut_path) · <a class="text-brand-600 hover:underline" href="{{ route('admin.stores.rut', $store) }}">Descargar RUT</a>@else · <span class="text-amber-700">sin RUT</span>@endif</p>
    <form method="post" action="{{ route('admin.stores.verify', $store) }}" class="mt-3 flex items-center gap-3 text-sm">
        @csrf
        <input type="hidden" name="verified" value="{{ $store->isVerified() ? 0 : 1 }}">
        <span>{{ $store->isVerified() ? 'Verificada desde el '.fecha($store->verified_at, 'd/m/Y') : 'No verificada' }}</span>
        <button class="btn-ghost btn-sm">{{ $store->isVerified() ? 'Quitar verificación' : 'Marcar como verificada' }}</button>
    </form>

    <div class="mt-4 border-t border-slate-100 pt-4 text-sm">
        @if ($approved)
            <p>Cuenta vigente: <strong>{{ $approved->bank }} · {{ $approved->account_type }} {{ $approved->account_number }}</strong><br>Titular: {{ $approved->holder_name }} ({{ $approved->holder_document_type }} {{ $approved->holder_document_number }})</p>
        @else
            <p class="text-amber-700">Sin cuenta aprobada: las liquidaciones de esta tienda están retenidas.</p>
        @endif
        @if ($pending)
            <div class="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                <p class="font-semibold text-blue-900">Cambio de cuenta en revisión (enviado el {{ fecha($pending->created_at) }} desde {{ $pending->submitted_ip }})</p>
                <p class="mt-1">{{ $pending->bank }} · {{ $pending->account_type }} {{ $pending->account_number }}<br>Titular: {{ $pending->holder_name }} ({{ $pending->holder_document_type }} {{ $pending->holder_document_number }})</p>
                @if ($pending->holder_document_number !== $store->document_number)
                    <p class="mt-2 text-red-700">Atención: el documento del titular no coincide con el de la tienda.</p>
                @endif
                <p class="mt-2 text-xs text-slate-600">Antes de aprobar, confírmala con el vendedor por un canal conocido (no respondiendo a un mensaje nuevo).</p>
                <div class="mt-3 flex flex-wrap gap-3">
                    <form method="post" action="{{ route('admin.stores.bank.approve', [$store, $pending]) }}">@csrf<button class="btn-primary btn-sm">Aprobar cuenta</button></form>
                    <form method="post" action="{{ route('admin.stores.bank.reject', [$store, $pending]) }}" class="flex gap-2">@csrf<input class="input" name="note" placeholder="Motivo del rechazo" required><button class="btn-ghost btn-sm">Rechazar</button></form>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
