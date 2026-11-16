@extends('layouts.seller', ['title' => 'Pedidos'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Pedidos</h1>
    <div class="flex flex-wrap gap-1 text-sm">
        <a href="{{ route('seller.orders') }}" class="rounded-full px-3 py-1 {{ request('estado') ? 'hover:bg-white' : 'bg-white font-semibold' }}">Todos</a>
        @foreach (\App\Models\SellerOrder::STATUSES as $k => $v)
            <a href="{{ route('seller.orders', ['estado' => $k]) }}" class="rounded-full px-3 py-1 {{ request('estado') === $k ? 'bg-white font-semibold' : 'hover:bg-white' }}">{{ $v }}</a>
        @endforeach
    </div>
</div>
<div class="card mt-4">@include('seller.orders._table')</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
