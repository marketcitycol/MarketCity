# 03 · Plugins, tema y snippets del sitio actual

- **Fecha:** 2026-11-17. **Fuente:** inventario de Fase 0 en el clon aislado (E-031, E-032; `docs/archivo/fase-0/discovery/private-inventory-code.md` y `private-inventory-db.md` del PR #1), más lo observado hoy en la red del sitio público (AUD-E-003).
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

## 6. Lista completa de plugins con versiones (Fase B, 2026-11-17, pantalla Plugins con sesión del propietario)

**85 plugins: 52 activos, 33 inactivos, 8 con actualización pendiente.** WordPress ya está en **7.1.4** (en el clon de Fase 0 era 7.0.4), PHP 8.2.35, MariaDB 11.8.9, base de 707,8 MB.

### Activos (52)
| Plugin | Versión | Autor | Nota |
|---|---|---|---|
| WooCommerce | 11.3.0 | Automattic | HPOS apagado |
| WCFM Frontend Manager / Multivendor Marketplace / Membership | 6.8.2 / 3.8.4 / 2.12.1 | WC Lovers | Núcleo del marketplace |
| Addi | 2.1.1 | Addi | Activo |
| Bold pagos en línea | 3.5.0 | Bold | Activo |
| Wompi Portal de Pagos | 3.2.1 | Wompi | Activo |
| Mercado Pago | 8.10.2 | Mercado Pago | Activo (solo Checkout Pro; tarjeta, efectivo y PSE apagados) |
| WooCommerce Sistecrédito Payment Gateway | 2.0.0 | Sistecrédito | Activo; su CSS da 404 en el sitio |
| Wava Payment (Nequi o Daviplata) | 0.3.10 | Wava | Activo |
| MarketCity Pay | 2.0.1 | MarketCityPay | Plugin activo, **medio de pago apagado** |
| ELEX Discount Per Payment Method | 1.3.3 | ELEXtensions | |
| Checkout Field Editor for WooCommerce | 2.2.1 | ThemeHigh | Aviso de conflicto con otro editor instalado |
| QCode Departamentos y Ciudades de Colombia | 1.2.1 | QCode | |
| Coordinadora | 1.2.0 | Coordinadora | |
| YITH Ajax Product Filter / Wishlist | 5.25.0 (actualizar) / 4.21.0 | YITH | |
| Customizer Search | 1.2.1 | — | |
| Elementor | 4.4.0 (actualizar) | Elementor | |
| Image Optimization | 1.7.7 | Elementor | 885 tareas fallidas |
| Wolmart Core | 1.10.3 (actualizar) | D-Themes | |
| Kirki 6.4.2 · Meta Box 5.15.5 · MetForm 4.3.4 · Stackable 3.20.3 | | | Sin uso de negocio visible |
| Jetpack | 16.4 | Automattic | |
| LiteSpeed Cache | 7.9.3 | LiteSpeed | QUIC.cloud sin configurar |
| Code Snippets | 4.1.0 | Code Snippets Pro | 70 snippets |
| FluentSMTP | 2.4.5 | WPManageNinja | |
| Meta for WooCommerce | 3.7.8 | Meta | WhatsApp con error de conexión |
| Google for WooCommerce | 3.10.0 (actualizar) | WooCommerce | |
| Google Analytics for WooCommerce | 2.4.4 | WooCommerce | |
| Nextend Social Login | 3.1.27 | Nextendweb | |
| Chaty | 3.6.5 | Premio | **Nuevo frente a Fase 0** |
| Hostinger Amazon Affiliate 3.0.57 · Hostinger AI 3.1.9 | | Hostinger | |
| Regenerate Thumbnails 3.1.6 · WooCommerce.com Update Manager 1.0.3 | | | |
| Propios MarketCity (15) | 1.0.0–2.0.1 | MarketCity | Incluye **Navidad Marketcity (activo)** y "Bandera de Colombia en imágenes" instalado dos veces (una copia activa, otra inactiva) |

### Inactivos (33) — candidatos a borrar con respaldo y autorización
Advanced Order Export 4.1.4, All-in-One WP Migration 7.113, Alpus AI Review Summary 2.0.0, Alpus FlexBox 2.6.2, Blocks for ShopEngine 2.4.4, ShopEngine 4.9.9, Conector Gravity para Addi 3.0.0, Contact Form 7 6.1.7, **Dokan 5.3.4**, **MarketKing Core 2.1.80 y Pro 2.0.70**, **MultiVendorX 5.0.22**, **WC Vendors 2.7.3** (cuatro marketplaces alternativos instalados), Dropify 4.7.3, Editor de checkout Acowebs 3.0.10, Email Deliverability 1.4.7, ePayco 8.8.5, GTranslate 5.0.2, Helio 2.1.0, Hostinger Tools 3.0.82, Login & Register Forms 4.0.4, MailPoet 5.45.2, Meta Pixel 5.2.2, PAYCY 0.1.0, PWA for WP 1.7.88.2, Quick Comm Notification 1.5, Tidio 8.0.1, Ultimate Addons for Elementor 2.9.7, Variation Swatches 1.0.14, MarketCity BLACKOUT, MarketCity Flash 0.1.0, MarketCity Carrito Lateral, copia de MarketCity Bandera.

### Medios de pago configurados (13)
| Id | Título | Estado |
|---|---|---|
| addi | Paga a cuotas | Activo |
| wcsistecredito | (sin título) | Activo |
| cod | CONTRAENTREGA | Activo |
| wava_payment | Nequi o Daviplata | Activo |
| woo-mercado-pago-basic | Paga con Mercado Pago | Activo |
| bold_co | Paga en línea con Bold | Activo |
| wompi | Paga con Wompi | Activo |
| marketcity | MarketCityPay - Créditos flexibles | Apagado |
| bacs / cheque | Transferencia / Cheque | Apagado |
| woo-mercado-pago-custom / ticket / pse | Tarjeta / Efectivo / PSE | Apagado |

### Estados de pedido en uso (130 pedidos)
pending 3 · processing 37 · on-hold 19 · completed 8 · cancelled 49 · failed 5 · **addi-approved 5** · **wava-confirm 4** · refunded 0. Los estados propios de Addi y Wava deben mapearse al estado canónico de v1 (`07-mapa-migracion-datos.md`).

### Envío
Zona Colombia: tarifa fija, envío por zona del vendedor (WCFM); envío gratis y recogida local apagados a nivel de WooCommerce. Zona GLOBAL: tarifa fija.
