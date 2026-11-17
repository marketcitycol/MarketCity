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

$app->handleRequest(Request::capture());
