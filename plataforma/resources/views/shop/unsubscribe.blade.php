@extends('layouts.shop', ['title' => 'Mensajes de MarketCity'])

@section('content')
<div class="mx-auto max-w-md">
    <div class="card p-6 text-center">
        @if ($done)
            <h1 class="text-xl font-bold">Listo, no te escribiremos más</h1>
            <p class="mt-2 text-sm text-slate-600">Quedó registrado. Si compras, igual te enviaremos los correos del pedido (pago, despacho y entrega), que no son publicidad.</p>
        @else
            <h1 class="text-xl font-bold">¿Dejar de recibir mensajes?</h1>
            <p class="mt-2 text-sm text-slate-600">Ya no te enviaremos recordatorios de carrito ni ofertas.</p>
            <form method="post" action="{{ request()->fullUrl() }}" class="mt-5 space-y-2">
                @csrf
                <button name="canal" value="todo" class="btn-primary w-full">No quiero más mensajes</button>
                @if ($hasPhone)<button name="canal" value="whatsapp" class="btn-ghost w-full">Solo dejar de recibir WhatsApp</button>@endif
            </form>
        @endif
    </div>
</div>
@endsection
