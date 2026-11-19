# ADR-0002 · Modelo de datos de la versión 1

- **Estado:** ACEPTADA para la v1
- **Fecha:** 2026-11-16

## Decisiones
1. **Dinero en COP como enteros**, sin decimales.
2. **Pedido → subpedido por tienda** (`seller_orders`): envío, estado, guía, comisión, neto y fecha de liquidación viven en el subpedido, porque cada vendedor despacha y cobra por separado.
3. **Comisión copiada en el subpedido** (`commission_rule`): cambiar la regla no altera pedidos anteriores.
4. **Titular del comercio como entidad** (`merchant_entities`): el pedido y el pago guardan el titular vigente. Pasar de persona natural a sociedad es activar otro registro.
5. **`legacy_id`** en usuarios, tiendas, categorías, marcas, productos y pedidos para migrar desde WooCommerce/WCFM y trazar.
6. **Historial de eventos** (`order_events`) para auditar quién cambió qué.
7. **Productos no se borran**: se pausan, porque los pedidos los referencian.
8. **Fechas en UTC**, mostradas en America/Bogota.
