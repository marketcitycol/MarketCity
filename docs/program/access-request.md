# Solicitud de acceso seguro — Fase 0

**Principio:** nunca se pide ni se almacena una contraseña por chat, ticket, commit o documento. Todo acceso es nominal, temporal, de solo lectura y con fecha de caducidad. Si la plataforma no permite solo lectura, el propietario abre la sesión y comparte pantalla, o entrega exportaciones.

## 1. Procedimiento para `/admin` (wp-admin)

1. **Propósito:** inventariar versiones, plugins, configuración, roles, reportes agregados y procesos (sección 7.3 del prompt maestro).
2. **Pantallas necesarias:** Escritorio → Actualizaciones; Plugins; Apariencia → Temas; Usuarios → lista de roles (solo conteos); WooCommerce → Ajustes (pestañas General, Envío, Pagos, Cuentas, Correos, Avanzado), Estado → Informe del sistema, Analíticas (agregados), Cupones; WCFM → Ajustes, Comisiones, Membresías (sin datos individuales); Herramientas → Salud del sitio.
3. **Datos a ocultar:** nombres, correos, teléfonos, direcciones, documentos, números de pedido individuales, claves API y secretos de PSP (aparecen en ajustes de pago: no se copian ni se capturan).
4. **Mecanismo preferido (en orden):**
   a. Cuenta nominal temporal con rol personalizado de solo lectura (p. ej. plugin de roles con capacidades `read`, `view_woocommerce_reports` y sin `manage_options`/`edit_*`), MFA activo, caducidad ≤ 7 días.
   b. Si no es viable: sesión guiada en la que el propietario navega y comparte pantalla; el equipo toma notas redactadas.
   c. Exportaciones entregadas por el propietario (ver §2).
5. **Registro:** se anota en `docs/program/access-log.md` (se crea al primer acceso): fecha, persona, mecanismo, alcance, caducidad, autorizado por.
6. **Restricciones:** sin descargas de datos personales, sin cambios, sin "Guardar", sin activar/desactivar plugins, sin actualizar nada.
7. **Pedidos/clientes/pagos individuales:** no se abren salvo necesidad aprobada; se priorizan reportes agregados.
8. **Cierre:** cerrar sesión, el propietario elimina o desactiva la cuenta, registrar revocación y cualquier exposición accidental.

## 2. Accesos y exportaciones solicitadas

| ID | Qué | Formato / mecanismo | Datos personales | Por qué | Prioridad |
|---|---|---|---|---|---|
| A-01 | Informe de estado del sistema de WooCommerce | Texto copiado desde WooCommerce → Estado (sin claves) | No | Versiones WP/WC/PHP/BD, plugins, tema, cron | P0 |
| A-02 | Lista de plugins con versión, autor, estado y licencia | Captura o `wp plugin list --format=csv` por el administrador | No | Matriz de plugins, licencias | P0 |
| A-03 | Configuración de roles y conteo de usuarios por rol | Conteos agregados | No | Separación de funciones | P0 |
| A-04 | Copia de solo lectura del código propio (plugin `marketcity-envios`, child theme, mu-plugins, snippets) | ZIP con hash SHA-256 o push a este repo en rama aparte | No | Activo propio, lógica de negocio | P0 |
| A-05 | Evidencia de backups (frecuencia, destino, última restauración) | Descripción + captura del panel | No | R-003 | P0 |
| A-06 | Reportes agregados de ventas 24 meses (pedidos, GMV, por método de pago, por vendedor, por estado) | CSV agregado sin datos personales | No | Línea base de negocio | P0 |
| A-07 | Search Console: rendimiento 16 meses, cobertura, Core Web Vitals | Usuario con permiso restringido o exportación | No | SEO, rendimiento | P0 |
| A-08 | Contenido de `/estadisticas-ecommerce/` | Lectura guiada por el propietario | Verificar | Métricas existentes | P1 |
| A-09 | Contratos/condiciones de Bold, Addi, Sistecrédito (sin credenciales) | PDF o resumen firmado por el propietario | No | Flujo de dinero | P0 |
| A-10 | Reportes de liquidación de PSP y retiros de WCFM (agregados, 3 meses) | CSV agregado | No | Conciliación | P0 |
| A-11 | Proveedor de hosting, DNS, correo; titulares | Lista | No | Dependencias | P0 |
| A-12 | Esquema de base de datos (solo estructura, sin filas) | `mysqldump --no-data` ejecutado por el administrador | No | Diccionario de datos | P1 |
| A-13 | Perfil de calidad de datos (conteos, nulos, duplicados) | Consultas agregadas ejecutadas por el administrador o en copia anonimizada | No (agregado) | Calidad de datos | P1 |
| A-14 | App Android: repositorio o APK, cuenta de Play Console | Acceso de lectura | No | J-12 | P1 |
| A-15 | Plantillas de correo transaccional y proveedor SMTP | Capturas | No | Comunicaciones | P2 |
| A-16 | Métricas de WhatsApp Business (volumen, tiempos) | Exportación agregada | No | Soporte | P2 |

## 3. Contactos necesarios
Propietario de producto, responsable técnico/hosting, responsable financiero (PSP y liquidaciones), operación/soporte, asesor legal (privacidad y consumidor), contador (facturación DIAN), un vendedor tercero activo (entrevista, con autorización explícita para contactar), responsable de la app Android. **No se contacta a nadie sin autorización.**
