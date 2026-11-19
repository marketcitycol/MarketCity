# Catálogo de journeys — as-is (observación pública)

Cada journey registra actor, disparador, pasos observados, reglas, datos, integraciones, estados alternos, evidencia y propietario. "Observado" = visto el 2026-11-12 sin ejecutar acciones con efecto. "No verificado" = requiere sesión de prueba, entorno clonado o entrevista.

Propietario de negocio por defecto: `POR DEFINIR` (Q-001). Prioridad: P0 dinero/datos/pedido, P1 crecimiento/operación, P2 contenido.

---

## J-01 Descubrir y buscar productos — P1
- **Actor:** comprador anónimo. **Disparador:** visita al inicio o a una categoría.
- **Pasos observados:** menú (Ofertas, A crédito, 10 categorías) → `/shop/` o categoría → filtros (cuota, precio, categoría, color, marca) → orden → paginación (E-001, E-003).
- **Reglas:** filtro "Cuota de hasta" se traduce a `max_price` con valores que no coinciden con la etiqueta (E-003) → **defecto probable**.
- **Búsqueda:** texto en header y hero; **búsqueda por foto** "analizada en el dispositivo" (E-001). Mecanismo, proveedor y datos enviados: no verificado (Q-011).
- **Estados alternos:** sin resultados, error de búsqueda, imagen no reconocida: no verificados.
- **Evidencia:** E-001, E-003, E-017.

## J-02 Evaluar un producto — P1
- **Pasos observados:** detalle con precio, SKU, stock numérico, cuotas Addi/Sistecrédito, simulador de cuotas, envío "32 departamentos y Bogotá", contador de despacho, garantía legal, retracto, pestañas Vendedor/Preguntas/Relacionados (E-009).
- **Reglas:** cuota = precio / 4 (60.000 → 4 × 15.000). Regla real del proveedor (intereses, mínimos, aprobación) no verificada (Q-005).
- **Defectos observados:** contador "Pide en --:--:-- y lo despachamos hoy" sin valor; sin reseñas ni JSON-LD de producto.
- **Evidencia:** E-009.

## J-03 Carrito y checkout — P0 (**bloqueo de evidencia**)
- **Observado:** carrito vacío (`/cart-2/`); `/checkout/` vacío redirige al carrito (E-010, E-019).
- **No verificado:** añadir al carrito, cupón ("2026" anunciado en E-001), cálculo de envío por vendedor, impuestos, selección de método de pago, creación del pedido, división por vendedor, confirmación, correos.
- **Motivo:** ejecutarlo en producción crea pedidos/reservas de stock reales → prohibido en esta misión. Requiere entorno clonado o pedido de prueba autorizado (Q-012).

## J-04 Pagar (Addi, Sistecrédito, Bold, PSE, Nequi, Daviplata, tarjetas) — P0 (**bloqueo**)
- **Observado:** métodos anunciados en inicio, pie y política (E-001, E-007). "Sistecrédito (con MarketCityPay)".
- **No verificado:** qué plugin implementa cada método; si Nequi/Daviplata/PSE van por Bold u otro agregador; webhooks; confirmación servidor-servidor; reembolsos; contracargos; conciliación. Ver `integration-map.md`.

## J-05 Cuenta del comprador — P0
- **Observado:** login usuario/correo + contraseña; registro solo con correo y contraseña enviada por correo; recuperación por enlace; sin CAPTCHA visible, sin OTP, sin login social (E-024).
- **No verificado:** direcciones, historial de pedidos, detalle de pedido, preferencias de notificación.
- **Eliminación de cuenta:** por correo o WhatsApp, 15 días hábiles, retención contable 10 años (E-022). Proceso manual.

## J-06 Seguimiento de pedido — P0
- **Observado:** `/rastrear-pedido/`: transportadora + guía → redirección a la web de la transportadora; "no guardamos tu guía" (E-006). FAQ: seguimiento también por WhatsApp con número de pedido y ciudad (E-011).
- **Reglas:** estados mostrados al comprador: Recibido, Validado, Despachado, Entregado (E-005). El paso "Validado" sugiere validación manual (I-008, Q-013).
- **Inconsistencia:** dos rutas `/rastrear-pedido/` y `/rastrea-tu-pedido/`.

## J-07 Postventa: retracto, garantía, reversión del pago, PQRS — P0
- **Observado:** retracto de 5 días con contradicción calendario/hábiles (E-007); garantía mínima 1 mes para nuevos; diagnóstico hasta 30 días hábiles; reversión del pago enlazada pero sin procedimiento descrito; PQRS por formulario que abre WhatsApp, sin radicado ni SLA (E-011).
- **Riesgo:** no hay número de radicado verificable ni trazabilidad del caso (R-005).

## J-08 Captación y alta de vendedor — P1
- **Observado:** formulario de interés → WhatsApp; registro de cuenta con CC/NIT, dirección, teléfono, contraseña; asistente Registro→Planes→Confirmación→Éxito con código por correo; planes no publicados; tienda creada "con el vendedor" (E-004).
- **No verificado:** KYC/KYB, validación de NIT/RUT, cuenta bancaria, contrato, comisiones, aprobación manual, moderación de catálogo.

## J-09 Operación del vendedor (Seller Center) — P0 (**bloqueo**)
- **Observado:** solo la puerta de entrada (E-005). Se anuncia carga de fotos, precio y stock.
- **No verificado:** gestión de pedidos, guías, devoluciones, comisiones, retiros/liquidaciones, reportes.

## J-10 Campañas y ofertas — P1
- **Observado:** ofertas relámpago con estado vacío correcto y fallback a productos con descuento (E-012); campaña "Black City" inactiva (E-021); cupón de bienvenida anunciado (E-001); opt-in de notificaciones con horarios de Ley 2300 de 2023 (E-021).
- **No verificado:** motor de cupones, reglas de stock por oferta, temporizadores.

## J-11 Backoffice y procesos manuales — P0 (**bloqueo**)
- Fuera de alcance sin acceso autorizado. Ver `docs/program/access-request.md`.

## J-12 App Android — P1 (**descubierta**)
- **Observado:** `/eliminar-mi-cuenta/` menciona "marketcity.co y la app Android", con favoritos, búsquedas y carrito guardados en el dispositivo (E-022). Ficha de tienda no localizada (E-025).
- **Pendiente:** código fuente, propietario, API que consume, versión publicada, usuarios activos (Q-014).

---

## Cobertura pendiente por journey

| Journey | Escritorio | Móvil | Anónimo | Autenticado | Fallo proveedor |
|---|---|---|---|---|---|
| J-01…J-02 | Observado (texto) | Pendiente | Observado | Pendiente | n/a |
| J-03…J-04 | Bloqueo | Bloqueo | Bloqueo | Bloqueo | Bloqueo |
| J-05…J-07 | Parcial | Pendiente | Parcial | Pendiente | Pendiente |
| J-08…J-09 | Parcial | Pendiente | Parcial | Bloqueo | Pendiente |
| J-11…J-12 | Bloqueo | Bloqueo | n/a | Bloqueo | Bloqueo |
