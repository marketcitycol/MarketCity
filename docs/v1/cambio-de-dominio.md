# Cambio de dominio: marketcity.co pasa a la plataforma propia

- **Fecha:** 2026-11-19. **Estado:** lista preparada; **no hay fecha de cambio**. El cambio lo ordena el propietario por escrito.
- Complementa `docs/marketcity-audit/10-plan-cutover-rollback.md` (estrategia, criterios y reversión) con los pasos concretos de Hostinger y lo que la plataforma ya comprueba sola.

## 1. Lo que la plataforma comprueba sola

En **Backoffice → Cambio de dominio** hay una lista sí/no. Mientras diga «TODAVÍA NO», no se cambia.

| Comprobación | Obligatoria | Cómo se pone en verde |
|---|---|---|
| Modo de prueba apagado | Sí | `MC_DEMO=false` en el `.env` (quita el aviso de prueba, el `noindex` y el pago de prueba). Se hace **el mismo día del cambio**, no antes. |
| Base de datos MySQL | Sí | Guía `docs/v1/guia-mysql-hostinger.md`. |
| Correo saliente | Sí | SMTP de Hostinger en el `.env` (`MAIL_MAILER=smtp`). |
| Un medio de pago real | Sí | Datos de la cuenta en Configuración → Transferencia, y/o llaves de Mercado Pago en el `.env`. |
| Cron corriendo | Sí | Tarea en hPanel → Avanzado → Cron: `php artisan schedule:run` cada minuto. |
| Respaldo de las últimas 24 h | Sí | Se crea solo con cada actualización y con el cron. |
| Fotos copiadas | Sí | Importar catálogo → Copiar fotos, hasta que no quede ninguna enlazada a WordPress. |
| URL antiguas revisadas | Sí | En la misma página: «Empezar revisión» con el sitemap `https://marketcity.co/wp-sitemap.xml`. Debe terminar con 0 «Sin destino». |
| Páginas legales publicadas | Sí | Páginas: `politica-de-privacidad`, `contacto`, `eliminar-mi-cuenta`. |
| PQRS sin vencer | Recomendado | Backoffice → PQRS. |
| Medición con consentimiento | Recomendado | Plugins → Medición y aviso de cookies (GTM o GA4, y píxel de Meta). |
| `APP_URL` con https | Recomendado | El día del cambio: `APP_URL=https://marketcity.co`. |

## 2. Redirecciones (ya programadas)

`App\Http\Middleware\LegacyRedirects` responde 301 a las URL de WordPress que cambian y 410 a las que se retiran. Las URL que son iguales en los dos sitios (`/producto/{slug}`, `/tienda/{slug}`, `/categoria-producto/{slug}`, `/shop`, `/mi-cuenta`, `/rastrear-pedido`, `/seller-center`, `/tiendas`, `/ofertas-relampago` y las páginas copiadas) no se tocan.

| Antes (WordPress) | Ahora |
|---|---|
| `/cart/`, `/cart-2/` | `/carrito` |
| `/checkout-2/`, `/finalizar-compra/` | `/checkout` |
| `/checkout/orden-recibida/{n}/` | `/rastrear-pedido` |
| `/my-account/`, `/my-account-2/…`, `/my-orders/` | `/mi-cuenta` |
| `/rastrea-tu-pedido/` | `/rastrear-pedido` |
| `/vendor-membership/`, `/vender-2/`, `/become-a-vendor/`, `/store-registration/` | `/vender` |
| `/store-listing/`, `/vendors/`, `/tienda/` | `/tiendas` |
| `/store/{slug}/`, `/tienda/{slug}/…` | `/tienda/{slug}` |
| `/seller-center/products-manage/` y similares | su sección en `/seller-center/…` |
| `/product/{slug}/`, `/?product={slug}`, `/?p={id}` | `/producto/{slug}` |
| `/categoria-producto/padre/hija/` | `/categoria-producto/hija` (la más específica que exista) |
| `/etiqueta-producto/{x}/`, `/?s=…` | búsqueda en `/shop` |
| `/…/page/{n}/` | `?page={n}` |
| `/black-city/` | `/ofertas-relampago` |
| `/contact-us/`, `/faq/` | `/contacto` |
| `/?page_id=6103` | `/politica-de-privacidad` |
| `/wp-sitemap*.xml` | `/sitemap.xml` |
| `/wp-login.php`, `/wp-admin/`, `/admin` | `/ingresar` |
| `/estadisticas/`, `/estadisticas-ecommerce/`, `/wp-json/…` | 410 (retiradas) |

Si el equipo crea en v1 una página con el mismo enlace que una redirección, gana la página.

## 3. Pasos el día del cambio (Hostinger)

**Antes (T-7 a T-1)**
1. Lista sí/no en verde salvo «Modo de prueba» y «APP_URL».
2. Respaldo completo de WordPress desde hPanel (archivos + base) **y restaurarlo una vez** en otro sitio para saber que sirve.
3. Avisar a los vendedores de la fecha. Bajar el TTL del DNS a 300 s si el dominio no apunta ya a Hostinger.
4. Copiar la carpeta `wp-content/uploads` de WordPress a la carpeta pública de v1 con el mismo nombre, para que las fotos viejas enlazadas desde Google, WhatsApp o anuncios sigan abriendo.

**T0 (hora de poco movimiento)**
1. En WordPress: modo mantenimiento para compras (o pausar el checkout).
2. Volver a copiar a v1 los pedidos y clientes nuevos desde la última copia (Importar catálogo → Pedidos).
3. En hPanel, hacer que `marketcity.co` sirva la carpeta de v1. La forma más segura: renombrar la carpeta pública de WordPress (por ejemplo `public_html_wordpress`) sin borrarla, y poner en `public_html` de marketcity.co el `index.php` y los archivos públicos de v1 apuntando a su carpeta de la app. Confirmar con el soporte de Hostinger si prefieren otra forma para sitios separados.
4. En el `.env`: `APP_URL=https://marketcity.co`, `MC_DEMO=false`. Revisar que el SSL de marketcity.co esté activo.
5. Pruebas de humo: inicio, una ficha, carrito, checkout con transferencia, un pago real pequeño con la pasarela activa, Seller Center, backoffice, `/sitemap.xml`, una URL vieja (`/cart-2/` debe dar 301).
6. Correr otra vez la revisión de URL desde el backoffice, ahora contra marketcity.co.

**Después (72 h)**
- Google Search Console: enviar `https://marketcity.co/sitemap.xml` y vigilar «Páginas no encontradas».
- Merchant Center y catálogo de Meta: cambiar la fuente al feed nuevo `https://marketcity.co/catalogo/productos.xml` (el de WooCommerce deja de existir).
- Revisar a diario: pagos pendientes, PQRS, correos que no salieron (registro de Laravel) y errores 5xx.
- WordPress queda guardado 14 días sin borrar para volver si hace falta.

## 4. Volver a WordPress (reversión)

Disparadores y procedimiento en `10-plan-cutover-rollback.md` §4. En Hostinger es deshacer el paso T0.3: devolver la carpeta `public_html_wordpress` a `public_html` y quitar el modo mantenimiento. Los pedidos tomados en v1 durante la ventana se listan en Backoffice → Pedidos y se registran a mano en WooCommerce.
