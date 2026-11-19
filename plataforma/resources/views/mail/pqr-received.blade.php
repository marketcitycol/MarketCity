@extends('mail.layout', ['subject' => 'Radicamos tu solicitud'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">Hola {{ $pqr->name }}, recibimos tu solicitud.</p>
<p style="margin:0;">Tipo: <strong>{{ $pqr->typeLabel() }}</strong><br>Número de radicado: <strong style="font-size:18px;">{{ $pqr->radicado }}</strong>@if ($pqr->order_number)<br>Pedido: {{ $pqr->order_number }}@endif</p>
<p style="margin:12px 0 0;">Te respondemos a este correo a más tardar el <strong>{{ fecha($pqr->due_at, 'd/m/Y') }}</strong> (15 días hábiles). Guarda el radicado para consultar el estado.</p>
@if ($pqr->type === 'retracto')
    <p style="margin:12px 0 0;">Para el retracto: no uses el producto y guárdalo con su empaque, manuales y accesorios. Te escribimos con las instrucciones de devolución. El dinero se devuelve dentro de los 30 días calendario siguientes a tu solicitud, por el mismo medio con el que pagaste.</p>
@elseif ($pqr->type === 'reversion')
    <p style="margin:12px 0 0;">Para la reversión del pago, avisa también a tu banco o entidad de pago dentro de los mismos 5 días hábiles, como indica el artículo 51 de la Ley 1480.</p>
@endif
<p style="margin:20px 0 0;"><a href="{{ route('pqrs.create') }}#consultar" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Consultar mi solicitud</a></p>
@endsection
