@extends('layouts.shop', ['title' => 'Crear o recuperar contraseña'])

@section('content')
<div class="mx-auto max-w-sm">
    <h1 class="text-2xl font-bold">Crear o recuperar tu contraseña</h1>
    <p class="mt-2 text-sm text-slate-600">Escribe tu correo y te enviamos un enlace. Si compraste en la tienda anterior de MarketCity, así creas tu contraseña para esta.</p>
    <form method="post" action="{{ route('password.email') }}" class="card mt-5 space-y-4 p-5">
        @csrf
        <div><label class="label" for="email">Correo</label><input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></div>
        <button class="btn-primary w-full">Enviar enlace</button>
        <p class="text-center text-sm"><a href="{{ route('login') }}" class="text-brand-600 hover:underline">Volver a ingresar</a></p>
    </form>
</div>
@endsection
