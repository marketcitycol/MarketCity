# Índice de evidencia — MC-DISC-001

- **Misión:** MC-DISC-001 · Fase 0 · Descubrimiento público de solo lectura
- **Fecha de captura:** 2026-11-12 (UTC-5, America/Bogota)
- **Método:** solicitudes GET anónimas a páginas públicas mediante un lector web (conversión HTML→texto). Sin login, sin envío de formularios, sin añadir al carrito, sin compras. Una solicitud por URL; 24 URLs en total.
- **Limitación de método:** el lector devuelve texto extraído, no HTML crudo ni cabeceras HTTP; por eso no hay hash SHA-256 del cuerpo ni códigos de estado. `curl` directo y la API de PageSpeed fueron bloqueados por la política de red del entorno (E-026, E-027). Se recomienda recapturar con un rastreador propio que guarde HTML + cabeceras + hash (ver `docs/program/questions.md`, Q-015).
- **Regla de datos:** se omitieron teléfonos personales; solo se registra el correo corporativo público `somos@marketcity.co`. Ningún dato de comprador fue observado.

| ID | Fecha | Fuente | Tipo | Hallazgo principal | Confianza |
|---|---|---|---|---|---|
| E-001 | 2026-11-12 | https://marketcity.co/ | HECHO | Título "MarketCity \| Compra ahora y paga después en Colombia". Menú: Ofertas, A crédito, 10 categorías. Pagos anunciados: Addi, Sistecrédito ("MarketCityPay"), Nequi, Daviplata, PSE, Bold, Visa/MC/Amex/Diners. Búsqueda por foto ("se analiza en el dispositivo"). Cupón de bienvenida "2026". Tiendas destacadas con `/tienda/{slug}`. Meta Pixel presente. | Alta |
| E-002 | 2026-11-12 | /robots.txt | HECHO | Bloquea `wc-logs`, `woocommerce_uploads`, `?add-to-cart=`, `/wp-admin/`. Sitemap `wp-sitemap.xml` (sitemaps nativos de WordPress, sin plugin SEO aparente). | Alta |
| E-003 | 2026-11-12 | /shop/ | HECHO | 639 productos, 24/página, 27 páginas. Filtros: cuota (mal mapeada a `max_price`), categoría (15), precio (139/310/185/27 = 661 ≠ 639), color, marca (14). Sin filtro por vendedor. | Alta |
| E-004 | 2026-11-12 | /vendor-membership/ | HECHO | Onboarding de vendedor: formulario que abre WhatsApp + registro de cuenta (CC o NIT, dirección, contraseña) + asistente Registro→Planes→Confirmación→Éxito con código por correo. Planes no publicados; se explican por WhatsApp. | Alta |
| E-005 | 2026-11-12 | /seller-center/ | HECHO | Página de login/registro con contenido promocional; no expone panel sin sesión. Estados de pedido mostrados: Recibido, Validado, Despachado, Entregado. | Alta |
| E-006 | 2026-11-12 | /rastrear-pedido/ | HECHO | Selector de transportadora (Servientrega, Coordinadora, Envía, Inter Rapidísimo, TCC) + número de guía; redirige al sitio de la transportadora. Activos desde un plugin con ruta `marketcity-envios` (plugin propio). | Alta |
| E-007 | 2026-11-12 | /?page_id=6103 | HECHO | Política única de privacidad + términos + retracto. Publicada 2025-10-16, modificada 2026-10-22. MarketCity se declara intermediario. Retracto: "5 días calendario" vs "5 días hábiles" (contradicción). Aliados logísticos: Envia.com, MiPaquete, Melonn, Rappi. Pagos: Addi, Sistecrédito, Nequi, Daviplata, PSE, Bold, tarjetas. | Alta |
| E-008 | 2026-11-12 | /wp-sitemap.xml | HECHO | 9 sub-sitemaps, incluido `wolmart_template` y `tiendas`. | Alta |
| E-009 | 2026-11-12 | /producto/cepillo-secador-one-step/ | HECHO | SKU, stock numérico visible ("81 disponibles"), cuotas Addi/Sistecrédito, pestaña `#tab-wcfm_product_store_tab`, tema en `/wp-content/themes/wolmart/`. Sin JSON-LD detectado; sí Open Graph. Contador de despacho sin valor ("--:--:--"). | Alta (JSON-LD: media, el lector puede omitir scripts) |
| E-010 | 2026-11-12 | /cart-2/ | HECHO | Carrito vacío, sin campo de cupón ni calculadora de envío visibles en estado vacío. | Alta |
| E-011 | 2026-11-12 | /contacto/ | HECHO | Formulario de PQRS que no envía al servidor: abre WhatsApp con el texto. Sin SLA publicado. Horarios contradictorios (L-V 10–17 vs L-S 8–18). Punto físico: C.C. Sabana Plaza. | Alta |
| E-012 | 2026-11-12 | /ofertas-relampago/ | HECHO | 0 ofertas activas (estado vacío correcto) + 3 productos con descuento regular como respaldo. | Alta |
| E-013 | 2026-11-12 | /wp-json/ | HECHO | Índice REST con `namespaces` y `routes` vacíos → la API está filtrada/endurecida para anónimos. | Alta |
| E-014 | 2026-11-12 | /wp-sitemap-posts-page-1.xml | HECHO | 41 páginas, incl. duplicados (cart/cart-2, checkout/checkout-2, my-account/my-account-2), páginas demo de 2021 y 6 páginas de plugins multivendedor creadas el 2026-05-03. | Alta |
| E-015 | 2026-11-12 | /wp-sitemap-tiendas-1.xml | HECHO | Solo contiene `/tiendas/`; tiendas individuales no están en sitemap. | Alta |
| E-016 | 2026-11-12 | /wp-sitemap-posts-product-1.xml | HECHO (conteo aprox.) | ~570 URLs de producto; lastmod 2024-12-12 → 2026-11-11. | Media (respuesta truncada) |
| E-017 | 2026-11-12 | /wp-sitemap-taxonomies-product_cat-1.xml | HECHO | 15 categorías, incl. `sin-categorizar` y `otro` indexables. | Alta |
| E-018 | 2026-11-12 | /estadisticas/ | HECHO | Página protegida con contraseña de página de WordPress (no con cuenta). Publicada en sitemap. | Alta |
| E-019 | 2026-11-12 | /checkout/ | HECHO | Con carrito vacío el lector terminó en `/cart-2/` (redirección estándar de WooCommerce). | Media |
| E-020 | 2026-11-12 | /tiendas/ | HECHO | La solicitud falló con error de cliente (4xx). Página listada en sitemap pero no servible. | Media (código exacto no visible) |
| E-021 | 2026-11-12 | /black-city/ | HECHO | Campaña "Black City" creada 2026-11-11, "no está activa en este momento". Política de envíos comerciales según Ley 2300 de 2023. | Alta |
| E-022 | 2026-11-12 | /eliminar-mi-cuenta/ | HECHO | Proceso de eliminación por correo/WhatsApp, 15 días hábiles, retención 10 años de registros contables. **Menciona una app Android de MarketCity** con datos locales. | Alta |
| E-023 | 2026-11-12 | /tienda/impowinner/ | HECHO | Tienda de vendedor: 9 productos, 0 reseñas, pestañas Productos/Acerca de/política/Reseñas. | Alta |
| E-024 | 2026-11-12 | /my-account/ | HECHO | Login usuario/correo + contraseña; registro solo con correo (contraseña enviada por correo). Sin login social, OTP ni CAPTCHA visible. | Media (CAPTCHA invisible no descartable) |
| E-025 | 2026-11-12 | Búsqueda web pública | HECHO | No se encontró ficha pública de la app Android de MarketCity en resultados de búsqueda. | Baja |
| E-026 | 2026-11-12 | API PageSpeed Insights | HECHO (limitación) | Solicitud rechazada desde el entorno; no hay medición de Core Web Vitals. | Alta |
| E-027 | 2026-11-12 | curl directo a marketcity.co | HECHO (limitación) | Proxy del entorno devolvió 403 al túnel; no se capturaron cabeceras HTTP, TLS ni HTML crudo. | Alta |
| E-028 | 2026-11-12 | github.com/marketcitycol/MarketCity | HECHO | Repositorio sin commits ni ramas. No contiene código legado. | Alta |
| E-029 | 2026-11-13 | github.com/marketcitycol/marketcity-go (rama por defecto, clon de solo lectura) | HECHO | Marketplace de entregas (API Express + PostgreSQL, web React, app Flutter `co.marketcity.marketcity_go`), pagos Wompi, ledger, comisión 15 %. Sin secretos productivos detectados. Ver `related-repositories.md`. | Alta |
| E-030 | 2026-11-13 | github.com/marketcitycol/marketcity-turbo (rama por defecto, clon de solo lectura) | HECHO | SaaS multitenant de última milla (NestJS, RLS, outbox, ledger, plugin WooCommerce), 10 ADR aceptadas el 2026-11-07 incl. AWS ECS. Sin secretos productivos detectados. | Alta |

## Inferencias derivadas (no son hechos)

| ID | Inferencia | Basada en | Confianza |
|---|---|---|---|
| I-001 | La plataforma es WordPress + WooCommerce. | E-002, E-003, E-009 | Alta |
| I-002 | El marketplace usa **WCFM Marketplace** (+ probablemente WCFM Membership por `/vendor-membership/`). | E-009 (`wcfm_*`), E-004 | Alta |
| I-003 | El tema es **Wolmart** (licencia comercial de ThemeForest). | E-008, E-009 | Alta |
| I-004 | Se probaron o conviven varios plugins multivendedor (páginas `dashboard`, `store-listing`, `vendor-onboarding`, `store-registration`, `vendor_dashboard`, `vendors` creadas el mismo día). | E-014 | Media |
| I-005 | Jetpack (Photon/i0.wp.com) sirve imágenes. | E-001, E-003 | Alta |
| I-006 | Existe un plugin propio `marketcity-envios`; puede haber más código propio. | E-006 | Alta |
| I-007 | Hay hardening del REST API (plugin de seguridad o snippet). | E-013 | Media |
| I-009…I-012 | Ver `related-repositories.md` §4 (reutilización de Turbo, portafolio de tres productos, identidad de la app Android, dos pasarelas). | E-029, E-030 | Media/Baja |
| I-008 | La operación de soporte, onboarding y seguimiento depende de WhatsApp y de personas, sin sistema de casos. | E-004, E-011 | Media |
