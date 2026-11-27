# Traslado de los snippets de WordPress a la plataforma propia

- **Fecha del inventario:** 2026-11-27, leído en wp-admin → Fragmentos de marketcity.co (solo lectura, API de Code Snippets con la sesión del propietario). No se cambió nada en WordPress.
- **Total:** 87 snippets: 81 activos y 6 inactivos (2,9 MB de código). El inventario del 19 de noviembre tenía 74; los nuevos son del #75 al #87.
- **Regla:** no se copia código de WordPress. Cada regla de negocio se reescribe en Laravel con pruebas. Lo que solo arreglaba el tema Wolmart, Elementor, WCFM o el rendimiento de WordPress no se traslada: la v1 tiene su propio diseño y no carga nada de eso.
- **Secretos:** ningún snippet tiene llaves de pasarela escritas en el código (revisión por patrones el 2026-11-27). El #35 (login secreto) guarda la ruta de acceso al admin y el #70 (MarketTurbo) lee llaves cifradas desde la base. Nada de eso se copió al repositorio.

## Estado por snippet

Leyenda: **HECHO** = ya existía en v1 · **S1/S2/S3** = ola de este traslado · **NO APLICA** = arreglo propio de WordPress/Wolmart/WCFM · **DECISIÓN** = espera una decisión del propietario.

| # | Snippet | Estado en WP | Destino en v1 |
|---|---|---|---|
| 1–3 | Ejemplos de Code Snippets | Inactivos | NO APLICA |
| 4 | Año actual (shortcode) | Activo | HECHO (el pie usa el año del servidor) |
| 5 | Skip Cart | Activo | HECHO («Comprar ahora» va al pago) |
| 6, 8, 9, 41 | Textos y traducciones | Activos | HECHO (v1 está en español) |
| 7 | Etiqueta de Google Ads | Activo | **S2** (ID y conversión de compra en el plugin de Medición, con consentimiento) |
| 10 | «Por: tienda» en tarjetas | Activo | HECHO |
| 11 | Open Graph de producto | Activo | **S2** |
| 12 | Vende aquí | Activo | HECHO (/vender con aprobación en Backoffice) |
| 13, 15, 17–27, 38, 40, 51, 53, 55, 56, 58, 60, 80, 84 | Ajustes visuales del tema, rendimiento y vista previa | Activos | NO APLICA (ola visual de v1) |
| 14 | Ficha: «Lo que debes saber» | Activo | **S2** |
| 16 | Temporizador real | Activo | **S1**: cuenta regresiva de la campaña y hora de corte de despacho configurable |
| 20 | Logos de pago y redes | Activo | HECHO (pie con medios de pago) |
| 28, 29, 42 | Juego de cajas y juegos | Activos (vista previa) | DECISIÓN (Q-010: ¿premios reales con cupones?) |
| 30, 34, 36, 37 | Ocultar tecnología y Blackout | Activos | NO APLICA (sin WordPress no hay huellas que ocultar; v1 ya envía cabeceras de seguridad) |
| 31, 44, 50 | Login con estilo | Activos | HECHO (ingreso propio) |
| 32, 63 | Rendimiento y retraso de Google/Meta | Activos | NO APLICA (v1 no carga terceros sin consentimiento) |
| 33 | Mapa de cobertura sin datos de clientes | Activo | S3 |
| 35 | Login secreto | Activo | NO APLICA (Backoffice protegido por rol; recomendado: segundo factor, S3) |
| 39 | Rechazo de cookies recordado | Activo | HECHO (aviso de cookies) |
| 43 | Títulos y meta | Activo | HECHO |
| 45 | Rotar productos del inicio | Activo (vista previa) | S3 |
| 46 | Arreglos QA ronda 3 | Activo | NO APLICA |
| 47 | Crear cuenta si el correo no existe | Activo | **S2** |
| 48, 49, 79 | Mi cuenta (detalles, rediseño, «Universo») | Activos (#79 publicado) | **S2**: inicio, pedidos, direcciones, datos, seguridad (con eliminar cuenta), favoritos, notificaciones y consultas. Diseño del manual de marca: ola visual |
| 52 | Fundaciones SEO y redirecciones | Activo | HECHO (redirecciones 301 propias) |
| 54 | Carrito lateral | Publicado | HECHO (panel «Agregado al carrito»). El descuento por medio de pago de ELEX queda como DECISIÓN |
| 57 | Checkout guiado | Publicado | HECHO (checkout propio en una página) |
| 59 | Páginas de servicio (404, simulador, rastreo) | Publicado | 404: HECHO · simulador de cuotas: S3 |
| 61 | Sin contraentrega, políticas, buscador | Publicado | HECHO |
| 62 | Compra sin cuenta y página de gracias | Publicado | HECHO (cédula enmascarada, transferencia con «Copiar») + **S2** «Crear cuenta con un toque» en gracias |
| 64 | Modo oscuro | Publicado | S3 |
| 65 | Rastreo seguro | Vista previa | **S1** |
| 66 | Servicio y limpieza (contacto con copia por correo) | Vista previa | S3 (formulario de contacto) |
| 67 | Accesibilidad lote C | Vista previa | NO APLICA (v1 propia) |
| 68 | Directorio de tiendas | Publicado | HECHO (/tiendas) + **S2** «Destacar» y «Ocultar del directorio» |
| 69 | Perfil de vendedor | Vista previa | HECHO tienda, datos legales y cuenta bancaria + **S2** logística, notificaciones y seguridad. Equipo (varios usuarios por tienda): S3 |
| 70 | MarketTurbo (envío el mismo día) | Vista previa | S3 (adaptador de envío; credenciales del proveedor en .env) |
| 71, 72 | Sub-URL y pantalla completa del Seller Center (WCFM) | Activos | NO APLICA |
| 73 | Rescate del regreso de Wava | Vista previa | NO APLICA hasta integrar Wava |
| 74 | Productos del Seller Center | Vista previa | **S1** (precios «1.200.000»); el resto ya estaba (validaciones y mínimo de $1.000) |
| 75 | Verificación de dominio de Meta | Activo | **S2** (campo en el plugin de Medición) |
| 76 | Black Friday: precio con correo | Activo | **S1** |
| 77 | Black City: tope de 10 unidades | Activo | **S1** |
| 78 | Página de ofertas en cuadrícula | Activo | **S1** (/campana/{nombre}; /black-city redirige) |
| 81 | Inicio mezcla V1 | Publicado | HECHO (inicio de la ola visual) |
| 82 | Permisos de mensajes (correo y WhatsApp) | Activo | **S1** |
| 83 | Recuperar carritos abandonados | Activo | **S1** |
| 85 | Meta: compra solo con pedido pagado | Activo | **S1** |
| 86 | TEMP diagnóstico Meta | Inactivo | NO APLICA |
| 87 | Pago claro y WhatsApp | Vista previa | **S2**: WhatsApp en el pago, plan B del pago rechazado, envío visible, recordar datos del invitado. «Compra directa»: DECISIÓN |

## Ola S1 (este cambio)

| Snippet | Qué hace ahora la plataforma | Dónde |
|---|---|---|
| #16 | Cuenta regresiva real en la ficha cuando el producto está en campaña. Sin campaña, si el Backoffice configuró la hora de corte, muestra «Pide antes de las X y, con el pago confirmado, lo despachamos hoy» (nunca domingos ni festivos). | `CampaignService::dispatchCutoff`, Backoffice → Campañas |
| #77, #78 | Campañas con fechas en hora de Colombia, precio por producto y tope de unidades. El precio se aplica y se retira solo (cada minuto). Al llenarse el tope el producto vuelve a su precio normal; si un pedido se cancela, recupera el precio. El carrito y el pago no dejan llevar más de lo que queda. Si el vendedor cambia el precio durante la campaña, ese valor queda como precio normal. Página `/campana/{slug}` en cuadrícula. | `app/Services/Campaigns`, Backoffice → Campañas |
| #76 | «Ver mi precio» en la ficha: correo + autorización de datos → cupón único (1 uso, 1 unidad, ese producto, ese correo, vence con la campaña), se envía por correo y queda en el carrito. Se paga el menor entre lo prometido y el precio con correo del momento. Solo vale pagando con el mismo correo. Si el pago se rechaza, el cupón vuelve a servir. 30 días después del fin se borra el correo. | `EmailPriceService` |
| #82 | Casillas Correo y WhatsApp en el pago, sin marcar. Registro inmutable de cada alta y baja (texto exacto con huella y versión, IP cifrada, navegador, pedido). Desmarcar solo retira lo autorizado en esa misma visita. Horario legal (L-V 7-19, sábados 8-15, sin domingos ni festivos), un mensaje al día, mismo canal 7 días, cupos por hora y por día. Baja con un clic (enlace firmado y List-Unsubscribe). CSV del registro. | `MessagingPermissions`, Backoffice → Mensajes |
| #83 | Copia del carrito de quien marcó una casilla. Recordatorios a la hora y a las 24 h (72 h opcional) por un solo canal. El enlace rehace el carrito en cualquier dispositivo; si hay un pedido sin pagar, lleva a ese pedido. Se detiene si compra, se da de baja, vacía el carrito o se agota. Modos Apagado / Prueba / Público (llega Apagado). WhatsApp: el panel arma el mensaje y el equipo lo envía y lo marca. | `AbandonedCarts`, comando `mensajes:carritos` |
| #65 | Rastreo con número + correo **o celular**. Mismo mensaje si el pedido no existe o el dato no coincide. 15 fallos por IP en 15 min; 5 fallos con celular por pedido en 24 h (con el correo se puede seguir). El número se normaliza (10001 = MC-010001). | `Shop\OrderController::track` |
| #85 | El navegador envía «purchase» solo si el pedido está pagado. Si el pago llega después, se envía por la API de conversiones con el mismo id de evento, solo si el comprador aceptó la medición. | `MetaConversions`, `META_CAPI_TOKEN` en .env |
| #74 | El Seller Center acepta precios escritos «1.200.000» o «$ 1,200,000» y los guarda como 1200000. | `Seller\ProductController` |

### Qué hay que hacer en el servidor después de publicar

1. `php artisan migrate` (lo hace la actualización con `migrate=1`).
2. El cron de hPanel ya corre `schedule:run` cada minuto: con eso se aplican las campañas y salen los recordatorios.
3. Opcional: `META_CAPI_TOKEN=` en el .env para la compra por la API de conversiones (el propietario lo pega; nunca en el chat).
4. Backoffice → Mensajes llega en **Apagado**. Pasar a **Prueba** con los correos del equipo y, cuando se revise, a **Público**.

## Ola S2

| Snippet | Qué hace ahora la plataforma | Dónde |
|---|---|---|
| #48, #49, #79 | Mi cuenta con secciones: inicio, pedidos, direcciones (hasta 10, una principal que llena el pago; la primera compra con sesión la guarda sola), mis datos, seguridad, favoritos, notificaciones (correo y WhatsApp, con el mismo registro de permisos de S1) y consultas (PQRS). «Eliminar mi cuenta» con contraseña: borra cuenta, direcciones y favoritos, retira permisos y detiene recordatorios; los pedidos se conservan sin la cuenta por obligación legal. Solo clientes. | `Shop\AccountController`, vistas `account/*` |
| #47 | Si el ingreso falla, ofrece «Crear mi cuenta con este correo» y llena el registro. El mensaje es igual exista o no la cuenta. | `AuthController::login` |
| #62 | En gracias, a quien compró como invitado en ese navegador: «Crear mi cuenta». Crea la cuenta con los datos del pedido, la enlaza y envía «Crea tu contraseña». No inicia sesión: abrir el correo prueba que es suyo. Si ya hay cuenta, invita a ingresar. | `Shop\OrderController::createAccount` |
| #87 | Pago rechazado: sin «¡Gracias!», «Pagar con otro medio» rearma el carrito y lleva al pago, y ayuda por WhatsApp. En el pago, enlace de WhatsApp con productos, total y medio elegido (sin datos personales). El invitado no pierde lo escrito si recarga (sessionStorage, nunca el documento ni el medio). | `OrderController::retry`, `resources/js/app.js` |
| #69 | Mi tienda: días hábiles de despacho, transportadoras, correo para avisos y aviso de pedido pagado (las PQRS siempre llegan). Página Seguridad en el Seller Center. | `Seller\DashboardController` |
| #14 | Ficha: «Lo que debes saber» con vendedor, envío, despacho, medios de pago de esa tienda, garantía legal, retracto y reversión con enlace a PQRS. | `shop/product` |
| #11 | Open Graph y tarjeta de X en todas las páginas; en la ficha, tipo producto, imagen, precio y disponibilidad. | `layouts/shop` |
| #7, #75 | Medición: ID de Google Ads y conversión de compra (después de «Aceptar»), y verificación de dominio de Meta (etiqueta sin rastreo, siempre visible si está llena). | `Support\Analytics`, Backoffice → Plugins |
| #68 | Backoffice → Vendedores → tienda: «Destacar» (sale primero con sello) y «Ocultar del directorio» (fuera de /tiendas, del inicio y del mapa del sitio). | `Admin\StoreController::directory` |

Después de publicar: `php artisan migrate` crea `addresses`, `favorites` y las columnas nuevas de `stores`. Los enlaces viejos `/my-account/orders`, `edit-address` y `edit-account` llevan a la sección nueva.

## P1 tras la revisión (cupones, reembolsos, búsqueda y ofertas)

| Pendiente | Qué hace ahora la plataforma | Dónde |
|---|---|---|
| Cupones (WooCommerce → Marketing → Cupones) | Backoffice → Cupones: porcentaje (con tope opcional), valor fijo o envío gratis; compra mínima, fechas, usos totales y por cliente (por correo), sin productos en oferta. Sin tienda lo asume MarketCity (la comisión va sobre el valor de lista y el neto del vendedor no cambia). Con tienda solo vale para sus productos, baja la base de la comisión y sale del neto de esa tienda. Un cupón por carrito; los pedidos cancelados liberan el uso. El tope de usos se revisa con el cupón bloqueado al crear el pedido. | `Coupon`, `CouponService`, `Admin\CouponController` |
| Reembolsos | En el pedido del Backoffice: «Registrar reembolso» total o parcial, de un subpedido o general, con motivo, referencia y quién lo asume. Sin entregar y con «devolver unidades», el subpedido se cancela. Entregado y a cargo de la tienda: se le devuelve la comisión de lo reembolsado y el resto sale de su neto; si ya estaba liquidado, queda como descuento pendiente. Correo «Te reembolsamos» al cliente. Al completar el total el pedido queda «Reembolsado». No mueve dinero: la devolución se hace en la pasarela o el banco. | `RefundService`, `Admin\OrderController::refund` |
| Buscador con precios | «audífonos menos de 80 mil», «celular hasta $1.200.000», «tenis entre 100 y 200 mil», «reloj más de 50k», «portátil por debajo de 2 millones». Las medidas («50 pulgadas», «128 GB») no se leen como precio. Cada palabra se busca por separado en nombre, SKU, marca y categoría. | `Support\SearchQuery` |
| Ofertas relámpago: máximo por cliente | Campañas → «Máximo por cliente en cada producto». Con sesión el carrito no deja pasar del máximo; sin sesión se revisa con el correo al pagar. La ficha muestra «máximo N por cliente». | `CampaignService::customerAllowance` |

Después de publicar: `php artisan migrate` crea `coupons` y `refunds`, y agrega columnas a `orders`, `seller_orders` y `campaigns`.

Pendiente: los 20 cupones de WooCommerce no se copian solos; se crean en Backoffice → Cupones (o se leen del admin en vivo, solo lectura, y se cargan). Liquidaciones todavía no resta los descuentos pendientes por reembolsos de subpedidos ya pagados: se ven en el pedido.

## Pendientes y decisiones

- **DECISIÓN:** juegos y premios (#28/#29), descuento por medio de pago (ELEX: Addi 5 %, Bold 10 %), y si la «Compra directa» del #87 se activa.
- **S3:** equipo de la tienda (#69), MarketTurbo (#70), mapa de cobertura (#33), simulador de cuotas (#59), rotación del inicio (#45), modo oscuro (#64), formulario de contacto (#66), segundo factor para el Backoffice.
