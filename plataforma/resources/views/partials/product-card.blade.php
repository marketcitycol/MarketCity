{{-- Tarjeta de producto con el diseño de marketcity.co: precio, cuota con Addi o Sistecrédito, envío y «Lo llevo». --}}
@php($inst = installments())
<article class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-[#e3e6ec] bg-white transition hover:shadow-[0_8px_24px_-12px_rgba(15,23,42,.25)]">
    <a href="{{ route('product', $product) }}" class="relative block aspect-square bg-[#f7f8fa]">
        @include('partials.product-image', ['product' => $product, 'size' => 'text-5xl'])
        @if ($product->isOnSale())
            <span class="absolute top-2 left-2 rounded-full bg-offer-600 px-2 py-0.5 text-xs font-bold text-white">{{ $product->discountPercent() }}%</span>
        @endif
        @isset($rank)
            <span class="absolute top-2 right-2 grid h-7 min-w-7 place-items-center rounded-full bg-brand-900 px-1.5 text-xs font-extrabold text-white">#{{ $rank }}</span>
        @endisset
        @unless ($product->inStock())
            <span class="absolute bottom-2 left-2 rounded-full bg-slate-800 px-2 py-0.5 text-xs font-bold text-white">Agotado</span>
        @endunless
    </a>
    <div class="flex flex-1 flex-col gap-1 p-2.5 sm:p-3">
        <a href="{{ route('product', $product) }}" class="line-clamp-2 min-h-10 text-sm leading-5 font-medium text-[#1e293b] group-hover:text-brand-600">{{ $product->name }}</a>
        <p class="truncate text-xs text-[#64748b]">Por: <a href="{{ route('store', $product->store) }}" class="font-semibold text-[#0b5cff] hover:underline">{{ $product->store->name }}</a></p>
        <div class="mt-auto pt-1">
            <p class="flex flex-wrap items-baseline gap-x-1.5">
                <span class="text-lg font-bold text-[#0f172a]">{{ cop($product->price) }}</span>
                @if ($product->isOnSale())<del class="text-[13px] text-[#64748b]">{{ cop($product->regular_price) }}</del>@endif
            </p>
            <p class="text-[11.5px] leading-4 font-semibold text-credit-600">o {{ $inst }} cuotas de <strong>{{ cop(ceil($product->price / $inst)) }}</strong> con Addi o Sistecrédito*</p>
            <p class="mt-1 flex items-center gap-1 text-[11px] font-semibold text-[#047a3a]">{!! icon('envio', 'h-3.5 w-3.5') !!} Envío a toda Colombia</p>
        </div>
        @if ($product->inStock())
            <form method="post" action="{{ route('cart.add') }}" class="mt-2" data-add-to-cart>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="1">
                <button class="flex h-11 w-full items-center justify-center gap-1.5 rounded-xl bg-brand-600 text-sm font-bold text-white hover:bg-brand-700">{!! icon('carrito', 'h-4 w-4') !!} Lo llevo</button>
            </form>
        @else
            <a href="{{ route('product', $product) }}" class="mt-2 flex h-11 w-full items-center justify-center rounded-xl border border-[#e3e6ec] text-sm font-bold text-[#4a5068]">Ver producto</a>
        @endif
    </div>
</article>
