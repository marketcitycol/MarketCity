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

/*
 * Conciliación: si un aviso de la pasarela no llegó, se consulta el estado de los pagos en línea pendientes.
 */
Artisan::command('pagos:conciliar', function (\App\Services\Payments\PaymentRegistry $payments, \App\Services\Payments\PaymentReconciler $reconciler) {
    $online = array_filter($payments->all(), fn ($g) => $g instanceof \App\Services\Payments\OnlineGateway && $g::isConfigured());
    Order::whereIn('payment_method', array_keys($online))->where('payment_status', 'pending')
        ->where('created_at', '>', now()->subDays(3))->where('created_at', '<', now()->subMinutes(10))
        ->each(function (Order $order) use ($online, $reconciler) {
            try {
                $id = $online[$order->payment_method]->findPaymentForOrder($order);
                $this->line($order->number.': '.($id ? $reconciler->apply($online[$order->payment_method], $id, $order) : 'sin pago en la pasarela'));
            } catch (\Throwable $e) {
                report($e);
                $this->warn($order->number.': no se pudo consultar');
            }
        });
})->purpose('Consulta en las pasarelas los pagos en línea que siguen pendientes');

Schedule::command('pagos:conciliar')->everyFifteenMinutes()->withoutOverlapping();
