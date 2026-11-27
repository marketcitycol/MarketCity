@extends('mail.layout', ['subject' => 'Respondieron tu pregunta'])
@section('body')
<p style="margin:0 0 8px;font-size:18px;font-weight:700;">{{ $q->store->name }} respondió tu pregunta sobre {{ $q->product->name }}.</p>
<p style="margin:0 0 4px;color:#4A5068;font-size:14px;">Preguntaste: «{{ $q->question }}»</p>
<p style="margin:8px 0 0;padding:12px 16px;background:#F4F6FB;border-radius:12px;color:#1B1F2E;white-space:pre-line;">{{ $q->answer }}</p>
<p style="margin:20px 0 0;"><a href="{{ route('product', $q->product) }}#preguntas" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Ver el producto</a></p>
@endsection
