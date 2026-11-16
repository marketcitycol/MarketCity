@if ($orders->isEmpty())
    <p class="p-6 text-center text-sm text-slate-500">Todavía no hay pedidos.</p>
@else
<div class="overflow-x-auto">
<table class="table-std">
    <thead><tr><th>Pedido</th><th>Fecha</th><th>Cliente</th><th>Pago</th><th>Estado</th><th class="text-right">Tu neto</th></tr></thead>
    <tbody>
    @foreach ($orders as $so)
        <tr>
            <td><a href="{{ route('seller.orders.show', $so) }}" class="font-medium text-brand-600 hover:underline">{{ $so->order->number }}</a></td>
            <td>{{ fecha($so->created_at, 'd/m/Y') }}</td>
            <td>{{ $so->order->customerName() }}<br><span class="text-xs text-slate-500">{{ $so->order->city }}</span></td>
            <td>{{ $so->order->paymentStatusLabel() }}</td>
            <td>@include('partials.status-badge')</td>
            <td class="text-right">{{ cop($so->seller_net) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endif
