@extends('layouts.admin', ['title' => 'Configuración'])

@section('content')
<h1 class="text-2xl font-bold">Configuración</h1>

<div class="mt-5 grid gap-6 xl:grid-cols-2">
<section class="card p-5">
    <h2 class="font-semibold">Comisión, liquidación y pagos</h2>
    <p class="mt-1 text-sm text-slate-500">Valores generales. Lo negociado con cada comercio (comisión y medios de pago) se ajusta en <a href="{{ route('admin.stores') }}" class="text-brand-600 hover:underline">Vendedores</a>. Cada pedido guarda la regla con la que se calculó.</p>
    <form method="post" action="{{ route('admin.settings.update') }}" class="mt-4 space-y-4">
        @csrf @method('put')
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="commission_percent">Comisión (%)</label><input class="input" type="number" step="0.1" id="commission_percent" name="commission_percent" value="{{ $s('commission_percent', 15) }}"></div>
            <div><label class="label" for="commission_fixed">Valor fijo adicional por pedido (COP, opcional)</label><input class="input" type="number" id="commission_fixed" name="commission_fixed" value="{{ $s('commission_fixed', 0) }}"></div>
            <div><label class="label" for="settlement_business_days">Días hábiles para liquidar</label><input class="input" type="number" id="settlement_business_days" name="settlement_business_days" value="{{ $s('settlement_business_days', 10) }}"></div>
            <div><label class="label" for="installments">Cuotas de referencia en catálogo</label><input class="input" type="number" id="installments" name="installments" value="{{ $s('installments', 4) }}"></div>
            <div><label class="label" for="terms_version">Versión vigente de términos</label><input class="input" id="terms_version" name="terms_version" value="{{ $s('terms_version', '2026-11') }}"></div>
        </div>
        <fieldset>
            <legend class="label">Medios de pago activos</legend>
            @foreach ($gateways as $key => $g)
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="payment_methods[]" value="{{ $key }}" @checked(in_array($key, $enabled))> {{ $g->label() }}</label>
            @endforeach
        </fieldset>
        <button class="btn-primary">Guardar</button>
    </form>
</section>

<section class="card p-5">
    <h2 class="font-semibold">Transferencia bancaria</h2>
    <p class="mt-1 text-sm text-slate-500">El comprador ve estos datos en la página de gracias y en el correo del pedido. Se ofrece en el checkout solo cuando están completos. El pedido queda pendiente hasta que registres el pago en el pedido.</p>
    @unless (\App\Services\Payments\BankTransferGateway::isConfigured())
        <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">Faltan datos: hoy la transferencia no aparece en el checkout.</p>
    @endunless
    <form method="post" action="{{ route('admin.settings.transfer') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
        @csrf @method('put')
        <div><label class="label" for="transfer_bank">Banco</label><input class="input" id="transfer_bank" name="transfer_bank" value="{{ $s('transfer_bank') }}"></div>
        <div><label class="label" for="transfer_account_type">Tipo de cuenta</label>
            <select class="input" id="transfer_account_type" name="transfer_account_type"><option value="">Elige</option>@foreach (['Ahorros', 'Corriente'] as $t)<option @selected($s('transfer_account_type') === $t)>{{ $t }}</option>@endforeach</select></div>
        <div><label class="label" for="transfer_account_number">Número de cuenta</label><input class="input" id="transfer_account_number" name="transfer_account_number" inputmode="numeric" value="{{ $s('transfer_account_number') }}"></div>
        <div><label class="label" for="transfer_holder">Titular</label><input class="input" id="transfer_holder" name="transfer_holder" value="{{ $s('transfer_holder') }}"></div>
        <div><label class="label" for="transfer_holder_document">Documento del titular</label><input class="input" id="transfer_holder_document" name="transfer_holder_document" placeholder="CC 123… o NIT 900…" value="{{ $s('transfer_holder_document') }}"></div>
        <div><label class="label" for="transfer_deadline_hours">Horas para pagar antes de cancelar</label><input class="input" type="number" id="transfer_deadline_hours" name="transfer_deadline_hours" value="{{ $s('transfer_deadline_hours', 24) }}"></div>
        <div class="sm:col-span-2"><label class="label" for="transfer_instructions">Indicaciones adicionales (opcional, sin emojis)</label><textarea class="input" id="transfer_instructions" name="transfer_instructions" rows="2">{{ $s('transfer_instructions') }}</textarea></div>
        <div class="sm:col-span-2"><button class="btn-primary">Guardar datos de transferencia</button></div>
    </form>
</section>

<section class="card p-5">
    <h2 class="font-semibold">Titular del comercio</h2>
    <p class="mt-1 text-sm text-slate-500">Quien cobra ante las pasarelas y factura. Hoy es una persona natural; cuando la sociedad esté lista, agrégala y actívala. Los pedidos anteriores conservan su titular.</p>
    <ul class="mt-4 divide-y divide-slate-100 text-sm">
        @foreach ($entities as $e)
            <li class="flex items-center justify-between gap-3 py-3">
                <div>
                    <p class="font-medium">{{ $e->legal_name }} @if ($e->is_active)<span class="badge bg-emerald-100 text-emerald-700">Vigente</span>@endif</p>
                    <p class="text-xs text-slate-500">{{ $e->personTypeLabel() }} · {{ $e->document_type }} {{ $e->document_number }} @if ($e->active_from) · desde {{ fecha($e->active_from, 'd/m/Y') }}@endif @if ($e->active_until) hasta {{ fecha($e->active_until, 'd/m/Y') }}@endif</p>
                </div>
                @unless ($e->is_active)
                    <form method="post" action="{{ route('admin.entities.activate', $e) }}">@csrf<button class="btn-ghost btn-sm">Activar</button></form>
                @endunless
            </li>
        @endforeach
    </ul>
    <details class="mt-3">
        <summary class="cursor-pointer text-sm font-medium text-brand-600">Agregar titular</summary>
        <form method="post" action="{{ route('admin.entities.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2">
            @csrf
            <div class="sm:col-span-2"><label class="label" for="legal_name">Razón social o nombre</label><input class="input" id="legal_name" name="legal_name" required></div>
            <div><label class="label" for="person_type">Tipo</label><select class="input" id="person_type" name="person_type"><option value="juridica">Sociedad</option><option value="natural">Persona natural</option></select></div>
            <div><label class="label" for="document_type_e">Documento</label><select class="input" id="document_type_e" name="document_type"><option>NIT</option><option>CC</option></select></div>
            <div><label class="label" for="document_number_e">Número</label><input class="input" id="document_number_e" name="document_number" required></div>
            <div><label class="label" for="email_e">Correo</label><input class="input" type="email" id="email_e" name="email"></div>
            <div class="sm:col-span-2"><button class="btn-primary">Agregar</button></div>
        </form>
    </details>
</section>
</div>
@endsection
