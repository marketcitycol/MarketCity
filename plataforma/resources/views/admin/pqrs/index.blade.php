@extends('layouts.admin', ['title' => 'PQRS'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">PQRS</h1>
        <p class="text-sm text-slate-500">{{ $openCount }} abiertas @if ($overdueCount)· <span class="font-semibold text-red-700">{{ $overdueCount }} vencidas</span>@endif · plazo de respuesta 15 días hábiles</p>
    </div>
    <form method="get" class="flex flex-wrap gap-2">
        <input class="input w-56" name="q" value="{{ request('q') }}" placeholder="Radicado, correo, pedido o nombre">
        <select class="input w-auto" name="estado" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <option value="abiertas" @selected(request('estado') === 'abiertas')>Abiertas</option>
            @foreach (\App\Models\Pqr::STATUSES as $k => $v)<option value="{{ $k }}" @selected(request('estado') === $k)>{{ $v }}</option>@endforeach
        </select>
        <select class="input w-auto" name="tipo" onchange="this.form.submit()">
            <option value="">Todos los tipos</option>
            @foreach (\App\Models\Pqr::TYPES as $k => $v)<option value="{{ $k }}" @selected(request('tipo') === $k)>{{ $v }}</option>@endforeach
        </select>
        <button class="btn-ghost">Buscar</button>
    </form>
</div>
<div class="card mt-4 overflow-x-auto">
    @if ($pqrs->isEmpty())
        <p class="p-6 text-center text-sm text-slate-500">No hay solicitudes.</p>
    @else
    <table class="table-std">
        <thead><tr><th>Radicado</th><th>Tipo</th><th>Cliente</th><th>Pedido</th><th>Estado</th><th>Vence</th></tr></thead>
        <tbody>
        @foreach ($pqrs as $p)
            <tr>
                <td><a href="{{ route('admin.pqrs.show', $p) }}" class="font-medium text-brand-600 hover:underline">{{ $p->radicado }}</a><br><span class="text-xs text-slate-500">{{ fecha($p->created_at, 'd/m/Y') }}</span></td>
                <td>{{ $p->typeLabel() }}@if ($p->type === 'retracto' && $p->within_term === false)<br><span class="text-xs font-semibold text-amber-700">fuera de plazo</span>@endif</td>
                <td>{{ $p->name }}<br><span class="text-xs text-slate-500">{{ $p->email }}</span></td>
                <td>{{ $p->order_number ?: '—' }}</td>
                <td><span class="badge {{ $p->isOpen() ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700' }}">{{ $p->statusLabel() }}</span></td>
                <td class="{{ $p->isOverdue() ? 'font-semibold text-red-700' : '' }}">{{ $p->due_at ? fecha($p->due_at, 'd/m/Y') : '' }}{{ $p->isOverdue() ? ' · vencida' : '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
<div class="mt-4">{{ $pqrs->links() }}</div>
@endsection
