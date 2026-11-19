@php($a = \App\Support\Plugins::config('analytics'))
<div id="mc-consent" class="fixed inset-x-3 bottom-20 z-50 mx-auto hidden max-w-3xl rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:bottom-4" role="dialog" aria-live="polite" aria-label="Aviso de cookies">
    <p class="text-sm text-slate-700">{{ $a['banner_text'] }} <a href="{{ \App\Support\Plugins::url($a['policy_url']) }}" class="text-brand-600 underline">Más información</a>.</p>
    <div class="mt-3 flex flex-wrap justify-end gap-2">
        <button type="button" class="btn-ghost btn-sm" data-consent="denied">Rechazar</button>
        <button type="button" class="btn-primary btn-sm" data-consent="granted">Aceptar</button>
    </div>
</div>
