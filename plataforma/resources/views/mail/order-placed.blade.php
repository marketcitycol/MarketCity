@extends('mail.layout', ['subject' => 'Recibimos tu pedido '.$order->number])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">¡Hola, {{ $order->first_name }}! Recibimos tu pedido {{ $order->number }}.</p>
@if ($transfer)
    <p style="margin:0 0 12px;">Para que lo despachemos, transfiere <strong>{{ cop($order->total) }}</strong> a esta cuenta en las próximas {{ $deadline }} horas y escribe <strong>{{ $order->number }}</strong> en la referencia:</p>
    <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;background:#EEF3FF;border-radius:12px;font-size:14px;">
        @foreach ($transfer as $label => $value)
            <tr><td style="padding:6px 14px;color:#4A5068;">{{ $label }}</td><td style="padding:6px 14px;font-weight:600;">{{ $value }}</td></tr>
        @endforeach
    </table>
    @if ($extra = trim((string) \App\Models\Setting::get('transfer_instructions', '')))
        <p style="margin:12px 0 0;color:#4A5068;">{{ $extra }}</p>
    @endif
    <p style="margin:12px 0 0;color:#4A5068;font-size:13px;">Si no recibimos el pago en ese plazo, el pedido se cancela y no tienes que hacer nada.</p>
@elseif ($order->payment_status === 'paid')
    <p style="margin:0;">Tu pago quedó aprobado. Te avisamos cuando cada tienda despache.</p>
@else
    <p style="margin:0;">Estamos esperando la confirmación del pago. Te avisamos apenas llegue.</p>
@endif
@include('mail._items', ['order' => $order])
<p style="margin:0;color:#4A5068;font-size:13px;">Entrega a: {{ $order->customerName() }} · {{ $order->maskedDocument() }} · {{ $order->city }}, {{ $order->department }}</p>
<p style="margin:20px 0 0;"><a href="{{ route('orders.show', [$order, 't' => $order->access_token]) }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Ver mi pedido</a></p>
@endsection
