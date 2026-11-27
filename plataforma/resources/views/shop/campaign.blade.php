@extends('layouts.shop')
@section('title', $campaign->name)
@section('description', 'Productos de '.$campaign->name.' en MarketCity: precios hasta el '.fecha($campaign->ends_at, 'd/m/Y').' o hasta agotar las unidades de campaña.')
@section('canonical', route('campaign', $campaign))

@section('content')
<header class="rounded-2xl bg-ink-900 p-6 text-white sm:p-8">
    <p class="text-xs font-bold tracking-widest text-accent-400">{{ $campaign->badge ?: 'CAMPAÑA' }}</p>
    <h1 class="mt-1 text-3xl font-extrabold">{{ $campaign->name }}</h1>
    @if ($campaign->isRunning())
        <p class="mt-2 text-sm">Termina en <span class="font-mono font-bold" data-countdown="{{ $campaign->ends_at->getTimestamp() }}">{{ (int) now()->diffInHours($campaign->ends_at) }} h</span>@if ($campaign->unit_cap) · máximo {{ $campaign->unit_cap }} unidades por producto a precio de campaña @endif</p>
    @elseif ($campaign->starts_at->isFuture())
        <p class="mt-2 text-sm">Empieza el {{ fecha($campaign->starts_at, 'd/m/Y g:i a') }}.</p>
    @else
        <p class="mt-2 text-sm">Esta campaña terminó el {{ fecha($campaign->ends_at, 'd/m/Y') }}.</p>
    @endif
</header>

@if ($products->isEmpty())
    <p class="card mt-6 p-8 text-center text-slate-600">No hay productos a precio de campaña en este momento. <a href="{{ route('offers') }}" class="text-brand-600 hover:underline">Mira las ofertas</a>.</p>
@else
    <p class="mt-6 text-sm text-slate-500">{{ $products->count() }} productos</p>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-5">
        @foreach ($products as $product) @include('partials.product-card', ['product' => $product]) @endforeach
    </div>
@endif
@endsection
