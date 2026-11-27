@extends('account.layout', ['pageTitle' => 'Seguridad'])

@section('account')
<section class="card max-w-xl p-5">
    <h2 class="font-semibold">Cambiar contraseña</h2>
    <form method="post" action="{{ route('password.change') }}" class="mt-4 space-y-4">
        @csrf
        @method('put')
        <input type="email" name="email" value="{{ $user->email }}" autocomplete="username" class="hidden" readonly>
        <div><label class="label" for="current_password">Contraseña actual</label><input class="input" type="password" id="current_password" name="current_password" required autocomplete="current-password">
            @error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="new_password">Contraseña nueva (mínimo 8 caracteres)</label><input class="input" type="password" id="new_password" name="password" required minlength="8" autocomplete="new-password">
            @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
        <div><label class="label" for="new_password_confirmation">Repítela</label><input class="input" type="password" id="new_password_confirmation" name="password_confirmation" required minlength="8" autocomplete="new-password"></div>
        <button class="btn-primary">Guardar contraseña nueva</button>
        <p class="text-xs text-slate-500">Se cierra la sesión en los demás equipos. ¿No recuerdas la actual? Cierra sesión y usa «¿Olvidaste tu contraseña?».</p>
    </form>
</section>
@include('partials.two-factor-settings')

@if ($user->role === \App\Models\User::ROLE_CUSTOMER)
<details class="card mt-6 max-w-xl border-red-200 p-5" @if ($errors->hasAny(['confirm'])) open @endif>
    <summary class="cursor-pointer font-semibold text-red-700">Eliminar mi cuenta</summary>
    <p class="mt-3 text-sm text-slate-600">Borramos tu cuenta, tus direcciones y tus favoritos, y dejamos de enviarte mensajes. Tus pedidos se conservan sin la cuenta, solo porque la ley nos obliga a guardar las facturas y atender garantías. No se puede deshacer.</p>
    <form method="post" action="{{ route('account.destroy') }}" class="mt-4 space-y-3">
        @csrf @method('delete')
        <div><label class="label" for="del_password">Tu contraseña</label><input class="input" type="password" id="del_password" name="current_password" required autocomplete="current-password"></div>
        <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirm" value="1" class="mt-0.5" required> Entiendo que mi cuenta se elimina de forma definitiva.</label>
        @error('confirm')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
        <button class="btn-ghost text-red-700">Eliminar mi cuenta</button>
    </form>
</details>
@endif
@endsection
