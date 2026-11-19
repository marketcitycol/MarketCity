# Evidencia de la auditoría (AUD-E)

Todas las mediciones son de solo lectura (peticiones GET desde el navegador de Claude en el computador del propietario, 2026-11-17 ~06:07–06:30 UTC). No se enviaron formularios ni se inició sesión en producción. Los números de Fase 0 (E-0xx) están en `docs/archivo/fase-0/discovery/evidence-index.md` del PR #1.

| ID | Qué | Método | Resultado clave |
|---|---|---|---|
| AUD-E-001 | Inicio de marketcity.co: cabeceras, cookies, tiempos, DOM | `fetch` sin caché + Navigation/Resource Timing + DOM | Hostinger hcdn + LiteSpeed; sin HSTS/XFO/nosniff; 95 peticiones, 111 scripts; 50/151 imágenes sin alt |
| AUD-E-002 | 26 URL del legado (estado, tiempo, tamaño, SEO) | `fetch` en paralelo + DOMParser | Ver `02-inventario-frontend.csv` |
| AUD-E-003 | Ficha de producto del legado: consola y red | Registro de consola y red del navegador | 484 errores `o.update is not a function`; CSS de Sistecrédito 404; plugins de pago cargados en la ficha |
| AUD-E-004 | Volumen de catálogo | Store API pública (`x-wp-total`) | 644 productos, 15 categorías, 0 variables, 5 en oferta; muestra 100: 95 sin SKU |
| AUD-E-005 | 24 URL de v1 | `fetch` + DOMParser | 7–29 KB HTML; 404 en inglés; sin sitemap; `noindex` activo; `.env` y logs bloqueados |
| AUD-E-006 | Ficha de v1 | DOM | `<title>` con otro producto; JSON-LD con clave `@context` rota |
| AUD-E-007 | Identidad visual del legado | Estilos calculados y variables CSS | Paleta `--mc-*`, Poppins 300–700, radios 8/12/20/999 |
