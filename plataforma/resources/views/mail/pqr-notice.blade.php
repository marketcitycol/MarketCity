@extends('mail.layout', ['subject' => 'PQRS'])
@section('body')
@if ($forSeller)
    <p style="margin:0 0 8px;font-size:18px;font-weight:700;">Un comprador radicó {{ mb_strtolower($pqr->typeLabel()) }} sobre el pedido {{ $pqr->order_number }}.</p>
    <p style="margin:0;">Radicado {{ $pqr->radicado }}. MarketCity lo atiende y te escribe si necesita algo de {{ $store?->name ?? 'tu tienda' }}. Mientras esté abierto, la liquidación de ese pedido queda en espera. No contactes al comprador por fuera de MarketCity.</p>
@else
    <p style="margin:0 0 8px;font-size:18px;font-weight:700;">Nueva {{ mb_strtolower($pqr->typeLabel()) }}: {{ $pqr->radicado }}</p>
    <p style="margin:0;">De {{ $pqr->name }}@if ($pqr->order_number) · pedido {{ $pqr->order_number }}@endif. Vence el {{ fecha($pqr->due_at, 'd/m/Y') }}.@if ($pqr->type === 'retracto') {{ $pqr->within_term === false ? 'OJO: llegó después de los 5 días hábiles desde la entrega.' : 'Dentro del plazo de retracto.' }}@endif</p>
    <p style="margin:20px 0 0;"><a href="{{ route('admin.pqrs.show', $pqr) }}" style="display:inline-block;background:#0A4BFF;color:#FFFFFF;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:600;">Abrir en el backoffice</a></p>
@endif
@endsection
