# Plan del clon del sitio actual (staging de descubrimiento)

- **Autorización:** el propietario aprobó el 2026-11-13 "clonar todo tal cual como ya funciona" (Q-012).
- **Propósito:** observar sin riesgo lo que en producción no se puede tocar: checkout, pagos en modo prueba, Seller Center, backoffice, correos, cron, plugins, código propio, esquema y calidad de datos. Es un instrumento de Fase 0, **no** la nueva plataforma.
- **Entorno:** `LOCAL` / no productivo, aislado, sin acceso público.

## 1. Qué se necesita del propietario (Q-025)

Una de estas dos opciones (no se piden contraseñas por chat):
1. **Backup completo** generado por el propietario desde su hosting o plugin de respaldo (archivos `wp-content` + volcado SQL), entregado por un enlace temporal o subido a la carpeta del proyecto. Se registra su hash SHA-256 al recibirlo y el original no se modifica.
2. **Acceso SFTP/panel de solo lectura** con usuario nominal temporal, para que el agente descargue la copia. Las credenciales se cargan en un gestor de secretos, nunca en el chat ni en el repositorio.

## 2. Pasos del montaje

| # | Paso | Control |
|---|---|---|
| 1 | Recibir la copia, calcular hash y guardar el original inmutable | Registro en `evidence-index.md` |
| 2 | Levantar WordPress + base de datos + capturador de correo en contenedores aislados | Sin dominio público; acceso solo local o con usuario y contraseña |
| 3 | Cambiar la URL del sitio a la local | Evita que enlaces o redirecciones apunten a producción |
| 4 | **Neutralizar efectos externos antes del primer arranque** | Ver §3 |
| 5 | Aplicar la decisión de datos personales (§4) | Antes de que cualquier persona o agente navegue el clon |
| 6 | Bloquear indexación (`noindex`, robots `Disallow: /`) | Evita duplicar el sitio en Google |
| 7 | Prueba de humo: inicio, catálogo, producto, carrito, checkout en modo prueba, wp-admin | Evidencia fechada |
| 8 | Inventario §7.3: versiones, plugins, tema, código propio, cron, roles, esquema, perfil de datos | Actualiza los entregables de Fase 0 |

## 3. Neutralización obligatoria (nada sale del clon)

- Pasarelas (Bold, Addi, Sistecrédito, PSE/Nequi/Daviplata): borrar llaves productivas de la copia; usar modo sandbox solo si el proveedor entrega llaves de prueba, si no, dejar el método desactivado y documentarlo.
- Correo saliente: redirigido a un capturador local; ningún correo llega a clientes ni vendedores.
- WP-Cron y tareas programadas: desactivadas hasta revisarlas una por una.
- Webhooks salientes, Meta Pixel, Jetpack, Search Console, notificaciones push y WhatsApp: desconectados.
- Licencias de tema y plugins: no se reactivan en el clon con la clave de producción si la licencia lo prohíbe; se anota cada caso.

## 4. Datos personales — DECISIÓN 2026-11-13: opción B (datos reales)

**Registro:** el propietario eligió "Clon con datos reales" el 2026-11-13 (tarjeta de decisión en el hilo del proyecto). Esto sustituye, solo para este clon, `tratamiento_de_datos_personales_reales: false`. Controles obligatorios: acceso solo del propietario y del agente, cifrado en reposo, sin exportar datos personales fuera del clon, sin capturas ni notas con datos personales (los entregables solo usan agregados), log de accesos, y borrado del clon al cerrar la Fase 0.

Opciones evaluadas:

La configuración del programa tiene `tratamiento_de_datos_personales_reales: false`. Un clon "tal cual" incluye datos reales de clientes y vendedores (nombres, correos, teléfonos, direcciones, CC/NIT, pedidos). Opciones:

- **A (recomendada):** el clon se anonimiza antes de cualquier uso. Se reemplazan nombres, correos, teléfonos, direcciones y documentos por valores sintéticos consistentes; se conservan conteos, estados, montos y relaciones para medir calidad de datos y flujos. El original queda sin tocar y con acceso restringido.
- **B:** clon con datos reales. Requiere autorización explícita y registrada para tratar datos personales, acceso restringido, cifrado en reposo y borrado al terminar la Fase 0.

## 5. Herramienta lista (2026-11-13)
El entorno está en [`infra/legacy-clone/`](../../infra/legacy-clone/README.md) y se validó con un backup sintético: aislamiento de red, pasarelas desactivadas, correos capturados, `noindex`, inventario y destrucción. Falta solo la copia real (Q-025).

## 6. Cierre
Al terminar la Fase 0: borrar el clon y sus volúmenes, conservar solo el original cifrado (o devolverlo) y registrar la eliminación.
