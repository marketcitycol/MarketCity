@extends('layouts.admin', ['title' => $def['name']])
@section('content')
<p class="text-sm"><a href="{{ route('admin.plugins') }}" class="text-brand-600 hover:underline">← Plugins</a></p>
<h1 class="mt-1 text-2xl font-bold">{{ $def['name'] }}</h1>
<p class="mt-1 text-sm text-slate-500">{{ $def['description'] }} En los campos de varias líneas, escribe una opción por línea y separa sus partes con <code>|</code>.</p>
<form method="post" action="{{ route('admin.plugins.update', $key) }}" class="card mt-5 space-y-4 p-5">
    @csrf @method('put')
    <label class="flex items-center gap-2 text-sm font-semibold"><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" @checked($enabled)> Activo en la tienda</label>
    @foreach ($def['fields'] as $field => [$label, $type])
        <div>
            <label class="label" for="f-{{ $field }}">{{ $label }}</label>
            @if ($type === 'text')
                <input class="input" id="f-{{ $field }}" name="values[{{ $field }}]" value="{{ old('values.'.$field, $values[$field] ?? '') }}">
            @else
                <textarea class="input font-mono text-xs" id="f-{{ $field }}" name="values[{{ $field }}]" rows="{{ max(3, min(14, substr_count($values[$field] ?? '', "\n") + 2)) }}">{{ old('values.'.$field, $values[$field] ?? '') }}</textarea>
            @endif
        </div>
    @endforeach
    <div class="flex flex-wrap gap-2">
        <button class="btn-primary">Guardar</button>
        <a href="{{ route('home') }}" target="_blank" class="btn-ghost">Ver tienda</a>
    </div>
</form>
<form method="post" action="{{ route('admin.plugins.reset', $key) }}" class="mt-3">@csrf<button class="text-sm text-slate-500 hover:underline">Volver a los valores de marketcity.co</button></form>
@endsection
