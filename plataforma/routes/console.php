<?php

use App\Models\Order;
use App\Services\OrderService;
use App\Services\Payments\BankTransferGateway;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
 * Pedidos por transferencia sin pago después del plazo: se cancelan y las unidades vuelven al inventario.
 * Corre cada hora con el cron de hPanel (php artisan schedule:run cada minuto).
 */
Artisan::command('pedidos:vencer-transferencias', function (OrderService $orders) {
    $limit = now()->subHours(BankTransferGateway::deadlineHours());
    $count = 0;
    Order::where('payment_method', 'transferencia')->where('payment_status', 'pending')
        ->where('created_at', '<', $limit)->with('payments')->each(function (Order $order) use ($orders, &$count) {
            $payment = $order->payments->firstWhere('status', 'pending');
            if ($payment) {
                $orders->markFailed($order, $payment, ['motivo' => 'transferencia no recibida a tiempo'], 'Transferencia no recibida a tiempo');
                $count++;
            }
        });
    $this->info("Pedidos vencidos y cancelados: {$count}");
})->purpose('Cancela los pedidos por transferencia que no se pagaron a tiempo');

Schedule::command('pedidos:vencer-transferencias')->hourly()->withoutOverlapping();
