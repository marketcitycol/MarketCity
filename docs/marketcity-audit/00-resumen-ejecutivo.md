# 00 · Resumen ejecutivo — Auditoría MarketCity y plataforma v1

- **Fecha de corte:** 2026-11-17 (America/Bogota)
- **Fase actual:** A — Descubrimiento seguro y línea base (**en curso**)
- **Producción (`marketcity.co`) modificada:** No. Solo lectura (GET desde un navegador, sin formularios, sin login).
- **Entorno destino:** `https://v1.marketcity.co/` (Laravel 13 propio, Hostinger Cloud Startup, SQLite, datos de prueba).
- **Base previa reutilizada:** descubrimiento de Fase 0 (`docs/discovery`, `docs/program` en la rama `claude/project-thread-y580kw`, PR #1) y versión 1 (`plataforma/`, PR #2).

## Qué es MarketCity hoy (HECHO)

| Tema | Hallazgo | Evidencia |
|---|---|---|
| Stack real | WordPress 7.0.4 + WooCommerce 11.3.0 + WCFM (marketplace y membresía) + tema Wolmart 1.10.0 con hijo vacío + Elementor 4.4. 51 plugins activos, 14 plugins propios (~20 000 líneas) y 70 snippets (~2 MB) guardados en la base. | AUD-E-001, Fase 0 E-031/E-032 |
| Servidor | Hostinger (cabeceras `platform: hostinger`, `server: hcdn`), LiteSpeed Cache, PHP 8.2, MariaDB 11.8.9. | AUD-E-001 |
| Volumen | 644 productos públicos (0 variables), 15 categorías públicas, 98 usuarios, 6 vendedores, **129 pedidos en 5 años** (38 % cancelados). | AUD-E-004, Fase 0 E-031 |
| Pagos | 11 medios configurados; los usados: Addi (39 %), contraentrega, MarketCity Pay, transferencia, Sistecrédito, Bold, Wava, Wompi, Mercado Pago. | Fase 0 E-031, AUD-E-003 |
| Rendimiento | Páginas de 550–900 KB de HTML, 79–95 peticiones y >110 scripts; servidor 1,8–7 s sin caché. | AUD-E-001, AUD-E-002 |
| Errores en vivo | La ficha de producto genera cientos de errores de JavaScript por segundo (`o.update is not a function`) y un CSS del plugin de Sistecrédito da 404. | AUD-E-003 |
| Seguridad | Sin HSTS, sin `X-Frame-Options`, sin `X-Content-Type-Options`, sin `Referrer-Policy`. 31 llaves de producción sueltas en la base (R-022) y 34 snippets que se encienden con `?mc_preview=1` (R-023). | AUD-E-001, Fase 0 |

## Qué es v1 hoy (HECHO)

Marketplace propio en Laravel 13, publicado en v1.marketcity.co con aviso de prueba y `noindex`. Ya cubre catálogo, búsqueda, filtros, carrito, checkout multivendedor, pago de prueba y contraentrega, subpedidos por tienda, comisión 15 % ajustable por comercio, medios de pago por comercio, Seller Center, backoffice, liquidación manual a 10 días hábiles e importador de CSV de WooCommerce. Responde en 0,2–2 s con 7–29 KB de HTML.

### Fallas encontradas en v1 y corregidas en este bloque (con pruebas)

| ID | Severidad | Falla | Estado |
|---|---|---|---|
| MC-FR-034 | P1 (SEO) | El título de la ficha mostraba el nombre del **último producto relacionado** (p. ej. "Audífonos Redmi Buds 6" en la página del parlante JBL). | Corregido + prueba |
| MC-FR-034 | P1 (SEO) | El JSON-LD de producto salía **inválido**: Blade interpretaba `@context` como directiva. | Corregido + prueba |
| MC-NFR-012 | **P0 (privacidad)** | "Mi cuenta" mostraba los pedidos de invitado de cualquier correo con el que alguien se registrara, sin verificar el correo. | Corregido + prueba |
| MC-PAY-006 | **P0 (dinero)** | Una aprobación de pago tardía sobre un pedido ya cancelado lo marcaba como pagado sin reponer el stock descontado. | Corregido: va a revisión manual + prueba |
| MC-PAY-005 | P0 | Confirmación de pago sin bloqueo: dos confirmaciones simultáneas podían aplicarse dos veces. | Corregido con bloqueo de fila + prueba |
| MC-FR-024 | P0 | Se podía registrar la liquidación de un subpedido sin pago confirmado o cancelado. | Corregido + prueba |
| MC-NFR-010 | P1 | Faltaban cabeceras de seguridad y la versión de PHP se anunciaba. 404 en inglés. | Corregido + prueba |
| MC-FR-033 | P2 | Sin `sitemap.xml` ni canonical en listados. | Corregido + prueba |

**Pendiente de despliegue:** v1 se actualiza subiendo un paquete en hPanel. Estas correcciones están en el repositorio; se publican con el próximo paquete (ver `12-operacion-runbooks.md`, RB-01).

## Estado por fase

| Fase | Estado | Depende de |
|---|---|---|
| A. Línea base | EN CURSO (falta Lighthouse/CWV de laboratorio desde red sin restricción, Q-015) | — |
| B. Admin actual | BLOQUEADA: necesita que el propietario inicie sesión en marketcity.co/admin en el navegador de Claude | Acceso B-01 |
| C. Frontend por actor | PARCIAL (inventario de rutas en `02-inventario-frontend.csv`) | — |
| D. Plugins y datos | PARCIAL ALTA (Fase 0) | — |
| E. Identidad visual | INICIADA: tokens reales extraídos (`04-identidad-visual.md`); v1 aún usa paleta genérica | — |
| F. Arquitectura | BORRADOR (`06-arquitectura-objetivo.md`) | Decisiones D-01…D-04 |
| G–K | NO INICIADAS | Ver `11-riesgos-bloqueos-decisiones.md` |

## Decisiones pendientes del propietario (resumen)
Ver `11-riesgos-bloqueos-decisiones.md`. Las que cambian arquitectura: D-01 base de datos de v1 (SQLite → MySQL de Hostinger), D-02 modelo de cobro (MarketCity cobra y liquida vs. split del proveedor), D-03 alcance de MarketCityPay, D-04 qué histórico migrar.
