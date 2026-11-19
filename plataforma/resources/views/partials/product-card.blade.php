@php($inst = installments())
<article class="group card flex flex-col overflow-hidden transition hover:shadow-md">
    <a href="{{ route('product', $product) }}" class="relative block aspect-square bg-slate-100">
        @include('partials.product-image', ['product' => $product, 'size' => 'text-5xl'])
        @if ($product->isOnSale())
            <span class="badge absolute top-2 left-2 bg-offer-600 text-white">-{{ $product->discountPercent() }}%</span>
        @endif
        @unless ($product->inStock())
            <span class="badge absolute top-2 right-2 bg-slate-800 text-white">Agotado</span>
        @endunless
    </a>
    <div class="flex flex-1 flex-col gap-1 p-3">
        <a href="{{ route('product', $product) }}" class="line-clamp-2 text-sm font-medium text-slate-800 group-hover:text-brand-600">{{ $product->name }}</a>
        <p class="text-xs text-slate-500">Vendido por {{ $product->store->name }}</p>
        <div class="mt-auto pt-2">
            @if ($product->isOnSale())
                <p class="text-xs text-slate-500 line-through">{{ cop($product->regular_price) }}</p>
            @endif
            <p class="text-lg font-bold text-slate-900">{{ cop($product->price) }}</p>
            <p class="text-xs font-medium text-credit-600">o {{ $inst }} cuotas de {{ cop(ceil($product->price / $inst)) }}*</p>
        </div>
    </div>
</article>
