# 03 · Plugins, tema y snippets del sitio actual

- **Fecha:** 2026-11-17. **Fuente:** inventario de Fase 0 en el clon aislado (E-031, E-032; `docs/discovery/private-inventory-code.md` y `private-inventory-db.md` del PR #1), más lo observado hoy en la red del sitio público (AUD-E-003).
- **Destino** usa: CONSERVAR (mientras viva el legado), REEMPLAZAR (por un módulo de v1), REESCRIBIR (lógica propia que se rehace en v1), RETIRAR (no se lleva).
- Licencias y versiones exactas de cada plugin de terceros: **PENDIENTE Fase B** (pantalla Plugins con sesión del propietario). No se inventan.

## 1. Núcleo y tema

| Elemento | Versión | Licencia | Estado | Destino | Nota |
|---|---|---|---|---|---|
| WordPress | 7.0.4 | GPL | Activo | REEMPLAZAR (v1 Laravel) | Núcleo sin modificaciones detectadas. |
| WooCommerce | 11.3.0 | GPL | Activo, HPOS desactivado | REEMPLAZAR; datos MIGRAR | Pedidos en `wp_posts`. |
| Elementor | 4.4.0 (assets) | GPL + Pro? | Activo | RETIRAR | Visto en la red pública. |
| Tema Wolmart | 1.10.0 | Comercial (ThemeForest), no transferible | Activo | RETIRAR tras paridad | Íconos `wolmart.woff2`, color primario `#3843d0`. |
| Tema hijo `child-2025` | — | Propio | Activo, 13 líneas | RETIRAR | Está vacío; la personalización vive en plugins y snippets. |
| mu-plugins | `hostinger-auto-updates`, `elementor-safe-mode` | Proveedor | Activos | RETIRAR | — |

## 2. Plugins de terceros activos (51 en total)

| Grupo | Plugins | Destino |
|---|---|---|
| Marketplace | wc-frontend-manager, wc-multivendor-marketplace, wc-multivendor-membership (WCFM) | REEMPLAZAR (v1: tiendas, subpedidos, comisión, Seller Center) |
| Checkout y Colombia | woo-checkout-field-editor-pro, wc-departamentos-y-ciudades-colombia, elex-discount-per-payment-method | REEMPLAZAR (v1: cédula, departamentos; descuento por medio de pago = Fase G) |
| Catálogo y UX | yith-woocommerce-ajax-navigation, yith-woocommerce-wishlist, customizer-search | REEMPLAZAR (filtros ya en v1; favoritos Fase G) |
| Pagos | buy-now-pay-later-addi, sistecredito, bold-pagos-en-linea, wompi-portal-de-pagos, wava-payment, woocommerce-mercadopago | REEMPLAZAR por adaptadores del orquestador (`08-integraciones-pagos.md`) |
| Logística | coordinadora | REEMPLAZAR (adaptador de envíos) |
| Marketing | facebook-for-woocommerce, google-listings-and-ads, woocommerce-google-analytics-integration, nextend-facebook-connect, hostinger-affiliate-plugin | REEMPLAZAR solo lo que se use (Pixel con consentimiento, feed de Merchant); RETIRAR afiliado |
| Contenido | elementor, stackable, kirki, meta-box, metform, contact-form-7, wolmart-core | RETIRAR |
| Operación | code-snippets, fluent-smtp, litespeed-cache, jetpack, image-optimization, regenerate-thumbnails, hostinger-ai-assistant, woo-update-manager | RETIRAR (v1 tiene su propio correo, caché y medios) |

**Inactivos que siguen en el servidor (R-024):** Dokan, MultiVendorX, MailPoet, ePayco, Helio, `GravityInstall` (con `.git`, log de depuración y scripts `probar_credenciales*.php`), `quick-comm-notification` (con `.git`). Recomendación: borrarlos del servidor **con autorización y respaldo** (no se hace en esta fase).

## 3. Plugins propios (14 activos, ~20 000 líneas, fuera de Git)

| Plugin | Qué hace | Destino en v1 | Prioridad |
|---|---|---|---|
| MarketCity (MarketCity Pay) 2.0.1 | Pago completo, dividido y aprobación manual vía `api.marketcitypay.com` (HMAC) | REESCRIBIR como adaptador `marketcitypay` del orquestador | P0 |
| marketcity-envios 1.1.0 | Envío por vendedor y enlaces de rastreo (5 transportadoras) | YA REESCRITO en v1 (transportadora + guía) | P1 |
| marketcity-fixes 1.5.1 | Correcciones, rastreo, mapa de cobertura, búsqueda visual | REESCRIBIR por partes; búsqueda visual pendiente Q-011 | P2 |
| mc-ofertas-relampago | Ofertas con cuenta regresiva | REESCRIBIR (Fase G) | P2 |
| marketcity-experience | Header, búsqueda con registro de consultas | REESCRIBIR (búsqueda y analítica de búsquedas) | P2 |
| mc-insights | Analítica propia (embudo, mapas de calor). Endpoint público `mci/v1/c` visto hoy | REEMPLAZAR por eventos de analítica de v1 | P2 |
| marketcity-notify | Push Firebase (0 dispositivos) | RETIRAR o rehacer con consentimiento | P3 |
| marketcity-campaign-engine | Landings de campaña | REESCRIBIR si hay uso | P3 |
| mc-games / marketcity-bienvenida | Juegos y ruleta | Decisión de negocio (Q-010) | P3 |
| marketcity-footer | Pie y formulario de datos personales sin sesión | REESCRIBIR con consentimiento auditable | P2 |
| marketcity-seller-center, marketcity-shop, placeholder | Ajustes sobre WCFM y tienda | YA CUBIERTO en v1 | — |

## 4. Snippets (Code Snippets): 70 activos, ~2 MB en la base

- **34 en "vista previa"** que cualquiera enciende con `?mc_preview=1` (R-023). P0 de seguridad en el legado; la decisión de cuáles apagar es del propietario (Q-027).
- Más grandes: "MarketTurbo envío rápido" (448 KB, API logística externa), "Perfil de vendedor" (352 KB), "Carrito lateral" (160 KB), "Directorio de tiendas" (142 KB), "Seller Center Productos" (138 KB), "Checkout guiado" (124 KB), "Skip Cart" (añadir lleva directo al checkout).
- Seguridad por ocultación: "Login secreto", "Ocultar huellas", "Blackout anti-espías". No reemplazan 2FA.
- Destino general: la lógica útil se REESCRIBE en módulos de v1 con pruebas; los snippets no se migran.

## 5. Observado hoy en el sitio público (AUD-E-003)

| Hallazgo | Evidencia | Destino |
|---|---|---|
| Error JS repetido `o.update is not a function` desde un MutationObserver en la ficha de producto (484 mensajes en una carga) | consola | Diagnóstico en Fase D (probable snippet o tema); no se toca producción |
| `sistecredito/includes/View/Front/css/payment.css` → 404 | red | Plugin de Sistecrédito incompleto o desactualizado |
| Plugin `wava-payment` cargando logo en la ficha | red | Confirmar si Wava sigue contratado |
| `?mc54_js=…` sirve JS dinámico desde PHP | red | Snippet; reescribir |
| Scripts externos: Google Tag Manager, Meta, Jetpack Stats, Addi, Bold, Amazon S3, jsDelivr | DOM | Inventario de terceros para CSP y consentimiento |
