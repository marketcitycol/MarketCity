@extends('layouts.admin', ['title' => 'Mensajes'])

@section('content')
<h1 class="text-2xl font-bold">Mensajes</h1>
<p class="mt-1 text-sm text-slate-500">Permisos de correo y WhatsApp que dan los clientes en el pago (Ley 1581 y Ley 2300) y recordatorios de carritos abandonados. Solo se escribe a quien marcó la casilla, en horario legal y máximo una vez al día.</p>

<div class="mt-4 grid gap-3 sm:grid-cols-5">
    @foreach (['abiertos' => 'Carritos abiertos', 'recuperados' => 'Recuperados', 'valor_recuperado' => 'Valor recuperado', 'autorizaciones' => 'Autorizaciones', 'bajas' => 'Bajas'] as $k => $label)
        <div class="card p-4"><p class="text-xs text-slate-500">{{ $label }}</p><p class="text-xl font-bold">{{ $k === 'valor_recuperado' ? cop($stats[$k]) : $stats[$k] }}</p></div>
    @endforeach
</div>

<form method="post" action="{{ route('admin.messages.update') }}" class="card mt-4 grid gap-4 p-5 sm:grid-cols-3">
    @csrf @method('put')
    <div><label class="label" for="mode">Recordatorios de carrito</label>
        <select class="input" id="mode" name="mode">
            @foreach (['off' => 'Apagado (no guarda ni envía)', 'test' => 'Prueba (solo correos de prueba y administradores)', 'public' => 'Público'] as $k => $v)<option value="{{ $k }}" @selected($mode === $k)>{{ $v }}</option>@endforeach
        </select></div>
    <div class="sm:col-span-2"><label class="label" for="test_emails">Correos de prueba (separados por coma)</label><input class="input" id="test_emails" name="test_emails" value="{{ $settings['test_emails'] }}"></div>
    <div><label class="label" for="email_hour_cap">Correos publicitarios por hora</label><input class="input" type="number" id="email_hour_cap" name="email_hour_cap" value="{{ $settings['email_hour_cap'] }}"></div>
    <div><label class="label" for="email_day_cap">Correos publicitarios por día</label><input class="input" type="number" id="email_day_cap" name="email_day_cap" value="{{ $settings['email_day_cap'] }}"></div>
    <div><label class="label" for="whatsapp_day_cap">WhatsApp por día</label><input class="input" type="number" id="whatsapp_day_cap" name="whatsapp_day_cap" value="{{ $settings['whatsapp_day_cap'] }}"></div>
    <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="third" value="1" @checked($settings['third'])> Tercer recordatorio a las 72 horas</label>
    <div><button class="btn-primary w-full">Guardar</button></div>
</form>

<section class="card mt-6 overflow-x-auto">
    <h2 class="px-4 pt-4 font-semibold">Carritos guardados (últimos 100)</h2>
    <table class="table-std mt-2">
        <thead><tr><th>Fecha</th><th>Cliente</th><th>Canal</th><th>Total</th><th>Recordatorios</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($carts as $c)
            <tr>
                <td>{{ fecha($c->created_at) }}</td>
                <td>{{ $c->name ?: '—' }}<br><span class="text-xs text-slate-500">{{ $c->channel === 'email' ? $c->email : '+'.$c->phone }}</span></td>
                <td>{{ $c->channel === 'email' ? 'Correo' : 'WhatsApp' }}</td>
                <td>{{ cop($c->total) }}</td>
                <td>{{ $c->reminders_sent }}@if ($c->next_reminder_at) · próximo {{ fecha($c->next_reminder_at) }}@endif</td>
                <td>{{ $c->statusLabel() }}@if ($c->stop_reason) ({{ $c->stop_reason }})@endif</td>
                <td class="text-right">
                    @if ($whatsapp[$c->id])
                        <a href="{{ $whatsapp[$c->id] }}" target="_blank" rel="noopener" class="btn-ghost btn-sm">Abrir WhatsApp</a>
                        <form method="post" action="{{ route('admin.messages.whatsapp', $c) }}" class="inline">@csrf<button class="btn-primary btn-sm">Marcar enviado</button></form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-slate-500">No hay carritos guardados.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>

<section class="card mt-6 overflow-x-auto">
    <div class="flex flex-wrap items-end justify-between gap-3 px-4 pt-4">
        <h2 class="font-semibold">Registro de permisos (últimos 200)</h2>
        <div class="flex flex-wrap items-end gap-2">
            <form method="post" action="{{ route('admin.messages.revoke') }}" class="flex items-end gap-2">@csrf
                <div><label for="contact" class="label">Dar de baja (correo o celular)</label><input id="contact" name="contact" class="input" required></div>
                <button class="btn-ghost">Dar de baja</button>
            </form>
            <a href="{{ route('admin.messages.csv') }}" class="btn-ghost">Descargar CSV</a>
        </div>
    </div>
    <table class="table-std mt-2">
        <thead><tr><th>Fecha</th><th>Contacto</th><th>Canal</th><th>Finalidad</th><th>Autoriza</th><th>Origen</th></tr></thead>
        <tbody>
        @forelse ($consents as $r)
            <tr><td>{{ fecha($r->created_at) }}</td><td>{{ $r->contact }}</td><td>{{ $r->channel }}</td><td>{{ $r->purpose }}</td><td>{{ $r->granted ? 'Sí' : 'Baja' }}</td><td>{{ $r->source }}</td></tr>
        @empty
            <tr><td colspan="6" class="text-center text-slate-500">Sin registros todavía.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
