@extends('layouts.shop', ['title' => 'Completa tu pedido'])

@section('content')
<h1 class="text-2xl font-bold">Completa tu pedido</h1>
<form method="post" action="{{ route('checkout.place') }}" class="mt-5 grid gap-6 lg:grid-cols-[1fr_380px]">
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
        </section>

        <section class="card p-5">
            <h2 class="font-semibold">2. Dirección de entrega</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><label class="label" for="department">Departamento</label>
                    <select class="input" id="department" name="department" required>
                        <option value="">Elige…</option>
                        @foreach (array_keys(config('colombia.departments')) as $d)<option @selected(old('department') === $d)>{{ $d }}</option>@endforeach
                    </select></div>
                <div><label class="label" for="city">Ciudad o municipio</label>
                    <input class="input" id="city" name="city" list="cities" value="{{ old('city') }}" required>
                    <datalist id="cities">@foreach (collect(config('colombia.departments'))->flatten()->sort() as $c)<option value="{{ $c }}">@endforeach</datalist></div>
                <div class="sm:col-span-2"><label class="label" for="address">Dirección</label><input class="input" id="address" name="address" value="{{ old('address') }}" required placeholder="Calle 00 # 00-00" autocomplete="street-address"></div>
                <div class="sm:col-span-2"><label class="label" for="address_2">Apartamento, barrio o indicaciones (opcional)</label><input class="input" id="address_2" name="address_2" value="{{ old('address_2') }}"></div>
                <div class="sm:col-span-2"><label class="label" for="notes">Notas del pedido (opcional)</label><textarea class="input" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea></div>
            </div>
        </section>

        <section class="card p-5">
            <h2 class="font-semibold">3. Medio de pago</h2>
            <div class="mt-4 space-y-2">
                @foreach ($gateways as $key => $g)
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                        <input type="radio" name="payment_method" value="{{ $key }}" class="mt-1" @checked(old('payment_method', array_key_first($gateways)) === $key)>
                        <span><span class="block font-medium">{{ $g->label() }}</span><span class="block text-sm text-slate-500">{{ $g->description() }}</span></span>
                    </label>
                @endforeach
                <p class="pt-1 text-xs text-slate-500">Bold, Wompi, Mercado Pago, Addi, Sistecrédito y MarketCityPay se conectan en la siguiente etapa.</p>
            </div>
        </section>
    </div>

    <aside class="card h-fit p-5 lg:sticky lg:top-32">
        <h2 class="font-semibold">Tu pedido</h2>
        @php($shippingTotal = 0)
        @foreach ($groups as $g)
            @php($method = $cart->shippingChoice($g['store']->id))
            @php($ship = \App\Services\Cart::shippingCost($g['store'], $method))
            @php($shippingTotal += $ship)
            <div class="mt-4 border-t border-slate-100 pt-3 text-sm">
                <p class="font-medium">{{ $g['store']->name }}</p>
                @foreach ($g['lines'] as $line)
                    <p class="flex justify-between text-slate-600"><span>{{ $line['quantity'] }} × {{ \Illuminate\Support\Str::limit($line['product']->name, 32) }}</span><span>{{ cop($line['line_total']) }}</span></p>
                @endforeach
                <p class="flex justify-between text-slate-500"><span>{{ $method === 'recogida' ? 'Recogida en tienda' : 'Envío' }}</span><span>{{ $ship ? cop($ship) : 'Gratis' }}</span></p>
                <input type="hidden" name="shipping[{{ $g['store']->id }}]" value="{{ $method }}">
            </div>
        @endforeach
        <p class="mt-2 text-xs"><a href="{{ route('cart') }}" class="text-brand-600 hover:underline">Cambiar productos o entrega</a></p>
        <div class="mt-4 flex justify-between border-t border-slate-200 pt-3 text-lg font-bold"><span>Total</span><span>{{ cop($groups->sum('subtotal') + $shippingTotal) }}</span></div>

        <label class="mt-4 flex items-start gap-2 text-xs text-slate-600"><input type="checkbox" name="terms" value="1" class="mt-0.5" @checked(old('terms')) required>
            <span>Acepto los términos y condiciones y autorizo el tratamiento de mis datos personales según la política de privacidad (Ley 1581 de 2012).</span></label>
        <label class="mt-2 flex items-start gap-2 text-xs text-slate-600"><input type="checkbox" name="marketing_optin" value="1" class="mt-0.5" @checked(old('marketing_optin'))>
            <span>Quiero recibir ofertas por correo y WhatsApp (puedo retirarme cuando quiera).</span></label>
        <button class="btn-primary mt-4 w-full">Realizar el pedido</button>
    </aside>
</form>
@endsection
