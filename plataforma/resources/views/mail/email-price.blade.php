@extends('mail.layout', ['subject' => 'Tu precio especial'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">Este es tu precio {{ $coupon->campaign->name }}</p>
<p style="margin:0;">{{ $coupon->product->name }}: <strong style="font-size:18px;">{{ cop($coupon->promised_price) }}</strong></p>
<p style="margin:12px 0 0;">Tu código: <strong style="font-size:20px;letter-spacing:2px;">{{ $coupon->code }}</strong></p>
<p style="margin:12px 0 0;">Sirve para 1 unidad de este producto, una sola vez, con este mismo correo, hasta el {{ fecha($coupon->expires_at, 'd/m/Y h:i a') }}. Si el producto se agota a precio de campaña antes, el código deja de servir.</p>
<p style="margin:20px 0 0;"><a href="{{ route('product', $coupon->product) }}?cupon={{ $coupon->code }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Comprar con mi precio</a></p>
@endsection
