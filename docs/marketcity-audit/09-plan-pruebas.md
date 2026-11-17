# 09 · Plan de pruebas

- **Fecha:** 2026-11-17. **Herramientas:** PHPUnit (Laravel) para unitarias/integración/funcionales; Playwright para E2E y regresión visual; axe-core para accesibilidad automática.
- **Regla:** sin dinero real ni tarjetas reales; solo escenarios oficiales de sandbox.

## 1. Lo que existe hoy (automatizado, `plataforma/tests`)

| Suite | Casos | Cubre |
|---|---|---|
| `MarketplaceFlowTest` | 13 | Páginas públicas, filtro de cuotas, checkout multivendedor con comisión y fecha de liquidación, pago rechazado repone stock, token del pedido, aislamiento de vendedores, roles, cambio de titular, festivos de Colombia, importador CSV repetible, comisión y medios por tienda |
| `AuditFixesTest` (nuevo) | 7 | Título y JSON-LD de producto, sitemap y canonical, cabeceras de seguridad y 404 en español, pedidos de invitado ocultos sin correo verificado, aprobación tardía a revisión, confirmación idempotente, no liquidar sin pago |

Resultado de la última corrida: ver sección 5.

## 2. Pirámide objetivo

| Nivel | Qué | Fase |
|---|---|---|
| Unitarias | Comisión, días hábiles, transiciones de estado, normalización de estados de pasarela, asientos del ledger cuadrados | G/H |
| Integración | BD MySQL real, colas, adaptadores contra mocks HTTP | H |
| Contrato | Fixtures sanitizados de webhooks oficiales de cada proveedor | H |
| Funcionales | Rutas y permisos por rol (admin, soporte, finanzas, moderador, vendedor, comprador, invitado) | G |
| E2E (Playwright) | Comprador (móvil y escritorio), vendedor, administrador, soporte | J |
| Visual | Capturas 375/768/1280 px vs. referencia aprobada | E/J |
| Accesibilidad | axe-core en cada plantilla + revisión manual con teclado y lector | E/J |
| Rendimiento | Presupuesto: HTML < 60 KB, LCP < 2,5 s (p75 móvil), servidor < 600 ms; carga **autorizada** en staging | J (requiere autorización) |
| Seguridad | Análisis estático (PHPStan/Larastan), `composer audit`, `npm audit`, búsqueda de secretos; DAST solo con autorización | I |
| Migración | Ensayo en seco y reconciliación | K |
| Respaldo | Restauración completa en un sitio aparte | K |

## 3. Casos críticos de pago (MC-PAY)

| ID | Caso | Esperado |
|---|---|---|
| PAY-T01 | Aprobado / rechazado / pendiente / expirado / error | Estado canónico correcto, stock y liquidación coherentes |
| PAY-T02 | Retorno del navegador antes del webhook | Pedido sigue "pendiente" hasta la confirmación del servidor |
| PAY-T03 | Webhook duplicado, retrasado, fuera de orden, firma alterada | Un solo efecto; firma inválida se rechaza y registra |
| PAY-T04 | Mismo `idempotency_key` repetido | Devuelve el mismo intento |
| PAY-T05 | Reintento con otro proveedor | Sin doble cobro; intento anterior anulado |
| PAY-T06 | Reembolso total/parcial y fallo de reembolso | Ledger y pedido coherentes; alerta en fallo |
| PAY-T07 | Conciliación con diferencia de monto, moneda, referencia o estado | Registro de diferencia + alerta |
| PAY-T08 | Pedido multivendedor → comisión, reserva y liquidación | Asientos cuadrados por vendedor |
| PAY-T09 | Contracargo después de liquidar | Saldo negativo del vendedor registrado y bloqueo de liquidaciones |
| PAY-T10 | Aprobación tardía de pedido cancelado | A revisión (**automatizada hoy**) |
| PAY-T11 | Doble clic en "Pagar" | Un solo pedido |

## 4. Criterios de salida por fase
Cada PR: suite verde, sin secretos, sin vulnerabilidades altas nuevas en dependencias.

## 5. Ejecuciones
| Fecha | Comando | Resultado |
|---|---|---|
| 2026-11-17 | `php artisan test` (PHP 8.3, SQLite en memoria) | **19 pruebas, 98 verificaciones, todas en verde.** Control: sin las correcciones, 3 de las 7 pruebas nuevas fallan (título/JSON-LD, pedidos de invitado, aprobación tardía), lo que confirma que detectan las fallas. |
