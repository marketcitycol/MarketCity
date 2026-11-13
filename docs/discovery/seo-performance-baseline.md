# Línea base SEO y rendimiento

**Estado:** SEO estructural observado; **rendimiento NO medido** (bloqueo del entorno: E-026, E-027). No se inventan cifras.

## 1. SEO estructural (HECHO salvo indicación)

| Aspecto | Observación | Evidencia | Riesgo para migración |
|---|---|---|---|
| Sitemaps | Nativos de WordPress (`wp-sitemap.xml`), 9 sub-sitemaps; sin plugin SEO aparente | E-002, E-008 | Bajo; regenerar con prioridades |
| Robots | Bloquea `add-to-cart`, logs, uploads privados, `wp-admin` | E-002 | No bloquea facetas (`min_price`, `orderby`, `filter_color`) → posible indexación de facetas (INFERENCIA) |
| Canonical | Presente en producto | E-009 | Verificar en categorías con filtros |
| Open Graph / Twitter | Presentes | E-001, E-009 | Migrar |
| Datos estructurados (JSON-LD Product/Offer/Breadcrumb) | **No detectados** | E-009 | Oportunidad de mejora (confianza media: el lector puede omitir scripts) |
| Plantillas internas del tema en sitemap | `wolmart_template` expuesto | E-008 | Contenido basura indexable |
| Páginas demo/duplicadas indexables | ~15 (ver route inventory) | E-014 | Canibalización; definir 301 |
| Páginas internas en sitemap | `/estadisticas/`, `/estadisticas-ecommerce/` | E-014, E-018 | Exposición innecesaria |
| Tiendas de vendedor | No están en sitemap; `/tiendas/` falla | E-015, E-020 | Pérdida de indexación de tiendas |
| Idioma OG | `es_LA` | E-009 | Considerar `es_CO` |
| URLs | `/producto/{slug}/`, `/categoria-producto/{slug}/`, `/tienda/{slug}/` | E-003 | Base del mapa de redirecciones 1:1 |

## 2. Mapa de URLs para redirección (plantilla)

`URL anterior → URL nueva → estado → tráfico (90 d) → validación`. Semilla: 41 páginas + ~570 productos + 15 categorías + marcas + tiendas. Fuente de tráfico: Search Console (requiere acceso, A-07).

## 3. Plan para obtener la línea base de rendimiento

| Métrica | Fuente | Cómo | Bloqueo |
|---|---|---|---|
| LCP, INP, CLS (campo) | CrUX / Search Console | Informe Core Web Vitals por plantilla | Acceso GSC |
| LCP, TBT, CLS, peso (laboratorio) | Lighthouse / PageSpeed | 5 corridas móvil + escritorio por plantilla (inicio, catálogo, categoría, producto, tienda) | Ejecutar desde una red sin restricción |
| TTFB, caché, compresión, HTTP/2-3 | curl / WebPageTest | Cabeceras de 5 plantillas | Igual |
| Disponibilidad | Monitor externo | Sonda cada 5 min durante 30 días | Aprobar herramienta |
| Indexación, clics, posiciones | Search Console | Exportar 16 meses | Acceso GSC |

Criterio: la línea base se considera obtenida cuando existan las 5 plantillas × 2 dispositivos con mediana de 5 corridas y 28 días de datos de campo (o constancia de que CrUX no tiene volumen suficiente).
