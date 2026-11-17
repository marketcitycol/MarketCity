@if ($product->imageUrl())
    <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" class="h-full w-full object-cover" loading="lazy">
@else
    {{-- Sin foto: ícono de la categoría sobre un fondo suave --}}
    <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-brand-50 to-slate-100 {{ $size ?? 'text-6xl' }}" aria-hidden="true">{{ $product->category?->icon ?? '🛍️' }}</div>
@endif
