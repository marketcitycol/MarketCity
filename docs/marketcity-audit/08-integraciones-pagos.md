# 08 · Integraciones de pago y orquestador

- **Fecha:** 2026-11-17. **Estado:** DISEÑO. En v1 solo existen el contrato `PaymentGateway`, el pago de prueba (`sandbox`) y contraentrega.
- **Reglas:** sandbox primero; nunca confirmar un pago por el retorno del navegador; firma verificada en cada webhook; idempotencia; sin datos de tarjeta en MarketCity (checkout alojado o widget del proveedor).
- **Titular:** todas las cuentas están a nombre del fundador como persona natural (R-026). El modelo `MerchantEntity` permite cambiar de titular sin tocar código cuando pase a sociedad.
- **Endpoints y firmas:** se toman **solo** de la documentación oficial vigente al implementar cada adaptador; este documento no los inventa.

## 1. Modelo del orquestador (MC-PAY-001)

| Entidad | Para qué |
|---|---|
| `payment_intents` | Lo que hay que cobrar por un pedido (monto, moneda COP, titular, estado canónico). Uno por pedido y por intento de compra. |
| `payment_attempts` | Cada intento con un proveedor (permite reintentar con otro sin cobro doble). Guarda la referencia única enviada al proveedor. |
| `payment_transactions` | Movimientos inmutables reportados por el proveedor (autorización, captura, anulación, reembolso). Guarda el estado **original** y el canónico. |
| `refunds` | Reembolsos totales o parciales con su propio estado. |
| `disputes` | Contracargos y disputas. |
| `webhook_events` | Cada notificación recibida: proveedor, id de evento, firma válida o no, hash del cuerpo, estado de procesamiento. Único por (proveedor, id de evento). |
| `ledger_entries` | Asientos de doble partida (debe = haber) para cobros, comisiones, obligaciones con el vendedor, reembolsos y liquidaciones. |
| `settlement_batches` | Lotes de pago a vendedores con referencia bancaria. |
| `reconciliation_records` | Resultado de cruzar proveedor ↔ pedido ↔ ledger por día. |

### Estados canónicos
`creado → pendiente → requiere_accion → aprobado | rechazado | error | expirado | anulado`, y después de aprobado: `parcialmente_reembolsado`, `reembolsado`, `contracargo`. Cualquier transición no listada se rechaza y se registra.

### Reglas que ya cumple v1 (corregidas hoy)
- Confirmar un pago dos veces no duplica efectos (bloqueo de fila) — MC-PAY-005.
- Una aprobación que llega cuando el pedido ya se canceló **no** lo revive: queda "en revisión" para reembolso o reactivación manual — MC-PAY-006.

### Contrato de cada adaptador
`createIntent`, `redirectOrWidget`, `fetchStatus` (consulta servidor a servidor), `void`, `refund(parcial)`, `verifyWebhook`, `normalizeStatus`, `reconcile(día)`, `healthCheck`, `mode()` (sandbox/producción).

## 2. Proveedores

| Proveedor | Uso histórico | Producto a evaluar | Estado en v1 | Bloqueo externo |
|---|---|---|---|---|
| Addi (BNPL) | 50 pedidos (39 %) | Flujo e-commerce de Addi (solicitud, redirección, callback) | No iniciado | Credenciales de staging de aliado y homologación con Addi |
| Sistecrédito | 8 | API de aliado según contrato | No iniciado | Documentación y credenciales de aliado; si no hay API, solo mock + contrato |
| Bold | 7 | API de pagos / botón | No iniciado | Llaves de prueba |
| Wompi | 2 | Widget/Web Checkout + evento `transaction.updated` con firma | No iniciado | Llaves de sandbox (pública, privada, eventos, integridad) |
| Mercado Pago | 1 | Checkout Pro o Bricks; webhooks con firma `x-signature` | No iniciado | Credenciales de prueba; evaluar cuentas conectadas para marketplace |
| Wava | 6 | — | No | Confirmar si sigue contratado |
| MarketCity Pay | 13 | Producto propio (`api.marketcitypay.com`, HMAC) | No | Documentación de la API propia; definición legal (no es billetera) |
| Contraentrega | 27 | Interno | **Sí** | — |
| Transferencia | 11 | Interno con validación manual | No | Decidir si continúa |

## 3. MarketCity Pay: alcance seguro (D-03)
Primera versión como **orquestación y ledger**, no como entidad financiera: selección de proveedor, pago combinado con varios medios del mismo comprador (cada uno cobrado por su proveedor), saldo **informativo** del vendedor respaldado por transacciones conciliadas. No se usan las palabras "billetera", "depósito" ni "dinero disponible" mientras no exista estructura legal y técnica (revisión profesional obligatoria).

## 4. Modelo de cobro marketplace (D-02)
Hoy MarketCity cobra todo y liquida a mano a 10 días hábiles (15 % de comisión). Alternativa: split del proveedor (Mercado Pago cuentas conectadas, Wompi pagos a terceros) si está habilitado y es jurídicamente adecuado. Hasta decidirlo, el ledger registra la obligación con cada vendedor y la liquidación queda manual con referencia.

## 5. Seguridad de pagos
- Llaves en `.env` por modo (`*_SANDBOX_*`, `*_LIVE_*`); la llave privada nunca llega al navegador.
- CSP con solo los dominios del proveedor activo.
- Webhook: responder 200 rápido, procesar en cola, reconsultar al proveedor antes de pasar a "aprobado".
- Conciliación diaria automática con alertas: diferencia de monto/moneda/referencia, webhook fallido, pagos pendientes más de N horas, doble cobro.
- **El pago de prueba (`sandbox`) debe quedar desactivado en producción** (hoy se puede activar desde Configuración): requisito de salida MC-PAY-010.
