@extends('layouts.admin', ['title' => 'Cambio de dominio'])

@section('content')
<h1 class="text-2xl font-bold">Cambio de dominio</h1>
<p class="mt-1 text-sm text-slate-500">Lista para pasar marketcity.co de WordPress a la plataforma propia. Lo que no se puede comprobar desde aquí está en la guía <code>docs/operacion/cambio-de-dominio.md</code>.</p>

<section class="card mt-5 p-5">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="font-semibold">¿Listo para el cambio?</h2>
        <span class="badge {{ $ready ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $ready ? 'SÍ: todo lo obligatorio está listo' : 'TODAVÍA NO' }}</span>
    </div>
    <ul class="mt-4 divide-y divide-slate-100 text-sm">
        @foreach ($checks as $c)
            <li class="flex gap-3 py-2">
                <span class="mt-0.5 shrink-0 font-bold {{ $c['ok'] ? 'text-emerald-600' : ($c['blocking'] ? 'text-red-600' : 'text-amber-600') }}" aria-hidden="true">{{ $c['ok'] ? '✓' : ($c['blocking'] ? '✗' : '!') }}</span>
                <div><p class="font-medium">{{ $c['label'] }} @unless ($c['blocking'])<span class="text-xs font-normal text-slate-500">(recomendado)</span>@endunless</p><p class="text-slate-500">{{ $c['detail'] }}</p></div>
            </li>
        @endforeach
    </ul>
</section>

<section class="card mt-5 p-5 text-sm">
    <h2 class="font-semibold">URL del sitio anterior</h2>
    <p class="mt-1 text-slate-500">Lee el sitemap de WordPress y prueba cada dirección en esta plataforma: debe abrir igual, redirigir con 301 a su equivalente o estar retirada a propósito (410). Revisa {{ $state['base'] ?: request()->getSchemeAndHttpHost() }}.</p>

    <form method="post" action="{{ route('admin.cutover.start') }}" class="mt-4 space-y-3">
        @csrf
        <div><label class="label" for="sitemap">Sitemap</label><input class="input" id="sitemap" name="sitemap" value="https://marketcity.co/wp-sitemap.xml"></div>
        <details><summary class="cursor-pointer text-brand-600">O pega la lista de URL (una por línea)</summary>
            <textarea class="input mt-2 min-h-32 font-mono text-xs" name="list" placeholder="https://marketcity.co/producto/…"></textarea>
        </details>
        <button class="btn-primary">Empezar revisión</button>
    </form>

    @if ($summary['total'])
        <dl class="mt-5 grid grid-cols-3 gap-3 sm:grid-cols-6">
            @foreach (['revisadas' => 'Revisadas', 'ok' => 'Iguales', 'redirige' => 'Con 301', 'retirada' => 'Retiradas', 'falta' => 'Sin destino', 'error' => 'Error'] as $k => $v)
                <div class="rounded-xl bg-slate-50 p-3"><dt class="text-xs text-slate-500">{{ $v }}</dt><dd class="text-lg font-bold {{ in_array($k, ['falta', 'error']) && $summary[$k] ? 'text-red-700' : '' }}">{{ $summary[$k] }}{{ $k === 'revisadas' ? ' / '.$summary['total'] : '' }}</dd></div>
            @endforeach
        </dl>
        <div class="mt-3 flex flex-wrap gap-2">
            @if ($summary['revisadas'] < $summary['total'])
                <form method="post" action="{{ route('admin.cutover.batch') }}" id="mc-url-batch">@csrf<button class="btn-primary btn-sm">Revisar las siguientes</button></form>
                @if (session('continue'))<p class="self-center text-slate-500">Sigue sola en 1 segundo…</p><script>setTimeout(function () { document.getElementById('mc-url-batch').submit(); }, 1000);</script>@endif
            @endif
            <a href="{{ route('admin.cutover.csv') }}" class="btn-ghost btn-sm">Descargar detalle (CSV)</a>
        </div>
        <p class="mt-2 text-xs text-slate-500">Fuente: {{ $state['source'] }} · actualizado {{ $state['updated_at'] ? fecha(\Carbon\Carbon::parse($state['updated_at'])) : '' }}</p>

        @if ($problems->isNotEmpty())
            <h3 class="mt-5 font-semibold text-red-700">Sin destino</h3>
            <p class="text-slate-500">Si es un producto, revisa que esté publicado y con el mismo enlace. Si es una página, créala en Páginas o pide que se agregue una redirección.</p>
            <div class="mt-2 overflow-x-auto"><table class="table-std">
                <thead><tr><th>URL anterior</th><th>Código</th><th>Terminó en</th></tr></thead>
                <tbody>@foreach ($problems as $path => $r)<tr><td class="font-mono text-xs">{{ $path }}</td><td>{{ $r['status'] }}{{ $r['final_status'] !== $r['status'] ? ' → '.$r['final_status'] : '' }}</td><td class="font-mono text-xs">{{ $r['final'] }}</td></tr>@endforeach</tbody>
            </table></div>
        @endif
    @endif
</section>
@endsection
