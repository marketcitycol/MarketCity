# Guía: ajustes de v1.marketcity.co que solo puede hacer el propietario

Fecha: 2026-11-19. Todo se hace en hPanel, sin consola. **Ninguno toca marketcity.co.** Las contraseñas y llaves las escribe usted directamente en el archivo `.env`; no las envíe por chat.

Orden recomendado: 1 → 2 → 3 → 4. Los pasos 5 y 6 cuando tenga las cuentas.

Para editar el `.env`: hPanel → Archivos → Administrador de archivos → carpeta `mc-v1` → archivo `.env` (si no se ve, active «Mostrar archivos ocultos») → Editar → Guardar. Antes de editarlo, descargue una copia.

## 1. Subir la actualización (olas 1 a 6)

Siga `guia-actualizacion-hostinger.md` con el paquete **`marketcity-v1-actualizacion-sitio-….zip`** de esta carpeta. Al abrir v1 la primera vez, la página respalda la base, aplica los cambios y recarga sola.

Si después de subirlo ve «Estamos actualizando la tienda» por más de 5 minutos, algo falló y la tienda se detuvo a propósito para no trabajar con datos a medias: envíeme `mc-v1/storage/logs/actualizacion.log`.

## 2. Pasar la base de datos a MySQL

1. hPanel → **Bases de datos** → **Bases de datos MySQL**. Cree una base y un usuario (por ejemplo `mc_v1`). Anote el nombre completo de la base, el del usuario y la contraseña (Hostinger les pone un prefijo como `u123456789_`).
2. Descargue una copia de `mc-v1/database/database.sqlite` a su computador.
3. En el `.env`, cambie la línea `DB_CONNECTION=sqlite` por estas, con sus datos:
   ```
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=u123456789_mc_v1
   DB_USERNAME=u123456789_mc_v1
   DB_PASSWORD=la-contraseña-que-creó
   ```
4. En `mc-v1/storage/app/` cree dos archivos vacíos (Nuevo archivo): **`pasar-a-mysql.pendiente`** y **`actualizar.pendiente`**.
5. Abra https://v1.marketcity.co. La primera carga crea las tablas en MySQL y copia todo desde SQLite, tabla por tabla, comprobando que cuadren las filas. El archivo SQLite queda intacto como respaldo.
6. Revise en el backoffice que estén sus productos, pedidos y clientes. En `mc-v1/storage/logs/actualizacion.log` cada tabla debe decir `OK`.

**Volver atrás:** ponga otra vez `DB_CONNECTION=sqlite` en el `.env` y, si aparece, borre `mc-v1/storage/app/actualizar.fallida`.

## 3. Correo de la tienda (SMTP de Hostinger)

Sin esto, los correos de pedidos, PQRS y contraseñas no salen.

1. hPanel → **Correos** → confirme que existe el buzón `somos@marketcity.co` y que conoce su contraseña.
2. En el `.env`, reemplace las líneas que empiezan por `MAIL_` por:
   ```
   MAIL_MAILER=smtp
   MAIL_SCHEME=smtps
   MAIL_HOST=smtp.hostinger.com
   MAIL_PORT=465
   MAIL_USERNAME=somos@marketcity.co
   MAIL_PASSWORD=la-contraseña-del-buzón
   MAIL_FROM_ADDRESS="somos@marketcity.co"
   MAIL_FROM_NAME="MarketCity"
   ```
3. Pruebe en https://v1.marketcity.co/olvide-mi-contrasena con su correo: debe llegarle el enlace.

## 4. Tareas automáticas (cron)

Vencen transferencias no pagadas, consultan pagos pendientes en la pasarela y hacen un respaldo diario.

1. hPanel → **Avanzado** → **Cron Jobs** → tipo **Personalizado**.
2. Comando (cambie `u123456789` por su usuario de Hostinger, que aparece en la ruta del Administrador de archivos):
   ```
   /usr/bin/php /home/u123456789/domains/v1.marketcity.co/mc-v1/artisan schedule:run
   ```
3. Frecuencia: cada minuto (si no lo permite, cada 5 minutos).
4. A los 10 minutos, en Backoffice → **Cambio de dominio**, la línea «Tareas programadas» debe salir en verde.

## 5. Datos para pago por transferencia

Backoffice → **Configuración** → **Transferencia bancaria**: banco, tipo y número de cuenta, titular y documento. Mientras falte alguno, la transferencia no se ofrece en el checkout. Use la cuenta del titular del comercio.

## 6. Mercado Pago (cuando tenga la cuenta)

1. En Mercado Pago Developers → **Tus integraciones** → cree una aplicación «MarketCity v1» (Checkout Pro).
2. Empiece con las **credenciales de prueba**. En el `.env`:
   ```
   MP_ACCESS_TOKEN=el-access-token
   MP_WEBHOOK_SECRET=la-clave-secreta-de-webhooks
   ```
3. En la aplicación → **Webhooks**: URL `https://v1.marketcity.co/pagos/aviso/mercadopago`, evento **Pagos**. Copie la «clave secreta» que muestra en `MP_WEBHOOK_SECRET`.
4. Backoffice → **Configuración** → medios de pago: active Mercado Pago. Haga una compra de prueba con las tarjetas de prueba de Mercado Pago; el pedido debe pasar a «Pagado» solo, sin tocar nada.
5. Cuando funcione, cambie a las credenciales de producción.

## 7. Medición (opcional)

Backoffice → **Plugins** → **Medición y aviso de cookies**: pegue el ID de Google Tag Manager (`GTM-…`) o el de Google Analytics 4 (`G-…`), y el del píxel de Meta. Actívelo. Aparece el aviso de cookies y nada se mide hasta que el visitante pulsa «Aceptar».
