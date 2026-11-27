{{-- Formulario de contacto con copia por correo (#66). --}}
@php($u = auth()->user())
<section id="formulario" class="card mt-8 scroll-mt-24 p-5" aria-labelledby="contacto-form">
    <h2 id="contacto-form" class="text-lg font-bold">Escríbenos</h2>
    <p class="mt-1 text-sm text-slate-500">Te respondemos al correo en horario hábil. ¿Una queja, un reclamo, un retracto o una reversión del pago? <a href="{{ route('pqrs.create') }}" class="text-brand-600 hover:underline">Radícalo como PQRS</a> para que corra el plazo legal.</p>
    <form method="post" action="{{ route('contact.store') }}" class="mt-4 grid gap-3 text-sm sm:grid-cols-2">@csrf
        <div><label class="label" for="c-name">Nombre</label><input class="input" id="c-name" name="name" required maxlength="80" autocomplete="name" value="{{ old('name', $u?->name) }}"></div>
        <div><label class="label" for="c-email">Correo</label><input class="input" id="c-email" type="email" name="email" required maxlength="150" autocomplete="email" value="{{ old('email', $u?->email) }}"></div>
        <div><label class="label" for="c-phone">Celular (opcional)</label><input class="input" id="c-phone" type="tel" name="phone" maxlength="20" autocomplete="tel" value="{{ old('phone') }}"></div>
        <div><label class="label" for="c-topic">Tema</label>
            <select class="input" id="c-topic" name="topic" required>
                @foreach (\App\Models\ContactMessage::TOPICS as $k => $v)<option value="{{ $k }}" @selected(old('topic') === $k)>{{ $v }}</option>@endforeach
            </select>
        </div>
        <div><label class="label" for="c-order">Número de pedido (si es sobre una compra)</label><input class="input" id="c-order" name="order_number" maxlength="20" placeholder="MC-010001" value="{{ old('order_number') }}"></div>
        <div><label class="label" for="c-source">¿Cómo nos conociste? (opcional)</label>
            <select class="input" id="c-source" name="source"><option value="">Prefiero no decir</option>@foreach (\App\Models\ContactMessage::SOURCES as $src)<option @selected(old('source') === $src)>{{ $src }}</option>@endforeach</select>
        </div>
        <div class="sm:col-span-2"><label class="label" for="c-message">Mensaje</label><textarea class="input min-h-32" id="c-message" name="message" required minlength="10" maxlength="3000">{{ old('message') }}</textarea></div>
        <div class="hidden" aria-hidden="true"><label>No llenar <input name="website" tabindex="-1" autocomplete="off"></label></div>
        <input type="hidden" name="copy" value="0">
        <label class="flex items-center gap-2 sm:col-span-2"><input type="checkbox" name="copy" value="1" @checked(old('copy', '1') === '1')> Enviarme una copia a mi correo</label>
        <p class="text-xs text-slate-500 sm:col-span-2">Usamos tus datos solo para responderte (<a href="{{ url('/politica-de-privacidad') }}" class="text-brand-600 hover:underline">política de datos</a>).</p>
        <div class="sm:col-span-2"><button class="btn-primary">Enviar mensaje</button></div>
    </form>
</section>
