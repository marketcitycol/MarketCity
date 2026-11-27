@extends('mail.layout', ['subject' => 'Nueva pregunta de un cliente'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">Te preguntaron por {{ $q->product->name }}.</p>
<p style="margin:0 0 12px;padding:12px 16px;background:#F4F6FB;border-radius:12px;color:#1B1F2E;">«{{ $q->question }}»<br><span style="color:#4A5068;font-size:13px;">{{ $q->publicName() }}</span></p>
<p style="margin:0;color:#4A5068;font-size:14px;">Responde pronto: una pregunta respondida suele terminar en venta. La respuesta se publica en la página del producto y le llega al cliente por correo. No incluyas teléfonos, correos ni enlaces.</p>
<p style="margin:20px 0 0;"><a href="{{ route('seller.questions') }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Responder en el Seller Center</a></p>
@endsection
