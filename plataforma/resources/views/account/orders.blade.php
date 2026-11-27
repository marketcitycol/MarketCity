@extends('account.layout', ['pageTitle' => 'Mis pedidos'])

@section('account')
@if ($orders->isEmpty())
    <div class="card p-8 text-center text-sm text-slate-500">Aún no tienes pedidos. <a href="{{ route('shop') }}" class="text-brand-600 hover:underline">Ir a la tienda</a></div>
@else
    @include('account._orders-table')
    <div class="mt-4">{{ $orders->links() }}</div>
@endif
@unless ($user->email_verified_at)
    <p class="mt-4 text-xs text-slate-500">¿Falta un pedido que hiciste sin iniciar sesión? Confirma tu correo desde el inicio de Mi cuenta y aparecerá aquí.</p>
@endunless
@endsection
