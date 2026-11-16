<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MerchantEntity;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerOrder;
use App\Models\Store;

class DashboardController extends Controller
{
    public function index()
    {
        $paidSo = SellerOrder::where('status', '!=', 'cancelado')->whereHas('order', fn ($o) => $o->where('payment_status', 'paid'));

        return view('admin.dashboard', [
            'stats' => [
                'gmv' => Order::where('payment_status', 'paid')->sum('total'),
                'pedidos' => Order::count(),
                'pagados' => Order::where('payment_status', 'paid')->count(),
                'comision' => (clone $paidSo)->sum('commission_amount'),
                'por_liquidar' => (clone $paidSo)->whereNull('settled_at')->sum('seller_net'),
                'vencidas' => (clone $paidSo)->whereNull('settled_at')->whereDate('settlement_due_on', '<=', now('America/Bogota')->toDateString())->count(),
                'tiendas' => Store::where('status', 'active')->count(),
                'solicitudes' => Store::where('status', 'pending')->count(),
                'productos' => Product::where('status', 'active')->count(),
            ],
            'merchant' => MerchantEntity::current(),
            'recent' => Order::latest('id')->take(8)->get(),
        ]);
    }
}
