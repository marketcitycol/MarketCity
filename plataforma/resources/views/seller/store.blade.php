@extends('layouts.seller', ['title' => 'Mi tienda'])

@section('content')
<h1 class="text-2xl font-bold">Mi tienda</h1>
<form method="post" action="{{ route('seller.store.update') }}" class="card mt-4 max-w-2xl space-y-4 p-5">
    @csrf @method('put')
    <div><label class="label" for="name">Nombre</label><input class="input" id="name" name="name" value="{{ old('name', $store->name) }}" required></div>
    <div><label class="label" for="description">Descripción</label><textarea class="input" id="description" name="description" rows="3">{{ old('description', $store->description) }}</textarea></div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label" for="department">Departamento</label><select class="input" id="department" name="department">@foreach (array_keys(config('colombia.departments')) as $d)<option @selected(old('department', $store->department) === $d)>{{ $d }}</option>@endforeach</select></div>
        <div><label class="label" for="city">Ciudad</label><input class="input" id="city" name="city" value="{{ old('city', $store->city) }}" required></div>
        <div><label class="label" for="address">Dirección (para recogidas)</label><input class="input" id="address" name="address" value="{{ old('address', $store->address) }}"></div>
        <div><label class="label" for="phone">Teléfono</label><input class="input" id="phone" name="phone" value="{{ old('phone', $store->phone) }}"></div>
    </div>
    <fieldset class="rounded-lg border border-slate-200 p-4">
        <legend class="px-1 text-sm font-semibold">Envíos</legend>
        <input type="hidden" name="free_shipping" value="0"><input type="hidden" name="pickup_enabled" value="0">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="free_shipping" value="1" @checked(old('free_shipping', $store->free_shipping))> Envío gratis</label>
        <div class="mt-2"><label class="label" for="shipping_fee">Costo de envío si no es gratis (COP)</label><input class="input" type="number" id="shipping_fee" name="shipping_fee" min="0" step="100" value="{{ old('shipping_fee', $store->shipping_fee) }}"></div>
        <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="pickup_enabled" value="1" @checked(old('pickup_enabled', $store->pickup_enabled))> Permitir recogida en la tienda</label>
        <div class="mt-2"><label class="label" for="pickup_fee">Costo de la recogida (COP)</label><input class="input" type="number" id="pickup_fee" name="pickup_fee" min="0" step="100" value="{{ old('pickup_fee', $store->pickup_fee) }}"></div>
    </fieldset>
    <fieldset class="rounded-lg border border-slate-200 p-4">
        <legend class="px-1 text-sm font-semibold">Logística</legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="dispatch_days">Despachas en máximo (días hábiles con el pago confirmado)</label><input class="input" type="number" id="dispatch_days" name="dispatch_days" min="1" max="15" required value="{{ old('dispatch_days', $store->dispatch_days ?? 2) }}"></div>
            <div><label class="label" for="carriers">Transportadoras (opcional)</label><input class="input" id="carriers" name="carriers" maxlength="150" value="{{ old('carriers', $store->carriers) }}" placeholder="Servientrega, Interrapidísimo"></div>
        </div>
        <p class="mt-2 text-xs text-slate-500">Se muestra en cada producto en «Lo que debes saber». Promete solo lo que cumples: el cliente lo puede reclamar (Ley 1480).</p>
    </fieldset>
    <fieldset class="rounded-lg border border-slate-200 p-4">
        <legend class="px-1 text-sm font-semibold">Notificaciones</legend>
        <div><label class="label" for="notify_email">Correo para avisos (vacío = {{ $store->user?->email }})</label><input class="input" type="email" id="notify_email" name="notify_email" maxlength="120" value="{{ old('notify_email', $store->notify_email) }}">
            @error('notify_email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <input type="hidden" name="notify_new_order" value="0">
        <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="notify_new_order" value="1" @checked(old('notify_new_order', $store->notify_new_order ?? true))> Avisarme por correo cada pedido pagado</label>
        <p class="mt-2 text-xs text-slate-500">Las PQRS siempre te llegan a este correo: tienen plazos legales de respuesta.</p>
    </fieldset>
    <button class="btn-primary">Guardar</button>
</form>
@endsection
