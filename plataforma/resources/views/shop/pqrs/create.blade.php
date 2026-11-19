@extends('layouts.shop', ['title' => 'PQRS, retracto y reversión del pago'])
@section('description', 'Radica una petición, queja, reclamo, sugerencia, derecho de retracto o reversión del pago en MarketCity y consulta su estado con tu número de radicado.')

@section('content')
<div class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-[1fr_320px]">
    <div>
        <h1 class="text-2xl font-bold">PQRS, retracto y reversión del pago</h1>
        <p class="mt-1 text-sm text-slate-500">Te damos un número de radicado y respondemos a tu correo en máximo 15 días hábiles.</p>

        <form method="post" action="{{ route('pqrs.store') }}" class="card mt-5 space-y-4 p-5" data-pqr-form>
            @csrf
            <fieldset>
                <legend class="label">¿Qué necesitas?</legend>
                <div class="mt-1 grid gap-2 sm:grid-cols-3">
                    @foreach (\App\Models\Pqr::TYPES as $k => $v)
                        <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm has-checked:border-brand-500 has-checked:bg-brand-50">
                            <input type="radio" name="type" value="{{ $k }}" @checked(old('type', $type) === $k) required> {{ $v }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
            <div class="grid gap-4 sm:grid-cols-2">
                <div><label class="label" for="name">Nombre completo</label><input class="input" id="name" name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="120" autocomplete="name"></div>
                <div><label class="label" for="email">Correo</label><input class="input" type="email" id="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required autocomplete="email"></div>
                <div><label class="label" for="phone">Celular (opcional)</label><input class="input" id="phone" name="phone" value="{{ old('phone') }}" maxlength="30" autocomplete="tel"></div>
                <div><label class="label" for="document_number">Cédula (opcional)</label><input class="input" id="document_number" name="document_number" value="{{ old('document_number') }}" maxlength="20" inputmode="numeric"></div>
                <div class="sm:col-span-2"><label class="label" for="order_number">Número de pedido <span class="font-normal text-slate-500">(obligatorio para retracto y reversión)</span></label><input class="input" id="order_number" name="order_number" value="{{ old('order_number', $orderNumber) }}" maxlength="20" placeholder="MC-010001"></div>
            </div>
            <div><label class="label" for="message">Cuéntanos qué pasó</label><textarea class="input min-h-32" id="message" name="message" required minlength="10" maxlength="5000">{{ old('message') }}</textarea></div>
            <div class="hidden" aria-hidden="true"><label>No llenar <input name="website" tabindex="-1" autocomplete="off"></label></div>
            <label class="flex items-start gap-2 text-sm text-slate-600">
                <input type="checkbox" name="data_consent" value="1" class="mt-1" required @checked(old('data_consent'))>
                <span>Autorizo a MarketCity a tratar mis datos para atender esta solicitud, según la <a href="/politica-de-privacidad" class="text-brand-600 underline">política de tratamiento de datos</a> (Ley 1581 de 2012).</span>
            </label>
            <button class="btn-primary w-full">Radicar solicitud</button>
        </form>
    </div>

    <aside class="space-y-5 text-sm">
        <section class="card p-5">
            <h2 class="font-semibold">Derecho de retracto</h2>
            <p class="mt-1 text-slate-600">Si compraste en línea, puedes arrepentirte dentro de los 5 días hábiles siguientes a la entrega. El producto debe estar sin usar, con su empaque. Te devolvemos el dinero en máximo 30 días calendario. No aplica a productos perecederos, de uso personal o hechos a la medida (artículo 47, Ley 1480).</p>
        </section>
        <section class="card p-5">
            <h2 class="font-semibold">Reversión del pago</h2>
            <p class="mt-1 text-slate-600">Si pagaste con tarjeta, PSE u otro medio electrónico y hubo fraude, una operación que no hiciste, o el producto no llegó o no es el que pediste, pide la reversión dentro de los 5 días hábiles siguientes y avisa también a tu banco (artículo 51, Ley 1480).</p>
        </section>
        <section class="card p-5" id="consultar">
            <h2 class="font-semibold">Consultar una solicitud</h2>
            <form method="post" action="{{ route('pqrs.lookup') }}" class="mt-3 space-y-3">
                @csrf
                <div><label class="label" for="q-radicado">Radicado</label><input class="input" id="q-radicado" name="radicado" placeholder="PQR-2026-000001" required></div>
                <div><label class="label" for="q-email">Correo</label><input class="input" type="email" id="q-email" name="email" required></div>
                <button class="btn-ghost w-full">Consultar</button>
            </form>
        </section>
        <p class="text-xs text-slate-500">Si no quedas conforme, puedes acudir a la <a href="https://www.sic.gov.co/" class="underline" rel="noopener">Superintendencia de Industria y Comercio</a>.</p>
    </aside>
</div>
@endsection
