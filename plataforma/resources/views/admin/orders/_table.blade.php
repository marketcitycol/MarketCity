@if ($orders->isEmpty())
    <p class="p-6 text-center text-sm text-slate-500">No hay pedidos.</p>
@else
<div class="overflow-x-auto">
<table class="table-std">
    <thead><tr><th>Pedido</th><th>Fecha</th><th>Cliente</th><th>Pago</th><th>Tiendas</th><th class="text-right">Total</th></tr></thead>
    <tbody>
    @foreach ($orders as $o)
        <tr>
            <td><a href="{{ route('admin.orders.show', $o) }}" class="font-medium text-brand-600 hover:underline">{{ $o->number }}</a></td>
            <td>{{ fecha($o->created_at, 'd/m/Y g:i a') }}</td>
            <td>{{ $o->customerName() }}<br><span class="text-xs text-slate-500">{{ $o->city }}</span></td>
            <td><span class="badge {{ ['paid' => 'bg-emerald-100 text-emerald-700', 'failed' => 'bg-red-100 text-red-700'][$o->payment_status] ?? 'bg-amber-100 text-amber-800' }}">{{ $o->paymentStatusLabel() }}</span><br><span class="text-xs text-slate-500">{{ $o->paymentMethodLabel() }}</span></td>
            <td class="text-xs">@foreach ($o->sellerOrders as $so){{ $so->store->name ?? '' }} · {{ $so->statusLabel() }}<br>@endforeach</td>
            <td class="text-right">{{ cop($o->total) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endif
