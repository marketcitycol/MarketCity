@extends('account.layout', ['pageTitle' => 'Hola, '.\Illuminate\Support\Str::before($user->name, ' ')])

@section('account')
<p class="text-sm text-slate-500">{{ $user->email }}</p>
@unless ($user->email_verified_at)
    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <p>Confirma tu correo para ver aquí también los pedidos que hiciste sin iniciar sesión.</p>
        <form method="post" action="{{ route('verification.send') }}">@csrf<button class="btn-ghost btn-sm">Enviarme el enlace</button></form>
    </div>
@endunless
<div class="mt-4 grid gap-3 sm:grid-cols-3">
    @foreach ([['Pedidos', $counts['pedidos'], 'account.orders'], ['Direcciones', $counts['direcciones'], 'account.addresses'], ['Favoritos', $counts['favoritos'], 'account.favorites']] as [$label, $n, $route])
        <a href="{{ route($route) }}" class="card p-4 hover:border-brand-300"><span class="block text-2xl font-bold">{{ $n }}</span><span class="text-sm text-slate-500">{{ $label }}</span></a>
    @endforeach
</div>
<h2 class="mt-8 font-semibold">Mis pedidos recientes</h2>
@if ($orders->isEmpty())
    <div class="card mt-3 p-8 text-center text-sm text-slate-500">Aún no tienes pedidos. <a href="{{ route('shop') }}" class="text-brand-600 hover:underline">Ir a la tienda</a></div>
@else
    <div class="mt-3">@include('account._orders-table')</div>
    @if ($counts['pedidos'] > $orders->count())<p class="mt-2 text-sm"><a href="{{ route('account.orders') }}" class="text-brand-600 hover:underline">Ver todos los pedidos</a></p>@endif
@endif
<div class="mt-8 grid gap-3 text-sm sm:grid-cols-2">
    <a href="{{ route('account.security') }}" class="card p-4 hover:border-brand-300"><span class="font-semibold">Cambiar contraseña</span><span class="block text-slate-500">y eliminar tu cuenta</span></a>
    <a href="{{ route('account.notifications') }}" class="card p-4 hover:border-brand-300"><span class="font-semibold">Mensajes de ofertas</span><span class="block text-slate-500">Correo y WhatsApp, cuando quieras</span></a>
</div>
@endsection
