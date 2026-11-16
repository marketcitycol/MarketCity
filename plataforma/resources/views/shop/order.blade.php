@extends('layouts.shop', ['title' => 'Pedido '.$order->number])

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
            <h1 class="text-2xl font-bold">Pedido {{ $order->number }}</h1>
            <p class="text-sm text-slate-500">Hecho el {{ fecha($order->created_at) }} · {{ $order->paymentStatusLabel() }} · Total {{ cop($order->total) }}</p>
        </div>
    </div>

    @foreach ($order->sellerOrders as $so)
        <section class="card mt-5 p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="font-semibold">{{ $so->store->name }}</p>
                <span class="badge {{ $so->status === 'cancelado' ? 'bg-red-100 text-red-700' : 'bg-brand-100 text-brand-700' }}">{{ $so->statusLabel() }}</span>
            </div>
            @if ($so->status !== 'cancelado')
            <ol class="mt-4 grid grid-cols-4 gap-1 text-center text-xs">
                @php($pos = array_search($so->status, \App\Models\SellerOrder::FLOW))
                @foreach (\App\Models\SellerOrder::FLOW as $i => $st)
                    <li>
                        <div class="h-1.5 rounded-full {{ $i <= $pos ? 'bg-brand-600' : 'bg-slate-200' }}"></div>
                        <p class="mt-1 {{ $i <= $pos ? 'font-semibold text-brand-700' : 'text-slate-400' }}">{{ \App\Models\SellerOrder::STATUSES[$st] }}</p>
                    </li>
                @endforeach
            </ol>
            @endif
            @if ($so->tracking_number)
                <p class="mt-4 text-sm">Guía {{ $so->carrierName() }}: <strong>{{ $so->tracking_number }}</strong>
                    @if ($so->trackingUrl()) · <a href="{{ $so->trackingUrl() }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline">Rastrear en la transportadora</a>@endif</p>
            @endif
            <ul class="mt-3 text-sm text-slate-600">
                @foreach ($so->items as $i)<li>{{ $i->quantity }} × {{ $i->name }} — {{ cop($i->line_total) }}</li>@endforeach
            </ul>
        </section>
    @endforeach

    <section class="card mt-5 p-5 text-sm">
        <p class="font-semibold">Historial</p>
        <ul class="mt-2 space-y-1 text-slate-600">
            @foreach ($order->events as $e)<li><span class="text-slate-400">{{ fecha($e->created_at) }}</span> · {{ $e->message }}</li>@endforeach
        </ul>
    </section>
</div>
@endsection
