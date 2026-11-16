@extends('layouts.admin', ['title' => 'Pedidos'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="text-2xl font-bold">Pedidos</h1>
    <form method="get" class="flex gap-2">
        <input class="input" name="q" value="{{ request('q') }}" placeholder="Número, correo, cédula o apellido">
        <select class="input w-auto" name="pago" onchange="this.form.submit()">
            <option value="">Todos los pagos</option>
            @foreach (['pending' => 'Pendiente', 'paid' => 'Pagado', 'failed' => 'Rechazado'] as $k => $v)<option value="{{ $k }}" @selected(request('pago') === $k)>{{ $v }}</option>@endforeach
        </select>
        <button class="btn-ghost">Buscar</button>
    </form>
</div>
<div class="card mt-4">@include('admin.orders._table')</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
