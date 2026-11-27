<?php

use App\Legacy\MediaCopier;
use App\Legacy\UrlChecker;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Campaigns\CampaignService;
use App\Services\Campaigns\EmailPriceService;
use App\Services\Marketing\AbandonedCarts;
use App\Services\Orders\OrderService;
use App\Services\Payments\BankTransferGateway;
use App\Services\Payments\OnlineGateway;
use App\Services\Payments\PaymentReconciler;
use App\Services\Payments\PaymentRegistry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

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
Artisan::command('pagos:conciliar', function (PaymentRegistry $payments, PaymentReconciler $reconciler) {
    $online = array_filter($payments->all(), fn ($g) => $g instanceof OnlineGateway && $g::isConfigured());
    Order::whereIn('payment_method', array_keys($online))->where('payment_status', 'pending')
        ->where('created_at', '>', now()->subDays(3))->where('created_at', '<', now()->subMinutes(10))
        ->each(function (Order $order) use ($online, $reconciler) {
            try {
                $id = $online[$order->payment_method]->findPaymentForOrder($order);
                $this->line($order->number.': '.($id ? $reconciler->apply($online[$order->payment_method], $id, $order) : 'sin pago en la pasarela'));
            } catch (Throwable $e) {
                report($e);
                $this->warn($order->number.': no se pudo consultar');
            }
        });
})->purpose('Consulta en las pasarelas los pagos en línea que siguen pendientes');

Schedule::command('pagos:conciliar')->everyFifteenMinutes()->withoutOverlapping();

Artisan::command('mc:copiar-fotos {--lote=50}', function (MediaCopier $copier) {
    do {
        $r = $copier->copyBatch((int) $this->option('lote'));
        $this->line("Copiadas: {$r['copied']} · fallidas: {$r['failed']} · faltan: {$r['remaining']}");
    } while ($r['copied'] > 0 && $r['remaining'] > 0);
})->purpose('Copia a la versión 1 las fotos que aún se enlazan al sitio anterior');

Artisan::command('mc:revisar-urls {--base= : Dirección de la plataforma propia (por defecto APP_URL)} {--sitemap=https://marketcity.co/wp-sitemap.xml} {--archivo= : Lista de URL, una por línea, en vez del sitemap} {--seguir : Continúa la revisión guardada}', function (UrlChecker $checker) {
    if (! $this->option('seguir')) {
        $paths = $this->option('archivo')
            ? file($this->option('archivo'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
            : $checker->readSitemap($this->option('sitemap'));
        $checker->start($paths, $this->option('base') ?: config('app.url'), $this->option('archivo') ?: $this->option('sitemap'));
        $this->info(count($paths).' URL por revisar.');
    }
    do {
        $r = $checker->checkBatch(25);
        $this->line("Revisadas {$r['checked']} · faltan {$r['remaining']}");
    } while ($r['checked'] > 0 && $r['remaining'] > 0);

    $s = $checker->summary();
    $this->table(array_keys($s), [array_values($s)]);
    Storage::disk('local')->put('revision-urls.csv', $checker->csv());
    $this->info('Detalle en storage/app/revision-urls.csv');

    return $s['falta'] + $s['error'] > 0 ? 1 : 0;
})->purpose('Revisa que las URL del sitio WordPress respondan en la plataforma propia (200, 301 o 410)');

/*
 * Campañas (#16, #77): aplica y retira precios según fechas y topes. Idempotente.
 */
Artisan::command('campanas:sincronizar', function (CampaignService $campaigns) {
    $this->info('Productos con cambio de precio: '.$campaigns->sync());
})->purpose('Aplica o retira los precios de campaña según fechas y topes');

Schedule::command('campanas:sincronizar')->everyMinute()->withoutOverlapping();

/*
 * Recordatorios de carritos abandonados (#83) y limpieza de datos personales (Ley 1581).
 */
Artisan::command('mensajes:carritos', function (AbandonedCarts $carts) {
    $r = $carts->tick();
    $this->info(collect($r)->map(fn ($v, $k) => "{$k}: {$v}")->implode(' · '));
})->purpose('Envía los recordatorios de carritos abandonados que tocan');

Schedule::command('mensajes:carritos')->everyTenMinutes()->withoutOverlapping();

Artisan::command('mensajes:limpiar', function (AbandonedCarts $carts, EmailPriceService $prices) {
    $this->info('Carritos borrados: '.$carts->purge().' · correos de cupones borrados: '.$prices->purge());
})->purpose('Borra carritos de más de 30 días y correos de cupones vencidos hace más de 30 días');

Schedule::command('mensajes:limpiar')->dailyAt('04:00')->timezone('America/Bogota');

// Latido del programador de tareas: el backoffice avisa si el cron de hPanel dejó de correr.
Schedule::call(fn () => Setting::put('scheduler_last_run', now()->toIso8601String()))->everyFiveMinutes()->name('latido');

// Respaldo diario de la base (JSON comprimido en storage/app/respaldos; guarda los últimos 10).
Schedule::command('mc:respaldar')->dailyAt('03:00')->timezone('America/Bogota')->withoutOverlapping();
