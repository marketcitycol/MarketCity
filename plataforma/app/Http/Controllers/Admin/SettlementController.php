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
        // Un pedido reembolsado del todo sigue debiéndole al vendedor lo que no se le descontó
        // (por ejemplo, si el reembolso lo asumió MarketCity).
        $base = SellerOrder::with('order', 'store')->where('status', '!=', 'cancelado')
            ->whereHas('order', fn ($o) => $o->whereIn('payment_status', ['paid', 'refunded']));

        $pending = (clone $base)->whereNull('settled_at')->orderBy('settlement_due_on')->get();
        $deductions = $pending->pluck('store')->unique('id')->mapWithKeys(fn ($store) => [$store->id => $store->pendingDeduction()]);

        return view('admin.settlements', [
            'pending' => $pending,
            'deductions' => $deductions,
            'byStore' => $pending->groupBy('store_id')->map(fn ($rows, $storeId) => [
                'store' => $rows->first()->store,
                'total' => max(0, $rows->sum('seller_net') - $deductions[$storeId]),
                'deduction' => $deductions[$storeId],
                'due' => $rows->filter(fn ($r) => $r->settlement_due_on && $r->settlement_due_on->lte(now('America/Bogota')))->sum('seller_net'),
            ]),
            'done' => (clone $base)->whereNotNull('settled_at')->latest('settled_at')->take(20)->get(),
        ]);
    }

    public function settle(Request $request, SellerOrder $sellerOrder, OrderService $orders)
    {
        $data = $request->validate(['reference' => 'required|string|max:100']);
        abort_if($sellerOrder->settled_at, 422, 'Ya estaba liquidado.');
        abort_if($sellerOrder->status === 'cancelado' || ! in_array($sellerOrder->order->payment_status, ['paid', 'refunded'], true), 422, 'Solo se liquidan subpedidos pagados y no cancelados.');
        if ($hold = $sellerOrder->store->settlementHoldReason()) {
            return back()->with('error', "Liquidación retenida: {$hold}");
        }
        if ($claim = $sellerOrder->openClaim()) {
            return back()->with('error', "Liquidación retenida: hay una {$claim->typeLabel()} abierta ({$claim->radicado}). Respóndela antes de pagar al vendedor.");
        }
        $deducted = $orders->settle($sellerOrder, $request->user(), $data['reference']);

        return back()->with('success', $deducted
            ? 'Liquidación registrada. Se restaron '.cop($deducted).' de reembolsos pendientes: transferir '.cop($sellerOrder->seller_net - $deducted).'.'
            : 'Liquidación registrada.');
    }
}
