@extends('layouts.shop', ['title' => 'Crear cuenta'])

@section('content')
<div class="mx-auto max-w-sm">
    <h1 class="text-2xl font-bold">Crea tu cuenta</h1>
    <form method="post" action="{{ route('register') }}" class="card mt-5 space-y-4 p-5">
        @csrf
        <div><label class="label" for="name">Nombre completo</label><input class="input" id="name" name="name" value="{{ old('name') }}" required></div>
        <div><label class="label" for="email">Correo</label><input class="input" type="email" id="email" name="email" value="{{ old('email', $suggestedEmail ?? '') }}" required autocomplete="email"></div>
        <div><label class="label" for="password">Contraseña (mínimo 8)</label><input class="input" type="password" id="password" name="password" required minlength="8"></div>
        <div><label class="label" for="password_confirmation">Repite la contraseña</label><input class="input" type="password" id="password_confirmation" name="password_confirmation" required></div>
        <label class="flex items-start gap-2 text-xs text-slate-600"><input type="checkbox" name="terms" value="1" required class="mt-0.5"> <span>Autorizo el tratamiento de mis datos personales según la política de privacidad.</span></label>
        <button class="btn-primary w-full">Crear cuenta</button>
    </form>
</div>
@endsection
