@extends('layouts.shop', ['title' => 'Tiendas'])

@section('content')
<h1 class="text-2xl font-bold">Tiendas en MarketCity</h1>
<p class="text-sm text-slate-500">{{ $stores->count() }} tiendas activas.</p>
<div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
    @foreach ($stores as $store) @include('partials.store-card') @endforeach
</div>
<div class="card mt-8 flex flex-wrap items-center justify-between gap-4 p-6">
    <div><p class="font-semibold">¿Tienes una tienda?</p><p class="text-sm text-slate-500">Vende en MarketCity y llega a todo el país.</p></div>
    <a href="{{ route('sell') }}" class="btn-primary">Quiero vender</a>
</div>
@endsection
