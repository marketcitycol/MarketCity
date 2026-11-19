# Guía: actualizar v1.marketcity.co sin perder datos (Hostinger, sin consola)

Tiempo: unos 10 minutos. **No toca marketcity.co.** Conserva la configuración (`.env`), la base de datos, los pedidos y las fotos subidas.

El paquete trae solo código. En la primera visita después de subirlo, la página:
1. hace una copia de la base en `mc-v1/storage/app/respaldos/`;
2. aplica solo los cambios nuevos de la base (nunca la borra);
3. limpia cachés y recarga. Queda un registro en `mc-v1/storage/logs/actualizacion.log`.

## Paso 1. Saber cuál paquete usar
1. hPanel → **Archivos** → **Administrador de archivos**.
2. Busque la carpeta **`mc-v1`** y mire la ruta de arriba:
   - Si está en `domains/v1.marketcity.co` → use **`marketcity-v1-actualizacion-sitio-….zip`**.
   - Si está en `domains/marketcity.co` → use **`marketcity-v1-actualizacion-subdominio-….zip`**.
   - Si no la encuentra o ve otra cosa, **pare** y envíeme una captura.

## Paso 2. Respaldo (obligatorio)
1. Clic derecho sobre `mc-v1` → **Comprimir** → zip. Descargue ese zip a su computador.
   (Así, si algo falla, se vuelve a dejar todo como estaba.)

## Paso 3. Subir y extraer
1. Quédese en la carpeta que **contiene** `mc-v1` (la misma de `public_html`).
2. **Antes de extraer, con el paquete "sitio":** abra `public_html` y confirme que **no** hay un archivo `wp-config.php`. Si lo hay, **pare**: ese es el WordPress de marketcity.co.
3. Suba el zip, clic derecho → **Extraer** → carpeta actual (`.`).
4. Si pregunta si quiere reemplazar archivos, diga **sí** (solo reemplaza código; los datos no vienen en el paquete).
5. Borre el zip subido.

## Paso 4. Probar
1. Abra https://v1.marketcity.co. La primera carga puede tardar unos segundos (está actualizando).
2. Ingrese al backoffice y confirme que sus productos y pedidos siguen ahí.
3. Si ve error, envíeme una captura y el archivo `mc-v1/storage/logs/actualizacion.log`.

## Cómo volver atrás
Borre `mc-v1`, suba el zip del Paso 2 y extráigalo en el mismo lugar. Si solo falló la base, la copia está en `mc-v1/storage/app/respaldos/` (renómbrela a `database.sqlite` y póngala en `mc-v1/database/`).
