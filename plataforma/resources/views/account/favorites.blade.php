@extends('account.layout', ['pageTitle' => 'Mis favoritos'])

@section('account')
@if ($favorites->isEmpty())
    <p class="card p-8 text-center text-sm text-slate-500">Toca «Guardar en favoritos» en un producto para verlo aquí. <a href="{{ route('shop') }}" class="text-brand-600 hover:underline">Ir a la tienda</a></p>
@else
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
        @foreach ($favorites as $f)
            <div>
                @include('partials.product-card', ['product' => $f->product])
                <form method="post" action="{{ route('favorites.toggle', $f->product) }}" class="mt-1">@csrf<button class="text-xs text-red-600 hover:underline">Quitar de favoritos</button></form>
            </div>
        @endforeach
    </div>
@endif
@endsection
