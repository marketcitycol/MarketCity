@php($colors = ['recibido' => 'bg-slate-100 text-slate-700', 'validado' => 'bg-sky-100 text-sky-700', 'despachado' => 'bg-amber-100 text-amber-800', 'entregado' => 'bg-emerald-100 text-emerald-700', 'cancelado' => 'bg-red-100 text-red-700'])
<span class="badge {{ $colors[$so->status] ?? 'bg-slate-100' }}">{{ $so->statusLabel() }}</span>
