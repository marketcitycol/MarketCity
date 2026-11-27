@extends('account.layout', ['pageTitle' => 'Mis consultas'])

@section('account')
<div class="flex flex-wrap items-center justify-between gap-2">
    <p class="text-sm text-slate-600">Peticiones, quejas, reclamos, retractos y reversiones que hiciste con esta cuenta o con tu correo confirmado.</p>
    <a href="{{ route('pqrs.create') }}" class="btn-primary btn-sm">Nueva consulta</a>
</div>
@if ($pqrs->isEmpty())
    <p class="card mt-4 p-8 text-center text-sm text-slate-500">No tienes consultas.</p>
@else
    <div class="card mt-4 overflow-x-auto">
        <table class="table-std">
            <thead><tr><th>Radicado</th><th>Tipo</th><th>Fecha</th><th>Estado</th><th>Respuesta</th></tr></thead>
            <tbody>
            @foreach ($pqrs as $p)
                <tr>
                    <td class="font-mono text-xs">{{ $p->radicado }}</td>
                    <td>{{ $p->typeLabel() }}</td>
                    <td>{{ fecha($p->created_at, 'd/m/Y') }}</td>
                    <td>{{ $p->statusLabel() }}</td>
                    <td class="max-w-xs text-sm whitespace-pre-line text-slate-600">{{ $p->response ? \Illuminate\Support\Str::limit($p->response, 300) : ($p->due_at ? 'Te respondemos a más tardar el '.fecha($p->due_at, 'd/m/Y') : '—') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
