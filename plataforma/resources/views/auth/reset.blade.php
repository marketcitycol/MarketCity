@extends('layouts.shop', ['title' => 'Nueva contraseña'])

@section('content')
<div class="mx-auto max-w-sm">
    <h1 class="text-2xl font-bold">Tu contraseña nueva</h1>
    <form method="post" action="{{ route('password.update') }}" class="card mt-5 space-y-4 p-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div><label class="label" for="email">Correo</label><input class="input" type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username"></div>
        <div><label class="label" for="password">Contraseña nueva (mínimo 8 caracteres)</label><input class="input" type="password" id="password" name="password" required autocomplete="new-password"></div>
        <div><label class="label" for="password_confirmation">Repítela</label><input class="input" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"></div>
        <button class="btn-primary w-full">Guardar contraseña</button>
    </form>
</div>
@endsection
