#!/usr/bin/env bash
# Arma el paquete para subir la versión 1 a un subdominio de Hostinger (hosting compartido, sin consola).
# Uso: deploy/build-hostinger.sh https://v1.marketcity.co /ruta/salida
# Produce: marketcity-v1-hostinger.zip (mc-v1/ + public_html/v1/) y credenciales.txt (fuera del zip).
set -euo pipefail

URL="${1:?URL del subdominio}"
OUT="${2:?carpeta de salida}"
SRC="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(mktemp -d)"
APP="$WORK/mc-v1"
PUB="$WORK/public_html/v1"

mkdir -p "$APP" "$PUB" "$OUT"
# Solo archivos versionados (nada de .env, base local ni dependencias de desarrollo).
git -C "$SRC" ls-files -z . | (cd "$SRC" && xargs -0 tar -cf -) | tar -xf - -C "$APP"

(cd "$APP" && composer install --no-dev --optimize-autoloader --no-interaction --quiet)
find "$APP/vendor" -name .git -type d -prune -exec rm -rf {} +
(cd "$SRC" && npm run build >/dev/null) && cp -r "$SRC/public/build" "$APP/public/build"

# La carpeta pública va al subdominio; el resto queda fuera de public_html.
cp -r "$APP/public/." "$PUB/"
rm -rf "$APP/public"/* && mkdir -p "$PUB/uploads"
cp "$SRC/deploy/hostinger-index.php" "$PUB/index.php"
printf 'Require all denied\n' > "$APP/.htaccess"

cat > "$APP/.env" <<ENV
APP_NAME=MarketCity
APP_ENV=production
APP_KEY=base64:$(openssl rand -base64 32)
APP_DEBUG=false
APP_URL=$URL
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
LOG_CHANNEL=single
LOG_LEVEL=error
DB_CONNECTION=sqlite
SESSION_DRIVER=database
SESSION_LIFETIME=240
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log
MC_DEMO=true
MC_SHOW_DEMO_ACCOUNTS=false
ENV

touch "$APP/database/database.sqlite"
(cd "$APP" && php artisan migrate:fresh --seed --force --no-interaction >/dev/null)

# Contraseñas únicas para el subdominio (las de la demo local son públicas en el README).
CREDS="$OUT/credenciales.txt"
(cd "$APP" && php -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
echo "Cuentas del subdominio de prueba (guárdelas; no se envían en el paquete)\n\n";
foreach (App\Models\User::orderBy("id")->get() as $u) {
    $p = Illuminate\Support\Str::password(14, symbols: false);
    $u->update(["password" => $p]);
    printf("%-11s %-32s %s\n", $u->role, $u->email, $p);
}
') > "$CREDS"
rm -rf "$APP/storage/logs/"*.log "$APP/storage/framework/views/"*.php 2>/dev/null || true

(cd "$WORK" && zip -qr "$OUT/marketcity-v1-hostinger.zip" mc-v1 public_html)
rm -rf "$WORK"
echo "Listo: $OUT/marketcity-v1-hostinger.zip y $CREDS"
