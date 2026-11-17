@extends('layouts.shop', ['title' => 'Vende en MarketCity'])

@section('content')
<div class="grid gap-8 lg:grid-cols-2">
    <div>
        <h1 class="text-3xl font-extrabold">Vende en MarketCity</h1>
        <p class="mt-3 text-slate-600">Publica tus productos, recibe pedidos de todo el país y cobra cada {{ \App\Models\Setting::get('settlement_business_days', 10) }} días hábiles después de cada venta.</p>
        <ul class="mt-6 space-y-3 text-sm">
            <li>✅ Tu tienda con su propia página: <em>marketcity.co/tienda/tu-tienda</em></li>
            <li>✅ Seller Center para productos, inventario, pedidos y guías</li>
            <li>✅ Nosotros cobramos al cliente y te liquidamos el neto de tus ventas</li>
        </ul>
    </div>
    <form method="post" action="{{ route('sell.store') }}" class="card space-y-4 p-6">
        @csrf
        <p class="font-semibold">Crea tu cuenta de vendedor</p>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="label" for="store_name">Nombre de la tienda</label><input class="input" id="store_name" name="store_name" value="{{ old('store_name') }}" required></div>
            <div class="sm:col-span-2"><label class="label" for="name">Tu nombre completo</label><input class="input" id="name" name="name" value="{{ old('name') }}" required></div>
            <div><label class="label" for="document_type">Documento</label>
                <select class="input" id="document_type" name="document_type"><option value="CC">Cédula</option><option value="NIT" @selected(old('document_type') === 'NIT')>NIT</option><option value="CE" @selected(old('document_type') === 'CE')>Cédula de extranjería</option></select></div>
            <div><label class="label" for="document_number">Número</label><input class="input" id="document_number" name="document_number" value="{{ old('document_number') }}" required></div>
            <div><label class="label" for="department">Departamento</label>
                <select class="input" id="department" name="department" required><option value="">Elige…</option>@foreach (array_keys(config('colombia.departments')) as $d)<option @selected(old('department') === $d)>{{ $d }}</option>@endforeach</select></div>
            <div><label class="label" for="city">Ciudad</label><input class="input" id="city" name="city" value="{{ old('city') }}" required></div>
            <div><label class="label" for="email">Correo</label><input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required></div>
            <div><label class="label" for="phone">Celular</label><input class="input" id="phone" name="phone" value="{{ old('phone') }}" required></div>
            <div><label class="label" for="password">Contraseña</label><input class="input" type="password" id="password" name="password" required minlength="8"></div>
            <div><label class="label" for="password_confirmation">Repite la contraseña</label><input class="input" type="password" id="password_confirmation" name="password_confirmation" required></div>
        </div>
        <label class="flex items-start gap-2 text-xs text-slate-600"><input type="checkbox" name="terms" value="1" class="mt-0.5" required> <span>Acepto los términos para vendedores y la política de tratamiento de datos.</span></label>
        <button class="btn-primary w-full">Enviar solicitud</button>
    </form>
</div>
@endsection
