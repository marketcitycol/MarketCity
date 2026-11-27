@php($a = $address ?? null)
@php($p = $prefix ?? 'n')
<div class="grid gap-3 sm:grid-cols-2">
    <div><label class="label" for="{{ $p }}-label">Nombre de la dirección (opcional)</label><input class="input" id="{{ $p }}-label" name="label" maxlength="40" value="{{ $a?->label }}" placeholder="Casa, oficina…"></div>
    <div><label class="label" for="{{ $p }}-recipient">Quién recibe (opcional)</label><input class="input" id="{{ $p }}-recipient" name="recipient" maxlength="120" value="{{ $a?->recipient }}" autocomplete="name"></div>
    <div><label class="label" for="{{ $p }}-department">Departamento</label>
        <select class="input" id="{{ $p }}-department" name="department" required>
            <option value="">Elige…</option>
            @foreach (array_keys(config('colombia.departments')) as $d)<option @selected($a?->department === $d)>{{ $d }}</option>@endforeach
        </select></div>
    <div><label class="label" for="{{ $p }}-city">Ciudad o municipio</label><input class="input" id="{{ $p }}-city" name="city" list="cities" required maxlength="80" value="{{ $a?->city }}"></div>
    <div class="sm:col-span-2"><label class="label" for="{{ $p }}-address">Dirección</label><input class="input" id="{{ $p }}-address" name="address" required maxlength="150" value="{{ $a?->address }}" placeholder="Calle 00 # 00-00" autocomplete="street-address"></div>
    <div><label class="label" for="{{ $p }}-address_2">Apartamento, barrio o indicaciones</label><input class="input" id="{{ $p }}-address_2" name="address_2" maxlength="150" value="{{ $a?->address_2 }}"></div>
    <div><label class="label" for="{{ $p }}-phone">Celular de contacto (opcional)</label><input class="input" id="{{ $p }}-phone" name="phone" inputmode="tel" value="{{ $a?->phone }}"></div>
</div>
<label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" @checked($a?->is_default)> Usar como dirección principal al pagar</label>
