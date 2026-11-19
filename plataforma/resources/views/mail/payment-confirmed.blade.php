@extends('mail.layout', ['subject' => 'Pago confirmado'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">Pago confirmado, {{ $order->first_name }}.</p>
<p style="margin:0;">Recibimos {{ cop($order->total) }} por tu pedido {{ $order->number }}. Cada tienda prepara su envío y te escribimos con el número de guía.</p>
@include('mail._items', ['order' => $order])
<p style="margin:20px 0 0;"><a href="{{ route('orders.show', [$order, 't' => $order->access_token]) }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Ver mi pedido</a></p>
@endsection
