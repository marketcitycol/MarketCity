@extends('layouts.admin', ['title' => $campaign->exists ? $campaign->name : 'Nueva campaña'])

@section('content')
<a href="{{ route('admin.campaigns') }}" class="text-sm text-brand-600 hover:underline">← Campañas</a>
<h1 class="mt-2 text-2xl font-bold">{{ $campaign->exists ? $campaign->name : 'Nueva campaña' }}</h1>

<form method="post" action="{{ $campaign->exists ? route('admin.campaigns.update', $campaign) : route('admin.campaigns.store') }}" class="card mt-4 grid gap-4 p-5 sm:grid-cols-2">
    @csrf @if ($campaign->exists) @method('put') @endif
    <div><label class="label" for="name">Nombre</label><input class="input" id="name" name="name" value="{{ old('name', $campaign->name) }}" required maxlength="80" placeholder="Black City"></div>
    <div><label class="label" for="badge">Etiqueta (opcional)</label><input class="input" id="badge" name="badge" value="{{ old('badge', $campaign->badge) }}" maxlength="30" placeholder="BLACK CITY"></div>
    @unless ($campaign->exists)<div><label class="label" for="slug">Dirección (opcional)</label><input class="input" id="slug" name="slug" value="{{ old('slug') }}" placeholder="black-city"></div>@endunless
    <div><label class="label" for="unit_cap">Tope de unidades por producto (vacío = sin tope)</label><input class="input" type="number" min="1" id="unit_cap" name="unit_cap" value="{{ old('unit_cap', $campaign->unit_cap) }}"></div>
    <div><label class="label" for="starts_at">Empieza (hora de Colombia)</label><input class="input" type="datetime-local" id="starts_at" name="starts_at" required value="{{ old('starts_at', $campaign->starts_at ? fecha($campaign->starts_at, 'Y-m-d\TH:i') : '') }}"></div>
    <div><label class="label" for="ends_at">Termina (hora de Colombia)</label><input class="input" type="datetime-local" id="ends_at" name="ends_at" required value="{{ old('ends_at', $campaign->ends_at ? fecha($campaign->ends_at, 'Y-m-d\TH:i') : '') }}"></div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="active" value="1" @checked(old('active', $campaign->active))> Encendida</label>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="email_price_enabled" value="1" @checked(old('email_price_enabled', $campaign->email_price_enabled))> Precio con correo encendido (botón «Ver mi precio» en la ficha)</label>
    <div class="sm:col-span-2"><button class="btn-primary">Guardar campaña</button></div>
</form>

@if ($campaign->exists)
<section class="card mt-6 p-5">
    <h2 class="font-semibold">Productos</h2>
    <form method="post" action="{{ route('admin.campaigns.products', $campaign) }}" class="mt-3 grid gap-3 sm:grid-cols-4">
        @csrf
        <div><label class="label" for="product">SKU o número del producto</label><input class="input" id="product" name="product" required value="{{ old('product') }}"></div>
        <div><label class="label" for="campaign_price">Precio de campaña</label><input class="input" type="number" min="1000" id="campaign_price" name="campaign_price" required value="{{ old('campaign_price') }}"></div>
        <div><label class="label" for="email_price">Precio con correo (opcional)</label><input class="input" type="number" min="1000" id="email_price" name="email_price" value="{{ old('email_price') }}"></div>
        <div class="flex items-end"><button class="btn-primary w-full">Agregar o actualizar</button></div>
    </form>
    <div class="mt-4 overflow-x-auto">
    <table class="table-std">
        <thead><tr><th>Producto</th><th>Precio normal</th><th>Campaña</th><th>Con correo</th><th>Vendidas</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($items as $item)
            @php($sold = $campaigns->soldUnits($item))
            <tr>
                <td>{{ $item->product?->name }}<br><span class="text-xs text-slate-500">SKU {{ $item->product?->sku ?? '—' }} · {{ $item->product?->store?->name }}</span></td>
                <td>{{ cop($item->applied_at ? $item->base_price : $item->product?->price) }}</td>
                <td>{{ cop($item->campaign_price) }}</td>
                <td>{{ $item->email_price ? cop($item->email_price) : '—' }}</td>
                <td>{{ $sold }}{{ $campaign->unit_cap ? ' / '.$campaign->unit_cap : '' }}</td>
                <td><span class="badge {{ $item->applied_at ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $item->closed_at ? 'Tope lleno' : ($item->applied_at ? 'Precio aplicado' : 'Sin aplicar') }}</span></td>
                <td class="text-right">
                    <form method="post" action="{{ route('admin.campaigns.products.remove', [$campaign, $item]) }}">@csrf @method('delete')<button class="btn-ghost btn-sm">Quitar</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-slate-500">Sin productos todavía.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</section>
@endif
@endsection
