@extends('mail.layout', ['subject' => $copy ? 'Recibimos tu mensaje' : 'Nuevo mensaje de contacto'])
@section('body')
@if ($copy)
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">Hola, {{ strtok($c->name, ' ') }}. Recibimos tu mensaje.</p>
<p style="margin:0 0 12px;color:#4A5068;">Te respondemos a este correo en horario hábil. Esta es la copia de lo que nos escribiste:</p>
@else
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">{{ $c->name }} escribió por el formulario de contacto.</p>
<p style="margin:0 0 12px;color:#4A5068;font-size:14px;">{{ $c->email }}@if ($c->phone) · {{ $c->phone }}@endif · Responde a este correo para contestarle.</p>
@endif
<p style="margin:0 0 4px;color:#4A5068;font-size:14px;"><strong>Tema:</strong> {{ $c->topicLabel() }}@if ($c->order_number) · <strong>Pedido:</strong> {{ $c->order_number }}@endif</p>
<p style="margin:8px 0 0;padding:12px 16px;background:#F4F6FB;border-radius:12px;color:#1B1F2E;white-space:pre-line;">{{ $c->message }}</p>
@if ($copy && $c->topic === 'compra')
<p style="margin:16px 0 0;color:#4A5068;font-size:14px;">Si es una queja, un reclamo, un retracto o una reversión del pago, radícalo como PQRS para que corra el plazo legal: <a href="{{ route('pqrs.create') }}" style="color:#0A4BFF;">{{ route('pqrs.create') }}</a></p>
@endif
@endsection
