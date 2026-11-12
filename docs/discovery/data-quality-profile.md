# Perfil de calidad de datos — señales públicas

**Estado:** PRELIMINAR. Solo señales observables desde el sitio. El perfil completo (nulos, duplicados, huérfanos, crecimiento) requiere exportación de solo lectura.

## 1. Hallazgos observados

| ID | Hallazgo | Evidencia | Severidad | Hipótesis |
|---|---|---|---|---|
| DQ-01 | Tienda reporta 639 productos; sitemap ~570. | E-003, E-016 | Media | Productos ocultos de sitemap, privados, borradores o conteo truncado. |
| DQ-02 | Suma de rangos de precio (661) ≠ total (639). | E-003 | Baja | Productos variables contados en varios rangos o caché de conteos. |
| DQ-03 | Marca "Genérico" en 202 productos (~32 %). | E-003 | Media | Atributo marca no gestionado; afecta búsqueda y SEO. |
| DQ-04 | Categorías `sin-categorizar` y `otro` publicadas. | E-017 | Baja | Productos sin clasificar. |
| DQ-05 | Filtro de color con solo 2 productos con color. | E-003 | Baja | Atributos no poblados. |
| DQ-06 | 0 reseñas en tiendas y productos observados. | E-009, E-023 | Info | Sin datos de reputación para migrar. |
| DQ-07 | Productos de un mismo artículo publicados varias veces (AirPods "...-1-1-2"). | E-003 | Baja | Duplicados por slug incremental. |
| DQ-08 | SKUs con formato libre ("MS-017 -- BG193"). | E-009 | Baja | Sin convención de SKU. |
| DQ-09 | Páginas duplicadas (cart/cart-2, checkout/checkout-2, my-account/my-account-2). | E-014 | Media | Reinstalaciones/importación de demo. |
| DQ-10 | Tiendas con nombres casi iguales (DAVSHOP / davidshop). | E-001 | Info | ¿Tiendas de prueba internas? (Q-016) |

## 2. Plan de perfilado (cuando exista exportación autorizada)

Para cada entidad del diccionario: conteo total y por estado, % nulos por campo, duplicados por clave natural (SKU, correo, NIT), huérfanos (pedido sin cliente, producto sin vendedor, subpedido sin pedido), rangos (precios ≤ 0, stock negativo), consistencia (total pedido = suma líneas + envío − descuento), crecimiento mensual, distribución por vendedor. Salida: tablas agregadas, sin datos personales.
