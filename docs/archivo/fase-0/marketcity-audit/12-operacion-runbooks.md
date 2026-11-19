# 12 · Operación y runbooks

- **Fecha:** 2026-11-17. **Estado:** los runbooks marcados BORRADOR se completan y prueban en fases posteriores. Ningún runbook pide escribir contraseñas en el chat.

| ID | Runbook | Estado |
|---|---|---|
| RB-01 | Actualizar v1 sin perder datos | BORRADOR |
| RB-02 | Pago pendiente o aprobación tardía | BORRADOR |
| RB-03 | Configurar tareas programadas | BORRADOR |
| RB-04 | Importar catálogo desde WooCommerce | VIGENTE (`docs/operacion/guia-hostinger.md` paso 6) |
| RB-05 | Vendedor bloqueado / suspendido | BORRADOR |
| RB-06 | Stock inconsistente | BORRADOR |
| RB-07 | Respaldo y restauración | BORRADOR |
| RB-08 | Rotación de llaves | BORRADOR |
| RB-09 | Webhook caído / proveedor degradado | Fase H |
| RB-10 | Reembolso | Fase H |
| RB-11 | Incidente de seguridad | BORRADOR |

## RB-01 · Actualizar v1 sin perder datos
1. Antes: en hPanel → Archivos, descargar copia de `mc-v1/database/database.sqlite` y `mc-v1/.env` (o respaldo de MySQL cuando exista D-01).
2. Subir el paquete **de actualización** (contiene código y `public/build`, **no** contiene `.env` ni la base).
3. Extraer sobre `mc-v1` y `public_html` aceptando reemplazar solo código.
4. Ejecutar migraciones nuevas: con SSH, `php artisan migrate --force` (nunca `migrate:fresh`). Sin SSH: PENDIENTE construir un mecanismo seguro (B-02).
5. Probar `/up`, inicio, una ficha y el ingreso al backoffice.
6. Si falla: restaurar los archivos del paso 1.

## RB-02 · Pago pendiente o aprobación tardía
- En Backoffice → Pedidos, filtrar pagos en estado "review".
- Consultar el estado en el panel del proveedor con la referencia.
- Si el cliente pagó y el pedido se canceló: reembolsar (o reactivar si hay stock y el cliente lo quiere) y dejar nota en el pedido.

## RB-03 · Tareas programadas
hPanel → Avanzado → Cron: cada minuto `php /home/<usuario>/domains/<sitio>/mc-v1/artisan schedule:run`. Ruta exacta: PENDIENTE de confirmar en hPanel.

## RB-05 · Vendedor bloqueado
Backoffice → Vendedores → estado "suspendido": sus productos dejan de mostrarse; los pedidos abiertos siguen visibles para el admin.

## RB-06 · Stock inconsistente
Comparar `products.stock` con pedidos no cancelados; corregir desde Seller Center del vendedor y anotar en el pedido afectado.

## RB-07 · Respaldo y restauración
- Legado: respaldo de hPanel (BD + archivos). Restaurar en un sitio aparte, nunca encima de producción. Registrar fecha, tamaño, SHA-256 y prueba de humo.
- v1: copia de la base y `.env` cifrada fuera del servidor. Objetivo RPO 24 h / RTO 4 h.

## RB-08 · Rotación de llaves
Prioridad por R-022: Mercado Pago, Bold, tokens de Meta, Firebase, 19 llaves REST de WooCommerce (16 de escritura). Para cada una: crear nueva en el panel del proveedor → actualizar en el sitio → probar → revocar la anterior → registrar fecha (sin el valor).

## RB-11 · Incidente de seguridad
Contener (desactivar la función o el usuario), preservar registros, rotar llaves afectadas, evaluar notificación a titulares de datos y a la SIC según la Ley 1581 de 2012 (con asesoría legal), y documentar la causa.
