# Inventario privado — código del sitio (vía clon aislado)

- **Fecha:** 2026-11-15
- **Fuente:** `wp-content` completo de producción entregado por el propietario en 3 volúmenes 7-Zip (1,08 GB comprimido; 79 641 entradas; SHA-256 en el registro del clon), más el `.htaccess` raíz y una captura del listado de `public_html` (E-032).
- **Método:** extraído dentro del clon aislado (`infra/legacy-clone`, sin salida a internet). Solo lectura del código; búsqueda de patrones automatizada y revisión manual puntual. **No se copian valores de llaves ni datos personales en este documento.**

## 1. Sitio levantado en el clon (HECHO)

| Prueba | Resultado |
|---|---|
| Núcleo de producción | WordPress **7.0.4** (revisión de base 61833). El clon se ajustó a la imagen `wordpress:7.0-php8.3`; con 6.8 o 6.9 WordPress pedía "actualizar la base". |
| PHP | 8.3 en el clon. **Pendiente:** confirmar la versión de PHP del sitio en Hostinger (Q-029). |
| Inicio, tienda, tiendas, carrito, mi cuenta, búsqueda, ficha de producto | 200, con el aviso rojo de clon. Inicio ≈ 0,9 MB de HTML (sin caché). |
| Checkout | Redirige al carrito si está vacío (comportamiento normal). |
| Seller Center | `/seller-center/` redirige a "mi cuenta" (requiere sesión de vendedor). |
| `wp-login.php` | Redirige al inicio: el login de administración está oculto por un snippet ("Login secreto"). La ruta no se registra aquí. |
| Drop-in `object-cache.php` (LiteSpeed) | Desactivado en el clon (no hay Redis/Memcached). |
| Salida a internet | Bloqueada; `clone-guard` registró intentos hacia `connect.facebook.net`. |

**Rutas reales de WooCommerce** (no las del texto público): tienda `/shop/`, carrito `/cart-2/`, checkout `/checkout/`, cuenta `/my-account/` (existe además `/my-account-2/`, que redirige). `/tienda/` redirige a `/tiendas/` (directorio de vendedores). Permalinks con fecha (`/%year%/%monthnum%/%day%/%postname%/`).

## 2. Plugins

| Grupo | Cantidad |
|---|---|
| Plugins en disco | 86 (51 activos, 35 inactivos) |
| Must-use | 2 de Hostinger/Elementor (`hostinger-auto-updates`, `elementor-safe-mode`) |
| Temas | `wolmart` 1.10.0 (padre) + `child-2025` activo (13 líneas: el tema hijo está vacío; la personalización vive en plugins y snippets) |
| `uploads` | 989 MB (2025: 529 MB) |

Plugins inactivos que siguen en el servidor y exponen archivos: restos de Dokan, MultiVendorX (`dc-woocommerce-multi-vendor`), MailPoet, ePayco, Helio, `GravityInstall` (conector Gravity para Addi, con carpeta `.git`, `gravity-debug.log` y scripts de prueba `probar_credenciales*.php`) y `quick-comm-notification` (con `.git`).

## 3. Código propio (14 plugins activos + 4 inactivos)

≈ **20 000 líneas** de PHP/JS en los plugins activos. Ninguno está en control de versiones de MarketCity (R-019).

| Plugin | Ver. | Líneas | Qué hace (resumen) | Superficie expuesta |
|---|---|---|---|---|
| `MarketCity` (MarketCity Pay) | 2.0.1 | 1 951 | Pasarela propia: pago completo, **pago dividido** y aprobación manual; firma HMAC hacia `api.marketcitypay.com`. Llaves en ajustes (no en el código). | Webhook de pagos |
| `marketcity-notify` | 1.0.0 | 3 950 | Notificaciones push (Firebase FCM): seguir tienda, avisos de precio, panel del vendedor. Cuenta de servicio de Firebase en la base (`mcn_settings`). 0 dispositivos registrados. | Shortcodes, REST |
| `marketcity-experience` | 1.0.0 | 3 059 | Header, búsqueda con registro de consultas (`mcx_search_log`). | Cron diario |
| `mc-insights` | 1.0.0 | 1 860 | Analítica propia: sesiones, embudo, mapas de calor (1 424 sesiones). Consultas con `prepare` y permisos correctos. | Endpoint REST público de recolección |
| `marketcity-fixes` | 1.5.1 | 1 841 | Correcciones varias, rastreo de guías, mapa de cobertura, búsqueda visual. | REST `mcf/v1` (admin + 1 público) |
| `mc-ofertas-relampago` | 1.0.0 | 1 763 | Ofertas relámpago con cuenta regresiva. | REST público de lectura |
| `marketcity-campaign-engine` | 1.0.0 | 1 355 | Landings de campaña (`[mcce_landing]`). | — |
| `mc-games` | 1.0.0 | 1 136 | Juegos/premios para compradores. | REST (1 público, 2 con sesión) |
| `marketcity-bienvenida` | 2.0.0 | 900 | Ruleta de bienvenida (`mcb_spin`, accesible sin sesión). | AJAX sin sesión |
| `marketcity-envios` | 1.1.0 | 743 | Envío por vendedor, enlaces de rastreo (Coordinadora, Servientrega, TCC, Envía, Interrapidísimo). | AJAX con sesión |
| `marketcity-footer` | 1.0.0 | 593 | Pie de página; formulario de datos personales (`mcft_personal`, sin sesión). | AJAX sin sesión |
| `marketcity-seller-center` | 1.0.0 | 431 | Pantallas del Seller Center sobre WCFM. | Shortcodes |
| `marketcity-colombia-image-placeholder` | 1.0.0 | 214 | Imagen por defecto. | — |
| `marketcity-shop` | 1.0.0 | 170 | Ajustes de la tienda. | — |
| Inactivos: `marketcity-flash`, `marketcity-blackout`, `marketcity-navidad-particles`, `mc-side-cart` | — | 5 736 | Versiones previas o estacionales. | — |

No se encontraron `eval`, código ofuscado ni llaves escritas en el código propio (los `base64_decode` de `marketcity-fixes` decodifican parámetros de búsqueda).

## 4. Snippets (Code Snippets)

- **70 activos**, ≈ **2 MB de código** guardado en la base de datos (45 globales, 23 front-end, 2 de contenido). Los más grandes: "MarketTurbo envío rápido" (448 KB, llama a una API logística externa), "Perfil de vendedor" (352 KB), "Carrito lateral" (160 KB), "Directorio de tiendas" (142 KB), "Seller Center Productos" (138 KB), "Checkout guiado" (124 KB).
- **34 están marcados "vista previa".** Todos se activan solo si el visitante abre cualquier URL con `?mc_preview=1` (queda una cookie). Para un visitante normal están apagados, pero **cualquiera que conozca el parámetro puede encender funciones sin terminar**, y la caché de LiteSpeed podría guardar esa variante (R-023). Esto responde en parte Q-027: falta que el propietario diga cuáles ya deben publicarse y cuáles borrar.
- Snippets con nombre de seguridad ("Ocultar huellas de tecnología", "Blackout anti-espías", "Login secreto") ocultan versiones y el login: es seguridad por ocultación, no reemplaza 2FA (R-013).

## 5. Credenciales guardadas en la base (solo nombres y conteos)

- Además de los ajustes de pasarelas, hay **31 opciones sueltas con llaves o tokens de producción**: Mercado Pago (token productivo), Bold (llaves de producción y prueba guardadas fuera de los ajustes), Facebook/Meta (tokens de página, comercio y WhatsApp), Tidio, Weglot, Hostinger, Addi (contraseña de callback), un token "producción" de MarketTurbo (`mcmt_*`) y la cuenta de servicio de Firebase.
- **19 llaves de la API REST de WooCommerce** (16 de lectura y escritura; último uso 2026-11-13) y 1 usuario con contraseñas de aplicación.
- En el clon todas quedaron reemplazadas por `REDACTED_BY_CLONE` (`neutralize.sh` ampliado: 42 opciones). En producción siguen vigentes; ver R-022.

## 6. Tareas programadas fallidas (amplía R-020)

De las 989 fallidas: 885 son limpieza de `image-optimization` (ruido), **65 son la sincronización de estado de productos con Google Merchant** (Google Listings & Ads), 33 `fetch_patterns`, 2 latidos de Facebook y **1 entrega de webhook de WooCommerce** (2026-11-11).

## 7. Fuera de WordPress (E-033)

Las carpetas `seller` y `sellercenter` de `public_html` solo tienen `default.php`, la página por defecto de Hostinger (las dos son idénticas). Como una carpeta física tiene prioridad sobre WordPress, **hoy `marketcity.co/seller/` y `marketcity.co/sellercenter/` muestran "¡Ya todo está listo!" de Hostinger** en vez de llevar al Seller Center (verificado con GET público). Probablemente quedaron de subdominios creados en 2024. `.htaccess.bk` es el bloque estándar de WordPress; `llms.txt` es un índice estático de octubre de 2025 con entradas demo y páginas viejas (R-027). No hay más código fuera de WordPress.
