@extends('layouts.shop', ['title' => 'Rastrear pedido'])

@section('content')
<div class="mx-auto max-w-md">
    <h1 class="text-2xl font-bold">Rastrea tu pedido</h1>
    <p class="mt-1 text-sm text-slate-500">Escribe el número del pedido y el correo con el que compraste.</p>
    <form method="post" action="{{ route('track.lookup') }}" class="card mt-5 space-y-4 p-5">
        @csrf
        <div><label class="label" for="number">Número de pedido</label><input class="input" id="number" name="number" value="{{ old('number') }}" placeholder="MC-010001" required></div>
        <div><label class="label" for="email">Correo</label><input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required></div>
        <button class="btn-primary w-full">Ver mi pedido</button>
    </form>
</div>
@endsection
