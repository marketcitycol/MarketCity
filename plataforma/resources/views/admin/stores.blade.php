@extends('layouts.admin', ['title' => 'Vendedores'])

@section('content')
<h1 class="text-2xl font-bold">Vendedores</h1>
<div class="card mt-4 overflow-x-auto">
<table class="table-std">
    <thead><tr><th>Tienda</th><th>Responsable</th><th>Documento</th><th>Ubicación</th><th>Productos</th><th>Comisión</th><th>Pagos</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @foreach ($stores as $store)
        <tr>
            <td class="font-medium">@if ($store->isActive())<a href="{{ route('store', $store) }}" class="text-brand-600 hover:underline" target="_blank">{{ $store->name }}</a>@else{{ $store->name }}@endif</td>
            <td>{{ $store->user->name }}<br><span class="text-xs text-slate-500">{{ $store->user->email }}</span></td>
            <td>{{ $store->document_type }} {{ $store->document_number }}</td>
            <td>{{ $store->city }}, {{ $store->department }}</td>
            <td>{{ $store->products_count }}</td>
            <td>{{ $store->hasCustomCommission() ? rtrim(rtrim(number_format((float) ($store->commission_percent ?? \App\Models\Setting::get('commission_percent', 15)), 2, ',', '.'), '0'), ',').' %'.($store->commission_fixed ? ' + '.cop($store->commission_fixed) : '').' · negociada' : 'General' }}</td>
            <td>{{ $store->allowedPaymentMethods() === null ? 'Todos' : implode(', ', $store->allowedPaymentMethods()) }}</td>
            <td><span class="badge {{ ['active' => 'bg-emerald-100 text-emerald-700', 'pending' => 'bg-amber-100 text-amber-800', 'suspended' => 'bg-red-100 text-red-700'][$store->status] }}">{{ ['active' => 'Activa', 'pending' => 'En revisión', 'suspended' => 'Suspendida'][$store->status] }}</span></td>
            <td class="text-right">
                <a href="{{ route('admin.stores.edit', $store) }}" class="btn-ghost btn-sm">Condiciones</a>
                <form method="post" action="{{ route('admin.stores.status', $store) }}" class="inline">
                    @csrf
                    @if ($store->status !== 'active')<button name="status" value="active" class="btn-primary btn-sm">Aprobar</button>@endif
                    @if ($store->status === 'active')<button name="status" value="suspended" class="btn-ghost btn-sm">Suspender</button>@endif
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
