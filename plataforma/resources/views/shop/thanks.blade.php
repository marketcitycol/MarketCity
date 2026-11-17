@extends('layouts.shop', ['title' => 'Pedido '.$order->number])

@section('content')
<div class="mx-auto max-w-2xl">
    @if ($order->payment_status === 'failed')
        <div class="card p-8 text-center">
            <p class="text-5xl">⚠️</p>
            <h1 class="mt-3 text-2xl font-bold">El pago no se aprobó</h1>
            <p class="mt-2 text-slate-600">Tu pedido {{ $order->number }} quedó cancelado y no se cobró nada. Puedes intentarlo de nuevo.</p>
            <a href="{{ route('shop') }}" class="btn-primary mt-5">Volver a la tienda</a>
        </div>
    @else
        <div class="card p-8 text-center">
            <p class="text-5xl">🎉</p>
            <h1 class="mt-3 text-2xl font-bold">¡Gracias por tu compra, {{ $order->first_name }}!</h1>
            <p class="mt-2 text-slate-600">Tu pedido es el <strong>{{ $order->number }}</strong>. {{ $order->payment_status === 'paid' ? 'El pago quedó aprobado.' : 'Pagas al recibir.' }}</p>
            <p class="text-sm text-slate-500">Te escribiremos a {{ $order->email }} con cada novedad.</p>
        </div>
        <div class="card mt-4 p-5 text-sm">
            <p class="font-semibold">Llega en {{ $order->sellerOrders->count() }} {{ $order->sellerOrders->count() === 1 ? 'envío' : 'envíos' }}, uno por tienda:</p>
            @foreach ($order->sellerOrders as $so)
                <div class="mt-3 border-t border-slate-100 pt-3">
                    <p class="font-medium">{{ $so->store->name }} · {{ $so->shipping_method === 'recogida' ? 'Recogida en tienda' : 'Envío a domicilio' }}</p>
                    @foreach ($so->items as $i)<p class="text-slate-600">{{ $i->quantity }} × {{ $i->name }}</p>@endforeach
                </div>
            @endforeach
            <a href="{{ route('orders.show', [$order, 't' => $order->access_token]) }}" class="btn-primary mt-5">Ver el estado del pedido</a>
        </div>
    @endif
</div>
@endsection
