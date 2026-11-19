{{-- Título de sección del inicio: título, subtítulo y enlace «Ver todo →». --}}
<div class="flex items-end justify-between gap-3">
    <div class="min-w-0">
        <h2 class="text-xl leading-tight font-bold tracking-[-0.01em] text-ink-900 md:text-2xl">{{ $title }}</h2>
        @isset($sub)<p class="mt-0.5 text-[13px] text-ink-500 md:text-sm">{{ $sub }}</p>@endisset
    </div>
    @isset($href)<a href="{{ $href }}" class="shrink-0 text-sm font-semibold text-electric-600 hover:underline">{{ $link ?? 'Ver todo' }} →</a>@endisset
</div>
