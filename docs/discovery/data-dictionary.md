# Diccionario de datos — borrador desde la vista pública

**Estado:** PRELIMINAR. Las entidades se infieren de campos visibles y del modelo estándar de WooCommerce/WCFM. Tablas, columnas, volúmenes y calidad reales requieren exportación o acceso de solo lectura (`docs/program/access-request.md`). Clasificación: PÚBLICO, INTERNO, PERSONAL, PERSONAL-SENSIBLE/FINANCIERO.

| Entidad | Campos observados | Origen probable (WooCommerce/WCFM) | Clasificación | Evidencia | Pendiente |
|---|---|---|---|---|---|
| Producto | nombre, slug, precio, precio regular, SKU, stock (cantidad), categoría, marca, color, imágenes, descripción, vendedor | `wp_posts` (product), `wp_postmeta` / tablas de lookup, `wp_term_relationships` | PÚBLICO | E-003, E-009 | Variaciones, atributos completos, peso/dimensiones, impuestos (IVA), costo |
| Categoría | nombre, slug (15) | `product_cat` | PÚBLICO | E-017 | Jerarquía |
| Marca | nombre (14 en filtro) | `product_brand` | PÚBLICO | E-003 | Normalización ("Genérico" = 202) |
| Tienda / vendedor | nombre, slug, logo, Instagram, política, reseñas | `wp_users` + `wp_usermeta` (WCFM), tablas `wcfm_*` | PÚBLICO / PERSONAL | E-023 | KYC, cuenta bancaria, comisión, plan |
| Solicitud de vendedor | usuario, correo, nombre, apellido, tienda, dirección, país, ciudad, depto, código postal, teléfono, CC o NIT, contraseña, aceptación T&C | Formulario WCFM Membership | PERSONAL (CC/NIT identificador) | E-004 | Dónde se almacena el documento, retención |
| Cliente | correo (registro), usuario | `wp_users`, `wp_wc_customer_lookup` | PERSONAL | E-024 | Direcciones, teléfono, cédula para facturación |
| Pedido | estados públicos Recibido/Validado/Despachado/Entregado; número de pedido | `wp_posts` (shop_order) o HPOS `wp_wc_orders` | PERSONAL / FINANCIERO | E-005 | ¿HPOS activo? volumen, estados internos reales |
| Subpedido/comisión | — | `wcfm_marketplace_orders`, retiros | FINANCIERO | I-002 | Todo |
| Pago | método | metadatos del pedido + tablas de plugins PSP | FINANCIERO | E-001 | Referencias de transacción, estados |
| Envío / guía | transportadora, número de guía | Plugin `marketcity-envios` | PERSONAL (dirección) | E-006 | Persistencia ("no guardamos tu guía" solo aplica al formulario de tracking) |
| Cupón | código "2026" anunciado | `shop_coupon` | INTERNO | E-001 | Reglas |
| Reseña / pregunta | 0 visibles | `wp_comments`, WCFM inquiries | PERSONAL | E-009, E-023 | Volumen |
| Consentimiento | aceptación tratamiento de datos, opt-in notificaciones, cookies | Desconocido | PERSONAL | E-001, E-021 | **Si se registra con fecha/versión** (R-006) |
| Lista de deseos | — | Plugin wishlist | PERSONAL | E-014 | Plugin y uso |
| Datos de app Android | favoritos, recientes, búsquedas, carrito (en dispositivo) | Local | PERSONAL | E-022 | API/backend |

## Convenciones propuestas para el modelo canónico (SUPUESTO, revisar en Fase 1)
- Moneda COP en enteros (sin decimales), zona horaria America/Bogota en origen, UTC en almacenamiento (E-013 confirma `America/Bogota`).
- Conservar `legacy_id` de WooCommerce para producto, pedido, cliente, vendedor y cupón.
