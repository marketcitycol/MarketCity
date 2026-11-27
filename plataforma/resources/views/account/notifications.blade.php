@extends('account.layout', ['pageTitle' => 'Notificaciones'])

@section('account')
<form method="post" action="{{ route('account.notifications.update') }}" class="card max-w-xl space-y-4 p-5 text-sm">
    @csrf @method('put')
    <p class="text-slate-600">Los avisos de tus pedidos (pago, envío y entrega) siempre te llegan por correo. Aquí decides si también quieres recordatorios del carrito y ofertas.</p>
    <label class="flex items-start gap-3"><input type="checkbox" name="email" value="1" class="mt-0.5" @checked($email)>
        <span><span class="font-medium">Correo</span> a {{ $user->email }}</span></label>
    <label class="flex items-start gap-3 {{ $phone ? '' : 'opacity-60' }}"><input type="checkbox" name="whatsapp" value="1" class="mt-0.5" @checked($whatsapp) @disabled(! $phone)>
        <span><span class="font-medium">WhatsApp</span> @if ($phone) al {{ $user->phone }} @else (agrega tu celular en <a href="{{ route('account.details') }}" class="underline">Mis datos</a>) @endif</span></label>
    <p class="text-xs text-slate-500">Máximo un mensaje al día, de lunes a viernes de 7 a. m. a 7 p. m. y sábados de 8 a. m. a 3 p. m. (Ley 2300 de 2023). Puedes retirarte aquí o desde cualquier mensaje.</p>
    <button class="btn-primary">Guardar preferencias</button>
</form>
@endsection
