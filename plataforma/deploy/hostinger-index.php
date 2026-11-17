<?php

/*
 * index.php para hosting compartido (Hostinger).
 * Esta carpeta es la pública del sitio; el resto de la aplicación vive fuera de public_html,
 * en la carpeta mc-v1, para que .env, la base de datos y el código no se puedan descargar.
 * Sirve para un sitio propio (public_html/ junto a mc-v1/) o un subdominio (public_html/v1/).
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$app_dir = is_dir(dirname(__DIR__).'/mc-v1') ? dirname(__DIR__).'/mc-v1' : dirname(__DIR__, 2).'/mc-v1';

if (file_exists($maintenance = $app_dir.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $app_dir.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $app_dir.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);

/*
 * Actualización sin consola: el paquete de actualización deja el archivo
 * storage/app/actualizar.pendiente. La primera visita copia la base de datos
 * (storage/app/respaldos/), aplica solo las migraciones nuevas (nunca borra datos),
 * limpia cachés, borra el aviso y recarga la página.
 */
$pending = $app_dir.'/storage/app/actualizar.pendiente';
if (is_file($pending) && ($lock = fopen($pending, 'r')) && flock($lock, LOCK_EX | LOCK_NB)) {
    $log = $app_dir.'/storage/logs/actualizacion.log';
    $db = $app_dir.'/database/database.sqlite';
    $backupDir = $app_dir.'/storage/app/respaldos';
    is_dir($backupDir) || mkdir($backupDir, 0750, true);
    $stamp = date('Ymd-His');
    $ok = ! is_file($db) || copy($db, "$backupDir/database-$stamp.sqlite");
    if ($ok) {
        $console = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $code = $console->call('migrate', ['--force' => true]);
        $out = $console->output();
        $console->call('optimize:clear');
        file_put_contents($log, "[$stamp] migrate=$code\n$out\n", FILE_APPEND);
        if ($code === 0) {
            flock($lock, LOCK_UN);
            fclose($lock);
            unlink($pending);
        }
    } else {
        file_put_contents($log, "[$stamp] no se pudo copiar la base; no se migró\n", FILE_APPEND);
    }
    header('Location: '.($_SERVER['REQUEST_URI'] ?? '/'), true, 302);
    exit;
}

$app->handleRequest(Request::capture());
