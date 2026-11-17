@extends('layouts.seller', ['title' => 'Productos'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Productos</h1>
    <div class="flex gap-2">
        <form method="get"><input class="input" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre o SKU"></form>
        <a href="{{ route('seller.products.create') }}" class="btn-primary">Nuevo producto</a>
    </div>
</div>
<div class="card mt-4 overflow-x-auto">
    @if ($products->isEmpty())
        <p class="p-8 text-center text-sm text-slate-500">Aún no tienes productos. <a href="{{ route('seller.products.create') }}" class="text-brand-600 hover:underline">Crea el primero</a>.</p>
    @else
    <table class="table-std">
        <thead><tr><th></th><th>Producto</th><th>SKU</th><th>Precio</th><th>Stock</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @foreach ($products as $product)
            <tr>
                <td class="w-14"><div class="h-12 w-12 overflow-hidden rounded">@include('partials.product-image', ['size' => 'text-xl'])</div></td>
                <td><span class="font-medium">{{ $product->name }}</span><br><span class="text-xs text-slate-500">{{ $product->category?->name }}</span></td>
                <td>{{ $product->sku }}</td>
                <td>{{ cop($product->price) }}</td>
                <td class="{{ $product->stock === 0 ? 'font-semibold text-red-600' : '' }}">{{ $product->stock }}</td>
                <td><span class="badge {{ $product->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ ['active' => 'Publicado', 'draft' => 'Borrador', 'paused' => 'Pausado'][$product->status] }}</span></td>
                <td class="text-right"><a href="{{ route('seller.products.edit', $product) }}" class="text-brand-600 hover:underline">Editar</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
