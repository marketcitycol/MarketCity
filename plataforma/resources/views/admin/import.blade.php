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

@php($copier = app(\App\Services\LegacyMediaCopier::class))
@php($remaining = $copier->pendingQuery()->count())
@php($failed = count($copier->failed()))
<section class="card mt-6 max-w-2xl p-5 text-sm">
    <h2 class="font-semibold">Fotos del sitio anterior</h2>
    <p class="mt-1 text-slate-600">{{ $remaining }} productos todavía muestran la foto desde marketcity.co. Cópialas aquí antes de apagar WordPress; se hace por lotes de 15 y la página sigue sola.@if ($failed) {{ $failed }} no se pudieron copiar (enlace roto o archivo que no es imagen).@endif</p>
    <form method="post" action="{{ route('admin.import.media') }}" class="mt-3 flex flex-wrap items-center gap-3" id="mc-media-form">
        @csrf
        <button class="btn-primary" @disabled(! $remaining && ! $failed)>Copiar fotos</button>
        @if ($failed)<label class="flex items-center gap-2"><input type="checkbox" name="reintentar" value="1"> Reintentar las que fallaron</label>@endif
    </form>
    @if (session('media') && session('media')['remaining'] > 0 && session('media')['copied'] > 0)
        <p class="mt-2 text-xs text-slate-500">Siguiente lote en 2 segundos…</p>
        <script>setTimeout(function () { document.getElementById('mc-media-form').submit(); }, 2000);</script>
    @endif
</section>
@endsection
