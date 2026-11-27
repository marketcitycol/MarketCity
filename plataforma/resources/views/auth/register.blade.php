@extends('layouts.shop', ['title' => 'Crear cuenta'])

@section('content')
<div class="mx-auto max-w-sm">
    <h1 class="text-2xl font-bold">Crea tu cuenta</h1>
    <form method="post" action="{{ route('register') }}" class="card mt-5 space-y-4 p-5">
        @csrf
        @if (\App\Http\Controllers\Auth\GoogleController::enabled())
        <a href="{{ route('login.google') }}" class="btn-ghost w-full"><svg class="h-5 w-5" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z"/><path fill="#FF3D00" d="m6.3 14.7 6.6 4.8C14.7 15.1 19 12 24 12c3.1 0 5.8 1.2 7.9 3.1l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z"/></svg>Continuar con Google</a>
        <p class="text-center text-[11px] text-slate-500">Si es tu primera vez, al continuar con Google creas tu cuenta y aceptas la <a href="{{ url('/politica-de-privacidad') }}" class="underline">política de tratamiento de datos</a>.</p>
        <p class="flex items-center gap-3 text-xs text-slate-400"><span class="h-px flex-1 bg-slate-200"></span>o con tu correo<span class="h-px flex-1 bg-slate-200"></span></p>
        @endif
        <div><label class="label" for="name">Nombre completo</label><input class="input" id="name" name="name" value="{{ old('name') }}" required></div>
        <div><label class="label" for="email">Correo</label><input class="input" type="email" id="email" name="email" value="{{ old('email', $suggestedEmail ?? '') }}" required autocomplete="email"></div>
        <div><label class="label" for="password">Contraseña (mínimo 8)</label><input class="input" type="password" id="password" name="password" required minlength="8"></div>
        <div><label class="label" for="password_confirmation">Repite la contraseña</label><input class="input" type="password" id="password_confirmation" name="password_confirmation" required></div>
        <label class="flex items-start gap-2 text-xs text-slate-600"><input type="checkbox" name="terms" value="1" required class="mt-0.5"> <span>Autorizo el tratamiento de mis datos personales según la política de privacidad.</span></label>
        <button class="btn-primary w-full">Crear cuenta</button>
    </form>
</div>
@endsection
