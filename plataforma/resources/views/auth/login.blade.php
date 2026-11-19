@extends('layouts.shop', ['title' => 'Ingresar'])

@section('content')
<div class="mx-auto max-w-sm">
    <h1 class="text-2xl font-bold">Ingresar</h1>
    <form method="post" action="{{ route('login') }}" class="card mt-5 space-y-4 p-5">
        @csrf
        <div><label class="label" for="email">Correo</label><input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
        <div><label class="label" for="password">Contraseña</label><input class="input" type="password" id="password" name="password" required autocomplete="current-password"></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> Recordarme</label>
        <button class="btn-primary w-full">Ingresar</button>
        <p class="text-center text-sm"><a href="{{ route('password.request') }}" class="text-brand-600 hover:underline">¿Olvidaste tu contraseña o compraste en la tienda anterior? Créala aquí</a></p>
        <p class="text-center text-sm text-slate-500">¿No tienes cuenta? <a href="{{ route('register') }}" class="text-brand-600 hover:underline">Regístrate</a></p>
    </form>
    @if (config('marketcity.show_demo_accounts'))
    <div class="mt-4 rounded-lg border border-dashed border-slate-300 p-4 text-xs text-slate-500">
        <p class="font-semibold">Cuentas de prueba (contraseña <code>marketcity</code>)</p>
        <p>Backoffice: admin@marketcity.test · Vendedor: vendedor1@marketcity.test · Cliente: cliente@marketcity.test</p>
    </div>
    @endif
</div>
@endsection
