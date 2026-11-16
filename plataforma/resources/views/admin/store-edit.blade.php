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
@endsection
