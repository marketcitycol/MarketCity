@extends('mail.layout', ['subject' => $unpaidOrder ? 'Tu pedido quedó sin pagar' : 'Tu carrito te espera'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">Hola {{ $cart->name ?: '' }}, {{ $unpaidOrder ? 'tu pedido quedó sin pagar' : 'dejaste esto en tu carrito' }}</p>
<table role="presentation" width="100%" style="border-collapse:collapse;">
@foreach ($lines as $l)
    <tr><td style="padding:6px 0;border-bottom:1px solid #E2E8F0;">{{ $l['name'] }} × {{ $l['quantity'] }}</td><td style="padding:6px 0;border-bottom:1px solid #E2E8F0;text-align:right;">{{ cop($l['price'] * $l['quantity']) }}</td></tr>
@endforeach
</table>
<p style="margin:12px 0 0;">Los precios y las unidades disponibles se confirman al abrir el carrito.</p>
<p style="margin:20px 0 0;"><a href="{{ $restoreUrl }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">{{ $unpaidOrder ? 'Terminar mi compra' : 'Volver a mi carrito' }}</a></p>
<p style="margin:20px 0 0;font-size:12px;color:#64748B;">Recibes este correo porque autorizaste recordatorios por correo en el pago. <a href="{{ $unsubscribeUrl }}" style="color:#64748B;">No quiero recibir más mensajes</a>.</p>
@endsection
