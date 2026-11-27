@extends('layouts.admin', ['title' => $coupon->exists ? 'Cupón '.$coupon->code : 'Nuevo cupón'])

@section('content')
<a href="{{ route('admin.coupons') }}" class="text-sm text-brand-600 hover:underline">← Cupones</a>
<h1 class="mt-2 text-2xl font-bold">{{ $coupon->exists ? 'Cupón '.$coupon->code : 'Nuevo cupón' }}</h1>

<form method="post" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="card mt-4 grid gap-4 p-5 sm:grid-cols-2">
    @csrf @if ($coupon->exists) @method('put') @endif
    <div><label class="label" for="code">Código</label><input class="input font-mono uppercase" id="code" name="code" value="{{ old('code', $coupon->code) }}" required maxlength="40" placeholder="BIENVENIDA10"></div>
    <div><label class="label" for="description">Descripción interna (opcional)</label><input class="input" id="description" name="description" value="{{ old('description', $coupon->description) }}" maxlength="200"></div>
    <div><label class="label" for="type">Tipo</label>
        <select class="input" id="type" name="type">@foreach (\App\Models\Coupon::TYPES as $k => $label)<option value="{{ $k }}" @selected(old('type', $coupon->type) === $k)>{{ $label }}</option>@endforeach</select></div>
    <div><label class="label" for="amount">Valor (% o pesos; vacío en envío gratis)</label><input class="input" type="number" min="1" id="amount" name="amount" value="{{ old('amount', $coupon->amount ?: null) }}"></div>
    <div><label class="label" for="max_discount">Tope del descuento en pesos (solo porcentaje, opcional)</label><input class="input" type="number" min="1" id="max_discount" name="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}"></div>
    <div><label class="label" for="min_subtotal">Compra mínima en productos que apliquen</label><input class="input" type="number" min="0" id="min_subtotal" name="min_subtotal" value="{{ old('min_subtotal', $coupon->min_subtotal) }}"></div>
    <div><label class="label" for="store_id">Tienda</label>
        <select class="input" id="store_id" name="store_id"><option value="">Todo el sitio (lo asume MarketCity)</option>@foreach ($stores as $st)<option value="{{ $st->id }}" @selected((int) old('store_id', $coupon->store_id) === $st->id)>{{ $st->name }} (lo asume la tienda)</option>@endforeach</select></div>
    <label class="flex items-center gap-2 self-end text-sm"><input type="checkbox" name="exclude_sale_items" value="1" @checked(old('exclude_sale_items', $coupon->exclude_sale_items))> No aplica a productos en oferta, campaña o precio con correo</label>
    <div><label class="label" for="usage_limit">Usos en total (vacío = sin límite)</label><input class="input" type="number" min="1" id="usage_limit" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}"></div>
    <div><label class="label" for="usage_limit_per_customer">Usos por cliente (por correo)</label><input class="input" type="number" min="1" id="usage_limit_per_customer" name="usage_limit_per_customer" value="{{ old('usage_limit_per_customer', $coupon->usage_limit_per_customer) }}"></div>
    <div><label class="label" for="starts_at">Empieza (hora de Colombia, opcional)</label><input class="input" type="datetime-local" id="starts_at" name="starts_at" value="{{ old('starts_at', $coupon->starts_at ? fecha($coupon->starts_at, 'Y-m-d\TH:i') : '') }}"></div>
    <div><label class="label" for="ends_at">Termina (hora de Colombia, opcional)</label><input class="input" type="datetime-local" id="ends_at" name="ends_at" value="{{ old('ends_at', $coupon->ends_at ? fecha($coupon->ends_at, 'Y-m-d\TH:i') : '') }}"></div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" value="1" @checked(old('active', $coupon->active))> Activo</label>
    <div class="sm:col-span-2"><button class="btn-primary">Guardar cupón</button></div>
</form>
@endsection
