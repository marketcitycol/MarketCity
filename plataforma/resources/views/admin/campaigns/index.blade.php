@extends('layouts.admin', ['title' => 'Campañas'])

@section('content')
<div class="flex items-center justify-between">
    <h1 class="text-2xl font-bold">Campañas</h1>
    <a href="{{ route('admin.campaigns.create') }}" class="btn-primary">Nueva campaña</a>
</div>
<p class="mt-1 text-sm text-slate-500">Precio programado por fechas (hora de Colombia), tope de unidades por producto y precio con correo. El precio vuelve solo a la normalidad al terminar o al llenarse el tope.</p>
<div class="card mt-4 overflow-x-auto">
<table class="table-std">
    <thead><tr><th>Campaña</th><th>Fechas</th><th>Tope</th><th>Productos</th><th>Precio con correo</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse ($campaigns as $c)
        <tr>
            <td class="font-medium"><a class="text-brand-600 hover:underline" href="{{ route('campaign', $c) }}" target="_blank">{{ $c->name }}</a><br><span class="text-xs text-slate-500">/campana/{{ $c->slug }}</span></td>
            <td>{{ fecha($c->starts_at) }}<br>{{ fecha($c->ends_at) }}</td>
            <td>{{ $c->unit_cap ? $c->unit_cap.' u.' : 'Sin tope' }}</td>
            <td>{{ $c->items_count }}</td>
            <td>{{ $c->email_price_enabled ? 'Encendido' : 'Apagado' }}</td>
            <td><span class="badge {{ $c->isRunning() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ! $c->active ? 'Apagada' : ($c->isRunning() ? 'En curso' : ($c->starts_at->isFuture() ? 'Programada' : 'Terminada')) }}</span></td>
            <td class="text-right"><a href="{{ route('admin.campaigns.edit', $c) }}" class="btn-ghost btn-sm">Editar</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-center text-slate-500">Todavía no hay campañas.</td></tr>
    @endforelse
    </tbody>
</table>
</div>

<form method="post" action="{{ route('admin.campaigns.cutoff') }}" class="card mt-6 grid gap-3 p-5 sm:grid-cols-3">
    @csrf @method('put')
    <p class="text-sm text-slate-600 sm:col-span-3">Hora de corte de despacho: en las fichas sin campaña muestra «Pide antes de las X y, con el pago confirmado, lo despachamos hoy». Solo días hábiles y sábados; nunca domingos ni festivos. Vacío = no se muestra.</p>
    <div><label class="label" for="hour">Lunes a viernes (hora, 6 a 22)</label><input class="input" type="number" min="6" max="22" id="hour" name="hour" value="{{ $cutoff }}"></div>
    <div><label class="label" for="saturday_hour">Sábados (vacío = no despacha)</label><input class="input" type="number" min="6" max="22" id="saturday_hour" name="saturday_hour" value="{{ $cutoffSaturday }}"></div>
    <div class="flex items-end"><button class="btn-primary w-full">Guardar hora de corte</button></div>
</form>
@endsection
