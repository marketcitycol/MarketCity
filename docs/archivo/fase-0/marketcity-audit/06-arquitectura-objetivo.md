# 06 · Arquitectura objetivo

- **Fecha:** 2026-11-17. **Estado:** BORRADOR para aprobación. Parte de la decisión ya tomada por el propietario (Laravel + MySQL en Hostinger, 2026-11-16) y de `docs/arquitectura/0001-pila-tecnologica.md` y `0002-modelo-de-datos.md`.

## 1. Principio
**Monolito modular** en Laravel. Con ~130 pedidos en 5 años y 650 productos, separar servicios no aporta y sí multiplica la operación. Cada dominio es un módulo con fronteras claras (servicios, eventos y tablas propias) para poder extraerlo después si la escala lo justifica.

## 2. Módulos y fronteras

| Módulo | Responsabilidad | Hoy en v1 | Siguiente paso |
|---|---|---|---|
| Catálogo e inventario | Productos, categorías, marcas, stock, moderación | `Product`, `Category`, `Brand` | Variantes, SKU obligatorio, reservas de stock con vencimiento |
| Carrito y checkout | Carrito por sesión, envío por tienda, validación Colombia | `Cart`, `CheckoutController` | Carrito persistente para usuarios, idempotencia del envío del formulario |
| Pedidos | Pedido padre + subpedido por tienda, estados, historial | `Order`, `SellerOrder`, `OrderEvent`, `OrderService` | Máquina de estados explícita, devoluciones |
| Vendedores y liquidaciones | Tiendas, condiciones, comisión, liquidación | `Store`, `CommissionCalculator`, `SettlementController` | **Ledger** de doble partida y lotes de liquidación |
| Pagos (orquestador) | Intentos, transacciones, webhooks, reembolsos, conciliación | Contrato `PaymentGateway` + prueba + contraentrega | Ver `08-integraciones-pagos.md` |
| Envíos | Transportadora, guía, rastreo | Campos en `SellerOrder` | Adaptadores de transportadora |
| Identidad y permisos | Usuarios, roles, sesiones | Roles `admin/seller/customer` | Roles finos (soporte, finanzas, moderador), permisos por tienda, MFA para admin |
| Contenido y configuración | Ajustes, titular de cobro, textos legales con versión | `Setting`, `MerchantEntity` | Configuración versionada y auditada |
| Búsqueda | Texto, filtros, cuotas | SQL `LIKE` | Índice FULLTEXT de MySQL; motor aparte solo si el volumen lo pide |
| Notificaciones | Correo transaccional | `MAIL_MAILER=log` | Plantillas versionadas + SMTP/proveedor (D-06) |
| Analítica y auditoría | Eventos de negocio, bitácora | `OrderEvent` | Bitácora de acciones de admin/vendedor, tablero de KPIs |
| Integraciones | Importador WooCommerce | `WooCommerceCsvImporter` | Migrador directo desde la BD (Fase K) |

## 3. Decisiones de infraestructura

| Tema | Propuesta | Por qué | Costo/riesgo |
|---|---|---|---|
| Hosting | Hostinger (mismo proveedor), sitio aparte del legado | Ya pagado y conocido por el propietario | Sin consola en el plan: despliegues por paquete (RB-01) |
| Base de datos | **MySQL de Hostinger** en v1 (hoy SQLite) | Concurrencia real, respaldos del panel, igual que la decisión del propietario | D-01: crear la base y el usuario en hPanel |
| Caché / sesiones / colas | Base de datos (drivers `database`) | No hay Redis en el plan | Suficiente para el volumen; revisar con carga |
| Tareas programadas | Cron de hPanel cada minuto → `php artisan schedule:run` | Conciliación diaria, vencimiento de pagos, liquidaciones | Requiere configurar 1 cron (RB-03) |
| Medios | Disco del hosting + CDN de Hostinger; URLs propias | Quitar dependencia de Jetpack | Migrar 989 MB de uploads (seleccionados) |
| Secretos | `.env` fuera de `public_html`, permisos 600; llaves por pasarela en `.env`, nunca en BD sin cifrar | R-022 | Rotación manual documentada (RB-08) |
| Observabilidad | Logs JSON diarios, tabla de eventos, alertas por correo; `/up` de salud | Sin APM en el plan | Suficiente para la fase actual |
| CI/CD | GitHub Actions: pruebas + análisis estático + auditoría de dependencias en cada PR; artefacto `zip` versionado | Repetible y revisable | Propuesto en Fase J |
| Despliegue | Paquete por versión con migraciones hacia adelante; carpeta `mc-v1-<versión>` y cambio de enlace = equivalente a blue/green | No hay SSH | RB-01, `10-plan-cutover-rollback.md` |
| Respaldos | Diario de BD + semanal de archivos; **RPO 24 h, RTO 4 h** propuestos | Volumen bajo | Prueba de restauración trimestral (RB-07) |

## 4. API y apps
- API REST versionada `/api/v1` (Sanctum, tokens por app), OpenAPI en `docs/api/openapi.yaml`, límites de tasa por token. Fase G.
- Webhooks salientes firmados (HMAC-SHA256, marca de tiempo, reintentos). Fase G.

## 5. Multitenant (SaaS) — camino
El modelo ya separa **titular de cobro** (`MerchantEntity`) de las tiendas. El siguiente paso es un `tenant_id` en tablas de negocio con un ámbito global; no se activa hasta que haya un segundo comercio real.

## 6. ADRs
| ADR | Tema | Estado |
|---|---|---|
| 0001 | Pila tecnológica (Laravel) | ACEPTADA por el propietario 2026-11-16 |
| 0002 | Modelo de datos marketplace | ACEPTADA |
| 0003 | Orquestador de pagos y ledger | PROPUESTA (`08-integraciones-pagos.md`) |
| 0004 | MySQL en Hostinger para v1 | PROPUESTA (D-01) |
| 0005 | Despliegue por paquete versionado | PROPUESTA |
