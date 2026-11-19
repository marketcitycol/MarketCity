@extends('layouts.shop', ['title' => 'Pago de prueba'])

@section('content')
<div class="mx-auto max-w-md">
    <div class="card overflow-hidden">
        <div class="bg-slate-900 px-5 py-4 text-white">
            <p class="text-xs tracking-wide text-slate-500 uppercase">Pasarela de prueba</p>
            <p class="text-lg font-semibold">Pedido {{ $order->number }}</p>
        </div>
        <div class="p-5">
            <p class="text-sm text-slate-500">Valor a pagar</p>
            <p class="text-3xl font-extrabold">{{ cop($order->total) }}</p>
            <p class="mt-1 text-xs text-slate-500">Referencia {{ $payment?->provider_reference }}</p>
            <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Esta pantalla reemplaza a la pasarela real mientras se conectan Bold, Wompi y las demás. No se cobra nada.</p>
            <form method="post" action="{{ route('payments.sandbox.decide', [$order, 't' => $order->access_token]) }}" class="mt-5 grid grid-cols-2 gap-3">
                @csrf
                <button name="decision" value="decline" class="btn-ghost">Rechazar pago</button>
                <button name="decision" value="approve" class="btn-primary">Aprobar pago</button>
            </form>
        </div>
    </div>
</div>
@endsection
