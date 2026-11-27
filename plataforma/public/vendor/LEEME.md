# Librerías de terceros servidas desde el propio sitio (sin CDN)

- `leaflet/` — Leaflet 1.9.4 (BSD-2-Clause, ver `leaflet/LICENSE`): mapa de cobertura del inicio.
- `qrcode/qrcode.js` — qrcode-generator 1.4.4 de Kazuhiko Arase (MIT, ver la cabecera del archivo): QR de la verificación en dos pasos.

Se cargan solo en la pantalla que las usa (`loadAsset` en `resources/js/app.js`). Para actualizarlas,
copiar los archivos `dist/` del paquete npm de la versión nueva.
