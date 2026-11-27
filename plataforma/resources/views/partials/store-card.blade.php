{{-- Tarjeta de tienda (diseño de /tiendas en marketcity.co): portada, logo, sello, categoría, ciudad y tres productos. --}}
@php($official = $store->isVerified() || $store->slug === 'marketcity')
@php($main = $store->mainCategory ?? null)
@php($thumbs = $store->thumbs ?? collect())
<article class="flex flex-col overflow-hidden rounded-[18px] border bg-white {{ $official ? 'border-gold-500' : 'border-line' }}">
    <a href="{{ route('store', $store) }}" class="relative block h-[84px]" style="background: {{ \App\Services\Content\BrandAssets::storeCover($main?->slug) }}" aria-label="{{ $store->name }}">
        @if ($store->featured)
            <span class="absolute top-2.5 left-2.5 rounded-full bg-accent-400 px-2 py-0.5 text-xs font-bold text-brand-900">Destacada</span>
        @endif
        @if ($official)
            <span class="absolute top-2.5 right-2.5 inline-flex items-center gap-1 rounded-full bg-brand-900 px-2 py-0.5 text-xs font-semibold text-white">{!! icon('verificado', 'h-3.5 w-3.5 text-gold-200') !!} {{ $store->isVerified() ? 'Tienda verificada' : 'Tienda oficial' }}</span>
        @endif
    </a>
    <div class="relative flex flex-1 flex-col px-4 pb-4">
        <span class="-mt-8 grid h-16 w-16 place-items-center overflow-hidden rounded-full border-[2.4px] border-white bg-white text-lg font-extrabold text-brand-700 shadow-[0_4px_12px_-4px_rgba(15,23,42,.35)]">
            @if ($logo = $store->logoUrl())<img src="{{ $logo }}" alt="" class="h-full w-full object-contain" loading="lazy">@else{{ $store->initials() }}@endif
        </span>
        <h3 class="mt-2 text-lg leading-tight font-extrabold text-ink-900"><a href="{{ route('store', $store) }}" class="hover:text-brand-600">{{ $store->name }}</a></h3>
        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-sm text-ink-700">
            @if ($main)<span class="inline-flex items-center gap-1">{!! icon(\App\Services\Content\Icons::forCategory($main->slug), 'h-4 w-4') !!}{{ $main->name }}</span>@endif
            @if ($store->city)<span class="inline-flex items-center gap-1">{!! icon('pin', 'h-4 w-4') !!}{{ $store->city }}{{ $store->department && $store->department !== $store->city ? ', '.$store->department : '' }}</span>@endif
        </p>
        <p class="mt-1 flex items-center gap-1 text-xs text-ink-500">{!! icon('estrella', 'h-3.5 w-3.5') !!} Aún sin reseñas</p>
        @if ($thumbs->isNotEmpty())
            <div class="mt-3 grid grid-cols-3 gap-2">
                @foreach ($thumbs as $t)
                    <a href="{{ route('product', $t) }}" class="aspect-square overflow-hidden rounded-[10px] border border-line bg-surface"><img src="{{ $t->imageUrl() }}" alt="{{ $t->name }}" class="h-full w-full object-cover" loading="lazy"></a>
                @endforeach
            </div>
        @endif
        <div class="mt-auto flex items-center justify-between gap-3 pt-4">
            <span class="text-sm font-semibold text-ink-700">{{ $store->products_count ?? $store->products()->visible()->count() }} productos</span>
            <a href="{{ route('store', $store) }}" class="inline-flex h-11 items-center rounded-xl bg-brand-600 px-4 text-sm font-bold text-white hover:bg-brand-700">Ver tienda →</a>
        </div>
    </div>
</article>
