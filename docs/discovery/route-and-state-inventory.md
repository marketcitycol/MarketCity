# Inventario de rutas y estados — vista pública

Fuente: sitemaps nativos de WordPress (E-008, E-014–E-017) y navegación pública (E-001…E-024). Capturado 2026-11-12. Estados marcados "observado" se vieron; "no observado" requiere sesión, datos o entorno de prueba.

## 1. Volumen indexable

| Tipo | Cantidad | Evidencia | Nota |
|---|---|---|---|
| Productos (sitemap) | ~570 | E-016 | La tienda reporta 639 (E-003): diferencia ~69 por explicar (Q-009). |
| Páginas | 41 | E-014 | Incluye duplicados y restos de demo. |
| Categorías de producto | 15 | E-017 | `sin-categorizar` y `otro` son indexables. |
| Marcas (`product_brand`) | 14 visibles en filtro | E-003 | Sitemap existe; no contado. |
| Tiendas de vendedor | No están en sitemap | E-015 | Solo `/tiendas/`, que falla (E-020). |
| Plantillas `wolmart_template` | sitemap expuesto | E-008 | Contenido interno del tema expuesto a buscadores (R-014). |
| Entradas de blog / etiquetas | sitemap existe | E-008 | No inspeccionado. |

## 2. Rutas por superficie

| Ruta | Superficie | Estado observado | Nota / disposición preliminar |
|---|---|---|---|
| `/` | Inicio | Observado (E-001) | Conservar URL. |
| `/home/`, `/inicio-marketcity/`, `/7736-2/`, `/flas/`, `/juegos/` | Páginas de inicio/landing alternas | No inspeccionadas | Probables borradores; decidir 301 o retiro (Q-010). |
| `/shop/` | Catálogo | Observado: lista, paginación | Conservar o 301. |
| `/categoria-producto/{slug}/` | Categoría | Enlaces observados | Conservar patrón o 301 1:1. |
| `/producto/{slug}/` | Detalle | Observado (E-009) | 301 1:1 obligatorio. |
| `/tienda/{slug}/` | Tienda de vendedor | Observado (E-023) | 301 1:1; añadir a sitemap. |
| `/tiendas/` | Directorio de tiendas | **Error 4xx** (E-020) | Hallazgo. |
| `/ofertas-relampago/` | Ofertas | Observado estado vacío (E-012) | Conservar. |
| `/black-city/` | Campaña | Observado "no activa" (E-021) | Patrón de campañas. |
| `/cart/`, `/cart-2/` | Carrito | `/cart-2/` vacío observado (E-010) | Duplicado: consolidar. |
| `/checkout/`, `/checkout-2/` | Checkout | Redirige a carrito vacío (E-019) | Duplicado: consolidar. No probado con productos. |
| `/my-account/`, `/my-account-2/`, `/my-account-2/pedidos/`, `/my-orders/` | Cuenta | Login/registro observado (E-024) | Duplicados. |
| `/wishlist/`, `/compare/` | Lista de deseos / comparar | No inspeccionado | Validar uso real. |
| `/rastrear-pedido/`, `/rastrea-tu-pedido/` | Tracking | `/rastrear-pedido/` observado (E-006) | Dos rutas enlazadas desde distintos sitios. |
| `/contacto/`, `/contact-us/` | Soporte / PQRS | `/contacto/` observado (E-011) | `contact-us` es demo 2021. |
| `/vendor-membership/`, `/vender/`, `/vender-2/`, `/become-a-vendor/` | Captación de vendedores | `/vendor-membership/` observado (E-004) | 4 rutas para el mismo objetivo. |
| `/seller-center/` | Acceso vendedor | Observado (E-005) | Panel no visible sin sesión. |
| `/dashboard/`, `/vendor_dashboard/`, `/store-listing/`, `/vendor-onboarding/`, `/store-registration/`, `/vendors/` | Páginas de plugins multivendedor | No inspeccionadas | Creadas 2026-05-03 (I-004). |
| `/eliminar-mi-cuenta/` | Derechos del titular | Observado (E-022) | Conservar (requisito de tiendas de apps). |
| `/?page_id=6103` (`/politica-de-privacidad/`) | Legal | Observado (E-007) | Todos los enlaces legales apuntan a una sola página. |
| `/estadisticas/`, `/estadisticas-ecommerce/` | Interno | Protegido por contraseña de página (E-018) | No debe ser público ni indexable (R-008). |
| `/faq/`, `/about-us/`, `/coming-soon/` | Demo 2021 | No inspeccionado | Probable retiro con 301. |
| `/blog/` | Contenido | No inspeccionado | Evaluar tráfico antes de decidir. |
| `/wp-json/` | API | Índice vacío (E-013) | Hardening. |
| `/wp-admin/`, `/admin` | Backoffice | **No accedido** (fuera de alcance) | Ver `docs/program/access-request.md`. |

## 3. Parámetros de URL observados

`?add-to-cart={id}` (bloqueado en robots), `?add_to_wishlist=`, `?orderby=popularity|date|price|price-desc`, `?min_price=`, `?max_price=`, `?product_brand=`, `?filter_color=`, `?query_type_color=or`, `?page_id=`. Riesgo de facetas indexables: ver `seo-performance-baseline.md`.

## 4. Matriz de estados por plantilla

| Plantilla | Normal | Vacío | Error | Carga | Sin stock | Autenticado | Móvil |
|---|---|---|---|---|---|---|---|
| Inicio | Observado | n/a | No obs. | No obs. | n/a | No obs. | No obs. |
| Catálogo | Observado | No obs. | No obs. | No obs. | No obs. | No obs. | No obs. |
| Detalle producto | Observado | n/a | No obs. | No obs. | No obs. | No obs. | No obs. |
| Tienda vendedor | Observado | No obs. | Directorio falla | No obs. | n/a | No obs. | No obs. |
| Ofertas | n/a | **Observado** | No obs. | No obs. | n/a | n/a | No obs. |
| Carrito | No obs. | **Observado** | No obs. | No obs. | No obs. | No obs. | No obs. |
| Checkout | No obs. (requiere carrito) | Redirige | No obs. | No obs. | No obs. | No obs. | No obs. |
| Cuenta | Login/registro observado | n/a | No obs. | No obs. | n/a | No obs. | No obs. |
| Tracking | Observado | n/a | No obs. | n/a | n/a | n/a | No obs. |

Las celdas "No obs." se completan en un entorno clonado o con sesión de prueba autorizada (ver `access-request.md`). La captura móvil requiere un navegador real con viewport de teléfono (pendiente, Q-015).
