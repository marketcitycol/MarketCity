@extends('layouts.admin', ['title' => 'Plugins'])
@section('content')
<h1 class="text-2xl font-bold">Plugins</h1>
<p class="mt-1 text-sm text-slate-500">Piezas de la identidad de la tienda. Vienen con el contenido de marketcity.co; actívalas, desactívalas o edítalas sin tocar código.</p>
<div class="mt-5 grid gap-4 lg:grid-cols-2">
@foreach ($plugins as $key => $p)
    @php($on = \App\Support\Plugins::enabled($key))
    <section class="card flex flex-col p-5">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h2 class="font-semibold">{{ $p['name'] }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $p['description'] }}</p>
            </div>
            <span class="badge {{ $on ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $on ? 'Activo' : 'Inactivo' }}</span>
        </div>
        <div class="mt-4 flex gap-2">
            <a href="{{ route('admin.plugins.edit', $key) }}" class="btn-primary btn-sm">Editar</a>
            <form method="post" action="{{ route('admin.plugins.toggle', $key) }}">@csrf<button class="btn-ghost btn-sm">{{ $on ? 'Desactivar' : 'Activar' }}</button></form>
        </div>
    </section>
@endforeach
</div>
@endsection
