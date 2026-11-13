# Matriz de plugins y capacidades (preliminar)

Solo componentes con indicio público. El inventario real (nombre, versión, licencia, estado activo, autor) requiere `wp-admin → Plugins` o `wp plugin list` de solo lectura (access-request, superficie A-03).

| Componente | Estado | Evidencia | Capacidades que aporta | Licencia / transferibilidad | Disposición preliminar |
|---|---|---|---|---|---|
| WordPress core | HECHO | E-002, E-013 | CMS, usuarios, sitemaps, REST | GPL | REIMPLEMENTAR (CMS propio o headless) |
| WooCommerce | INFERENCIA alta | E-003 | Catálogo, carrito, checkout, pedidos, cupones, impuestos, correos | GPL | REIMPLEMENTAR; datos MIGRAR |
| WCFM Marketplace | INFERENCIA alta | E-009 | Tiendas, comisiones, subpedidos, retiros, reseñas de tienda, consultas | GPL + add-ons pagos (Ultimate) | REIMPLEMENTAR (núcleo marketplace) |
| WCFM Membership | INFERENCIA media | E-004 | Planes de vendedor, registro multipaso, verificación por correo | Comercial probable | REIMPLEMENTAR |
| Tema Wolmart | INFERENCIA alta | E-008, E-009 | Plantillas, quick view, wishlist/compare, constructor de plantillas | **Comercial (ThemeForest), no transferible** | RETIRAR_CON_APROBACION tras paridad |
| Plugin `marketcity-envios` (propio) | HECHO (ruta) | E-006 | Tracking por transportadora, logos | Propiedad de MarketCity (confirmar autoría) | MIGRAR lógica / REIMPLEMENTAR |
| Jetpack (Photon) | INFERENCIA alta | E-001 | CDN de imágenes | Servicio de WordPress.com | RETIRAR_CON_APROBACION (reemplazar por CDN propio) |
| Plugins de pago (Addi, Sistecrédito, Bold) | INFERENCIA | E-001 | Checkout, cuotas, simulador | Contratos con cada proveedor | REIMPLEMENTAR mediante adaptadores |
| Wishlist / Compare | INFERENCIA | E-009, E-014 | Lista de deseos, comparar | Probablemente del tema | MEJORAR solo si hay uso (Q-017) |
| Banner de cookies + opt-in notificaciones | HECHO | E-001 | Consentimiento | Desconocida | MEJORAR (registro auditable de consentimiento) |
| Página protegida "estadísticas" | HECHO | E-018 | Reporte interno | n/a | RETIRAR_CON_APROBACION; reemplazar por tablero con autenticación |
| Hardening REST | INFERENCIA media | E-013 | Seguridad | Desconocida | CONSERVAR_TEMPORALMENTE en el legado |
| Otros multivendedor (Dokan/MultiVendorX/WC Vendors?) | INFERENCIA media | E-014 | Restos de pruebas | — | Verificar si están activos; RETIRAR_CON_APROBACION |
| Búsqueda por imagen | ANUNCIADA | E-001 | Búsqueda visual | Desconocida | Por evaluar (Q-011) |

## Dependencias no transferibles identificadas (para el criterio de salida)
1. Licencia de Wolmart (ThemeForest: licencia por sitio final).
2. Licencias/suscripciones WCFM Ultimate / Membership (si aplican).
3. Jetpack / WordPress.com (CDN atado a su servicio).
4. Contratos de PSP/BNPL: credenciales y cuenta de comercio pueden estar a nombre de una persona o de la sociedad (Q-004).
5. Cuenta de Play Console de la app Android (Q-014).
6. Dominio, DNS y correo: titularidad desconocida (Q-007).

## Activos propios fuera del sitio (2026-11-13)
- `marketcity-turbo/plugins/woocommerce-marketcity-turbo`: plugin propio de WooCommerce (cotización y creación de entregas). No consta que esté instalado en marketcity.co.
- `marketcity-go`: plataforma propia con capacidades reutilizables (pagos Wompi con webhooks, ledger, importación desde WooCommerce, app Flutter). Ver `related-repositories.md`.
