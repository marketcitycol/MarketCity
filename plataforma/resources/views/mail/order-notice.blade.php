@extends('mail.layout', ['subject' => 'Pedido '.$order->number])
@section('body')
@php($btn = 'display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;')
@php($link = route('orders.show', [$order, 't' => $order->access_token]))
@switch($kind)
    @case('admin-new')
        <p style="margin:0 0 8px;font-size:18px;font-weight:700;">Nuevo pedido {{ $order->number }}</p>
        <p style="margin:0;">{{ cop($order->total) }} · {{ $order->paymentMethodLabel() }} · {{ $order->paymentStatusLabel() }}<br>{{ $order->city }}, {{ $order->department }} · {{ $order->sellerOrders->count() }} {{ $order->sellerOrders->count() === 1 ? 'tienda' : 'tiendas' }}: {{ $order->sellerOrders->map(fn ($s) => $s->store?->name)->filter()->join(', ') }}</p>
        @include('mail._items', ['order' => $order])
        <p style="margin:20px 0 0;"><a href="{{ route('admin.orders.show', $order) }}" style="{{ $btn }}">Abrir en el Backoffice</a></p>
        @break
    @case('admin-failed')
        <p style="margin:0 0 8px;font-size:18px;font-weight:700;">Pedido {{ $order->number }} cancelado sin pago</p>
        <p style="margin:0;">{{ $detail }}. {{ cop($order->total) }} con {{ $order->paymentMethodLabel() }}. Las unidades volvieron al inventario.</p>
        <p style="margin:20px 0 0;"><a href="{{ route('admin.orders.show', $order) }}" style="{{ $btn }}">Abrir en el Backoffice</a></p>
        @break
    @case('payment-failed')
        <p style="margin:0 0 8px;font-size:18px;font-weight:700;">{{ $order->first_name }}, el pago de tu pedido {{ $order->number }} no se aprobó.</p>
        <p style="margin:0;">{{ $detail ? $detail.'. ' : '' }}<strong>No se cobró nada</strong> y el pedido quedó cancelado. Si aún lo quieres, puedes pagarlo con otro medio: volvemos a armar tu carrito con lo que siga disponible.</p>
        <p style="margin:20px 0 0;"><a href="{{ route('orders.thanks', [$order, 't' => $order->access_token]) }}" style="{{ $btn }}">Pagar con otro medio</a></p>
        @break
    @case('cancelled')
        <p style="margin:0 0 8px;font-size:18px;font-weight:700;">Cancelamos parte de tu pedido {{ $order->number }}.</p>
        <p style="margin:0;">{{ $so?->store?->name }} no podrá enviar estos productos. Motivo: {{ $detail }}.</p>
        @if ($so)<ul style="margin:12px 0;padding-left:18px;color:#4A5068;">@foreach ($so->items as $i)<li>{{ $i->quantity }} × {{ $i->name }}</li>@endforeach</ul>@endif
        <p style="margin:0;">@if ($order->payment_status === 'paid')Te devolvemos {{ cop($so?->total() ?? 0) }} por el mismo medio de pago; te escribimos cuando quede hecho.@else No se te cobrará esta parte.@endif</p>
        <p style="margin:20px 0 0;"><a href="{{ $link }}" style="{{ $btn }}">Ver mi pedido</a></p>
        @break
    @case('delivered')
        <p style="margin:0 0 8px;font-size:18px;font-weight:700;">¡Tu pedido {{ $order->number }} fue entregado!</p>
        <p style="margin:0;">{{ $so?->store?->name }} marcó la entrega. Esperamos que lo disfrutes.</p>
        <p style="margin:12px 0 0;font-size:13px;color:#4A5068;">Tienes 5 días hábiles para retractarte de la compra sin dar razones y la garantía legal del producto (Ley 1480 de 2011). Si algo no está bien, radícalo en <a href="{{ route('pqrs.create', ['pedido' => $order->number]) }}">PQRS</a>.</p>
        <p style="margin:20px 0 0;"><a href="{{ $link }}" style="{{ $btn }}">Ver mi pedido</a></p>
        @break
    @case('refunded')
        <p style="margin:0 0 8px;font-size:18px;font-weight:700;">Te reembolsamos {{ cop((int) $amount) }} del pedido {{ $order->number }}.</p>
        <p style="margin:0;">@if ($so){{ $so->store?->name }}. @endif Motivo: {{ $detail }}.</p>
        <p style="margin:12px 0 0;">El dinero vuelve por el mismo medio con que pagaste ({{ $order->paymentMethodLabel() }}). Puede tardar algunos días hábiles en verse reflejado, según tu banco o la pasarela.@if ($order->payment_status === 'refunded') El pedido quedó reembolsado por completo.@endif</p>
        <p style="margin:20px 0 0;"><a href="{{ $link }}" style="{{ $btn }}">Ver mi pedido</a></p>
        @break
    @default
        <p style="margin:0 0 8px;font-size:18px;font-weight:700;">Novedad de tu pedido {{ $order->number }}</p>
        <p style="margin:0;white-space:pre-line;">{{ $detail }}</p>
        <p style="margin:20px 0 0;"><a href="{{ $link }}" style="{{ $btn }}">Ver mi pedido</a></p>
@endswitch
@endsection
