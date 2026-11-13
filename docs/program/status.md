# Estado del programa

- **Fecha:** 2026-11-12
- **Misión activa:** MC-DISC-001 · Fase 0 · Descubrimiento público de solo lectura
- **Estado:** EN_CURSO
- **Última compuerta:** Fase 0 → **NO_APROBADA** (ver `discovery-gate.md`)
- **Producción modificada:** No.

## Configuración del programa

```yaml
programa:
  nombre: MarketCity
  propietario_producto: equipo propietario de MarketCity  # Q-001, 2026-11-13
  responsable_tecnico: equipo propietario de MarketCity
  dri_mision: agente de ingeniería (Claude)
  repositorio_destino: github.com/marketcitycol/MarketCity  # SUPUESTO S-05
  pais_inicial: Colombia
autorizacion:
  entornos_autorizados: [LOCAL]
  produccion_solo_lectura: true
  cambios_en_produccion: false
```

## Última evidencia
Auditoría pública de 24 superficies de marketcity.co el 2026-11-12 (E-001…E-028).

## Novedades 2026-11-13
Respuestas del propietario: Q-001 (cerrada), Q-005 (parcial: liquidación a 10 días hábiles), Q-012 (cerrada: clonar todo). Plan de clon en `staging-clone-plan.md`. Falta Q-004 y el acceso para el clon (Q-025).

## Próxima acción
Recibir la copia o el acceso de Q-025, montar el clon según `staging-clone-plan.md` y desde él completar el inventario técnico privado (§7.3), checkout, Seller Center y backoffice. Pendientes: Q-004 y los accesos P0 de `access-request.md`; luego ejecutar el inventario técnico privado (§7.3) en sesión de solo lectura.

## Árbol documental

```text
docs/
  adr/README.md
  discovery/  as-is-system-map, route-and-state-inventory, journey-catalog,
              integration-map, data-dictionary, data-quality-profile,
              plugin-capability-matrix, seo-performance-baseline,
              business-metrics-baseline, evidence-index
  product/    capability-matrix
  security/   initial-threat-model
  program/    status, assumptions, questions, risks, access-request, discovery-gate,
              staging-clone-plan
```
