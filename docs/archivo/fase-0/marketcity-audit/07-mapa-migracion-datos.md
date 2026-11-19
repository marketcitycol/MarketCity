# 07 · Mapa de migración de datos (WooCommerce/WCFM → v1)

- **Fecha:** 2026-11-17. **Estado:** BORRADOR. Ninguna migración ejecutada aún sobre datos reales en v1; el importador de catálogo CSV ya existe y está probado con un archivo de ejemplo.
- **Regla:** todo registro migrado guarda `legacy_id` (id de WordPress) para conciliar y para poder repetir la migración sin duplicar.
- **Fuente:** volcado de la BD (Fase 0) y Store API pública. Prefijo de tablas `wp_`.

## 1. Entidades

| Origen (legado) | Destino (v1) | Volumen | Regla | Conciliación |
|---|---|---|---|---|
| `wp_users` rol `wcfm_vendor` + `wp_usermeta` (`wcfmmp_profile_settings`) | `users` (role `seller`) + `stores` | 6 (+1 deshabilitado) | Slug de tienda = `store_slug` de WCFM (mantiene `/tienda/<slug>`) | Conteo y lista de slugs |
| `wp_users` rol `customer` | `users` (role `customer`) | 87 | Sin contraseña: se envía "crear contraseña" en el corte (los hashes de WordPress no se reutilizan, D-04) | Conteo; correos únicos |
| `wp_users` administradores | `users` (role `admin`) | 2 | Se crean a mano con MFA | — |
| `wp_terms`/`wp_term_taxonomy` `product_cat` | `categories` | 15 públicas (más vacías) | Slug igual (`/categoria-producto/<slug>`) | Conteo por slug |
| Atributo/taxonomía de marca | `brands` | PENDIENTE | Slug igual | — |
| `wp_posts` `product` + `wp_postmeta` (`_price`, `_regular_price`, `_sale_price`, `_stock`, `_sku`, `_thumbnail_id`) + `wp_wc_product_meta_lookup` | `products` | 649 publicados + 67 borradores | Precio en COP enteros. **SKU obligatorio**: si falta (95 % de la muestra), se genera `LEG-<id>` | Conteo, suma de precios, suma de stock |
| `_wcfm_product_author` / `post_author` | `products.store_id` | — | Productos sin vendedor → tienda "MarketCity" | Conteo por tienda |
| Variaciones | — | 0 | No aplica hoy | — |
| `wp_posts` `shop_order` + meta (`_billing_*`, `_order_total`, `_payment_method`, campo propio de cédula — nombre exacto por confirmar) | `orders` | 129 | Estados: `completed→entregado`, `processing→recibido/pagado`, `on-hold→pendiente`, `cancelled/failed→cancelado`, `wava-confirm`/`addi-approved`→ revisar a mano | Conteo por estado y suma de totales por año |
| `wp_woocommerce_order_items` + `_itemmeta` | `order_items` | — | Copiar nombre, SKU y precio al momento de la compra | Suma de líneas = total − envío |
| `wp_wcfm_marketplace_orders` | `seller_orders` | 22 líneas | Comisión histórica se copia tal cual (`commission_rule = legacy`) | Suma de comisión |
| Notas de pedido (`comment_type = order_note`) | `order_events` | — | Solo notas no privadas | — |
| `shop_coupon` | `coupons` (Fase G) | 20 | Migrar solo vigentes | — |
| `wp_posts` `attachment` | Archivos en disco de v1 | 989 MB | Solo imágenes referenciadas por productos y tiendas activos; hash SHA-256 antes y después | Hash y conteo |
| Reseñas (`wp_comments` `review`) | `reviews` (Fase G) | 0 visibles | — | — |
| `wp_wcfm_detailed_analysis` | No se migra | 5 M filas | Se archiva en frío | — |
| Snippets, opciones de plugins | No se migran | — | Se reescriben | — |
| Llaves y tokens en `wp_options` | **Nunca** se migran | 31 + 19 | Se emiten llaves nuevas por pasarela (R-022) | — |

## 2. Datos personales
- Se migran solo los campos necesarios para operar (nombre, documento, contacto, dirección de pedidos).
- Los pedidos de más de 5 años y los clientes sin pedidos: decisión D-04 (migrar, archivar o dejar en solo lectura).
- La copia de trabajo para migración en seco se **sanitiza** (correos `cliente<id>@example.test`, teléfonos y documentos enmascarados) salvo en la corrida final autorizada.

## 3. Procedimiento
1. **Ensayo en seco** sobre copia sanitizada (Fase K): comando `php artisan mc:migrate-legacy --dry-run` que lee un volcado MySQL local.
2. Reporte de reconciliación automático: conteos por entidad, sumas por año, productos sin imagen, slugs en conflicto.
3. Corrida final en la ventana de corte con delta: solo pedidos y stock modificados desde el ensayo (por `post_modified`).
4. **Reversión:** v1 se restaura desde su respaldo previo; el legado no se toca (solo lectura).

## 4. Redirecciones SEO
v1 conserva `/producto/`, `/categoria-producto/`, `/tienda/`, `/shop`, `/ofertas-relampago`. Rutas duplicadas del legado ya redirigen con 301 (`/cart`, `/cart-2`, `/my-account*`, `/vendor-membership`, `/sellercenter`). Los slugs con sufijo `-2`, `-3` (productos repetidos) se revisan en la migración: el duplicado redirige al principal.
