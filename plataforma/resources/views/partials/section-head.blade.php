{{-- Título de sección del inicio: título, subtítulo y enlace «Ver todo →». --}}
<div class="flex items-end justify-between gap-3">
    <div class="min-w-0">
        <h2 class="text-xl leading-tight font-bold tracking-[-0.01em] text-[#0f172a] md:text-2xl">{{ $title }}</h2>
        @isset($sub)<p class="mt-0.5 text-[13px] text-[#6b7385] md:text-sm">{{ $sub }}</p>@endisset
    </div>
    @isset($href)<a href="{{ $href }}" class="shrink-0 text-sm font-semibold text-[#0b5cff] hover:underline">{{ $link ?? 'Ver todo' }} →</a>@endisset
</div>
