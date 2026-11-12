# Línea base de métricas de negocio

**Estado:** sin línea base. No se inventan valores. Cada métrica tiene fórmula, fuente probable y plan de obtención. Lo único observable públicamente se marca como HECHO.

| Métrica | Fórmula | Fuente probable | Valor observado | Calidad | Propietario | Cómo obtenerla |
|---|---|---|---|---|---|---|
| Productos publicables | productos `publish` y visibles | WooCommerce | 639 en tienda / ~570 en sitemap (E-003, E-016) | Inconsistente (DQ-01) | POR DEFINIR | Exportación de productos por estado |
| Vendedores activos | tiendas con ≥1 producto publicado | WCFM | ≥5 tiendas de terceros visibles (E-001) | Parcial | POR DEFINIR | Exportación de vendedores |
| GMV | Σ total pedidos pagados (COP) por mes | WooCommerce Analytics | — | — | POR DEFINIR | Reporte agregado mensual 24 meses |
| Pedidos | conteo por estado y mes | WooCommerce | — | — | POR DEFINIR | Igual |
| Compradores activos | clientes únicos con pedido en 90 días | WooCommerce | — | — | POR DEFINIR | Igual |
| Conversión | pedidos / sesiones | Analítica | — | **No hay GA4 observado** | POR DEFINIR | Instrumentar o usar Meta/servidor |
| Abandono de carrito | 1 − pedidos / carritos iniciados | Analítica / WooCommerce | — | Sin instrumentación conocida | POR DEFINIR | Instrumentar |
| Aprobación BNPL | aprobadas / solicitudes por proveedor | Portal Addi / Sistecrédito | — | — | POR DEFINIR | Reporte de cada proveedor |
| Mezcla de medios de pago | pedidos por método | WooCommerce | — | — | POR DEFINIR | Reporte agregado |
| Tiempo de alta de vendedor | fecha tienda activa − fecha solicitud | WCFM + WhatsApp | — | Proceso manual | POR DEFINIR | Muestreo de últimos 20 vendedores |
| Tiempo a primera venta | primer pedido − alta | WCFM | — | — | POR DEFINIR | Exportación |
| Sobreventa / cancelación | pedidos cancelados por falta de stock / total | WooCommerce + operación | — | — | POR DEFINIR | Reporte + entrevista |
| Entrega a tiempo | entregados ≤ promesa / entregados | Transportadoras | — | Sin integración conocida | POR DEFINIR | Exportes de transportadora |
| Devoluciones, retractos, garantías, reversiones | casos / pedidos | WhatsApp / manual | — | Sin sistema de casos (R-005) | POR DEFINIR | Registro manual de 30 días |
| Contactos de soporte y tiempo de resolución | casos y mediana de horas | WhatsApp Business | — | — | POR DEFINIR | Exportar métricas de WhatsApp Business |
| Disponibilidad y latencia | uptime %, p50/p95/p99 | Hosting / monitor | — | — | POR DEFINIR | Ver seo-performance-baseline §3 |
| Core Web Vitals | LCP/INP/CLS p75 | CrUX | — | Bloqueado (E-026) | POR DEFINIR | Search Console |
| Costo de infraestructura | Σ hosting + licencias + SaaS / mes | Facturas | — | — | POR DEFINIR | Inventario de facturas 12 meses |
| Comisiones y margen | comisiones cobradas − costos PSP | WCFM + PSP | — | — | POR DEFINIR | Reportes de PSP |
| Tráfico orgánico | clics, impresiones, URLs indexadas | Search Console | — | — | POR DEFINIR | Acceso GSC |
| Satisfacción | NPS/CSAT | No existe | — | Hallazgo: no instrumentado | POR DEFINIR | Definir en Fase 1 |

**Hallazgo:** existe una página interna `/estadisticas-ecommerce/` (E-014, E-018) que podría contener parte de estos indicadores; consultarla solo con autorización del propietario (A-08).
