@extends('mail.layout', ['subject' => 'Crea tu contraseña'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">Hola{{ $name ? ', '.$name : '' }}.</p>
<p style="margin:0;">Pediste crear o cambiar la contraseña de tu cuenta en MarketCity. Si vienes de la tienda anterior, este es el paso para volver a entrar con tus pedidos.</p>
<p style="margin:20px 0;"><a href="{{ $url }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Crear mi contraseña</a></p>
<p style="margin:0;color:#4A5068;font-size:13px;">El enlace vence en {{ $minutes }} minutos. Si no lo pediste, ignora este correo: tu cuenta sigue igual.</p>
@endsection
