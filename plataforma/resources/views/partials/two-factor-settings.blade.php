{{-- Segundo factor con app autenticadora. Se usa en Seguridad de Mi cuenta y del Seller Center. --}}
<section class="card mt-6 max-w-xl p-5" id="segundo-factor">
    <h2 class="font-semibold">Verificación en dos pasos</h2>
    @if ($codes = session('recovery_codes'))
        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            <p class="font-semibold">Guarda estos códigos de recuperación ahora: no se vuelven a mostrar.</p>
            <p class="mt-1">Cada uno sirve una vez si no tienes el celular.</p>
            <ul class="mt-2 grid grid-cols-2 gap-1 font-mono">@foreach ($codes as $c)<li>{{ $c }}</li>@endforeach</ul>
        </div>
    @endif
    @if ($user->hasTwoFactor())
        <p class="mt-2 text-sm text-emerald-700">Activa desde el {{ fecha($user->two_factor_confirmed_at, 'd/m/Y') }}. Al ingresar te pedimos el código de tu app autenticadora.</p>
        <p class="mt-1 text-xs text-slate-500">Te quedan {{ count($user->two_factor_recovery_codes ?? []) }} códigos de recuperación.</p>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
            <form method="post" action="{{ route('two-factor.codes') }}" class="space-y-2">@csrf
                <input class="input" type="password" name="current_password" required autocomplete="current-password" placeholder="Tu contraseña" aria-label="Tu contraseña">
                <button class="btn-ghost btn-sm w-full">Generar códigos nuevos</button>
            </form>
            <form method="post" action="{{ route('two-factor.disable') }}" class="space-y-2">@csrf @method('delete')
                <input class="input" type="password" name="current_password" required autocomplete="current-password" placeholder="Tu contraseña" aria-label="Tu contraseña">
                <button class="btn-ghost btn-sm w-full text-red-700">Desactivar</button>
            </form>
        </div>
    @elseif ($secret = session('2fa.pending_secret'))
        @php($uri = \App\Support\Totp::uri($secret, $user->email))
        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-600">
            <li>Abre Google Authenticator, Microsoft Authenticator u otra app autenticadora y escanea este código.</li>
            <li>Escribe el código de 6 dígitos que aparece.</li>
        </ol>
        <div class="keep-light mt-3 inline-block rounded-xl border border-slate-200 bg-white p-2"><div class="h-[180px] w-[180px]" data-qr="{{ $uri }}" data-lib="{{ asset('vendor/qrcode/qrcode.js') }}" role="img" aria-label="Código QR para la app autenticadora"></div></div>
        <p class="mt-2 text-xs text-slate-500">¿No puedes escanear? Escribe esta clave en la app: <code class="font-mono break-all select-all">{{ trim(chunk_split($secret, 4, ' ')) }}</code></p>
        <form method="post" action="{{ route('two-factor.confirm') }}" class="mt-3 flex gap-2">@csrf
            <input class="input max-w-40 text-center tracking-widest" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="10" placeholder="123456" aria-label="Código de 6 dígitos">
            <button class="btn-primary">Activar</button>
        </form>
        @error('code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    @else
        <p class="mt-2 text-sm text-slate-600">Además de la contraseña, te pedimos un código de tu celular al ingresar. Así nadie entra aunque conozca tu contraseña.@if ($user->isAdmin() || $user->isSeller()) <strong>Recomendado para cuentas que manejan dinero.</strong>@endif</p>
        <form method="post" action="{{ route('two-factor.start') }}" class="mt-3 flex gap-2">@csrf
            <input class="input" type="password" name="current_password" required autocomplete="current-password" placeholder="Tu contraseña" aria-label="Tu contraseña">
            <button class="btn-primary shrink-0">Activar</button>
        </form>
        @error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    @endif
</section>
