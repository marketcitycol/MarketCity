{{-- Carrusel de productos: desliza en celular, cuadrícula en escritorio. --}}
<div class="-mx-3.5 mt-3 flex snap-x snap-mandatory gap-3 overflow-x-auto px-3.5 pb-2 [scrollbar-width:none] md:mx-0 md:grid md:grid-cols-4 md:overflow-visible md:px-0 lg:grid-cols-5 [&::-webkit-scrollbar]:hidden">
    @foreach ($products as $i => $product)
        <div class="w-[46%] shrink-0 snap-start sm:w-[31%] md:w-auto {{ $i >= 10 ? 'md:hidden' : '' }}">
            @include('partials.product-card', isset($ranked) ? ['rank' => $i + 1] : [])
        </div>
    @endforeach
</div>
