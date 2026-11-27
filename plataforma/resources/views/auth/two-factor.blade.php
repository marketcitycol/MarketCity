@extends('layouts.shop', ['title' => 'Verificación'])

@section('content')
<div class="mx-auto max-w-sm">
    <h1 class="text-2xl font-bold">Verificación en dos pasos</h1>
    <form method="post" action="{{ route('two-factor.challenge') }}" class="card mt-5 space-y-4 p-5">
        @csrf
        <p class="text-sm text-slate-600">Abre tu app autenticadora y escribe el código de 6 dígitos de MarketCity. Si no tienes el celular, usa uno de tus códigos de recuperación.</p>
        <div><label class="label" for="code">Código</label><input class="input text-center text-lg tracking-widest" id="code" name="code" required autofocus autocomplete="one-time-code" inputmode="numeric" maxlength="20">
            @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <button class="btn-primary w-full">Verificar e ingresar</button>
        <p class="text-center text-xs text-slate-500">¿Perdiste el celular y los códigos? Escríbenos desde el correo de tu cuenta.</p>
    </form>
</div>
@endsection
