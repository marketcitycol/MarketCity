@extends('layouts.admin', ['title' => 'Productos'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Productos</h1>
    <form method="get" class="flex gap-2">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Nombre o SKU">
        <select class="input w-auto" name="tienda" onchange="this.form.submit()"><option value="">Todas las tiendas</option>@foreach ($stores as $s)<option value="{{ $s->id }}" @selected(request('tienda') == $s->id)>{{ $s->name }}</option>@endforeach</select>
    </form>
</div>
<div class="card mt-4 overflow-x-auto">
<table class="table-std">
    <thead><tr><th>Producto</th><th>Tienda</th><th>Precio</th><th>Stock</th><th>Vendidos</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @foreach ($products as $p)
        <tr>
            <td><a href="{{ route('product', $p) }}" target="_blank" class="font-medium hover:text-brand-600">{{ $p->name }}</a><br><span class="text-xs text-slate-500">{{ $p->sku }} · {{ $p->category?->name }}</span></td>
            <td>{{ $p->store->name }}</td>
            <td>{{ cop($p->price) }}</td>
            <td class="{{ $p->stock === 0 ? 'font-semibold text-red-600' : '' }}">{{ $p->stock }}</td>
            <td>{{ $p->sales_count }}</td>
            <td>{{ ['active' => 'Publicado', 'draft' => 'Borrador', 'paused' => 'Pausado'][$p->status] }}</td>
            <td class="text-right">
                <form method="post" action="{{ route('admin.products.status', $p) }}">@csrf
                    @if ($p->status === 'active')<button name="status" value="paused" class="btn-ghost btn-sm">Pausar</button>@else<button name="status" value="active" class="btn-ghost btn-sm">Publicar</button>@endif
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
