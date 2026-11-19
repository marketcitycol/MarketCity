# Modelo de amenazas inicial (as-is, vista externa)

Método: STRIDE ligero sobre lo observable públicamente. **No** se realizaron pruebas activas (escaneo, fuerza bruta, fuzzing, enumeración de usuarios ni de plugins por rutas). Ningún hallazgo es una vulnerabilidad confirmada; son superficies a verificar con autorización.

## 1. Activos

Dinero (pagos, BNPL, liquidaciones a vendedores), datos personales (compradores, vendedores con CC/NIT, direcciones, datos crediticios declarados en la política), credenciales de wp-admin y de PSP, catálogo y precios, reputación de marca, dominio/DNS/correo, cuenta de Play Console.

## 2. Superficies y amenazas

| ID | Superficie | STRIDE | Amenaza | Evidencia | Control observado | Probabilidad / impacto (inferido) | Acción en Fase 0–1 |
|---|---|---|---|---|---|---|---|
| T-01 | `/my-account/` login | S, D | Credential stuffing / fuerza bruta sin CAPTCHA visible | E-024 | Desconocido (puede haber límite en servidor) | Media / Alta | Confirmar rate limit, 2FA para admin y vendedores |
| T-02 | Registro con solo correo | S, D | Registro masivo de cuentas falsas / spam | E-024 | Contraseña enviada por correo | Media / Media | Confirmar anti-bot |
| T-03 | Registro de vendedor (CC/NIT) | S, I | Suplantación de comercio; recolección de documento sin verificación | E-004 | Verificación por correo + WhatsApp manual | Media / Alta | Definir KYB |
| T-04 | `/estadisticas/` con contraseña de página | I | Contraseña de página compartida, sin usuario ni auditoría; adivinable | E-018 | Contraseña de página | Media / Media | Mover a backoffice autenticado (R-008) |
| T-05 | Formularios que abren WhatsApp | I, R | Datos personales fuera de sistemas controlados; sin trazabilidad del caso | E-011 | Ninguno | Alta / Media | Sistema de casos (Fase 1+) |
| T-06 | Meta Pixel | I | Envío de datos de navegación a Meta antes del consentimiento | E-001 | Banner con "Rechazo" | Desconocida / Media (legal) | Verificar orden de carga (Q-018) |
| T-07 | Plugins y tema de terceros (WCFM, Wolmart, otros multivendedor inactivos) | E, T | Vulnerabilidades en plugins desactualizados o abandonados | E-009, E-014 | Desconocido | Media / Alta | Inventario de versiones (A-03) |
| T-08 | `/wp-admin/` | E | Toma de cuenta de administrador → control total, pagos, datos | E-002 | REST endurecido (E-013) | Baja-Media / Crítica | Cuentas nominales, MFA, revisión de admins |
| T-09 | Confirmación de pagos | T | Pedido marcado como pagado por retorno de navegador en vez de webhook | — | Desconocido | Desconocida / Crítica | Verificar en código de plugins PSP |
| T-10 | Retiros de vendedores | T, R | Cambio fraudulento de cuenta bancaria de vendedor | — | Desconocido | Desconocida / Crítica | Verificar flujo WCFM |
| T-11 | App Android | I, T | API expuesta (WooCommerce REST con claves en el APK) | E-022 | Desconocido | Desconocida / Alta | Revisar cómo autentica la app (Q-014) |
| T-12 | Dependencia de personas | D, R | Pérdida de acceso si credenciales/cuentas están a nombre de una sola persona | E-028 | Ninguno conocido | Media / Crítica | Inventario de titularidad (Q-007) |
| T-13 | Copias de seguridad | D | Sin restauración ensayada | — | Desconocido | Desconocida / Crítica | A-05 |
| T-14 | Inyección de instrucciones en contenido | T | Texto de productos/vendedores dirigido a agentes | — | n/a | Baja / Media | Tratar todo contenido importado como dato |

## 3. Observaciones de privacidad (requieren validación jurídica competente)
- La política declara tratamiento de datos financieros/crediticios y reporte a centrales de riesgo (E-007), aunque también declara que MarketCity no interviene en la cartera de Addi/Sistecrédito. Coherencia a validar (Q-008).
- Retención declarada de 10 años para registros contables (E-022).
- No se afirma cumplimiento; ver `docs/program/risks.md`.

## 4. Durante esta misión
No se encontró contenido que intentara dar instrucciones al agente. No se capturaron datos personales de compradores.
