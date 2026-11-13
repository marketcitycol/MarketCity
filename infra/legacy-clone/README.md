# Clon de descubrimiento de marketcity.co

Copia aislada del sitio actual (WordPress/WooCommerce) para la Fase 0 (MC-DISC-001). Sirve para observar checkout, Seller Center, backoffice, plugins, cron y datos **sin tocar producción**. No es la nueva plataforma.

Plan y decisiones: [`docs/program/staging-clone-plan.md`](../../docs/program/staging-clone-plan.md). El propietario autorizó el clon con datos reales el 2026-11-13, con estos controles: acceso restringido, sin exportar datos personales, reportes solo agregados y borrado al cerrar la Fase 0.

## Cómo está aislado

| Capa | Qué hace |
|---|---|
| Red Docker `internal` | WordPress, MariaDB y Mailpit no tienen ruta a internet. Pagos, webhooks, píxeles, licencias y correos no pueden salir. |
| `clone-guard` (mu-plugin) | Bloquea `wp_remote_*`, manda todo correo a Mailpit, fuerza `noindex` y muestra un aviso rojo "CLON DE DESCUBRIMIENTO". |
| `wp-config` | `DISABLE_WP_CRON`, `WP_HTTP_BLOCK_EXTERNAL`, `DISALLOW_FILE_MODS`, sin actualizaciones automáticas, `WP_ENVIRONMENT_TYPE=staging`. |
| `neutralize.sh` | Desactiva pasarelas de pago y reemplaza llaves y secretos de sus ajustes por `REDACTED_BY_CLONE`. |
| nginx | Publica solo en `127.0.0.1`, con usuario y contraseña, `X-Robots-Tag: noindex` y `robots.txt` que bloquea todo. |

## Uso

Requisitos: Docker con Compose v2, `openssl` y `unzip`.

```bash
cd infra/legacy-clone
cp .env.example .env          # completar contraseñas (openssl rand -hex 24), TABLE_PREFIX y SOURCE_URL
scripts/make-htpasswd.sh equipo   # pide la contraseña del acceso web; no queda en git
scripts/import.sh /ruta/backup-archivos.zip /ruta/backup-base.sql.gz
# sitio:   http://localhost:8080   correos capturados: http://localhost:8025
scripts/inventory.sh          # inventario técnico §7.3, solo agregados, en data/reports/
scripts/destroy.sh            # al cerrar la Fase 0: borra contenedores, base y data/
```

El backup debe incluir `wp-content/` (plugins, temas, uploads, mu-plugins) y un volcado SQL completo. `import.sh` registra el SHA-256 de los originales en `data/reports/originals.sha256` y trabaja sobre copias.

`data/`, `.env` y `nginx/htpasswd` están en `.gitignore`: **nunca** se suben datos del clon al repositorio.

## Validación (2026-11-13, con un backup sintético, sin datos reales)

| Prueba | Resultado |
|---|---|
| Importar archivos + SQL, registrar hash, reescribir URLs | OK (6 reemplazos, `guid` intacto) |
| Pasarela de pago ficticia "Bold" con llaves | Quedó `enabled: no` y llaves `REDACTED_BY_CLONE` |
| Salida a internet desde el contenedor de WordPress | Bloqueada (no resuelve DNS) |
| `wp_remote_get` a una API de pagos | Bloqueado por `clone-guard` |
| Correo de prueba y recuperación de contraseña | Capturados en Mailpit; ninguno salió |
| Acceso sin usuario | 401 |
| `noindex` (cabecera, meta y robots.txt) | OK |
| Inventario | 13 reportes agregados generados |
| `destroy.sh` | Sin contenedores, volúmenes ni `data/` restantes |

Limitación conocida: el volumen de la base de datos no va cifrado por Docker. Si el equipo donde corre el clon no tiene cifrado de disco (BitLocker o FileVault), hay que activarlo antes de importar datos reales.
