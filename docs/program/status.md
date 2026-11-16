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
Respuestas del propietario: Q-001 (cerrada), Q-005 (parcial: liquidación a 10 días hábiles), Q-012 (cerrada: clonar todo). Plan de clon en `staging-clone-plan.md`; el propietario autorizó el clon con datos reales y controles (decisión registrada). Entorno del clon listo y probado con datos sintéticos (`infra/legacy-clone/`). Falta Q-004 y la copia del sitio (Q-025).

## Novedades 2026-11-13 (tarde)
Base de datos de producción recibida e importada en el clon aislado; inventario agregado en `docs/discovery/private-inventory-db.md` (E-031). Falta `wp-content` y `.htaccess` para levantar el sitio completo.

## Novedades 2026-11-15
`wp-content` completo recibido: el clon ya levanta el sitio entero con WordPress 7.0.4 (misma versión que producción) y las integraciones neutralizadas, incluidas 31 llaves sueltas en la base. Inventario de código en `docs/discovery/private-inventory-code.md` (E-032): 14 plugins propios activos (~20 000 líneas) y 70 snippets (~2 MB). Riesgos nuevos R-022…R-025. Faltan las carpetas `seller` y `sellercenter` (Q-028).

## Novedades 2026-11-15 (tarde)
Q-028 y Q-029 cerradas; Q-004 parcial (titular: fundador y representante legal; se pregunta si es persona natural o sociedad, Q-030). Las URL `/seller/` y `/sellercenter/` de producción muestran la página por defecto de Hostinger (R-025). MarketCityPay es producto propio del propietario.

## Próxima acción
Fase 0: queda pendiente Q-031 (comisión WCFM) y los accesos P0 que no dependen del clon (A-01, A-02, A-05: copias de seguridad y restauración). Con eso, re-evaluar la compuerta. En paralelo, recapturar evidencia pública con HTML y cabeceras (Q-015).

## Novedades 2026-11-16
Recorrido de checkout, Seller Center y backoffice en el clon (`docs/discovery/clone-walkthrough.md`, E-034). WCFM: comisión 15 % + 900 COP marcada "para el vendedor" (Q-031), retiros manuales por transferencia o efectivo. Redirección `/sellercenter/` aplicada en producción por el propietario.

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
