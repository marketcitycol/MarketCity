# 11 · Riesgos, bloqueos y decisiones

- **Fecha:** 2026-11-17. Los riesgos R-001…R-028 de Fase 0 siguen vigentes (`docs/program/risks.md`, PR #1). Aquí se registran los nuevos (prefijo AUD-R) y el estado de los que afectan a v1.

## 1. Riesgos nuevos

| ID | Riesgo | Prob./Impacto | Mitigación | Estado |
|---|---|---|---|---|
| AUD-R-01 | v1 se actualiza subiendo paquetes a mano: un paquete completo reinicia la base de datos (`migrate:fresh --seed`) | Alta / Alta | Paquete de **actualización** que conserva `.env` y base y corre solo migraciones nuevas (RB-01) | ABIERTO |
| AUD-R-02 | v1 usa SQLite: un solo escritor; sin respaldos del panel | Media / Media | D-01: MySQL de Hostinger | ABIERTO |
| AUD-R-03 | El pago de prueba se puede activar en Configuración; en producción permitiría "aprobar" pedidos sin pagar | Baja hoy / Crítica en producción | Bloquear `sandbox` cuando `APP_ENV=production` y `MC_DEMO=false` (MC-PAY-010) | ABIERTO |
| AUD-R-04 | Las cookies de analítica del dominio raíz (`_ga`, `_fbp`, `sbjs_*`) llegan a v1 | Media / Baja | Tenerlo en cuenta en consentimiento y pruebas | ABIERTO |
| AUD-R-05 | Errores JS masivos en la ficha del legado (484 por carga) consumen CPU en celulares | Alta / Media | Diagnóstico en Fase D; no se toca producción sin autorización | ABIERTO |
| AUD-R-06 | Registro sin verificación de correo podía exponer pedidos de invitado | — | Corregido (MC-NFR-012) | CERRADO en repo; falta desplegar |
| AUD-R-07 | Aprobación tardía revivía pedidos cancelados | — | Corregido (MC-PAY-006) | CERRADO en repo; falta desplegar |
| AUD-R-08 | Imágenes del catálogo importado se enlazan a `marketcity.co/wp-content/uploads` (dependencia del legado) | Media / Media | Copia de medios en la migración (§07) | ABIERTO |
| AUD-R-09 | Sin recuperación de contraseña ni verificación de correo en v1 | Alta / Media | Fase G, con correo transaccional (D-06) | ABIERTO |

## 2. Bloqueos (formato de la sección 17)

### B-01 · Recorrido del panel administrador actual (Fase B) — RESUELTO 2026-11-17 para el rol administrador
Pendiente solo el recorrido con un vendedor de prueba (no se crean usuarios en producción sin autorización).

1. **Qué:** inventariar cada pantalla de `marketcity.co/admin` con permisos por rol.
2. **Evidencia:** sin sesión, `/wp-login.php` redirige (login oculto por snippet); no se pueden ver pantallas.
3. **Impacto:** `01-inventario-admin.csv` tiene 32 pantallas del legado en "PENDIENTE_FASE_B".
4. **Ya verificado:** menú de 38 entradas y configuración de WCFM en el clon (Fase 0).
5. **Alternativas:** recorrer de nuevo el clon (si se reconstruye) con cookies firmadas.
6. **Acceso mínimo:** que el propietario abra `marketcity.co/admin` en el navegador de Claude e inicie sesión él mismo. Solo lectura.

### B-02 · Desplegar las correcciones en v1
1. **Qué:** publicar el PR de la Fase A en v1.marketcity.co.
2. **Evidencia:** el plan de Hostinger no tiene consola; el despliegue anterior fue con un zip subido por el propietario.
3. **Impacto:** las correcciones P0 están en el repo pero no en el sitio.
4. **Verificado:** `.env` y `storage` viven en `mc-v1` fuera de `public_html`.
5. **Alternativas:** (a) paquete de actualización que el propietario sube en el Administrador de archivos; (b) acceso SSH/Git de Hostinger si el plan lo permite (Cloud Startup sí trae SSH: confirmar).
6. **Acceso mínimo:** confirmar si se puede activar SSH en hPanel para `v1` (sin compartir contraseñas: con llave pública).

### B-03 · Llaves sandbox de pasarelas (Fase H) — se pedirán al llegar a esa fase.
### B-04 · Respaldo y restauración probados del legado (A-01/A-02/A-05) — necesario antes de cualquier migración real.
### B-05 · Lighthouse / Core Web Vitals de laboratorio (Q-015) — el entorno de nube no llega a PageSpeed. Alternativa: correr Lighthouse en el navegador del propietario o aprobar una red sin restricción.

## 3. Decisiones pendientes del propietario

| ID | Decisión | Recomendación | Cambia arquitectura |
|---|---|---|---|
| D-01 | Base de datos de v1 | MySQL de Hostinger | Sí |
| D-02 | Cobro marketplace: MarketCity cobra y liquida vs. split del proveedor | Mantener cobro centralizado + ledger mientras se valida legalmente el split | Sí |
| D-03 | Alcance de MarketCity Pay | Orquestación + ledger, sin custodia de fondos | Sí |
| D-04 | Qué histórico migrar (pedidos 2021–2026, clientes sin pedidos) | Migrar todos los pedidos (son 129) y archivar la analítica | No |
| D-05 | Logo oficial vigente y archivo vectorial | — | No |
| D-06 | Proveedor de correo transaccional | SMTP del hosting al inicio; proveedor dedicado si sube el volumen | No |
| D-07 | Qué funciones del legado no se llevan (juegos, ruleta, push, búsqueda por imagen) | Llevar solo lo que tenga uso medido | No |
