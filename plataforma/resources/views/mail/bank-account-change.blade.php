@extends('mail.layout', ['subject' => 'Cambio de cuenta bancaria'])
@section('body')
@if ($forSeller)
    <p style="margin:0 0 8px;font-size:18px;font-weight:700;">Recibimos un cambio de cuenta bancaria para {{ $account->store->name }}.</p>
    <p style="margin:0;">Nueva cuenta: {{ $account->bank }} · {{ $account->account_type }} terminada en {{ mb_substr($account->account_number, -4) }}. MarketCity la revisa antes de pagarte en ella; mientras tanto tus liquidaciones quedan en espera.</p>
    <p style="margin:12px 0 0;color:#C9252C;font-weight:600;">Si no fuiste tú, cambia tu contraseña y escríbenos de inmediato.</p>
@else
    <p style="margin:0 0 8px;font-size:18px;font-weight:700;">{{ $account->store->name }} pidió cambiar su cuenta bancaria.</p>
    <p style="margin:0;">{{ $account->bank }} · {{ $account->account_type }} terminada en {{ mb_substr($account->account_number, -4) }} · titular {{ $account->holder_name }}. Confírmala con el vendedor por un canal conocido antes de aprobarla. Las liquidaciones de la tienda quedan retenidas hasta que decidas.</p>
    <p style="margin:20px 0 0;"><a href="{{ route('admin.stores.edit', $account->store) }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Revisar en el backoffice</a></p>
@endif
@endsection
