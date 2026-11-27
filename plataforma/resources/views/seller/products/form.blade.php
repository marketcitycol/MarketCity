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
        <div><label class="label" for="image">Foto principal</label><input class="input" type="file" id="image" name="image" accept="image/*">
            @if ($product->imageUrl())<img src="{{ $product->imageUrl() }}" alt="" class="mt-2 h-20 w-20 rounded-lg border border-slate-200 object-cover">@endif</div>
        @php($gallery = $product->exists ? $product->images : collect())
        <fieldset>
            <legend class="label">Más fotos (hasta {{ \App\Models\ProductImage::MAX_PER_PRODUCT }})</legend>
            @if ($gallery->isNotEmpty())
                <div class="mt-1 flex flex-wrap gap-3">
                    @foreach ($gallery as $img)
                        <label class="text-center text-xs text-slate-600">
                            <img src="{{ $img->url() }}" alt="" class="h-20 w-20 rounded-lg border border-slate-200 object-cover">
                            <span class="mt-1 flex items-center justify-center gap-1"><input type="checkbox" name="remove_images[]" value="{{ $img->id }}"> Quitar</span>
                        </label>
                    @endforeach
                </div>
            @endif
            @if ($gallery->count() < \App\Models\ProductImage::MAX_PER_PRODUCT)
                <input class="input mt-2" type="file" id="gallery" name="gallery[]" accept="image/*" multiple aria-label="Agregar más fotos">
                <p class="mt-1 text-xs text-slate-500">Puedes elegir varias a la vez. Máximo 4 MB cada una.</p>
            @endif
            @error('gallery')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            @error('gallery.*')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </fieldset>
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
