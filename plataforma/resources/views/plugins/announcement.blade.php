@php($a = \App\Support\Plugins::config('announcement'))
@php($ends = $a['ends_at'] ? rescue(fn () => \Illuminate\Support\Carbon::parse($a['ends_at'], 'America/Bogota'), null, false) : null)
@if (! $ends || $ends->isFuture())
<div class="bg-[#080b12] text-white" data-announcement>
    <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-3 gap-y-1 px-4 py-2 text-center text-xs font-bold sm:text-sm">
        <span>{{ $a['text'] }} @if ($a['highlight'])<span class="text-accent-400">{{ $a['highlight'] }}</span>@endif</span>
        @if ($ends)
            <span class="font-mono text-accent-400" data-countdown="{{ $ends->getTimestamp() }}" aria-label="Tiempo restante">{{ (int) now()->diffInDays($ends) }}d</span>
        @endif
        @if ($a['button'])
            <a href="{{ \App\Support\Plugins::url($a['url']) }}" class="rounded-full bg-accent-400 px-3 py-1 text-[#080b12] hover:bg-accent-500">{{ $a['button'] }}</a>
        @endif
    </div>
</div>
@endif
