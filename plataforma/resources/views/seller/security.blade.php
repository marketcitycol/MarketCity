@extends('layouts.seller', ['title' => 'Seguridad'])

@section('content')
<h1 class="text-2xl font-bold">Seguridad</h1>
<section class="card mt-4 max-w-xl p-5">
    <h2 class="font-semibold">Cambiar contraseña</h2>
    <form method="post" action="{{ route('password.change') }}" class="mt-4 space-y-4">
        @csrf @method('put')
        <input type="email" name="email" value="{{ $user->email }}" autocomplete="username" class="hidden" readonly>
        <div><label class="label" for="current_password">Contraseña actual</label><input class="input" type="password" id="current_password" name="current_password" required autocomplete="current-password">
            @error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="new_password">Contraseña nueva (mínimo 8 caracteres)</label><input class="input" type="password" id="new_password" name="password" required minlength="8" autocomplete="new-password">
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="new_password_confirmation">Repítela</label><input class="input" type="password" id="new_password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
        <button class="btn-primary">Guardar contraseña nueva</button>
    </form>
    <p class="mt-3 text-xs text-slate-500">Al cambiarla se cierra la sesión en los demás equipos. Usa una contraseña que no uses en otro sitio: con ella se manejan tus ventas y tu cuenta bancaria.</p>
</section>
@endsection
