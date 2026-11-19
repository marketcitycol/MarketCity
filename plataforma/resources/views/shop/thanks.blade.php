@extends('layouts.shop', ['title' => 'Pedido '.$order->number])

@section('content')
<div class="mx-auto max-w-2xl">
    @if ($order->payment_status === 'failed')
        <div class="card p-8 text-center">
            <h1 class="text-2xl font-bold">El pago no se aprobó</h1>
            <p class="mt-2 text-slate-600">Tu pedido {{ $order->number }} quedó cancelado y no se cobró nada. Puedes intentarlo de nuevo.</p>
            <a href="{{ route('shop') }}" class="btn-primary mt-5">Volver a la tienda</a>
        </div>
    @else
        <div class="card p-8 text-center">
            <h1 class="text-2xl font-bold">¡Gracias por tu compra, {{ $order->first_name }}!</h1>
            <p class="mt-2 text-slate-600">Tu pedido es el <strong>{{ $order->number }}</strong>.
                @if ($order->payment_status === 'paid') El pago quedó aprobado.
                @elseif ($transfer) Falta tu transferencia: te explicamos abajo cómo hacerla.
                @else Estamos esperando la confirmación del pago.
                @endif
            </p>
            <p class="text-sm text-slate-500">Te escribiremos a {{ $order->email }} con cada novedad.</p>
        </div>

        @if ($transfer)
            @php($deadline = \App\Services\Payments\BankTransferGateway::deadlineHours())
            @php($wa = preg_replace('/\D/', '', (string) (\App\Support\Plugins::config('whatsapp')['number'] ?? '')))
            <section class="card mt-4 p-5 text-sm" aria-labelledby="como-pagar">
                <h2 id="como-pagar" class="text-lg font-semibold">Cómo pagar</h2>
                <p class="mt-1 text-slate-600">Transfiere el valor exacto a esta cuenta y escribe el número del pedido en la referencia. Tienes {{ $deadline }} horas; después el pedido se cancela y las unidades vuelven al inventario.</p>
                <dl class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200">
                    @foreach ([['Valor a pagar', (string) $order->total, cop($order->total)], ['Referencia', $order->number, $order->number]] as [$label, $copy, $show])
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <div><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="font-semibold">{{ $show }}</dd></div>
                            <button type="button" class="btn-ghost btn-sm" data-copy="{{ $copy }}" aria-label="Copiar {{ mb_strtolower($label) }}">Copiar</button>
                        </div>
                    @endforeach
                    @foreach ($transfer as $label => $value)
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <div><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="font-semibold">{{ $value }}</dd></div>
                            @if ($label === 'Número de cuenta')
                                <button type="button" class="btn-ghost btn-sm" data-copy="{{ preg_replace('/[^0-9]/', '', $value) }}" aria-label="Copiar número de cuenta">Copiar</button>
                            @endif
                        </div>
                    @endforeach
                </dl>
                @if ($extra = trim((string) \App\Models\Setting::get('transfer_instructions', '')))
                    <p class="mt-3 whitespace-pre-line text-slate-600">{{ $extra }}</p>
                @endif
                @if ($wa)
                    <a class="btn-primary mt-4" target="_blank" rel="noopener" href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Hola MarketCity, envío el comprobante del pedido '.$order->number) }}">Enviar el comprobante por WhatsApp</a>
                @endif
                <p class="mt-3 text-xs text-slate-500">Despachamos cuando el pago se refleje en la cuenta. Nunca te pediremos pagar a otra cuenta distinta a esta.</p>
            </section>
            <script>
                document.querySelectorAll('[data-copy]').forEach(function (b) {
                    b.addEventListener('click', function () {
                        var done = function () { var t = b.textContent; b.textContent = 'Copiado'; setTimeout(function () { b.textContent = t; }, 1500); };
                        if (navigator.clipboard) { navigator.clipboard.writeText(b.dataset.copy).then(done); }
                    });
                });
            </script>
        @endif

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
