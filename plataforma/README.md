# MarketCity · Plataforma propia (versión 1)

Marketplace multivendedor de MarketCity para Colombia, que reemplaza a WordPress + WooCommerce + WCFM.
PHP 8.2+ con Laravel 12, MySQL en producción y SQLite para desarrollo. Los datos de `db:seed` son **de prueba**: no hay datos reales de clientes, vendedores ni del titular.

## Qué hace

| Área | Funciones |
|---|---|
| Tienda | Inicio, catálogo con búsqueda, filtros (cuota, precio, marca, ofertas, disponibles) y orden; categorías; ficha de producto con cuotas de referencia y datos estructurados; directorio `/tiendas` con búsqueda, filtro y orden; ofertas; páginas de contenido; `sitemap.xml` y feed de productos. |
| Compra | Carrito lateral con varios vendedores, envío o recogida por tienda, checkout con cédula y departamentos de Colombia, aceptación de términos con versión y fecha, autorización de marketing aparte, rastreo de pedido. |
| Pago | Medios de pago como adaptadores (`app/Services/Payments`): transferencia bancaria (con vencimiento), Mercado Pago (aviso firmado y conciliación) y pago de prueba en el modo demo. Activables por comercio. |
| Pedido | Un pedido se divide en un subpedido por tienda: Recibido → Validado → Despachado → Entregado (o Cancelado), con guía de transportadora e historial auditable. |
| PQRS | Formulario con radicado, plazos en días hábiles de Colombia, respuesta desde el Backoffice; una PQRS abierta retiene la liquidación del vendedor. |
| Seller Center | Indicadores, productos, pedidos de su tienda, liquidaciones, datos de la tienda, datos legales y cuenta bancaria (con revisión de MarketCity). |
| Backoffice | Resumen, pedidos, vendedores, productos, liquidaciones, PQRS, páginas, plugins de la identidad del sitio (encabezado, pie, anuncio, WhatsApp, medición con consentimiento) y configuración. |
| Dinero | Comisión de MarketCity: 15 % general, ajustable por comercio y copiada en cada subpedido. Liquidación a 10 días hábiles con festivos de Colombia. El titular del comercio es un dato que se cambia sin tocar código. |
| Traslado | Importar catálogo y pedidos de WooCommerce, copiar fotos, redirecciones 301 desde las URL de WordPress y lista de revisión para el cambio de dominio (ver `app/Legacy`). |

## Abrirla en su computador

Requisitos: PHP 8.2+ (con `pdo_sqlite`), Composer y Node 20+.

```bash
composer run setup     # instala, crea la base SQLite con datos de prueba y compila estilos
php artisan serve      # http://localhost:8000
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

## Calidad

```bash
composer lint          # revisa el estilo (Laravel Pint); `vendor/bin/pint` lo corrige
php artisan test       # pruebas
```

La integración continua corre las dos cosas en cada pull request, y las pruebas en SQLite y en MySQL 8.

## Dónde está cada cosa

```
app/
├─ Http/Controllers/
│  ├─ Shop/          tienda pública: catálogo, carrito, checkout, pedido, pagos, PQRS, páginas
│  ├─ Auth/          ingreso, registro, contraseña y verificación de correo
│  ├─ Seller/        Seller Center
│  └─ Admin/         Backoffice (Admin/Legacy: pantallas del traslado desde WordPress)
├─ Services/
│  ├─ Catalog/       directorio de tiendas
│  ├─ Orders/        carrito, pedidos (crear, pagar, despachar, cancelar, liquidar) y comisión
│  ├─ Payments/      contrato PaymentGateway y un adaptador por medio de pago
│  └─ Content/       plugins de la identidad del sitio, recursos de marca e iconos
├─ Legacy/           todo lo que solo sirve para salir de WordPress (ver su README)
├─ Models/           tablas de la base
├─ Mail/             correos transaccionales
└─ Support/          utilidades sin estado: días hábiles, calendario de Colombia, HTML seguro, correo, medición
resources/views/      shop/, seller/, admin/, auth/, plugins/ (piezas activables), partials/ (piezas compartidas), mail/
routes/web.php        rutas por área (tienda, cuenta, Seller Center, Backoffice)
routes/console.php    tareas programadas: vencer transferencias, conciliar pagos, respaldo diario
deploy/               paquetes para Hostinger (ver docs/operacion)
```

Reglas para que siga ordenado:

1. **Controladores delgados:** validan, llaman a un servicio y devuelven la vista. Las reglas de negocio van en `Services/`.
2. **Vistas sin consultas:** los datos llegan desde el controlador.
3. **Colores de la paleta:** usar los tokens de `resources/css/app.css` (`text-ink-700`, `border-line`, `bg-brand-600`…), no valores `#hex` sueltos.
4. **Cada cambio con su prueba** en `tests/Feature`.
5. **Lo temporal del traslado va en `app/Legacy`**, para borrarlo junto cuando marketcity.co pase a esta plataforma.

Más contexto en [docs/producto/alcance-v1.md](../docs/producto/alcance-v1.md) y [docs/arquitectura/](../docs/arquitectura/).
