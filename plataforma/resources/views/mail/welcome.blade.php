@extends('mail.layout', ['subject' => 'Bienvenido a MarketCity'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">Hola, {{ \Illuminate\Support\Str::before($user->name, ' ') }}. Tu cuenta quedó creada.</p>
<p style="margin:0;">Desde Mi cuenta ves tus pedidos, guardas direcciones y favoritos y decides qué mensajes recibir. Ingresas con {{ $user->email }}.</p>
<p style="margin:20px 0 0;"><a href="{{ route('account') }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Ir a Mi cuenta</a></p>
<p style="margin:16px 0 0;font-size:13px;color:#4A5068;">¿No creaste esta cuenta? Escríbenos y la cerramos.</p>
@endsection
