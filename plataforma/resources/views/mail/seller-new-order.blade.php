@extends('mail.layout', ['subject' => 'Nuevo pedido pagado'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">Tienes un pedido pagado: {{ $so->order->number }}.</p>
<p style="margin:0;">El pago ya está confirmado. Prepáralo y regístralo como despachado con la transportadora y el número de guía.</p>
<ul style="margin:12px 0;padding-left:18px;color:#4A5068;">
    @foreach ($so->items as $i)<li>{{ $i->quantity }} × {{ $i->name }} · {{ cop($i->line_total) }}</li>@endforeach
</ul>
<p style="margin:0;color:#4A5068;font-size:14px;">Envío: {{ $so->shipping_method === 'recogida' ? 'Recogida en tienda' : 'A domicilio, '.$so->order->city.', '.$so->order->department }}<br>Lo que recibes después de la comisión: <strong>{{ cop($so->seller_net) }}</strong></p>
<p style="margin:20px 0 0;"><a href="{{ route('seller.orders.show', $so) }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Abrir en el Seller Center</a></p>
@endsection
