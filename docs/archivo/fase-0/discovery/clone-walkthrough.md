# Recorrido de checkout, Seller Center y backoffice (clon aislado)

- **Fecha:** 2026-11-16
- **Dónde:** clon aislado `infra/legacy-clone` (WordPress 7.0.4, sin salida a internet, pasarelas neutralizadas). Producción no se tocó.
- **Cómo:** navegador automatizado (Playwright). Las sesiones de vendedor y administrador se abrieron con cookies firmadas generadas en el clon (sin contraseñas). Las capturas quedan dentro del clon y no se publican porque muestran datos reales. Aquí solo hay estructura y configuración (E-034).

## 1. Checkout (comprador invitado)

| Paso | Observado |
|---|---|
| Añadir al carrito | `?add-to-cart=` lleva **directo al checkout** (snippet "Skip Cart"). |
| Carrito | `/cart-2/`, con productos sugeridos y bloque de notificaciones push. |
| Checkout | `/checkout/` "Completa tu pedido". Campos: nombre, apellido, **cédula** (obligatoria, campo propio), país, dirección (2 líneas), departamento, ciudad, teléfono, correo, notas, aceptación de términos y casilla de notificaciones (`mcn_optin`). |
| Envío | Por vendedor: "Envío gratuito" o "Recogida en la tienda" con costo (10 000 COP) y dirección física del vendedor visible. |
| Pago | En el clon no aparece ningún medio, porque están neutralizados; en producción hay 11 medios (E-031). Contraentrega queda activa en la configuración, pero con condiciones que la ocultan aquí. El mensaje de "no puedes pagar con Bold" sale aunque Bold esté apagado: es texto fijo de un snippet o plugin. |
| Pedido | No se envió ningún pedido. |

## 2. Seller Center (vendedor real, sesión en el clon)

- Al entrar por primera vez, WCFM manda al **asistente de configuración de tienda** (`/?store-setup=yes`) aunque la tienda ya existe.
- Menú: DashBoard, Productos, Ordenes, Cuponera, Reclamos, Ofertar en mi Tienda, Configuracion, Transacciones, Reportes, Cerrar sesión.
- Mezcla de idiomas: varias pantallas siguen en inglés ("Store Settings", "Profile Manager", "Pending Withdrawals", "Learn more").
- `/seller-center/orders/` da 404. Las órdenes viven en otra ruta del menú, así que hay enlaces internos rotos.
- Retiros pendientes: 0 (coincide con 0 solicitudes en la base).

## 3. Backoffice (administrador, sesión en el clon)

- En el clon, **wp-admin quedaba en blanco tras ~9 s**: Google Listings & Ads se bloquea cuando no hay internet. Se desactivó solo en el clon (`neutralize.sh`). Con eso, wp-admin carga en ~3,5 s.
- El menú lateral tiene **38 entradas**: Hostinger, Elementor (dos), Wolmart, Jetpack, MetForm, Kirki, Stackable, Mercado (dos), WooCommerce, MarketCity Pay, MarketCity, Notificaciones, Embudo y calor, Análisis, WhatsApp, Ruleta Bienvenida, YITH, Fragmentos de código, Coordinadora, LiteSpeed Cache, WCFM Options y más. La operación diaria se reparte entre wp-admin y el Seller Center de WCFM, que también usa el administrador (menú con "Tienda Vendors", "capability" y "withdrawal").

### Configuración de dinero en WCFM (HECHO)

| Ajuste | Valor |
|---|---|
| Comisión | Modo porcentaje, **15 %** y un fijo de **900** COP. El ajuste "comisión para" está en **vendor**: en WCFM eso significa que el valor es lo que **recibe el vendedor**, no lo que cobra MarketCity (ver Q-031). |
| Envío e impuestos | Van al vendedor (`get_shipping`, `get_tax` = sí). |
| Cupones | Se descuentan de la comisión, también los del administrador. |
| Retiros | **Manuales**, por transferencia bancaria o efectivo; solo con pedidos en estado "completado"; sin mínimo ni cargo. |
| Vendido por | Visible en el producto. |

Esto completa la observación pedida en la compuerta (condición 4). La liquidación a 10 días hábiles (Q-005) no está automatizada en WCFM: se hace a mano.
