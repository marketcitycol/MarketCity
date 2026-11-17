# 13 · Matriz de trazabilidad

- **Fecha:** 2026-11-17. Estados: HECHO (código + prueba), PARCIAL, PENDIENTE, BLOQUEADO.
- **Fuente:** "Prompt" = pedido del propietario del 2026-11-17 (sección indicada); "F0" = descubrimiento de Fase 0; "AUD" = esta auditoría.

## Funcionales (MC-FR)

| ID | Requisito | Fuente | Implementación | Prueba | Estado |
|---|---|---|---|---|---|
| MC-FR-001 | Catálogo, categorías, marcas y ficha de producto | Prompt §10; F0 C-01/C-02 | `CatalogController`, `shop/*.blade.php` | `test_public_pages_load` | HECHO |
| MC-FR-002 | Búsqueda textual y filtros (precio, cuota, marca) | Prompt §10 | `CatalogController@index` | `test_installment_filter_uses_installment_value` | PARCIAL (sin tolerancia a errores ni autocompletado) |
| MC-FR-003 | Variantes de producto | Prompt §10 | — | — | PENDIENTE (legado tiene 0 variables) |
| MC-FR-008 | Checkout multivendedor con datos colombianos | Prompt §10; F0 C-08 | `CheckoutController`, `OrderService::place` | `test_multivendor_checkout_…` | HECHO |
| MC-FR-014 | Cuenta de comprador y mis pedidos | F0 C-14 | `AuthController` | `test_unverified_account_does_not_see_guest_orders_with_same_email` | PARCIAL (sin recuperación ni verificación de correo) |
| MC-FR-016 | Rastreo por guía | F0 C-16 | `SellerOrder`, `OrderController@track` | `test_seller_sees_only_own_orders_and_must_give_tracking_to_ship` | HECHO |
| MC-FR-022 | Alta y aprobación de vendedor | Prompt §10 | `SellerSignupController`, `Admin\StoreController` | `test_roles_protect_panels` | PARCIAL (sin KYC/KYB) |
| MC-FR-023 | Seller Center: productos, pedidos, liquidaciones | Prompt §10 | `Seller\*` | `test_seller_sees_only_own_orders…` | HECHO |
| MC-FR-024 | Comisión ajustable por comercio y liquidación solo de subpedidos pagados | Prompt §10; propietario 2026-11-16 | `CommissionCalculator`, `SettlementController` | `test_negotiated_commission_…`, `test_cannot_settle_unpaid_seller_order` | HECHO (sin ledger) |
| MC-FR-025 | Medios de pago activables por comercio | Propietario 2026-11-16 | `PaymentRegistry::forStores` | `test_negotiated_commission_and_payment_methods_per_store` | HECHO |
| MC-FR-030 | Importar catálogo de WooCommerce | Prompt §15 | `WooCommerceCsvImporter` | `test_woocommerce_csv_import_is_repeatable` | HECHO |
| MC-FR-033 | SEO: rutas compatibles, sitemap, canonical | Prompt §10 | `routes/web.php`, `SitemapController`, layout | `test_sitemap_and_canonical` | HECHO (AUD) |
| MC-FR-034 | Ficha con título y datos estructurados correctos | AUD-E-006 | `shop/product.blade.php` | `test_product_page_title_and_structured_data` | HECHO (AUD) |
| MC-FR-040 | Favoritos, comparación, reseñas, preguntas | Prompt §10 | — | — | PENDIENTE (Fase G) |
| MC-FR-041 | Cupones sin combinaciones indebidas | Prompt §10 | — | — | PENDIENTE (Fase G) |
| MC-FR-042 | Devoluciones, reembolsos y disputas | Prompt §10 | — | — | PENDIENTE (Fase G/H) |
| MC-FR-043 | API REST versionada + OpenAPI | Prompt §10 | — | — | PENDIENTE (Fase G) |

## No funcionales (MC-NFR)

| ID | Requisito | Fuente | Implementación | Prueba | Estado |
|---|---|---|---|---|---|
| MC-NFR-001 | Producción solo lectura durante la auditoría | Prompt §2.1 | Proceso: solo GET | Registro de evidencia AUD-E-* | CUMPLIDO |
| MC-NFR-002 | Sin secretos en el repositorio | Prompt §2.4 | `.gitignore`, `.env.example` | Revisión del diff | CUMPLIDO en este PR |
| MC-NFR-010 | Cabeceras de seguridad | Prompt §13 | `SecurityHeaders` | `test_security_headers_and_spanish_404` | HECHO (CSP completa pendiente Fase H) |
| MC-NFR-011 | Roles y aislamiento por tienda | Prompt §13 | `EnsureRole`, `own()` en controladores | `test_roles_protect_panels`, `test_seller_sees_only_own_orders…` | HECHO (roles finos pendientes) |
| MC-NFR-012 | Privacidad: no exponer pedidos de terceros | Prompt §13 | `AuthController@account` | `test_unverified_account_does_not_see_guest_orders_with_same_email` | HECHO (AUD) |
| MC-NFR-013 | MFA para administradores | Prompt §13 | — | — | PENDIENTE (Fase I) |
| MC-NFR-020 | Accesibilidad WCAG 2.2 AA | Prompt §2.11 | Etiquetas en formularios v1 (0 campos sin etiqueta medidos) | axe-core | PARCIAL |
| MC-NFR-021 | Mobile-first sin desbordes | Prompt §2.11 | Tailwind | Medición 375 px: sin scroll horizontal | PARCIAL (7 objetivos táctiles < 24 px en la ficha) |
| MC-NFR-030 | Rendimiento: servidor < 600 ms, HTML < 60 KB | AUD | v1: 7–29 KB | Medición AUD-E-005 | PARCIAL (servidor 0,6–2,4 s con SQLite) |
| MC-NFR-040 | Respaldo y restauración probados | Prompt §2.2 | — | — | BLOQUEADO (B-04) |

## Pagos (MC-PAY)

| ID | Requisito | Fuente | Implementación | Prueba | Estado |
|---|---|---|---|---|---|
| MC-PAY-001 | Orquestador con adaptadores | Prompt §11 | Contrato `PaymentGateway` | — | PARCIAL |
| MC-PAY-002 | Nunca confirmar por retorno del navegador | Prompt §2.7 | Diseño `08` | PAY-T02 | PENDIENTE (Fase H) |
| MC-PAY-003 | Webhooks firmados e idempotentes | Prompt §11 | Diseño `08` | PAY-T03 | PENDIENTE |
| MC-PAY-004 | Ledger de doble partida | Prompt §10 | Diseño `08` | PAY-T08 | PENDIENTE |
| MC-PAY-005 | Confirmación de pago idempotente | Prompt §11 | `OrderService::markPaid` con bloqueo | `test_mark_paid_is_idempotent` | HECHO (AUD) |
| MC-PAY-006 | Aprobación tardía no revive pedido cancelado | Prompt §14 | `OrderService::markPaid` → "review" | `test_late_approval_after_decline_goes_to_review` | HECHO (AUD) |
| MC-PAY-010 | Pago de prueba imposible en producción | AUD-R-03 | — | — | PENDIENTE |
| MC-PAY-020…026 | Adaptadores Addi, Sistecrédito, Bold, Wompi, Mercado Pago, MarketCity Pay, Wava | Prompt §12 | — | — | PENDIENTE / BLOQUEADO por credenciales (B-03) |
