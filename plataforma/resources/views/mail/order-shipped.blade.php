@extends('mail.layout', ['subject' => 'Tu pedido va en camino'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">{{ $so->store->name }} despachó tu pedido {{ $so->order->number }}.</p>
@if ($so->tracking_number)
    <p style="margin:0;">Transportadora: <strong>{{ $so->carrierName() }}</strong><br>Número de guía: <strong>{{ $so->tracking_number }}</strong></p>
@else
    <p style="margin:0;">Está listo para recoger en la tienda.</p>
@endif
<ul style="margin:12px 0;padding-left:18px;color:#4A5068;">
    @foreach ($so->items as $i)<li>{{ $i->quantity }} × {{ $i->name }}</li>@endforeach
</ul>
<p style="margin:20px 0 0;"><a href="{{ route('orders.show', [$so->order, 't' => $so->order->access_token]) }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Seguir mi pedido</a></p>
@endsection
