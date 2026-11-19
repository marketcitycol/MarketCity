@extends('mail.layout', ['subject' => 'Respuesta a tu solicitud'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">Hola {{ $pqr->name }}, respondimos tu solicitud {{ $pqr->radicado }}.</p>
<div style="margin:12px 0 0;padding:12px 16px;background:#F4F6FB;border-radius:12px;white-space:pre-line;">{{ $pqr->response }}</div>
<p style="margin:12px 0 0;color:#4A5068;">Si no estás de acuerdo con la respuesta, puedes responder este correo o acudir a la Superintendencia de Industria y Comercio (www.sic.gov.co).</p>
@endsection
