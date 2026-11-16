# MarketCity · Plataforma propia (versión 1)

Primera versión del marketplace propio de MarketCity, que reemplazará progresivamente a WordPress + WooCommerce + WCFM.
Código en PHP 8.3 con Laravel 13. Datos de prueba: **no contiene datos reales** de clientes, vendedores ni del titular.

## Qué hace

| Área | Funciones |
|---|---|
| Tienda | Inicio, catálogo con búsqueda, filtros (cuota real, precio, marca, ofertas, disponibles) y orden; categorías; detalle de producto con cuotas de referencia y datos estructurados; tiendas y directorio de tiendas (`/tiendas` ya funciona); ofertas. |
| Compra | Carrito con varios vendedores, envío o recogida por tienda, checkout con cédula, departamentos de Colombia, aceptación de términos con versión y fecha, autorización de marketing aparte. |
| Pago | Medios de pago como adaptadores. Hoy: **pago de prueba** (aprobar/rechazar) y **contraentrega**. Bold, Wompi, Mercado Pago, Addi, Sistecrédito y MarketCityPay entran como nuevos adaptadores. |
| Pedido | Un pedido se divide en un subpedido por tienda. Estados Recibido → Validado → Despachado → Entregado (o Cancelado). Guía con transportadora (Coordinadora, Servientrega, TCC, Envía, Interrapidísimo). Seguimiento con número y correo. Historial auditable. |
| Seller Center | Inicio con indicadores, productos (crear, editar, foto, stock, pausar), pedidos de su tienda, liquidaciones, datos y envíos de la tienda. Registro de vendedores con aprobación. |
| Backoffice | Resumen (GMV, comisión, por liquidar), pedidos, vendedores (aprobar/suspender), productos (pausar), liquidaciones (registrar transferencia), configuración (comisión, días hábiles, medios de pago, titular del comercio). |
| Dinero | Comisión configurable (hoy 15 % + 900 COP, ver supuesto Q-031) guardada en cada subpedido. Liquidación a 10 días hábiles con festivos colombianos calculados. |
| Titular | El titular del comercio (hoy persona natural) es un dato, no código: se agrega la sociedad y se activa; los pedidos viejos conservan su titular. |

## Abrirla en su computador

Requisitos: PHP 8.3+ (con `pdo_sqlite`), Composer y Node 20+.

```bash
cd plataforma
composer run setup     # instala, crea la base SQLite con datos de prueba y compila estilos
php artisan serve      # abre http://localhost:8000
```

Con Docker: `docker compose up --build` y abrir http://localhost:8000.

Cuentas de prueba (contraseña `marketcity`):

| Rol | Correo | Entra a |
|---|---|---|
| Backoffice | admin@marketcity.test | `/backoffice` |
| Vendedor | vendedor1@marketcity.test … vendedor4 | `/seller-center` |
| Vendedor en revisión | vendedor5@marketcity.test | `/seller-center` |
| Cliente | cliente@marketcity.test | `/mi-cuenta` |

Para empezar de cero: `php artisan migrate:fresh --seed`.

## Pruebas

```bash
php artisan test
```

Cubren: páginas públicas, filtro de cuota, compra con dos vendedores (división, comisión, stock, titular), pago rechazado (cancela y devuelve stock), acceso al pedido solo con enlace, permisos de vendedor y backoffice, cambio de titular y festivos de Colombia.

## Estructura

- `app/Services/OrderService.php`: crear pedido, pagar, rechazar, avanzar estado, cancelar, liquidar.
- `app/Services/CommissionCalculator.php`: regla de comisión.
- `app/Services/Payments/`: contrato `PaymentGateway` y adaptadores.
- `app/Support/BusinessDays.php`: días hábiles y festivos de Colombia.
- `database/migrations/2026_11_16_000100_create_marketplace_tables.php`: modelo de datos.
- `routes/web.php`: rutas, incluidas redirecciones 301 desde las rutas duplicadas del sitio actual.

Más contexto en `docs/v1/alcance.md` y `docs/adr/`.
