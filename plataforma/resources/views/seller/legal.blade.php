@extends('layouts.seller', ['title' => 'Datos legales y pagos'])

@section('content')
<h1 class="text-2xl font-bold">Datos legales y pagos</h1>
<p class="mt-1 text-sm text-slate-500">Con estos datos MarketCity te paga tus ventas. @if ($store->isVerified())<span class="badge bg-emerald-100 text-emerald-700">Tienda verificada</span>@endif</p>

<div class="mt-5 grid gap-6 xl:grid-cols-2">
<section class="card p-5">
    <h2 class="font-semibold">Titular de la tienda</h2>
    <form method="post" action="{{ route('seller.legal.update') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
        @csrf @method('put')
        <div><label class="label" for="legal_name">Razón social o nombre completo</label><input class="input" id="legal_name" name="legal_name" value="{{ old('legal_name', $store->legal_name) }}" required></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="document_type">Documento</label><select class="input" id="document_type" name="document_type">@foreach (['NIT' => 'NIT', 'CC' => 'Cédula', 'CE' => 'Cédula de extranjería'] as $k => $v)<option value="{{ $k }}" @selected(old('document_type', $store->document_type) === $k)>{{ $v }}</option>@endforeach</select></div>
            <div><label class="label" for="document_number">Número</label><input class="input" id="document_number" name="document_number" value="{{ old('document_number', $store->document_number) }}" required></div>
        </div>
        <div><label class="label" for="rut">RUT (PDF o foto, máximo 5 MB)</label><input class="input" type="file" id="rut" name="rut" accept=".pdf,.jpg,.jpeg,.png">
            @if ($store->rut_path)<p class="mt-1 text-xs text-emerald-700">Ya subiste tu RUT. Sube otro solo si cambió.</p>@endif</div>
        <button class="btn-primary">Guardar datos legales</button>
    </form>
</section>

<section class="card p-5">
    <h2 class="font-semibold">Cuenta bancaria para tus pagos</h2>
    @if ($approved)
        <p class="mt-3 text-sm">Cuenta vigente: <strong>{{ $approved->bank }} · {{ $approved->account_type }} {{ $approved->maskedNumber() }}</strong><br><span class="text-slate-500">Titular: {{ $approved->holder_name }}</span></p>
    @else
        <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800">Aún no tienes una cuenta aprobada. Tus liquidaciones quedan en espera hasta que la registres y MarketCity la apruebe.</p>
    @endif
    @if ($pending)
        <p class="mt-3 rounded-lg bg-blue-50 px-3 py-2 text-sm text-blue-900">En revisión: {{ $pending->bank }} · {{ $pending->account_type }} {{ $pending->maskedNumber() }}, enviada el {{ fecha($pending->created_at) }}. Mientras tanto tus liquidaciones quedan en espera.</p>
    @endif
    <details class="mt-4" @if ($errors->any() && old('bank')) open @endif>
        <summary class="cursor-pointer text-sm font-medium text-brand-600">{{ $approved || $pending ? 'Cambiar la cuenta bancaria' : 'Registrar la cuenta bancaria' }}</summary>
        <p class="mt-2 text-xs text-slate-500">Por seguridad pedimos tu contraseña, te avisamos por correo y MarketCity revisa la cuenta antes de usarla.</p>
        <form method="post" action="{{ route('seller.legal.bank') }}" class="mt-3 grid gap-3 sm:grid-cols-2" autocomplete="off">
            @csrf
            <div><label class="label" for="bank">Banco</label><input class="input" id="bank" name="bank" value="{{ old('bank') }}" required></div>
            <div><label class="label" for="account_type">Tipo de cuenta</label><select class="input" id="account_type" name="account_type">@foreach (\App\Models\StoreBankAccount::TYPES as $t)<option @selected(old('account_type') === $t)>{{ $t }}</option>@endforeach</select></div>
            <div class="sm:col-span-2"><label class="label" for="account_number">Número de cuenta</label><input class="input" id="account_number" name="account_number" inputmode="numeric" required></div>
            <div class="sm:col-span-2"><label class="label" for="holder_name">Titular de la cuenta</label><input class="input" id="holder_name" name="holder_name" value="{{ old('holder_name', $store->legal_name) }}" required></div>
            <div><label class="label" for="holder_document_type">Documento del titular</label><select class="input" id="holder_document_type" name="holder_document_type">@foreach (['NIT', 'CC', 'CE'] as $t)<option @selected(old('holder_document_type', $store->document_type) === $t)>{{ $t }}</option>@endforeach</select></div>
            <div><label class="label" for="holder_document_number">Número</label><input class="input" id="holder_document_number" name="holder_document_number" value="{{ old('holder_document_number', $store->document_number) }}" required></div>
            <div class="sm:col-span-2"><label class="label" for="current_password">Tu contraseña actual</label><input class="input" type="password" id="current_password" name="current_password" required autocomplete="current-password"></div>
            <div class="sm:col-span-2"><button class="btn-primary">Enviar a revisión</button></div>
        </form>
    </details>
    @if ($history->isNotEmpty())
        <ul class="mt-4 space-y-1 text-xs text-slate-500">
            @foreach ($history as $h)<li>{{ fecha($h->created_at, 'd/m/Y') }} · {{ $h->bank }} {{ $h->maskedNumber() }} · {{ $h->statusLabel() }}@if ($h->review_note) · {{ $h->review_note }}@endif</li>@endforeach
        </ul>
    @endif
</section>
</div>
@endsection
