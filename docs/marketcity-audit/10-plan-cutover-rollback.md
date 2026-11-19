# 10 · Plan de salida (cutover) y reversión

- **Fecha:** 2026-11-17. **Estado:** BORRADOR. **No hay fecha de salida.** El corte solo se ejecuta con autorización expresa del propietario.

## 1. Estrategia
Paso del legado a v1 **por dominio completo** en una ventana corta, con el legado intacto y listo para volver. Con ~2 pedidos por semana, una congelación de 2–4 horas en horario de baja actividad es suficiente; no se justifica sincronización bidireccional.

## 2. Criterios Go / No-Go

| # | Criterio | Evidencia requerida |
|---|---|---|
| 1 | Respaldo completo del legado (BD + archivos) **restaurado con éxito** en un sitio aparte | Acta de RB-07 |
| 2 | Migración en seco cuadra conteos y sumas (tolerancia 0 en dinero) | Reporte de reconciliación |
| 3 | Pasarelas habilitadas pasan sus escenarios sandbox y una transacción real mínima autorizada | `09-plan-pruebas.md` §3 |
| 4 | E2E de comprador, vendedor y admin en verde | Reporte de Playwright |
| 5 | Sin vulnerabilidades críticas/altas abiertas | Reporte de seguridad |
| 6 | Pago de prueba desactivado, `MC_DEMO=false`, `APP_DEBUG=false`, MFA de admins activo | Lista de verificación |
| 7 | Redirecciones 301 probadas para todas las URL del sitemap del legado | Reporte de rastreo |
| 8 | Textos legales revisados por un profesional | Visto bueno |
| 9 | Propietario aprueba por escrito | Mensaje en el hilo |

## 3. Pasos (borrador)
1. T-7 días: ensayo general completo en `v1` con datos sanitizados.
2. T-1 día: aviso a vendedores; bajar TTL del DNS (si aplica) a 300 s.
3. T0: poner el legado en mantenimiento para compras (solo lectura), exportar delta (pedidos y stock desde el ensayo).
4. T0+30 min: migración final y reconciliación.
5. T0+60 min: apuntar `marketcity.co` a v1 en hPanel; certificados; pruebas de humo (inicio, ficha, carrito, checkout con pago real mínimo autorizado, Seller Center, backoffice).
6. T0+2 h: abrir; monitoreo reforzado 72 h (errores, pagos, webhooks, pedidos, correo).

## 4. Reversión
- **Disparadores:** pagos que no confirman, errores 5xx > 2 % durante 15 min, pérdida de datos detectada, decisión del propietario.
- **Cómo:** volver a apuntar el dominio al legado (que no se modificó), reabrir compras en el legado y registrar en él a mano los pedidos tomados en v1 durante la ventana (lista exportada).
- **Ventana de reversión disponible:** 14 días; durante ese tiempo el legado no se borra.
