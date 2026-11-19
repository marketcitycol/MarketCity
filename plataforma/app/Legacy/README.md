# app/Legacy · Traslado desde WordPress

Código que solo sirve para pasar de WordPress + WooCommerce (marketcity.co) a esta plataforma.
Cuando el dominio esté cambiado y las URL viejas ya no reciban visitas, todo esto se borra junto.

| Pieza | Para qué |
|---|---|
| `WooCommerceCsvImporter` | Importa el catálogo desde el CSV que exporta WooCommerce (repetible, sin duplicar). |
| `WooOrderImporter` | Copia clientes y pedidos históricos de WooCommerce. |
| `MediaCopier` | Trae a la plataforma las fotos que todavía se enlazan a marketcity.co. |
| `UrlChecker` | Revisa que cada URL del sitemap de WordPress responda aquí (200, 301 o 410). |
| `CutoverReadiness` | Lista de sí / no para el cambio de dominio (ver `docs/operacion/cambio-de-dominio.md`). |

## Piezas fuera de esta carpeta

Laravel espera algunas cosas en lugares fijos. Al retirar el traslado, borrar también:

- Controladores: `app/Http/Controllers/Admin/Legacy/` y sus rutas en `routes/web.php` (`admin.import*`, `admin.cutover*`).
- Redirecciones: `app/Http/Middleware/LegacyRedirects.php` y su registro en `bootstrap/app.php`.
  Mantenerlo mientras Google y los enlaces guardados sigan llegando a las URL viejas.
- Comandos: `app/Console/Commands/ImportWooCommerce.php`, y `mc:copiar-fotos` y `mc:revisar-urls` en `routes/console.php`.
- Vistas: `resources/views/admin/import.blade.php`, `resources/views/admin/cutover.blade.php` y sus enlaces en `layouts/admin.blade.php`.
- Pruebas: `tests/Feature/DomainSwitchTest.php`, `tests/Feature/WooOrderImportTest.php` y la importación de `MarketplaceFlowTest`.
- Datos: las columnas `legacy_id` se quedan; sirven para rastrear cualquier registro hasta WordPress.
