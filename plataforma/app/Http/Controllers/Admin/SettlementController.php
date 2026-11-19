<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SellerOrder;
use App\Services\Orders\OrderService;
use Illuminate\Http\Request;

class SettlementController extends Controller
{
    public function index(Request $request)
    {
        $base = SellerOrder::with('order', 'store')->where('status', '!=', 'cancelado')
            ->whereHas('order', fn ($o) => $o->where('payment_status', 'paid'));

        $pending = (clone $base)->whereNull('settled_at')->orderBy('settlement_due_on')->get();

        return view('admin.settlements', [
            'pending' => $pending,
            'byStore' => $pending->groupBy('store_id')->map(fn ($rows) => [
                'store' => $rows->first()->store,
                'total' => $rows->sum('seller_net'),
                'due' => $rows->filter(fn ($r) => $r->settlement_due_on && $r->settlement_due_on->lte(now('America/Bogota')))->sum('seller_net'),
            ]),
            'done' => (clone $base)->whereNotNull('settled_at')->latest('settled_at')->take(20)->get(),
        ]);
    }

    public function settle(Request $request, SellerOrder $sellerOrder, OrderService $orders)
    {
        $data = $request->validate(['reference' => 'required|string|max:100']);
        abort_if($sellerOrder->settled_at, 422, 'Ya estaba liquidado.');
        abort_if($sellerOrder->status === 'cancelado' || $sellerOrder->order->payment_status !== 'paid', 422, 'Solo se liquidan subpedidos pagados y no cancelados.');
        if ($hold = $sellerOrder->store->settlementHoldReason()) {
            return back()->with('error', "Liquidación retenida: {$hold}");
        }
        if ($claim = $sellerOrder->openClaim()) {
            return back()->with('error', "Liquidación retenida: hay una {$claim->typeLabel()} abierta ({$claim->radicado}). Respóndela antes de pagar al vendedor.");
        }
        $orders->settle($sellerOrder, $request->user(), $data['reference']);

        return back()->with('success', 'Liquidación registrada.');
    }
}
