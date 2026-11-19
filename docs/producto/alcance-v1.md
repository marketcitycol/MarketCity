# Versión 1 de la plataforma propia — alcance

- **Fecha:** 2026-11-16
- **Pedido del propietario:** "que se haga la versión 1 y empiece a ser propio" (2026-11-16).
- **Estado de la compuerta de Fase 0:** NO_APROBADA (pendientes Q-031, A-01, A-02, A-05). El propietario decidió avanzar; esos pendientes se tratan como supuestos y riesgos abajo.
- **Producción:** no se tocó. La versión 1 corre aparte, con datos de prueba.
- **Actualización 2026-11-19:** las olas 1 a 6 y la ola visual cambiaron parte de este alcance (por ejemplo, la contraentrega se retiró y entraron transferencia, Mercado Pago y PQRS). Lo que hace hoy la plataforma está en [plataforma/README.md](../../plataforma/README.md).

## Objetivo
Tener un marketplace propio que el propietario pueda abrir y probar de punta a punta: comprar a varios vendedores, pagar (en prueba), despachar con guía, y liquidar a cada vendedor. Es la base sobre la que se migran los datos y se conectan las pasarelas reales.

## Incluido (capacidades de `capability-matrix.md`)

| Capacidad | En la v1 |
|---|---|
| C-01 Catálogo y PDP | Sí (sin variaciones) |
| C-02 Categorías y marcas | Sí |
| C-03 Filtros y orden | Sí. El filtro "Cuota de hasta" ahora usa el valor de la cuota (defecto corregido) |
| C-04 Búsqueda textual | Sí (nombre, SKU, marca) |
| C-06 Cuotas en catálogo/PDP | Sí, como referencia configurable (precio / 4) |
| C-07 Carrito | Sí, una sola ruta; `/cart`, `/cart-2` redirigen |
| C-08 Checkout multivendedor | Sí |
| C-09/C-10 Pagos | Contrato de adaptadores + pago de prueba + contraentrega |
| C-14 Cuenta de comprador | Sí (registro, ingreso, mis pedidos) |
| C-15/C-17 Pedidos y estados | Sí, con historial |
| C-16 Tracking por guía | Sí, guardando la guía y enlazando a la transportadora |
| C-18 Envío | Por tienda: gratis, tarifa fija o recogida |
| C-22 Alta de vendedor | Sí, con aprobación en backoffice |
| C-23 Seller Center | Sí |
| C-24 Comisiones y liquidación | Sí, liquidación manual registrada |
| C-25 Tiendas y directorio | Sí (`/tiendas` funciona) |
| C-29 Consentimiento | Términos con versión y fecha; marketing aparte |
| C-33 SEO | Mismas rutas `/producto/`, `/tienda/`, `/categoria-producto/`, `/shop`; JSON-LD de producto |
| C-37 Backoffice | Sí |

## No incluido todavía (siguientes entregas)
1. Pasarelas reales (Bold, Wompi, Mercado Pago, Addi, Sistecrédito, MarketCityPay) con webhooks y conciliación.
2. Migración de datos reales desde WooCommerce/WCFM (catálogo, tiendas, clientes, pedidos históricos) usando `legacy_id`.
3. Correos y WhatsApp transaccionales.
4. Cupones, ofertas relámpago con temporizador, reseñas, lista de deseos.
5. Facturación electrónica, retracto/garantía con radicado, PQRS.
6. Despliegue en un subdominio de prueba y luego el corte de producción.
7. Multitenant (SaaS): el modelo ya separa titular, tiendas y reglas por configuración, pero no hay inquilinos.

## Supuestos y riesgos de esta versión

| ID | Tipo | Detalle | Cómo se maneja |
|---|---|---|---|
| V1-S1 | HECHO (Q-031, propietario 2026-11-16) | MarketCity cobra **15 %** por venta, sin valor fijo, ajustable por comercio según lo negociado; también los medios de pago se activan o desactivan por comercio. Se aplica sobre el valor de los productos; el envío va al vendedor (SUPUESTO). | Backoffice → Vendedores → Condiciones; cada subpedido guarda la regla aplicada. |
| V1-S2 | SUPUESTO (Q-005) | Los 10 días hábiles se cuentan desde la confirmación del pago, sin contar festivos de Colombia. | Configurable. |
| V1-S3 | SUPUESTO | Pila Laravel + MySQL (en hosting) / SQLite (local). | Decisión del propietario pendiente; ver ADR-0001. |
| V1-R1 | RIESGO (A-01, A-02, A-05) | Sin copias de seguridad, licencias ni restauración confirmadas, no se puede planear el corte. | La v1 no toca producción. |
| V1-R2 | RIESGO (R-026) | Las pasarelas están a nombre de una persona natural. | El titular es un registro que se cambia sin tocar código. |
