{{-- Botón para pasar un subpedido al siguiente estado; al despachar pide transportadora y guía. --}}
@if ($next = $so->nextStatus())
<form method="post" action="{{ $action }}" class="flex flex-wrap items-end gap-2">
    @csrf
    <input type="hidden" name="to" value="{{ $next }}">
    @if ($next === 'despachado' && $so->shipping_method === 'envio')
        <div><label class="label text-xs" for="carrier-{{ $so->id }}">Transportadora</label>
            <select class="input" id="carrier-{{ $so->id }}" name="carrier" required>
                @foreach (\App\Models\SellerOrder::CARRIERS as $k => [$name])<option value="{{ $k }}">{{ $name }}</option>@endforeach
            </select></div>
        <div><label class="label text-xs" for="tracking-{{ $so->id }}">Número de guía</label><input class="input" id="tracking-{{ $so->id }}" name="tracking_number" required></div>
    @endif
    <button class="btn-primary">Marcar como {{ \App\Models\SellerOrder::STATUSES[$next] }}</button>
</form>
@endif
