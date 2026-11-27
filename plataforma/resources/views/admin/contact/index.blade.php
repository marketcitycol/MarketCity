@extends('layouts.admin', ['title' => 'Contacto'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Mensajes de contacto</h1>
    <div class="flex gap-1 text-sm">
        <a href="{{ route('admin.contact') }}" class="rounded-full px-3 py-1 {{ $all ? 'hover:bg-white' : 'bg-white font-semibold' }}">Pendientes</a>
        <a href="{{ route('admin.contact', ['estado' => 'todos']) }}" class="rounded-full px-3 py-1 {{ $all ? 'bg-white font-semibold' : 'hover:bg-white' }}">Todos</a>
    </div>
</div>
<p class="mt-1 text-sm text-slate-500">Llegan también al correo del pie de página (o al de los administradores). Responde desde tu correo; aquí solo se marca como atendido. Las quejas y reclamos con plazo legal van por PQRS.</p>
@forelse ($messages as $m)
    <div class="card mt-4 p-4 text-sm {{ $m->handled_at ? 'opacity-70' : '' }}">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <p><strong>{{ $m->name }}</strong> · <a href="mailto:{{ $m->email }}" class="text-brand-600 hover:underline">{{ $m->email }}</a>@if ($m->phone) · {{ $m->phone }}@endif</p>
            <span class="text-xs text-slate-500">{{ fecha($m->created_at, 'd/m/Y H:i') }}</span>
        </div>
        <p class="mt-1 text-xs text-slate-500">{{ $m->topicLabel() }}@if ($m->order_number) · Pedido {{ $m->order_number }}@endif @if ($m->source) · Nos conoció por {{ $m->source }}@endif</p>
        <p class="mt-2 whitespace-pre-line text-slate-800">{{ $m->message }}</p>
        <form method="post" action="{{ route('admin.contact.handle', $m) }}" class="mt-3">@csrf
            <button class="btn-ghost btn-sm">{{ $m->handled_at ? 'Volver a pendiente' : 'Marcar como atendido' }}</button>
            @if ($m->handled_at)<span class="text-xs text-slate-500">Atendido el {{ fecha($m->handled_at, 'd/m/Y') }}</span>@endif
        </form>
    </div>
@empty
    <p class="card mt-4 p-8 text-center text-sm text-slate-500">No hay mensajes {{ $all ? '' : 'pendientes' }}.</p>
@endforelse
<div class="mt-4">{{ $messages->links() }}</div>
@endsection
