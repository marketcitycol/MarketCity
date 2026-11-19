@extends('layouts.shop', ['title' => $store->name])
@section('canonical', route('store', $store))

@section('content')
<section class="overflow-hidden rounded-[22px] border border-[#e3e6ec] bg-white">
    <div class="h-24 md:h-32" style="background: {{ \App\Services\Content\BrandAssets::storeCover($store->products()->visible()->with('category')->get()->groupBy('category_id')->sortByDesc->count()->first()?->first()?->category?->slug) }}"></div>
    <div class="flex flex-wrap items-end gap-4 px-5 pb-5">
        <span class="-mt-10 grid h-20 w-20 place-items-center overflow-hidden rounded-full border-[3px] border-white bg-white text-2xl font-extrabold text-brand-700 shadow-[0_4px_12px_-4px_rgba(15,23,42,.35)]">
            @if ($logo = $store->logoUrl())<img src="{{ $logo }}" alt="" class="h-full w-full object-contain">@else{{ $store->initials() }}@endif
        </span>
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl leading-tight font-extrabold text-[#0f172a]">{{ $store->name }}</h1>
            @if ($store->isVerified())<p class="mt-1 inline-flex items-center gap-1 rounded-full bg-brand-900 px-2 py-0.5 text-xs font-semibold text-white" title="MarketCity revisó sus datos legales">{!! icon('verificado', 'h-3.5 w-3.5 text-[#f3d9a4]') !!} Tienda verificada</p>@endif
            <p class="mt-1 flex items-center gap-1 text-sm text-[#4a5068]">{!! icon('pin', 'h-4 w-4') !!}{{ $store->city }}, {{ $store->department }}</p>
        </div>
        <div class="text-sm text-[#4a5068]">
            <p class="flex items-center gap-1.5 font-semibold text-[#047a3a]">{!! icon('envio', 'h-4 w-4') !!}{{ $store->free_shipping ? 'Envío gratis' : 'Envío '.cop($store->shipping_fee) }}</p>
            @if ($store->pickup_enabled)<p class="flex items-center gap-1.5">{!! icon('tienda', 'h-4 w-4') !!}Recogida en tienda</p>@endif
        </div>
    </div>
    @if ($store->description)<p class="border-t border-[#eef0f4] px-5 py-3 text-sm text-[#4a5068]">{{ $store->description }}</p>@endif
</section>
<div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4 lg:grid-cols-5">
    @foreach ($products as $product) @include('partials.product-card') @endforeach
</div>
<div class="mt-6">{{ $products->links() }}</div>
@endsection
