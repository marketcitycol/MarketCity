# Documentación de MarketCity

| Carpeta | Qué hay | ¿Se mantiene al día? |
|---|---|---|
| [`producto/`](producto/) | Qué hace la plataforma y por qué: [alcance de la versión 1](producto/alcance-v1.md) y [matriz de capacidades](producto/capability-matrix.md). | Sí |
| [`arquitectura/`](arquitectura/) | Decisiones técnicas (ADR) y [modelo de amenazas](arquitectura/modelo-de-amenazas-inicial.md). | Sí |
| [`operacion/`](operacion/) | Cómo instalar, actualizar y ajustar v1 en Hostinger, y la lista para el cambio de dominio. | Sí |
| [`archivo/`](archivo/) | Descubrimiento y auditoría del sitio WordPress (Fase 0, noviembre de 2026). | No: es histórico |

Reglas:

- Un documento nuevo va en la carpeta de su tema; si cambia una decisión, se actualiza el documento en vez de crear otro.
- Nada de contraseñas, llaves ni datos personales en la documentación.
- Lo histórico no se edita: si algo de `archivo/` sigue vigente, se lleva a `producto/`, `arquitectura/` u `operacion/`.
