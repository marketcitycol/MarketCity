@php($f = \App\Services\Content\Plugins::config('footer'))
@php($P = \App\Services\Content\Plugins::class)
<footer class="mt-12 bg-brand-900 text-white" id="mcft">
    @if ($trust = $P::lines($f['trust']))
    <div class="mx-auto max-w-7xl px-4 pt-6">
        <ul class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-1 lg:mx-0 lg:grid lg:grid-cols-4 lg:overflow-visible lg:px-0">
            @foreach ($trust as $i => $t)
                <li class="flex min-w-[250px] snap-start items-center gap-3 rounded-[14px] border border-white/12 bg-white/6 px-3.5 py-3 lg:min-w-0">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-[10px] bg-[#6ecfbd]/12 text-[#6ecfbd]">{!! icon(['credito', 'envio', 'protegido', 'whatsapp'][$i % 4], 'h-5 w-5') !!}</span>
                    <span><span class="block text-sm font-extrabold">{{ $t[0] }}</span><span class="block text-xs text-white/68">{{ $t[1] ?? '' }}</span></span>
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if ($explore = $P::lines($f['explore']))
    <div class="mx-auto max-w-7xl px-4 pt-5">
        <div class="flex flex-wrap items-center gap-2">
            <span class="mr-1 text-sm font-black">Sigue explorando</span>
            @foreach ($explore as $e)
                <a href="{{ $P::url($e[1] ?? '#') }}" class="inline-flex h-9 items-center rounded-full border border-white/12 bg-white/6 px-3.5 text-xs font-extrabold hover:bg-white/12">{{ $e[0] }}</a>
            @endforeach
            <a href="{{ auth()->check() ? route('account') : route('login') }}" class="inline-flex h-9 items-center gap-1 rounded-xl border border-[#3843d0]/35 bg-[#3843d0]/18 px-3 text-xs font-extrabold lg:ml-auto">
                {{ auth()->check() ? 'Hola, '.\Illuminate\Support\Str::of(auth()->user()->name)->before(' ') : 'Hola' }} · <span class="text-[#9fb4ff]">Mis pedidos</span>
            </a>
        </div>
    </div>
    @endif

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 text-sm lg:grid-cols-[1.6fr_1fr_1fr_1.2fr] lg:gap-8">
        <div>
            <a href="{{ route('home') }}" aria-label="MarketCity, inicio" class="inline-block">
                <img src="{{ $P::logo() }}" alt="MarketCity" class="h-10 w-auto brightness-0 invert" width="127" height="40" loading="lazy">
            </a>
            <p class="mt-3 max-w-xs text-[13px] font-semibold text-white/68">{{ $f['description'] }}</p>
            <div class="mt-4 flex gap-2">
                @foreach ($P::lines($f['social']) as $s)
                    @php($net = strtolower($s[0]))
                    <a href="{{ $P::url($s[1] ?? '#') }}" target="_blank" rel="noopener" class="grid h-9 w-9 place-items-center rounded-xl bg-white text-brand-900 hover:bg-brand-100" aria-label="{{ ucfirst($net) }}">{!! icon(in_array($net, ['tiktok', 'instagram', 'facebook']) ? $net : 'flecha', 'h-[18px] w-[18px]') !!}</a>
                @endforeach
            </div>
        </div>
        @foreach (['Comprar' => 'buy', 'Ayuda' => 'help', 'Vende y contacto' => 'sell'] as $title => $field)
            <details class="group border-t border-white/10 pt-3 lg:border-0 lg:pt-0" open>
                <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-black lg:pointer-events-none">{{ $title }}<span class="text-white/50 transition group-open:rotate-90 lg:hidden">{!! icon('chevron', 'h-4 w-4') !!}</span></summary>
                <ul class="mt-1">
                    @foreach ($P::lines($f[$field]) as $l)
                        <li><a href="{{ $P::url($l[1] ?? '#') }}" class="flex min-h-9 items-center text-[13px] text-white/68 hover:text-white">{{ $l[0] }}</a></li>
                    @endforeach
                    @if ($field === 'sell')
                        @if ($f['email'])<li><a href="mailto:{{ $f['email'] }}" class="flex min-h-9 items-center gap-2 text-[13px] text-white/68 hover:text-white">{!! icon('correo', 'h-4 w-4') !!} {{ $f['email'] }}</a></li>@endif
                    @endif
                </ul>
                @if ($field === 'sell' && $f['whatsapp'])
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', $f['whatsapp']) }}" target="_blank" rel="noopener" data-track="whatsapp_click" class="mt-3 inline-flex h-[42px] items-center gap-2 rounded-full border border-[#12b76a]/35 bg-[#12b76a]/18 px-4 text-sm font-black text-white hover:bg-[#12b76a]/28">{!! icon('whatsapp', 'h-5 w-5') !!} Escríbenos por WhatsApp</a>
                    @if ($f['hours'])<p class="mt-2 text-xs text-white/60">{{ $f['hours'] }}</p>@endif
                @endif
            </details>
        @endforeach
    </div>

    @if ($pays = $P::lines($f['payments']))
    <div class="mx-auto max-w-7xl px-4 pb-6">
        <p class="text-sm font-black">Paga con</p>
        <ul class="mt-2 flex flex-wrap gap-2">
            @foreach ($pays as $p)
                @php($pl = \App\Services\Content\BrandAssets::paymentLogo($p[0]))
                <li class="grid h-[38px] w-[82px] place-items-center rounded-lg border border-[#e5e7eb] bg-white p-1.5">
                    @if ($pl)<img src="{{ $pl }}" alt="{{ $p[0] }}" class="max-h-full max-w-full object-contain" loading="lazy">@else<span class="text-xs font-extrabold text-brand-900">{{ $p[0] }}</span>@endif
                </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-black/18">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-5 text-xs text-white/68 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="font-bold text-white/80">© {{ date('Y') }} MarketCity.co. Todos los derechos reservados.</p>
                <p class="mt-1">{{ $f['company'] }}</p>
                <p class="mt-1">* Cuotas de referencia: el valor final depende del medio de pago y su aprobación. Precios en pesos colombianos con IVA incluido.</p>
            </div>
            <ul class="flex flex-wrap gap-x-4 gap-y-1 font-bold">
                @foreach ($P::lines($f['legal']) as $l)
                    <li><a href="{{ $P::url($l[1] ?? '#') }}" class="hover:text-white" @if (str_starts_with($l[1] ?? '', 'http')) target="_blank" rel="noopener" @endif>{{ $l[0] }}</a></li>
                @endforeach
                @if (\App\Support\Analytics::active())<li><button type="button" class="font-bold hover:text-white" data-consent-open>Preferencias de cookies</button></li>@endif
            </ul>
        </div>
    </div>
</footer>
