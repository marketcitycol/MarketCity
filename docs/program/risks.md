# Registro de riesgos

Severidad = impacto × probabilidad (C crítica, A alta, M media, B baja). Propietario: rol propuesto hasta que se nombren personas (Q-001).

| ID | Riesgo | Evidencia | Sev. | Propietario | Mitigación / próxima acción |
|---|---|---|---|---|---|
| R-001 | Flujo de dinero desconocido (quién cobra, liquida y concilia): imposible diseñar ledger ni migrar saldos con seguridad. | integration-map §2 | C | Finanzas | Responder Q-004/Q-005; obtener reportes de PSP y WCFM. |
| R-002 | Dependencia de personas y cuentas: repositorio vacío, sin código ni runbooks bajo control de MarketCity. | E-028 | C | Propietario | Inventario de titularidad (Q-007), copia de código del servidor (A-04). |
| R-003 | Restauración no probada / backups desconocidos. | — | C | Responsable técnico | A-05: evidencia de backup y una restauración en staging. |
| R-004 | Textos legales inconsistentes (retracto calendario vs. hábiles; intermediario siendo también vendedor; una sola página para todo). | E-007 | A | Legal | Revisión por asesor (Q-008). No se afirma cumplimiento. |
| R-005 | PQRS, garantías y retractos sin radicado ni trazabilidad (salen por WhatsApp). | E-011 | A | Ops | Medir volumen 30 días; requisito para Fase 1. |
| R-006 | Consentimientos (cookies, datos, marketing) posiblemente no registrados de forma auditable; Meta Pixel posiblemente antes del consentimiento. | E-001, E-021 | A | Privacy | Q-018; revisar almacenamiento de consentimiento. |
| R-007 | Licencias no transferibles (Wolmart, WCFM, Jetpack) y posibles plugins abandonados. | plugin matrix | A | Responsable técnico | A-03; inventario de licencias y versiones. |
| R-008 | Página interna `/estadisticas/` protegida solo por contraseña de página y listada en sitemap. | E-018 | M | Responsable técnico | Recomendar (no ejecutado) excluirla del sitemap y moverla tras login. Requiere aprobación: cambio en producción. |
| R-009 | Pérdida SEO en migración: ~570 productos, tiendas fuera de sitemap, ~15 páginas duplicadas/demo, sin JSON-LD. | seo baseline | A | SEO | Mapa 1:1 de URLs con tráfico (A-07). |
| R-010 | App Android desconocida podría romperse al cambiar la API. | E-022 | A | Mobile | Q-014. |
| R-011 | Defectos visibles que afectan conversión: filtro de cuota mal mapeado, `/tiendas/` en error, contador de despacho vacío, rutas de tracking duplicadas. | E-003, E-020, E-009, E-006 | M | Product | Reportar al operador del legado; no se corrigen en esta misión. |
| R-012 | Sin línea base de negocio ni rendimiento → no se podrá demostrar mejora ni detectar regresiones. | business/seo baseline | A | Data | Plan de obtención (A-06, A-07, Q-015). |
| R-013 | Riesgo de seguridad en cuentas (sin CAPTCHA visible, sin 2FA conocido en admin/vendedores). | E-024 | A | Security | Confirmar controles con acceso (A-02). |
| R-014 | Contenido interno del tema (`wolmart_template`) y categorías vacías indexados. | E-008, E-017 | B | SEO | Incluir en limpieza del mapa de URLs. |
| R-015 | Facturación electrónica DIAN desconocida. | — | A | Finanzas | Q-019. |
| R-016 | Observación limitada a texto extraído; sin HTML crudo, cabeceras ni hashes por restricción del entorno. | E-026, E-027 | M | DRI misión | Recaptura con rastreador aprobado (Q-015). |
| R-019 | Lógica de negocio fuera de control de versiones: 15 plugins propios y 70 snippets activos guardados en la base de datos, varios marcados "vista previa" con alcance global. | E-031 | A | Responsable técnico | Versionar el código propio en este repositorio (copia de solo lectura) y revisar snippets (Q-027). |
| R-020 | 989 tareas programadas fallidas en Action Scheduler: integraciones o notificaciones que fallan sin aviso. | E-031 | M | Responsable técnico | Analizar por tipo de tarea en el clon. |
| R-021 | Base inflada: 70 % es analítica de visitas de WCFM (458 MB). Respaldos y restauraciones más lentos. | E-031 | M | Responsable técnico | Excluir de la migración; evaluar purga en producción (requiere aprobación). |
