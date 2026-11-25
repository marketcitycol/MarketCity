@extends('layouts.shop', ['title' => 'Mi cuenta'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <div><h1 class="text-2xl font-bold">Hola, {{ $user->name }}</h1><p class="text-sm text-slate-500">{{ $user->email }}</p></div>
    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn-ghost">Cerrar sesión</button></form>
</div>
@unless ($user->email_verified_at)
    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p>Confirma tu correo para ver aquí también los pedidos que hiciste sin iniciar sesión.</p>
        <form method="post" action="{{ route('verification.send') }}">@csrf<button class="btn-ghost btn-sm">Enviarme el enlace</button></form>
    </div>
@endunless
<h2 class="mt-8 font-semibold">Mis pedidos</h2>
@if ($orders->isEmpty())
    <div class="card mt-3 p-8 text-center text-sm text-slate-500">Aún no tienes pedidos. <a href="{{ route('shop') }}" class="text-brand-600 hover:underline">Ir a la tienda</a></div>
@else
<div class="card mt-3 overflow-x-auto">
    <table class="table-std">
        <thead><tr><th>Pedido</th><th>Fecha</th><th>Pago</th><th>Estado</th><th class="text-right">Total</th></tr></thead>
        <tbody>
        @foreach ($orders as $o)
            <tr>
                <td><a href="{{ route('orders.show', [$o, 't' => $o->access_token]) }}" class="font-medium text-brand-600 hover:underline">{{ $o->number }}</a></td>
                <td>{{ fecha($o->created_at, 'd/m/Y') }}</td>
                <td>{{ $o->paymentStatusLabel() }}</td>
                <td>{{ $o->sellerOrders->map->statusLabel()->unique()->join(', ') }}</td>
                <td class="text-right">{{ cop($o->total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
<details class="card mt-8 p-5" @if ($errors->hasAny(['current_password', 'password'])) open @endif>
    <summary class="cursor-pointer font-semibold">Cambiar contraseña</summary>
    <form method="post" action="{{ route('password.change') }}" class="mt-4 max-w-sm space-y-4">
        @csrf
        @method('put')
        <input type="email" name="email" value="{{ $user->email }}" autocomplete="username" class="hidden" readonly>
        <div><label class="label" for="current_password">Contraseña actual</label><input class="input" type="password" id="current_password" name="current_password" required autocomplete="current-password"></div>
        <div><label class="label" for="new_password">Contraseña nueva (mínimo 8 caracteres)</label><input class="input" type="password" id="new_password" name="password" required minlength="8" autocomplete="new-password"></div>
        <div><label class="label" for="new_password_confirmation">Repítela</label><input class="input" type="password" id="new_password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
        <button class="btn-primary">Guardar contraseña nueva</button>
        <p class="text-xs text-slate-500">Se cierra la sesión en los demás equipos. ¿No recuerdas la actual? Cierra sesión y usa «¿Olvidaste tu contraseña?».</p>
    </form>
</details>
@endsection
