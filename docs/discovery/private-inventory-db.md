# Inventario privado — base de datos de producción (vía clon aislado)

- **Fecha:** 2026-11-13
- **Fuente:** volcado phpMyAdmin de la base `u832298070_kr99y` entregado por el propietario (13-11-2026 13:23 UTC), archivo 7z de 30,6 MB → SQL de 654 MB, 218 tablas. SHA-256 del 7z: `06e8f382…5b98` (completo en el registro del clon). Servidor de origen: MariaDB 11.8.9; phpMyAdmin corriendo sobre PHP 7.2.34.
- **Método:** importado en el clon aislado (`infra/legacy-clone`, red sin salida a internet), URLs reescritas a localhost, 11 integraciones de pago/logística neutralizadas. **Este documento contiene solo agregados**: ningún nombre, correo, teléfono, dirección, documento ni detalle de pedido individual.
- **Pendiente:** `wp-content` (código de plugins y tema) para levantar el sitio completo.

## 1. Plataforma (HECHO)

| Elemento | Valor |
|---|---|
| WooCommerce | 11.3.0 (HPOS **desactivado**: pedidos en `wp_posts`/`wp_postmeta`) |
| Tema | `wolmart` con child theme `child-2025` |
| Idioma / moneda / zona | es_CO / COP / America/Bogota |
| Plugins activos | **51** |
| Código propio activo | **15 plugins propios** + **70 snippets PHP/JS activos** en Code Snippets (74 en total) |

### Plugins activos (51)

- **Comercio y marketplace:** woocommerce, wc-frontend-manager, wc-multivendor-marketplace, wc-multivendor-membership (WCFM), woo-checkout-field-editor-pro, wc-departamentos-y-ciudades-colombia, elex-discount-per-payment-method, yith-woocommerce-ajax-navigation, yith-woocommerce-wishlist, woo-update-manager.
- **Pagos:** buy-now-pay-later-addi, sistecredito (plugin de Sistecrédito), bold-pagos-en-linea, wompi-portal-de-pagos, wava-payment, woocommerce-mercadopago; además un gateway propio `marketcity`.
- **Logística:** coordinadora, marketcity-envios.
- **Propios MarketCity (15):** MarketCity, marketcity-bienvenida, marketcity-campaign-engine, marketcity-colombia-image-placeholder, marketcity-envios, marketcity-experience, marketcity-fixes, marketcity-footer, marketcity-notify, marketcity-seller-center, marketcity-shop, mc-games, mc-insights, mc-ofertas-relampago (y el gateway `marketcity`).
- **Marketing y analítica:** facebook-for-woocommerce, google-listings-and-ads, woocommerce-google-analytics-integration, nextend-facebook-connect (login social), hostinger-affiliate-plugin.
- **Contenido y constructor:** elementor, stackable-ultimate-gutenberg-blocks, kirki, meta-box, metform, contact-form-7, wolmart-core, customizer-search.
- **Operación:** code-snippets, fluent-smtp (correo), litespeed-cache, jetpack, image-optimization, regenerate-thumbnails, hostinger-ai-assistant.

### Restos de otros plugins (tablas sin plugin activo)
Dokan (7 tablas), MultiVendorX (13), MailPoet (40 tablas, 53 suscriptores), TranslatePress (`trp`, 7), YITH, WPForms (3), Woo Discount Rules (`wdr`, 3). Confirma I-004: se probaron varios marketplaces.

## 2. Volumen de negocio (HECHO, agregados)

| Métrica | Valor |
|---|---|
| Productos | 649 publicados, 67 borradores, 4 privados, 1 archivado (máx. 721 no borrados) |
| Variaciones | 0 |
| Cupones | 20 publicados, 30 en papelera |
| Usuarios | 98 (87 clientes, 6 vendedores WCFM, 1 vendedor deshabilitado, 2 suscriptores, 2 administradores) |
| Pedidos totales (2021→2026) | **129** |
| Pedidos 2026 | 65 (27 cancelados, 18 en espera, 11 procesando, 2 completados, 3 wava-confirm, 1 addi-approved, 2 fallidos, 1 pendiente) |
| Suma de `_order_total` en estados completed/processing/on-hold | 2023: 0,45 M · 2024: 1,59 M · 2025: 0,91 M · 2026: 3,86 M COP (**no es GMV pagado confirmado**: "on-hold" y "processing" incluyen pedidos sin pago verificado) |
| Subpedidos de vendedores (WCFM) | 22 líneas; **0 solicitudes de retiro** en WCFM |

### Medios de pago usados en pedidos (histórico)
addi 50 · contraentrega (cod) 27 · gateway `marketcity` 13 · transferencia (bacs) 11 · Sistecrédito 8 · Bold 7 · Wava 6 · ePayco 3 · Wompi 2 · Paycy 1 · Mercado Pago 1.

**INFERENCIAS**
- I-013: el volumen es bajo (≈129 pedidos en 5 años), lo que reduce el riesgo de migración de datos transaccionales y permite reconciliar pedido por pedido.
- I-014: Addi es el método dominante (≈39 % de los pedidos) y la contraentrega es el segundo; la contraentrega no estaba anunciada en el sitio público.
- I-015: los pagos a vendedores no pasan por el módulo de retiros de WCFM (0 solicitudes): la liquidación de "10 días hábiles" (Q-005) es manual.
- I-016: la tasa de cancelación es alta (49 de 129, 38 %); falta saber por qué (Q-026).

## 3. Calidad y salud de datos (HECHO)

| Hallazgo | Detalle | Impacto |
|---|---|---|
| Tabla de analítica inflada | `wp_wcfm_detailed_analysis`: ~5 M filas, **458 MB (70 % de la base)**, visitas desde 2023-12-13 | Respaldos y migración lentos; no es dato de negocio crítico |
| Tareas programadas fallidas | Action Scheduler: **989 fallidas**, 30 pendientes, 85 k registros de log | Integraciones o correos que fallan en silencio |
| Sesiones | 270 sesiones WooCommerce | Normal |
| Tablas huérfanas | Dokan, MultiVendorX, MailPoet, TranslatePress, WPForms | Limpieza antes de migrar |
| Estados de pedido personalizados | `wc-addi-approved`, `wc-wava-confirm`, `wc-epayco-*` | Deben mapearse a la nueva máquina de estados |
| Productos | 649 publicados vs 639 visibles en tienda y ~570 en sitemap | Q-009 sigue abierta (diferencia de visibilidad) |

## 4. Código fuera de control de versiones (RIESGO)

- 70 snippets activos en la base de datos, muchos marcados "vista previa" pero activos con alcance `global` (por ejemplo login, checkout, Seller Center, "Rastreo seguro", "Retraso Google y Meta", "Rechazo de cookies recordado", "Ocultar huellas de tecnología").
- 15 plugins propios cuyo código solo existe en el servidor.
- Ambos son lógica de negocio real (checkout guiado, temporizador de ofertas, directorio de tiendas, Seller Center). Se inventariarán al recibir `wp-content`; los snippets ya están en el clon.
