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
  propietario_producto: POR DEFINIR
  responsable_tecnico: POR DEFINIR
  repositorio_destino: github.com/marketcitycol/MarketCity  # SUPUESTO S-05
  pais_inicial: Colombia
autorizacion:
  entornos_autorizados: [LOCAL]
  produccion_solo_lectura: true
  cambios_en_produccion: false
```

## Última evidencia
Auditoría pública de 24 superficies de marketcity.co el 2026-11-12 (E-001…E-028).

## Próxima acción
Obtener respuestas a Q-001, Q-004, Q-005, Q-012 y los accesos P0 de `access-request.md`; luego ejecutar el inventario técnico privado (§7.3) en sesión de solo lectura.

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
  program/    status, assumptions, questions, risks, access-request, discovery-gate
```
