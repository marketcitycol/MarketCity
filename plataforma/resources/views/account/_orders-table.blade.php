<div class="card overflow-x-auto">
    <table class="table-std">
        <thead><tr><th>Pedido</th><th>Fecha</th><th>Pago</th><th>Estado</th><th class="text-right">Total</th></tr></thead>
        <tbody>
        @foreach ($orders as $o)
            <tr>
                <td><a href="{{ route('orders.show', [$o, 't' => $o->access_token]) }}" class="font-medium text-brand-600 hover:underline">{{ $o->number }}</a></td>
                <td>{{ fecha($o->created_at, 'd/m/Y') }}</td>
                <td>{{ $o->paymentStatusLabel() }}</td>
                <td>{{ $o->sellerOrders->map->statusLabel()->unique()->join(', ') }}</td>
                <td class="text-right">{{ cop($o->total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
