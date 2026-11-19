# MarketCity

Plataforma propia de MarketCity: marketplace multivendedor para Colombia que reemplaza al sitio actual en WordPress + WooCommerce (marketcity.co).

**Estado (noviembre de 2026):** la versión 1 está construida y publicada en **v1.marketcity.co** (Hostinger, PHP 8.2) para pruebas. marketcity.co sigue en WordPress; el cambio de dominio necesita la orden expresa del propietario.

## Qué hay en este repositorio

| Carpeta | Qué contiene |
|---|---|
| [`plataforma/`](plataforma/) | La aplicación (Laravel 12, PHP 8.2+). Tienda, Seller Center y Backoffice. Ver su [README](plataforma/README.md). |
| [`docs/`](docs/) | Documentación: producto, arquitectura, operación y archivo histórico. Ver el [índice](docs/README.md). |
| [`infra/legacy-clone/`](infra/legacy-clone/) | Copia aislada del sitio WordPress que se usó en el descubrimiento (Fase 0). No es parte de la plataforma. |
| `.github/workflows/` | Integración continua: estilo del código y pruebas en SQLite y MySQL en cada cambio, y paquete para Hostinger desde `main`. |

## Empezar

```bash
cd plataforma
composer run setup   # dependencias, base SQLite con datos de prueba y estilos
php artisan serve    # http://localhost:8000
```

Antes de proponer un cambio:

```bash
composer lint        # estilo (Laravel Pint)
php artisan test     # pruebas
```

## Cómo se trabaja

1. Cada cambio va en una rama y llega a `main` por pull request, con las pruebas y el estilo en verde.
2. **Solo se publica en Hostinger lo que ya está en `main`.** Cada cambio en `main` arma solo el paquete de actualización (GitHub → Actions → «Paquete para Hostinger» → artefacto). También se puede armar a mano con `plataforma/deploy/build-update-hostinger.sh`. El paquete lleva el commit en el archivo `VERSION`. Guía: [docs/operacion/guia-actualizacion-hostinger.md](docs/operacion/guia-actualizacion-hostinger.md).
3. Los paquetes `.zip` no se guardan en el repositorio.
4. marketcity.co (WordPress) no se toca desde aquí.
