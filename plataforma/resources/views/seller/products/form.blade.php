@extends('layouts.seller', ['title' => $product->exists ? 'Editar producto' : 'Nuevo producto'])

@section('content')
<a href="{{ route('seller.products.index') }}" class="text-sm text-brand-600 hover:underline">← Productos</a>
<h1 class="mt-2 text-2xl font-bold">{{ $product->exists ? 'Editar «'.$product->name.'»' : 'Nuevo producto' }}</h1>

<form method="post" enctype="multipart/form-data" action="{{ $product->exists ? route('seller.products.update', $product) : route('seller.products.store') }}" class="mt-5 grid gap-5 lg:grid-cols-[1fr_320px]">
    @csrf
    @if ($product->exists) @method('put') @endif
    <div class="card space-y-4 p-5">
        <div><label class="label" for="name">Nombre</label><input class="input" id="name" name="name" value="{{ old('name', $product->name) }}" required></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="label" for="category_id">Categoría</label>
                <select class="input" id="category_id" name="category_id" required>@foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>@endforeach</select></div>
            <div><label class="label" for="brand_id">Marca</label>
                <select class="input" id="brand_id" name="brand_id"><option value="">Sin marca</option>@foreach ($brands as $b)<option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id) == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
        </div>
        <div><label class="label" for="short_description">Resumen</label><input class="input" id="short_description" name="short_description" value="{{ old('short_description', $product->short_description) }}" maxlength="300"></div>
        <div><label class="label" for="description">Descripción</label><textarea class="input" id="description" name="description" rows="6">{{ old('description', $product->description) }}</textarea></div>
        <div><label class="label" for="image">Foto principal</label><input class="input" type="file" id="image" name="image" accept="image/*"></div>
    </div>
    <div class="card h-fit space-y-4 p-5">
        <div><label class="label" for="price">Precio de venta (COP)</label><input class="input" type="number" id="price" name="price" min="1000" step="100" value="{{ old('price', $product->price) }}" required></div>
        <div><label class="label" for="regular_price">Precio antes (opcional)</label><input class="input" type="number" id="regular_price" name="regular_price" min="0" step="100" value="{{ old('regular_price', $product->regular_price) }}"></div>
        <div class="grid grid-cols-2 gap-3">
            <div><label class="label" for="stock">Stock</label><input class="input" type="number" id="stock" name="stock" min="0" value="{{ old('stock', $product->stock) }}" required></div>
            <div><label class="label" for="sku">SKU</label><input class="input" id="sku" name="sku" value="{{ old('sku', $product->sku) }}"></div>
        </div>
        <div><label class="label" for="color">Color</label><input class="input" id="color" name="color" value="{{ old('color', $product->color) }}"></div>
        <div><label class="label" for="status">Estado</label>
            <select class="input" id="status" name="status">
                @foreach (['active' => 'Publicado', 'draft' => 'Borrador', 'paused' => 'Pausado'] as $k => $v)<option value="{{ $k }}" @selected(old('status', $product->status) === $k)>{{ $v }}</option>@endforeach
            </select></div>
        <button class="btn-primary w-full">Guardar</button>
    </div>
</form>
@endsection
