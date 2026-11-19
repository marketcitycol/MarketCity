@extends('mail.layout', ['subject' => 'Confirma tu correo'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">Hola{{ $name ? ', '.$name : '' }}.</p>
<p style="margin:0;">Confirma que este correo es tuyo para ver en tu cuenta todos los pedidos que hiciste con él.</p>
<p style="margin:20px 0;"><a href="{{ $url }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Confirmar mi correo</a></p>
<p style="margin:0;color:#4A5068;font-size:13px;">El enlace vence en 48 horas.</p>
@endsection
