@extends('account.layout', ['pageTitle' => 'Mis direcciones'])

@section('account')
<datalist id="cities">@foreach (collect(config('colombia.departments'))->flatten()->sort() as $c)<option value="{{ $c }}">@endforeach</datalist>
@if ($errors->any())<p class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</p>@endif
<div class="space-y-3">
    @forelse ($addresses as $a)
        <details class="card p-4 text-sm">
            <summary class="flex cursor-pointer flex-wrap items-center justify-between gap-2">
                <span><span class="font-semibold">{{ $a->label ?: 'Dirección' }}</span>@if ($a->is_default) <span class="badge bg-brand-100 text-brand-700">Principal</span>@endif
                    <span class="block text-slate-600">{{ $a->oneLine() }}</span></span>
                <span class="text-xs text-brand-600">Editar</span>
            </summary>
            <form method="post" action="{{ route('account.addresses.update', $a) }}" class="mt-4">
                @csrf @method('put')
                @include('account._address-fields', ['address' => $a, 'prefix' => 'a'.$a->id])
                <button class="btn-primary btn-sm mt-3">Guardar cambios</button>
            </form>
            <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-100 pt-3">
                @unless ($a->is_default)
                    <form method="post" action="{{ route('account.addresses.default', $a) }}">@csrf<button class="btn-ghost btn-sm">Hacer principal</button></form>
                @endunless
                <form method="post" action="{{ route('account.addresses.destroy', $a) }}">@csrf @method('delete')<button class="btn-ghost btn-sm text-red-600">Borrar</button></form>
            </div>
        </details>
    @empty
        <p class="card p-6 text-center text-sm text-slate-500">Aún no tienes direcciones. Tu primera compra con sesión guarda la dirección sola, o agrégala aquí.</p>
    @endforelse
</div>
@if ($addresses->count() < 10)
<details class="card mt-4 p-4" @if ($addresses->isEmpty() || $errors->any()) open @endif>
    <summary class="cursor-pointer text-sm font-semibold">Agregar una dirección</summary>
    <form method="post" action="{{ route('account.addresses.store') }}" class="mt-4">
        @csrf
        @include('account._address-fields', ['address' => null, 'prefix' => 'n'])
        <button class="btn-primary btn-sm mt-3">Guardar dirección</button>
    </form>
</details>
@endif
@endsection
