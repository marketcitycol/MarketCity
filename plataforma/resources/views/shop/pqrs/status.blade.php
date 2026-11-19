@extends('layouts.shop', ['title' => 'Estado de tu solicitud'])

@section('content')
<div class="mx-auto max-w-xl">
    @if (! $pqr)
        <h1 class="text-2xl font-bold">Consulta tu solicitud</h1>
        <p class="mt-2 text-sm text-slate-500">Escribe tu radicado y tu correo en la <a href="{{ route('pqrs.create') }}#consultar" class="text-brand-600 underline">página de PQRS</a>.</p>
    @else
        @if ($justFiled)
            <p class="text-sm font-semibold text-emerald-700">¡Listo! Radicamos tu solicitud.</p>
        @endif
        <h1 class="mt-1 text-2xl font-bold">Radicado {{ $pqr->radicado }}</h1>
        <div class="card mt-5 space-y-2 p-5 text-sm">
            <p><span class="text-slate-500">Tipo:</span> {{ $pqr->typeLabel() }}</p>
            @if ($pqr->order_number)<p><span class="text-slate-500">Pedido:</span> {{ $pqr->order_number }}</p>@endif
            <p><span class="text-slate-500">Radicada:</span> {{ fecha($pqr->created_at) }}</p>
            <p><span class="text-slate-500">Estado:</span> <strong>{{ $pqr->statusLabel() }}</strong></p>
            @if ($pqr->isOpen())<p><span class="text-slate-500">Te respondemos a más tardar:</span> {{ fecha($pqr->due_at, 'd/m/Y') }}</p>@endif
            @if ($pqr->response)
                <div class="mt-3 rounded-xl bg-slate-50 p-4 whitespace-pre-line">{{ $pqr->response }}</div>
                <p class="text-xs text-slate-500">Respondida el {{ fecha($pqr->responded_at) }}</p>
            @endif
        </div>
        @if ($justFiled)<p class="mt-4 text-sm text-slate-600">Te enviamos el radicado a tu correo. Guárdalo para consultar el estado.</p>@endif
    @endif
</div>
@endsection
