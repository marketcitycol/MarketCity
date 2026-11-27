@extends('account.layout', ['pageTitle' => 'Mis datos'])

@section('account')
<form method="post" action="{{ route('account.details.update') }}" class="card max-w-xl space-y-4 p-5">
    @csrf @method('put')
    <div><label class="label" for="name">Nombre completo</label><input class="input" id="name" name="name" required maxlength="120" value="{{ old('name', $user->name) }}" autocomplete="name">
        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
    <div><label class="label" for="email">Correo</label><input class="input bg-slate-50" id="email" value="{{ $user->email }}" readonly>
        <p class="mt-1 text-xs text-slate-500">Para cambiar el correo escríbenos por <a href="{{ route('pqrs.create') }}" class="underline">PQRS</a>: lo confirmamos antes de cambiarlo.</p></div>
    <div><label class="label" for="phone">Celular</label><input class="input" id="phone" name="phone" inputmode="tel" value="{{ old('phone', $user->phone) }}" autocomplete="tel" placeholder="300 123 4567">
        @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label" for="document_type">Tipo de documento</label>
            <select class="input" id="document_type" name="document_type">
                @foreach (config('colombia.document_types') as $k => $v)<option value="{{ $k }}" @selected(old('document_type', $user->document_type ?? 'CC') === $k)>{{ $v }}</option>@endforeach
            </select></div>
        <div><label class="label" for="document_number">Número de documento</label><input class="input" id="document_number" name="document_number" inputmode="numeric" value="{{ old('document_number', $user->document_number) }}">
            @error('document_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
    </div>
    <p class="text-xs text-slate-500">Usamos estos datos para llenar el pago más rápido y para la factura. Política de datos: <a class="underline" href="{{ url('/politica-de-privacidad') }}">Política de privacidad</a>.</p>
    <button class="btn-primary">Guardar</button>
</form>
@endsection
