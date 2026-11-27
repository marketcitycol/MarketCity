@extends('layouts.shop', ['title' => 'Rastrear pedido'])

@section('content')
<div class="mx-auto max-w-md">
    <h1 class="text-2xl font-bold">Rastrea tu pedido</h1>
    <p class="mt-1 text-sm text-slate-500">Escribe el número del pedido y el correo o el celular con el que compraste.</p>
    <form method="post" action="{{ route('track.lookup') }}" class="card mt-5 space-y-4 p-5">
        @csrf
        <div><label class="label" for="number">Número de pedido</label><input class="input" id="number" name="number" value="{{ old('number') }}" placeholder="MC-010001" required maxlength="20" autocomplete="off"></div>
        <div><label class="label" for="contact">Correo o celular de la compra</label><input class="input" id="contact" name="contact" value="{{ old('contact') }}" required maxlength="120" autocomplete="email"></div>
        <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
        <button class="btn-primary w-full">Ver mi pedido</button>
    </form>
    <p class="mt-3 text-xs text-slate-500">Por tu privacidad, solo mostramos el pedido si el correo o el celular coinciden con los de la compra.</p>
</div>
@endsection
