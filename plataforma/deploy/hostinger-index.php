<?php

/*
 * index.php para hosting compartido (Hostinger).
 * Esta carpeta es la pública del subdominio; el resto de la aplicación vive fuera de public_html,
 * en ../../mc-v1, para que .env, la base de datos y el código no se puedan descargar.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$app_dir = dirname(__DIR__, 2).'/mc-v1';

if (file_exists($maintenance = $app_dir.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $app_dir.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $app_dir.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
