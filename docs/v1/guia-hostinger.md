# Guía: montar la versión 1 en v1.marketcity.co (Hostinger)

Tiempo: unos 15 minutos. **No cambia nada de marketcity.co**: solo se crea un subdominio y dos carpetas nuevas.
Si en algún paso Hostinger pide **reemplazar o sobrescribir** un archivo, cancele y avíseme.

Necesita: el archivo `marketcity-v1-hostinger.zip` (adjunto en el hilo) y `credenciales.txt` con las contraseñas.

## Paso 1. Crear el subdominio
1. Entre a **hPanel** → **Sitios web** → en marketcity.co pulse **Administrar**.
2. En el menú de la izquierda: **Dominios** → **Subdominios**.
3. En "Crear un nuevo subdominio" escriba `v1`. Deje **sin marcar** "Carpeta personalizada".
4. Pulse **Crear**. En la lista debe aparecer `v1.marketcity.co` con la carpeta `/public_html/v1`.

## Paso 2. Revisar PHP (solo mirar)
1. Menú **Avanzado** → **Configuración de PHP**.
2. Mire la versión: debe ser **8.2 o mayor**. No la cambie.
3. Pestaña **Extensiones PHP**: busque `pdo_sqlite` y `sqlite3`. Si están marcadas, siga. Si alguna **no** está marcada, no la marque todavía: avíseme.

## Paso 3. Subir y descomprimir el paquete
1. Menú **Archivos** → **Administrador de archivos**.
2. Va a ver la carpeta `public_html`. Suba **un nivel** (flecha o enlace de la ruta arriba) hasta la carpeta que **contiene** `public_html`; normalmente se llama `marketcity.co` (ruta `domains/marketcity.co`).
3. Pulse **Subir** (ícono de flecha hacia arriba) y elija `marketcity-v1-hostinger.zip`.
4. Clic derecho sobre el zip → **Extraer** → deje la carpeta actual (`.` o la misma) → **Extraer**.
5. Compruebe:
   - Junto a `public_html` apareció una carpeta nueva **`mc-v1`**.
   - Dentro de `public_html/v1` hay `index.php`, `.htaccess`, `build` y `uploads`.
6. Si dentro de `public_html/v1` existe un archivo `default.php` (lo crea Hostinger al hacer el subdominio), bórrelo.
7. Borre el zip (ya no se necesita).

## Paso 4. Certificado SSL
1. Menú **Seguridad** → **SSL**.
2. Si `v1.marketcity.co` no aparece como "Activo", pulse **Instalar SSL** y elíjalo. Puede tardar unos minutos.

## Paso 5. Probar
1. Abra **https://v1.marketcity.co**. Debe ver la tienda con el aviso "Versión 1 de prueba".
2. Ingrese en **https://v1.marketcity.co/ingresar** con las cuentas de `credenciales.txt`:
   - `admin@marketcity.test` → Backoffice.
   - `vendedor1@marketcity.test` → Seller Center.
3. Haga una compra de prueba y apruebe el "pago de prueba". No se cobra nada.

Si sale una pantalla de error o en blanco, envíeme una captura.

## Paso 6 (opcional). Cargar el catálogo real
1. En wp-admin de marketcity.co: **Productos** → **Exportar** → deje todo como viene → **Generar CSV**. Exportar no cambia la tienda.
2. En https://v1.marketcity.co/backoffice → **Importar catálogo** → suba el CSV.
   Se importan nombres, precios, stock, categorías, marcas y fotos (por enlace). No se importan clientes ni pedidos.

## Cómo quitarlo
Borrar el subdominio `v1` en hPanel y las carpetas `public_html/v1` y `mc-v1`. marketcity.co no se ve afectado.
