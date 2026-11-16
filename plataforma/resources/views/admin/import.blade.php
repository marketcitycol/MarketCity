@extends('layouts.admin', ['title' => 'Importar catálogo'])

@section('content')
<h1 class="text-2xl font-bold">Importar catálogo desde WooCommerce</h1>
<div class="mt-4 grid gap-6 xl:grid-cols-2">
    <form method="post" enctype="multipart/form-data" action="{{ route('admin.import.store') }}" class="card space-y-4 p-5">
        @csrf
        <div><label class="label" for="file">Archivo CSV de productos</label><input class="input" type="file" id="file" name="file" accept=".csv,text/csv" required></div>
        <div><label class="label" for="store_id">Tienda para los productos sin vendedor en el archivo</label>
            <select class="input" id="store_id" name="store_id">@foreach ($stores as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
        <button class="btn-primary">Importar</button>
        <p class="text-xs text-slate-500">Se puede repetir: los productos se actualizan por su ID de WooCommerce, no se duplican.</p>
    </form>
    <div class="card p-5 text-sm text-slate-600">
        <p class="font-semibold text-slate-800">Cómo sacar el archivo del sitio actual</p>
        <ol class="mt-2 list-decimal space-y-1 pl-5">
            <li>Entra a wp-admin de marketcity.co → <strong>Productos</strong> → <strong>Exportar</strong>.</li>
            <li>Deja "Todas las columnas", "Todos los tipos de productos" y "Todas las categorías".</li>
            <li>Pulsa <strong>Generar CSV</strong> y guarda el archivo.</li>
        </ol>
        <p class="mt-3">Exportar no cambia nada en la tienda actual. Solo se importa el catálogo (nombres, precios, stock, categorías, marcas y fotos por enlace); no clientes ni pedidos.</p>
    </div>
</div>
@endsection
