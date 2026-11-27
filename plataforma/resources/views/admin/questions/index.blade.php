@extends('layouts.admin', ['title' => 'Preguntas'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Preguntas al vendedor</h1>
    <div class="flex gap-1 text-sm">
        @foreach (['' => 'Todas', 'sin-responder' => 'Sin responder', 'ocultas' => 'Ocultas'] as $k => $v)
            <a href="{{ route('admin.questions', $k ? ['estado' => $k] : []) }}" class="rounded-full px-3 py-1 {{ (string) request('estado') === $k ? 'bg-white font-semibold' : 'hover:bg-white' }}">{{ $v }}</a>
        @endforeach
    </div>
</div>
<p class="mt-1 text-sm text-slate-500">Las tiendas responden desde su Seller Center. Aquí puedes ocultar una pregunta o respuesta que no deba publicarse.@if ($late) <strong class="text-amber-700">{{ $late }} {{ $late === 1 ? 'lleva' : 'llevan' }} más de 2 días sin respuesta.</strong>@endif</p>

<div class="card mt-4 overflow-x-auto">
    <table class="table-std">
        <thead><tr><th>Fecha</th><th>Tienda</th><th>Producto</th><th>Pregunta</th><th>Respuesta</th><th></th></tr></thead>
        <tbody>
        @forelse ($questions as $q)
            <tr class="{{ $q->hidden ? 'opacity-60' : '' }}">
                <td class="whitespace-nowrap">{{ fecha($q->created_at, 'd/m/Y') }}</td>
                <td>{{ $q->store->name }}</td>
                <td><a href="{{ route('product', $q->product) }}#preguntas" class="text-brand-600 hover:underline" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($q->product->name, 40) }}</a></td>
                <td class="max-w-xs">{{ $q->question }}<br><span class="text-xs text-slate-500">{{ $q->name }} · {{ $q->email }}</span></td>
                <td class="max-w-xs">{{ $q->answer ?? '—' }}</td>
                <td><form method="post" action="{{ route('admin.questions.toggle', $q) }}">@csrf<button class="btn-ghost btn-sm">{{ $q->hidden ? 'Mostrar' : 'Ocultar' }}</button></form></td>
            </tr>
        @empty
            <tr><td colspan="6" class="p-8 text-center text-slate-500">No hay preguntas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $questions->links() }}</div>
@endsection
