@extends('layouts.shop', ['title' => 'Completa tu pedido'])

@section('content')
@php($addr = $user?->defaultAddress())
<h1 class="text-2xl font-bold">Completa tu pedido</h1>
<form method="post" action="{{ route('checkout.place') }}" data-checkout-form @guest data-remember-fields @endguest class="mt-5 grid gap-6 lg:grid-cols-[1fr_380px]">
    @csrf
    <div class="space-y-6">
        <section class="card p-5">
            <h2 class="font-semibold">1. Tus datos</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="label" for="first_name">Nombres</label><input class="input" id="first_name" name="first_name" value="{{ old('first_name', $user ? explode(' ', $user->name)[0] : '') }}" required autocomplete="given-name"></div>
                <div><label class="label" for="last_name">Apellidos</label><input class="input" id="last_name" name="last_name" value="{{ old('last_name', $user ? trim(\Illuminate\Support\Str::after($user->name, ' ')) : '') }}" required autocomplete="family-name"></div>
                <div><label class="label" for="document_type">Tipo de documento</label>
                    <select class="input" id="document_type" name="document_type">
                        @foreach (config('colombia.document_types') as $k => $v)<option value="{{ $k }}" @selected(old('document_type', $user?->document_type ?? 'CC') === $k)>{{ $v }}</option>@endforeach
                    </select></div>
                <div><label class="label" for="document_number">Número de documento</label><input class="input" id="document_number" name="document_number" value="{{ old('document_number', $user?->document_number) }}" required inputmode="numeric"></div>
                <div><label class="label" for="email">Correo</label><input class="input" type="email" id="email" name="email" value="{{ old('email', $user?->email) }}" required autocomplete="email"></div>
                <div><label class="label" for="phone">Celular</label><input class="input" id="phone" name="phone" value="{{ old('phone', $user?->phone) }}" required inputmode="tel" autocomplete="tel" placeholder="300 123 4567"></div>
            </div>
            {{-- Permisos de mensajes por canal (#82): opcionales, nada viene marcado. --}}
            <fieldset class="mt-4 rounded-lg bg-surface p-3 text-xs text-slate-600" data-messages-consent data-url="{{ route('checkout.messages') }}">
                <legend class="sr-only">Mensajes opcionales</legend>
                <p>Si no terminas la compra, ¿te recordamos tu carrito y te contamos ofertas? Opcional:</p>
                <div class="mt-2 flex flex-wrap gap-4">
                    <label class="flex items-center gap-2"><input type="checkbox" name="messages[email]" value="1" @checked(old('messages.email'))> Correo</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="messages[whatsapp]" value="1" @checked(old('messages.whatsapp'))> WhatsApp</label>
                </div>
                <p class="mt-2 text-[11px] leading-4 text-slate-500">Te escribimos máximo una vez al día, de lunes a viernes de 7 a. m. a 7 p. m. y sábados de 8 a. m. a 3 p. m. Puedes retirarte desde cualquier mensaje. Responsable: {{ \App\Models\MerchantEntity::current()?->legal_name ?? 'MarketCity' }}. Tus derechos y la política están en <a class="underline" href="{{ url('/politica-de-privacidad') }}">Política de privacidad</a> (Ley 1581 de 2012 y Ley 2300 de 2023).</p>
            </fieldset>
        </section>

        <section class="card p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-semibold">2. Dirección de entrega</h2>
                @if ($user && $user->addresses()->count() > 1)
                    <a href="{{ route('account.addresses') }}" class="text-xs text-brand-600 hover:underline">Usar otra dirección guardada</a>
                @endif
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="label" for="department">Departamento</label>
                    <select class="input" id="department" name="department" required>
                        <option value="">Elige…</option>
                        @foreach (array_keys(config('colombia.departments')) as $d)<option @selected(old('department', $addr?->department) === $d)>{{ $d }}</option>@endforeach
                    </select></div>
                <div><label class="label" for="city">Ciudad o municipio</label>
                    <input class="input" id="city" name="city" list="cities" value="{{ old('city', $addr?->city) }}" required>
                    <datalist id="cities">@foreach (collect(config('colombia.departments'))->flatten()->sort() as $c)<option value="{{ $c }}">@endforeach</datalist></div>
                <div class="sm:col-span-2"><label class="label" for="address">Dirección</label><input class="input" id="address" name="address" value="{{ old('address', $addr?->address) }}" required placeholder="Calle 00 # 00-00" autocomplete="street-address"></div>
                <div class="sm:col-span-2"><label class="label" for="address_2">Apartamento, barrio o indicaciones (opcional)</label><input class="input" id="address_2" name="address_2" value="{{ old('address_2', $addr?->address_2) }}"></div>
                <div class="sm:col-span-2"><label class="label" for="notes">Notas del pedido (opcional)</label><textarea class="input" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea></div>
            </div>
        </section>

        <section class="card p-5">
            <h2 class="font-semibold">3. Medio de pago</h2>
            <div class="mt-4 space-y-2">
                @if (empty($gateways))
                    <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Las tiendas de tu carrito no tienen un medio de pago en común. Compra sus productos en pedidos separados.</p>
                @endif
                @foreach ($gateways as $key => $g)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                        <input type="radio" name="payment_method" value="{{ $key }}" class="mt-1" required @checked(old('payment_method', count($gateways) === 1 ? $key : null) === $key)>
                        <span><span class="block font-medium">{{ $g->label() }}</span><span class="block text-sm text-slate-500">{{ $g->description() }}</span></span>
                    </label>
                @endforeach
                <p class="pt-1 text-xs text-slate-500">Ningún medio viene marcado: elige el que prefieras. Nunca te pediremos datos de tarjeta en MarketCity.</p>
            </div>
        </section>
        @php($wa = preg_replace('/\D/', '', (string) (\App\Services\Content\Plugins::config('whatsapp')['number'] ?? '')))
        @if ($wa)
            {{-- Pago claro (#87): ayuda por WhatsApp con productos, total y medio elegido; sin datos personales. --}}
            @php($waText = "Hola MarketCity, tengo una duda para pagar:\n".$groups->flatMap(fn ($g) => $g['lines']->map(fn ($l) => '- '.$l['quantity'].' × '.\Illuminate\Support\Str::limit($l['product']->name, 60)))->join("\n")."\nTotal: ".cop($groups->sum('subtotal') + $groups->sum(fn ($g) => \App\Services\Orders\Cart::shippingCost($g['store'], $cart->shippingChoice($g['store']->id)))))
            <p class="text-sm text-slate-600">¿Dudas con el pago? <a class="font-semibold text-brand-600 hover:underline" target="_blank" rel="noopener" data-track="whatsapp_click" data-checkout-whatsapp="{{ $wa }}" data-text="{{ $waText }}" href="https://wa.me/{{ $wa }}?text={{ rawurlencode($waText) }}">Escríbenos por WhatsApp</a> y te ayudamos antes de pagar.</p>
        @endif
    </div>

    <aside class="card h-fit p-5 lg:sticky lg:top-32">
        <h2 class="font-semibold">Tu pedido</h2>
        @php($shippingTotal = 0)
        @foreach ($groups as $g)
            @php($method = $cart->shippingChoice($g['store']->id))
            @php($ship = \App\Services\Orders\Cart::shippingCost($g['store'], $method))
            @php($shippingTotal += $ship)
            <div class="mt-4 border-t border-slate-100 pt-3 text-sm">
                <p class="font-medium">{{ $g['store']->name }}</p>
                @foreach ($g['lines'] as $line)
                    <p class="flex justify-between text-slate-600"><span>{{ $line['quantity'] }} × {{ \Illuminate\Support\Str::limit($line['product']->name, 32) }}</span><span>{{ cop($line['line_total']) }}</span></p>
                    @if ($line['discount'])<p class="flex justify-between text-xs text-emerald-700"><span>Precio con correo (1 unidad, código {{ $line['coupon']->code }})</span><span>−{{ cop($line['discount']) }}</span></p>@endif
                @endforeach
                <p class="flex justify-between text-slate-500"><span>{{ $method === 'recogida' ? 'Recogida en tienda' : 'Envío' }}</span><span>{{ $ship ? cop($ship) : 'Gratis' }}</span></p>
                <input type="hidden" name="shipping[{{ $g['store']->id }}]" value="{{ $method }}">
            </div>
        @endforeach
        <p class="mt-2 text-xs"><a href="{{ route('cart') }}" class="text-brand-600 hover:underline">Cambiar productos o entrega</a></p>
        <div class="mt-4 flex justify-between border-t border-slate-200 pt-3 text-lg font-bold"><span>Total</span><span>{{ cop($groups->sum('subtotal') + $shippingTotal) }}</span></div>

        <label class="mt-4 flex items-start gap-2 text-xs text-slate-600"><input type="checkbox" name="terms" value="1" class="mt-0.5" @checked(old('terms')) required>
            <span>Acepto los términos y condiciones y autorizo el tratamiento de mis datos personales según la política de privacidad (Ley 1581 de 2012).</span></label>
        <button class="btn-primary mt-4 w-full">Realizar el pedido</button>
    </aside>
</form>
@include('partials.track', ['event' => 'begin_checkout', 'params' => ['currency' => 'COP', 'value' => (int) $groups->sum('subtotal'), 'items' => $groups->flatMap(fn ($g) => $g['lines']->map(fn ($l) => \App\Support\Analytics::product($l['product'], $l['quantity'])))->values()->all()]])
@endsection
