@extends('layouts.shop')

@section('content')
<nav class="text-xs text-slate-500" aria-label="Ruta"><a href="{{ route('home') }}" class="hover:underline">Inicio</a> / {{ $title }}</nav>
<div class="mt-2 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold">{{ request('q') ? 'Resultados para «'.request('q').'»' : $title }}</h1>
        <p class="text-sm text-slate-500">{{ $products->total() }} {{ $products->total() === 1 ? 'producto' : 'productos' }}</p>
    </div>
    <form method="get" class="flex items-center gap-2 text-sm">
        @foreach (request()->except('orderby', 'page') as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
        <label for="orderby" class="text-slate-500">Ordenar</label>
        <select id="orderby" name="orderby" class="input w-auto" onchange="this.form.submit()">
            <option value="">Relevancia</option>
            <option value="price" @selected(request('orderby') === 'price')>Menor precio</option>
            <option value="price-desc" @selected(request('orderby') === 'price-desc')>Mayor precio</option>
            <option value="date" @selected(request('orderby') === 'date')>Más nuevos</option>
        </select>
    </form>
</div>

<div class="mt-5 grid gap-6 lg:grid-cols-[230px_1fr]">
    <aside>
        <form method="get" class="card space-y-4 p-4 text-sm">
            @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
            @if (request('orderby'))<input type="hidden" name="orderby" value="{{ request('orderby') }}">@endif
            <p class="font-semibold">Filtrar</p>
            <div>
                <label class="label" for="cuota">Cuota de hasta</label>
                <select id="cuota" name="cuota" class="input">
                    <option value="">Cualquier cuota</option>
                    @foreach ([25000, 50000, 100000, 200000, 500000] as $c)
                        <option value="{{ $c }}" @selected((int) request('cuota') === $c)>{{ cop($c) }} / mes</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div><label class="label" for="min_price">Desde</label><input id="min_price" name="min_price" type="number" min="0" step="1000" value="{{ request('min_price') }}" class="input" placeholder="$"></div>
                <div><label class="label" for="max_price">Hasta</label><input id="max_price" name="max_price" type="number" min="0" step="1000" value="{{ request('max_price') }}" class="input" placeholder="$"></div>
            </div>
            <div>
                <label class="label" for="marca">Marca</label>
                <select id="marca" name="marca" class="input">
                    <option value="">Todas</option>
                    @foreach ($brands as $b)<option value="{{ $b->slug }}" @selected(request('marca') === $b->slug)>{{ $b->name }}</option>@endforeach
                </select>
            </div>
            <label class="flex items-center gap-2"><input type="checkbox" name="ofertas" value="1" @checked(request('ofertas'))> Solo ofertas</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="disponibles" value="1" @checked(request('disponibles'))> Solo disponibles</label>
            <div class="flex gap-2">
                <button class="btn-primary flex-1">Aplicar</button>
                <a href="{{ url()->current() }}" class="btn-ghost">Limpiar</a>
            </div>
        </form>
    </aside>

    <div>
        @if ($products->isEmpty())
            <div class="card p-10 text-center">
                <p class="text-4xl">🔎</p>
                <p class="mt-2 font-semibold">No encontramos productos con esos filtros</p>
                <p class="text-sm text-slate-500">Prueba con otra búsqueda o quita algún filtro.</p>
                <a href="{{ route('shop') }}" class="btn-primary mt-4">Ver todo el catálogo</a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                @foreach ($products as $product) @include('partials.product-card') @endforeach
            </div>
            <div class="mt-6">{{ $products->links() }}</div>
        @endif
    </div>
</div>
@endsection
