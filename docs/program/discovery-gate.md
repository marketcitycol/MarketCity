# Compuerta de Fase 0 — evaluación

- **Misión:** MC-DISC-001
- **Fecha de evaluación:** 2026-11-12 (actualizada 2026-11-13)
- **Evaluador:** agente de ingeniería (DRI provisional hasta Q-001)
- **Veredicto:** **NO_APROBADA** (avance parcial; misión EN_CURSO)
- **Próxima revisión:** al recibir respuestas a Q-001, Q-004, Q-005, Q-012 y los accesos A-01…A-07, A-09…A-11 (fecha objetivo 2026-11-26).

| # | Criterio (prompt §7.6) | Estado | Evidencia | Falta |
|---|---|---|---|---|
| 1 | Superficies públicas relevantes con evidencia fechada | CUMPLIDO PARCIAL | 24 superficies, E-001…E-024 | Vista móvil, HTML crudo, cabeceras y hashes (Q-015) |
| 2 | Capacidades críticas con propietario, prioridad y disposición | CUMPLIDO | 39 capacidades; propietario = equipo MarketCity, DRI = agente (Q-001, 2026-11-13) | Asignar roles internos cuando el equipo crezca |
| 3 | Flujos de dinero, datos, inventario y pedidos dibujados o marcados como bloqueo | CUMPLIDO (como bloqueo explícito) | integration-map §2–§4 | Confirmación con contratos y acceso |
| 4 | Fuentes privadas, accesos y contactos necesarios conocidos | CUMPLIDO | access-request §2–§3 | Entrega de los accesos |
| 5 | Riesgos críticos y supuestos materiales con propietario y plan | CUMPLIDO | risks.md, assumptions.md; propietario = equipo MarketCity (Q-001) | — |
| 6 | Línea base o plan concreto para obtenerla | CUMPLIDO (plan) | business-metrics-baseline, seo-performance-baseline §3 | Datos |
| 7 | Licencias y dependencias no transferibles identificadas o pendientes | CUMPLIDO (identificadas/pendientes) | plugin-capability-matrix | Confirmación de licencias (A-02) |
| 8 | Informe diferencia hechos, inferencias y preguntas | CUMPLIDO | evidence-index (HECHO / I-xxx), questions.md | — |
| 9 | No se modificó producción | CUMPLIDO | Solo GET anónimos; sin formularios, carrito, login ni /admin | — |

## Condiciones para aprobar
1. ~~Nombrar propietarios (Q-001)~~ Cumplido 2026-11-13.
2. Confirmar flujo de dinero con contratos y reportes (Q-004 pendiente; Q-005 parcial: liquidación a 10 días hábiles; A-09, A-10).
3. Inventario técnico privado mínimo: A-01, A-02, A-04, A-05.
4. Observar checkout, Seller Center y backoffice en el clon autorizado (Q-012 cerrada; falta la copia, Q-025).
5. Recapturar evidencia pública con HTML + cabeceras + hash y medir rendimiento (Q-015).
