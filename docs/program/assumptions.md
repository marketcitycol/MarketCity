# Supuestos activos

Cada supuesto es reversible y se usa solo para avanzar en el descubrimiento. Ninguno es una decisión.

| ID | Supuesto | Base | Impacto si es falso | Cómo validarlo | Propietario | Fecha objetivo |
|---|---|---|---|---|---|---|
| S-01 | El sitio corre en WordPress + WooCommerce + WCFM + tema Wolmart. | I-001…I-003 | Cambia el plan de extracción de datos | Lista de plugins (A-03) | Responsable técnico (POR DEFINIR) | Al obtener acceso |
| S-02 | MarketCity es el comercio registrado ante los PSP y liquida a los vendedores. | Política (E-007), "MarketCityPay" | Cambia todo el modelo de ledger y cumplimiento | Contratos PSP (Q-004) | Propietario de producto | Fase 0 |
| S-03 | La mayor parte del GMV proviene de inventario propio de MarketCity. | Catálogo (E-003) | Cambia prioridad Seller Center vs. comercio propio | Reporte GMV por vendedor | Propietario de producto | Fase 0 |
| S-04 | No existe código legado versionado fuera del servidor. | Repo vacío (E-028) | Si existe, debe incorporarse | Preguntar (Q-002) | Propietario | Fase 0 |
| S-05 | El repositorio `marketcitycol/MarketCity` será el repositorio destino del programa. | Configuración del proyecto | Se movería la documentación | Confirmar (Q-002) | Propietario | Fase 0 |
| S-06 | La moneda es COP sin decimales y la zona horaria America/Bogota. | E-003, E-013 | Ajustes de modelo | Exportación | Data | Fase 1 |
| S-07 | La app Android consume la API de WooCommerce del mismo sitio. | E-022 | Cambia el alcance de la migración y la compatibilidad de API | Q-014 | Propietario | Fase 0 |
| S-08 | No hay volumen de reseñas ni preguntas que migrar. | E-009, E-023 | Bajo | Exportación | Data | Fase 1 |
| S-09 | Las tiendas DAVSHOP/davidshop podrían ser internas o de prueba. | E-001 | Métricas de vendedores infladas | Q-016 | Marketplace Ops | Fase 0 |
| S-10 | El documento maestro (prompt 2.0) es la referencia del programa; su stack es hipótesis, no decisión. | Prompt §8 | n/a | ADR en Fase 1 | Propietario | Fase 1 |
