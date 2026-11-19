<?php

namespace App\Legacy;

use App\Models\Page;
use App\Models\Pqr;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Payments\BankTransferGateway;
use App\Services\Payments\MercadoPagoGateway;
use App\Support\Analytics;
use Carbon\Carbon;

/**
 * Lista de "sí / no" para pasar marketcity.co a la plataforma propia.
 * Lo que la app puede comprobar sola se comprueba aquí; lo demás está en docs/operacion/cambio-de-dominio.md.
 */
class CutoverReadiness
{
    public function __construct(private UrlChecker $urls) {}

    /** @return list<array{label:string, ok:bool, detail:string, blocking:bool}> */
    public function checks(): array
    {
        $c = [];
        $add = function (string $label, bool $ok, string $detail, bool $blocking = true) use (&$c) {
            $c[] = compact('label', 'ok', 'detail', 'blocking');
        };

        $add('Modo de prueba apagado', ! config('marketcity.demo'), config('marketcity.demo')
            ? 'MC_DEMO=true: se ve el aviso de prueba, los buscadores no indexan y el pago de prueba sigue disponible. Pon MC_DEMO=false en el .env el día del cambio.'
            : 'Sin aviso de prueba; el pago de prueba está apagado.');

        $driver = config('database.default');
        $add('Base de datos MySQL', in_array($driver, ['mysql', 'mariadb'], true), "Conexión actual: {$driver}.");

        $mailer = config('mail.default');
        $add('Correo saliente configurado', ! in_array($mailer, ['log', 'array'], true), "MAIL_MAILER={$mailer}.".(in_array($mailer, ['log', 'array'], true) ? ' Los correos no salen: configura el SMTP de Hostinger.' : ''));

        $mp = MercadoPagoGateway::isConfigured();
        $transfer = BankTransferGateway::isConfigured();
        $add('Al menos un medio de pago real', $mp || $transfer, trim(($transfer ? 'Transferencia lista. ' : 'Faltan los datos de la cuenta para transferencia. ').($mp ? 'Mercado Pago listo.' : 'Mercado Pago sin llaves.')));

        $last = Setting::get('scheduler_last_run');
        $fresh = $last && Carbon::parse($last)->gt(now()->subMinutes(15));
        $add('Tareas programadas (cron) corriendo', $fresh, $last ? 'Último latido: '.fecha(Carbon::parse($last)) : 'Nunca ha corrido: falta el cron de hPanel (php artisan schedule:run cada minuto).');

        $backups = glob(storage_path('app/respaldos/*')) ?: [];
        $newest = $backups ? max(array_map('filemtime', $backups)) : null;
        $add('Respaldo de las últimas 24 horas', $newest && $newest > time() - 86400, $newest ? 'Último respaldo: '.fecha(Carbon::createFromTimestamp($newest)) : 'No hay respaldos en storage/app/respaldos.');

        $remoteImages = Product::where('image_path', 'like', '%marketcity.co/wp-content/%')->count();
        $add('Fotos copiadas del sitio anterior', $remoteImages === 0, $remoteImages
            ? "{$remoteImages} productos aún muestran la foto desde WordPress; al apagarlo se verían sin foto. Usa Importar catálogo → Copiar fotos."
            : 'Todas las fotos están en la plataforma propia.');

        $s = $this->urls->summary();
        $urlsOk = $s['total'] > 0 && $s['revisadas'] === $s['total'] && $s['falta'] + $s['error'] === 0;
        $add('URL del sitio anterior revisadas', $urlsOk, $s['total']
            ? "{$s['revisadas']} de {$s['total']} revisadas: {$s['ok']} iguales, {$s['redirige']} con 301, {$s['retirada']} retiradas, {$s['falta']} sin destino, {$s['error']} con error."
            : 'Aún no se ha corrido la revisión.');

        $legal = ['politica-de-privacidad', 'contacto', 'eliminar-mi-cuenta'];
        $missing = array_values(array_diff($legal, Page::whereIn('slug', $legal)->where('status', 'published')->pluck('slug')->all()));
        $add('Páginas legales publicadas', ! $missing, $missing ? 'Faltan: '.implode(', ', $missing).'.' : 'Política de datos, contacto y eliminar mi cuenta.');

        $overdue = Pqr::whereIn('status', Pqr::OPEN)->where('due_at', '<', now())->count();
        $add('PQRS sin vencer', $overdue === 0, $overdue ? "{$overdue} PQRS vencidas." : 'Ninguna vencida.', false);

        $add('Medición con consentimiento', Analytics::active(), Analytics::active() ? 'Activa (solo tras «Aceptar»).' : 'Sin GA4/GTM/Meta: no se medirán las ventas del nuevo sitio.', false);

        $add('Dirección del sitio (APP_URL)', str_starts_with((string) config('app.url'), 'https://'), 'APP_URL='.config('app.url').'. El día del cambio debe ser https://marketcity.co.', false);

        return $c;
    }

    public function ready(): bool
    {
        return collect($this->checks())->where('blocking', true)->every(fn ($c) => $c['ok']);
    }
}
