@extends('layouts.shop')
@section('title', 'Compra ahora, paga después')
@section('description', 'Llévalo hoy y págalo a cuotas con Addi o Sistecrédito en MarketCity. Simula tu cuota.')
@section('content')
{{-- Página de servicio «Compra ahora, paga después» con simulador de cuotas (#59). --}}
<div class="mx-auto max-w-4xl space-y-8">
    <section class="grid justify-items-start gap-3 rounded-[20px] bg-brand-900 p-7 text-white">
        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold tracking-wider text-accent-400 uppercase">Compra ahora, paga después</span>
        <h1 class="text-3xl font-extrabold">Llévalo hoy y págalo a cuotas</h1>
        <p class="max-w-2xl text-white/85">En MarketCity puedes pagar con Addi o Sistecrédito en el checkout. Eliges el producto, solicitas tu cupo en minutos y lo pagas en cuotas.</p>
        <a href="{{ route('shop') }}" class="btn-accent">Ver productos</a>
    </section>

    @unless ($creditLive)
        <p class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">Addi y Sistecrédito llegan muy pronto al pago de esta tienda. Mientras tanto puedes pagar con {{ $methods ?: 'los medios disponibles en el checkout' }}.</p>
    @endunless

    <section>
        <h2 class="text-xl font-bold">Así funciona</h2>
        <ol class="mt-3 grid gap-3 sm:grid-cols-3">
            @foreach ([['Elige tu producto', 'Busca en la tienda y agrégalo al carrito.'], ['Selecciona Addi o Sistecrédito', 'En el pago, elige el aliado y sigue sus pasos para solicitar el cupo.'], ['Recibe y paga a cuotas', 'Si te aprueban, despachamos tu pedido y pagas las cuotas directamente al aliado.']] as $i => [$t, $d])
                <li class="card grid gap-1 p-4"><span class="grid h-8 w-8 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">{{ $i + 1 }}</span><b>{{ $t }}</b><span class="text-sm text-slate-600">{{ $d }}</span></li>
            @endforeach
        </ol>
    </section>

    <section class="grid gap-3 rounded-[20px] bg-credit-50 p-5" data-bnpl-sim>
        <h2 class="text-xl font-bold">Simula tu cuota</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <div><label class="label" for="sim-valor">Valor del producto</label><input class="input text-lg" id="sim-valor" inputmode="numeric" value="200.000" data-sim-value></div>
            <div><label class="label" for="sim-cuotas">Número de cuotas</label>
                <select class="input text-lg" id="sim-cuotas" data-sim-n>
                    @foreach (array_unique([3, installments(), 6, 12]) as $n)<option value="{{ $n }}" @selected($n === installments())>{{ $n }} cuotas</option>@endforeach
                </select>
            </div>
        </div>
        <p class="text-lg" aria-live="polite"><span data-sim-total>$ 200.000</span> en <span data-sim-count>{{ installments() }}</span> cuotas de <b class="text-credit-600" data-sim-quota>{{ cop((int) ceil(200000 / installments())) }}</b></p>
        <p class="text-xs text-slate-600">Valores aproximados. El crédito lo otorga Addi o Sistecrédito y está sujeto a su aprobación; la cuota final, los intereses y los cargos los define el aliado al pagar.</p>
    </section>

    <section>
        <h2 class="text-xl font-bold">Aliados de crédito</h2>
        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <article class="card p-4"><b>Addi</b><p class="mt-1 text-sm text-slate-600">Solicitas el cupo en el checkout siguiendo los pasos de Addi. Si te aprueban, pagas tu compra en cuotas.</p></article>
            <article class="card p-4"><b>Sistecrédito</b><p class="mt-1 text-sm text-slate-600">Si ya tienes cupo o lo solicitas, pagas tu compra en cuotas según las condiciones de tu crédito.</p></article>
        </div>
    </section>

    <section>
        <h2 class="text-xl font-bold">Preguntas frecuentes</h2>
        <div class="mt-3 divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
            @foreach ([
                ['¿Quién me presta el dinero?', 'El crédito lo otorga el aliado que elijas (Addi o Sistecrédito), no MarketCity. Ellos revisan y aprueban tu solicitud.'],
                ['¿Cuánto pago por cuota?', 'Depende del valor, del plazo y de las condiciones que te asigne el aliado. Antes de confirmar verás el detalle.'],
                ['¿Qué necesito?', 'Normalmente tu cédula y un celular para validar tu identidad. El aliado te indica los requisitos en el proceso.'],
                ['¿Puedo usarlo en cualquier producto?', 'Puedes elegirlo en el checkout para los productos de la tienda, sujeto a los montos mínimos y máximos del aliado.'],
            ] as [$q, $a])
                <details class="group p-4"><summary class="cursor-pointer font-semibold">{{ $q }}</summary><p class="mt-2 text-sm text-slate-600">{{ $a }}</p></details>
            @endforeach
        </div>
    </section>

    <p class="text-center text-sm text-slate-600">¿Dudas? <a href="{{ route('contact') }}" class="text-brand-600 hover:underline">Escríbenos</a> y te ayudamos a comprar.</p>
</div>
@endsection
