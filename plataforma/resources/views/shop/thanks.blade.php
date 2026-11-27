@extends('layouts.shop', ['title' => 'Pedido '.$order->number])

@section('content')
<div class="mx-auto max-w-2xl">
    @php($wa = preg_replace('/\D/', '', (string) (\App\Services\Content\Plugins::config('whatsapp')['number'] ?? '')))
    @if ($order->payment_status === 'failed')
        {{-- Plan B del pago rechazado (#87): sin «¡Gracias!», con otro medio de pago y ayuda por WhatsApp. --}}
        <div class="card p-8 text-center">
            <h1 class="text-2xl font-bold">El pago no se aprobó</h1>
            <p class="mt-2 text-slate-600">El pedido {{ $order->number }} quedó cancelado y <strong>no se cobró nada</strong>. No hace falta que pagues este pedido de nuevo: arma uno nuevo con otro medio.</p>
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <form method="post" action="{{ route('orders.retry', [$order, 't' => $order->access_token]) }}">@csrf<button class="btn-primary">Pagar con otro medio</button></form>
                @if ($wa)
                    <a class="btn-ghost" target="_blank" rel="noopener" data-track="whatsapp_click" href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Hola MarketCity, mi pago del pedido '.$order->number.' no se aprobó. ¿Me ayudan?') }}">Pedir ayuda por WhatsApp</a>
                @endif
            </div>
            <p class="mt-3 text-xs text-slate-500">Si en tu banco aparece un cobro de este pedido, escríbenos con el número {{ $order->number }} y lo revisamos.</p>
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
                    <a class="btn-primary mt-4" target="_blank" rel="noopener" data-track="whatsapp_click" href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Hola MarketCity, envío el comprobante del pedido '.$order->number) }}">Enviar el comprobante por WhatsApp</a>
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

        @if ($offerAccount === true)
            {{-- «Crear cuenta con un toque» (#62): el correo de «crea tu contraseña» confirma que el correo es tuyo. --}}
            <form method="post" action="{{ route('orders.account', [$order, 't' => $order->access_token]) }}" class="card mt-4 flex flex-wrap items-center justify-between gap-3 p-5 text-sm">
                @csrf
                <div><p class="font-semibold">Sigue tus pedidos desde Mi cuenta</p><p class="text-slate-600">Creamos la cuenta con {{ $order->email }} y te enviamos un correo para elegir la contraseña.</p></div>
                <button class="btn-primary">Crear mi cuenta</button>
            </form>
        @elseif ($offerAccount === false)
            <p class="card mt-4 p-5 text-sm">Ya tienes una cuenta con {{ $order->email }}. <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:underline">Ingresa</a> para ver este pedido en Mi cuenta.</p>
        @endif
    @endif
</div>
<script>try { sessionStorage.removeItem('mc_ck_v1'); } catch (e) {}</script>
{{-- La compra se mide solo con el pago confirmado (#85); si llega después, se envía desde el servidor. --}}
@if ($order->payment_status === 'paid')
@include('partials.track', ['event' => 'purchase', 'params' => [
    'transaction_id' => $order->number,
    'currency' => 'COP',
    'value' => (int) $order->total,
    'shipping' => (int) $order->sellerOrders->sum('shipping_total'),
    'payment_type' => $order->paymentMethodLabel(),
    'items' => $order->sellerOrders->flatMap(fn ($so) => $so->items->map(fn ($i) => \App\Support\Analytics::orderItem($i, $so->store?->name)))->values()->all(),
]])
@endif
@endsection
