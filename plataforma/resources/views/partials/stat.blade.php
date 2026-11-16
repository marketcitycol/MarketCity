<div class="card p-4">
    <p class="text-xs font-medium tracking-wide text-slate-500 uppercase">{{ $label }}</p>
    <p class="mt-1 text-2xl font-bold {{ $class ?? '' }}">{{ $value }}</p>
    @isset($hint)<p class="text-xs text-slate-500">{{ $hint }}</p>@endisset
</div>
