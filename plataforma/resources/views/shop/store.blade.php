@extends('layouts.shop', ['title' => $store->name])
@section('canonical', route('store', $store))

@section('content')
<section class="card flex flex-wrap items-center gap-5 p-6">
    <span class="flex h-20 w-20 items-center justify-center rounded-full bg-brand-100 text-2xl font-bold text-brand-700">{{ $store->initials() }}</span>
    <div class="flex-1">
        <h1 class="text-2xl font-bold">{{ $store->name }}</h1>
        <p class="text-sm text-slate-500">{{ $store->city }}, {{ $store->department }}</p>
        @if ($store->description)<p class="mt-1 text-sm">{{ $store->description }}</p>@endif
    </div>
    <div class="text-sm text-slate-600">
        <p>🚚 {{ $store->free_shipping ? 'Envío gratis' : 'Envío '.cop($store->shipping_fee) }}</p>
        @if ($store->pickup_enabled)<p>🏬 Recogida en tienda</p>@endif
    </div>
</section>
<div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-4">
    @foreach ($products as $product) @include('partials.product-card') @endforeach
</div>
<div class="mt-6">{{ $products->links() }}</div>
@endsection
