@php($f = \App\Support\Plugins::config('footer'))
@php($P = \App\Support\Plugins::class)
@php($logo = \App\Support\Plugins::config('header')['logo_url'] ?: null)
@php($icons = [
    'tiktok' => '<path d="M14 3v10.5a3.5 3.5 0 1 1-3-3.46V7.1A6.5 6.5 0 1 0 17 13.5V8.6a7 7 0 0 0 4 1.3V6.9A4 4 0 0 1 17 3z"/>',
    'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.2"/>',
    'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>',
])
<footer class="mt-12 bg-brand-900 text-white">
    @if ($trust = $P::lines($f['trust']))
    <div class="border-b border-white/10">
        <ul class="mx-auto grid max-w-7xl gap-3 px-4 py-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($trust as $t)
                <li class="flex items-center gap-3 rounded-xl border border-white/10 bg-white/5 px-4 py-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white/10 text-emerald-300" aria-hidden="true">✓</span>
                    <span><span class="block text-sm font-bold">{{ $t[0] }}</span><span class="block text-xs text-white/70">{{ $t[1] ?? '' }}</span></span>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if ($explore = $P::lines($f['explore']))
    <div class="border-b border-white/10">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-2 px-4 py-4 text-sm">
            <span class="mr-1 font-bold">Sigue explorando</span>
            @foreach ($explore as $e)
                <a href="{{ $P::url($e[1] ?? '#') }}" class="rounded-full border border-white/20 px-3 py-1 font-semibold hover:bg-white/10">{{ $e[0] }}</a>
            @endforeach
            <a href="{{ auth()->check() ? route('account') : route('login') }}" class="ml-auto rounded-full border border-white/20 px-3 py-1 font-semibold">
                {{ auth()->check() ? 'Hola, '.\Illuminate\Support\Str::of(auth()->user()->name)->before(' ') : 'Hola' }} <span class="text-emerald-300">Mis pedidos</span>
            </a>
        </div>
    </div>
    @endif

    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-sm sm:grid-cols-2 lg:grid-cols-[1.6fr_1fr_1fr_1.2fr]">
        <div>
            <a href="{{ route('home') }}" aria-label="MarketCity, inicio">
                @if ($logo)
                    <img src="{{ $logo }}" alt="MarketCity" class="h-11 w-auto brightness-0 invert" width="130" height="44" loading="lazy">
                @else
                    <span class="text-xl font-extrabold">Market<span class="text-accent-400">City</span></span>
                @endif
            </a>
            <p class="mt-3 max-w-xs text-white/75">{{ $f['description'] }}</p>
            <div class="mt-4 flex gap-2">
                @foreach ($P::lines($f['social']) as $s)
                    @php($net = strtolower($s[0]))
                    <a href="{{ $P::url($s[1] ?? '#') }}" target="_blank" rel="noopener" class="grid h-9 w-9 place-items-center rounded-lg bg-white text-brand-900 hover:bg-brand-100" aria-label="{{ ucfirst($net) }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">{!! $icons[$net] ?? '' !!}</svg>
                    </a>
                @endforeach
            </div>
        </div>
        @foreach (['Comprar' => 'buy', 'Ayuda' => 'help'] as $title => $field)
            <div>
                <p class="font-bold">{{ $title }}</p>
                <ul class="mt-3 space-y-2 text-white/75">
                    @foreach ($P::lines($f[$field]) as $l)
                        <li><a href="{{ $P::url($l[1] ?? '#') }}" class="hover:text-white">{{ $l[0] }}</a></li>
                    @endforeach
                </ul>
            </div>
        @endforeach
        <div>
            <p class="font-bold">Vende y contacto</p>
            <ul class="mt-3 space-y-2 text-white/75">
                @foreach ($P::lines($f['sell']) as $l)
                    <li><a href="{{ $P::url($l[1] ?? '#') }}" class="hover:text-white">{{ $l[0] }}</a></li>
                @endforeach
                @if ($f['email'])<li><a href="mailto:{{ $f['email'] }}" class="hover:text-white">✉ {{ $f['email'] }}</a></li>@endif
            </ul>
            @if ($f['whatsapp'])
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $f['whatsapp']) }}" target="_blank" rel="noopener" class="mt-4 inline-flex rounded-full border border-emerald-300/40 bg-emerald-400/10 px-4 py-2 font-bold text-emerald-200 hover:bg-emerald-400/20">Escríbenos por WhatsApp</a>
                @if ($f['hours'])<p class="mt-2 text-xs text-white/60">{{ $f['hours'] }}</p>@endif
            @endif
        </div>
    </div>

    @if ($pays = $P::lines($f['payments']))
    <div class="border-t border-white/10">
        <div class="mx-auto max-w-7xl px-4 py-5">
            <p class="text-xs text-white/70">Paga con</p>
            <ul class="mt-2 flex flex-wrap gap-2">
                @foreach ($pays as $p)
                    <li class="rounded-md bg-white px-3 py-1.5 text-sm font-extrabold text-brand-900">{{ $p[0] }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-5 text-xs text-white/70 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p>© {{ date('Y') }} MarketCity.co. Todos los derechos reservados.</p>
                <p>{{ $f['company'] }}</p>
                <p class="mt-1">* Cuotas de referencia: el valor final depende del medio de pago y su aprobación. Precios en pesos colombianos con IVA incluido.</p>
            </div>
            <ul class="flex flex-wrap gap-x-4 gap-y-1">
                @foreach ($P::lines($f['legal']) as $l)
                    <li><a href="{{ $P::url($l[1] ?? '#') }}" class="hover:text-white" @if (str_starts_with($l[1] ?? '', 'http')) target="_blank" rel="noopener" @endif>{{ $l[0] }}</a></li>
                @endforeach
                @if (\App\Support\Analytics::active())<li><button type="button" class="hover:text-white" data-consent-open>Preferencias de cookies</button></li>@endif
            </ul>
        </div>
    </div>
</footer>
