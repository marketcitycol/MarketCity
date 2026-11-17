#!/usr/bin/env bash
# Arma un paquete de ACTUALIZACIÓN para la versión 1 ya instalada en Hostinger.
# Conserva .env, la base de datos (database/database.sqlite), storage/ y las fotos subidas (uploads/).
# En la primera visita después de subirlo, index.php respalda la base y corre solo las migraciones nuevas.
# Uso: deploy/build-update-hostinger.sh /ruta/salida [sitio|subdominio]
set -euo pipefail

OUT="${1:?carpeta de salida}"
LAYOUT="${2:-sitio}"
SRC="$(cd "$(dirname "$0")/.." && pwd)"
WORK="$(mktemp -d)"
APP="$WORK/mc-v1"
if [ "$LAYOUT" = "subdominio" ]; then PUB="$WORK/public_html/v1"; else PUB="$WORK/public_html"; fi
mkdir -p "$APP" "$PUB" "$OUT"

git -C "$SRC" ls-files -z . | (cd "$SRC" && xargs -0 tar -cf -) | tar -xf - -C "$APP"
# Reusar vendor/ local (sin .git) para no volver a descargar; composer quita lo de desarrollo.
[ -d "$SRC/vendor" ] && (cd "$SRC" && tar --exclude=.git -cf - vendor) | tar -xf - -C "$APP"
(cd "$APP" && composer install --no-dev --optimize-autoloader --no-interaction --quiet)
find "$APP/vendor" -name .git -type d -prune -exec rm -rf {} +
(cd "$SRC" && npm run build >/dev/null) && cp -r "$SRC/public/build" "$APP/public/build"

cp -r "$APP/public/." "$PUB/"
rm -rf "$APP/public" "$PUB/uploads"
cp "$SRC/deploy/hostinger-index.php" "$PUB/index.php"
printf 'Require all denied\n' > "$APP/.htaccess"

# Nada de datos ni configuración del servidor: no se envían .env, base, storage ni uploads.
rm -rf "$APP/.env" "$APP/.env.example" "$APP/database/database.sqlite" "$APP/storage" \
       "$APP/tests" "$APP/deploy" "$APP/Dockerfile" "$APP/docker-compose.yml" "$APP/node_modules" \
       "$APP"/*.png "$APP/phpunit.xml"
mkdir -p "$APP/storage/app"
date -u +%FT%TZ > "$APP/storage/app/actualizar.pendiente"

VERSION="$(git -C "$SRC" rev-parse --short HEAD)"
echo "$VERSION" > "$APP/VERSION"
ZIP="$OUT/marketcity-v1-actualizacion-$LAYOUT-$VERSION.zip"
rm -f "$ZIP"
(cd "$WORK" && zip -qr "$ZIP" mc-v1 public_html)
rm -rf "$WORK"
echo "Listo: $ZIP"
