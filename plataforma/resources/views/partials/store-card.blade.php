<a href="{{ route('store', $store) }}" class="card flex items-center gap-3 p-4 hover:border-brand-500">
    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-100 font-bold text-brand-700">{{ $store->initials() }}</span>
    <span>
        <span class="block font-semibold">{{ $store->name }}@if ($store->isVerified()) <span class="badge bg-emerald-100 text-emerald-700" title="MarketCity revisó sus datos legales">Verificada</span>@endif</span>
        <span class="block text-xs text-slate-500">{{ $store->city }} · {{ $store->products_count ?? $store->products()->count() }} productos</span>
    </span>
</a>
