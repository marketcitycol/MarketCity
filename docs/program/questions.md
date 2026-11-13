# Preguntas abiertas

Impacto: **B** bloquea compuerta de Fase 0, **A** alto (cambia dinero/datos/legal), **M** medio.

| ID | Pregunta | Impacto | Para quién | Bloquea | Fecha objetivo |
|---|---|---|---|---|---|
| Q-001 | ~~¿Quién es el propietario de producto, el responsable técnico y el DRI?~~ **RESPONDIDA 2026-11-13:** propietario de producto y responsable técnico = el equipo propietario de MarketCity ("nosotros"); DRI operativo de la misión = el agente de ingeniería (Claude). Las aprobaciones de dinero, datos y producción siguen siendo del propietario (segregación de funciones, prompt §4.3). | — | — | — | Cerrada |
| Q-002 | ¿Existe código propio (plugin `marketcity-envios`, child theme, snippets, app Android) en algún otro repositorio o solo en el servidor? ¿Este repo es el destino oficial? | B | Responsable técnico | Inventario técnico | 2026-11-19 |
| Q-003 | ¿Proveedor de hosting, plan, acceso SSH/SFTP/panel y quién lo administra? | B | Responsable técnico | A-01, A-05 | 2026-11-19 |
| Q-004 | (PARCIAL 2026-11-13: sin respuesta sobre titularidad) ¿A nombre de quién están los contratos y cuentas de comercio de Bold, Addi, Sistecrédito, PSE/Nequi/Daviplata? ¿Qué es "MarketCityPay"? | B | Propietario / Finanzas | Flujo de dinero | 2026-11-19 |
| Q-005 | (PARCIAL 2026-11-13) **Respuesta del propietario:** al vendedor se le paga 10 días hábiles después de la venta. **Falta:** ¿"venta" = pago aprobado, despacho o entrega?; comisión; retenciones (ReteFuente/ReteIVA/ReteICA); medio de pago al vendedor; ¿qué pasa si hay retracto o devolución dentro de esos 10 días? | A | Finanzas | Ledger y liquidación | 2026-11-26 |
| Q-006 | ¿Cómo se registran hoy reembolsos, reversiones y contracargos? ¿Quién los aprueba? | A | Finanzas / Ops | Postventa | 2026-11-26 |
| Q-007 | ¿Quién es titular del dominio, DNS, correo `@marketcity.co`, Meta Business, Search Console y Play Console? | A | Propietario | Riesgo de dependencia | 2026-11-26 |
| Q-008 | ¿Revisión jurídica de los textos legales? (intermediario vs. vendedor directo; retracto 5 días calendario vs. hábiles; datos crediticios) | A | Asesor legal | Riesgo legal R-004 | 2026-12-03 |
| Q-009 | ¿Por qué la tienda muestra 639 productos y el sitemap ~570? | M | Catálogo | Calidad de datos | Con exportación |
| Q-010 | ¿Páginas `/home/`, `/inicio-marketcity/`, `/7736-2/`, `/flas/`, `/juegos/` y las demo de 2021 están en uso o se pueden retirar? | M | Contenido | Mapa de redirecciones | Fase 1 |
| Q-011 | ¿Cómo funciona la búsqueda por imagen (librería, modelo, licencia, se envían imágenes a terceros)? ¿Cuánto se usa? | M | Responsable técnico | C-05 | Fase 1 |
| Q-012 | ~~¿Se autoriza un entorno clonado?~~ **RESPONDIDA 2026-11-13:** sí, clonar todo tal como funciona hoy. Plan y condiciones en `staging-clone-plan.md`. Depende de Q-003 (acceso al hosting o a un backup). | — | — | — | Cerrada |
| Q-013 | ¿Qué ocurre en el estado "Validado"? ¿Validación manual por llamada/WhatsApp, antifraude, aprobación BNPL? | A | Ops | Flujo de pedido | 2026-11-26 |
| Q-014 | ¿Dónde está la app Android (código, tienda, versión, usuarios) y cómo se autentica contra el sitio? | A | Responsable técnico | J-12, T-11 | 2026-11-26 |
| Q-015 | ¿Se aprueba ejecutar un rastreo de solo lectura (1 req/s, respeta robots) y Lighthouse desde una red sin restricciones para capturar HTML, cabeceras, hashes y rendimiento? | M | Propietario | Línea base de rendimiento | 2026-11-26 |
| Q-016 | ¿DAVSHOP y davidshop son tiendas reales de terceros o internas/de prueba? | M | Marketplace Ops | Métrica de vendedores | 2026-11-26 |
| Q-017 | ¿Wishlist, comparar y blog tienen uso real? | M | Growth | C-27, C-34 | Fase 1 |
| Q-018 | ¿El Meta Pixel se carga antes de que el usuario acepte cookies? ¿Hay Conversions API? | A | Analytics / Privacy | T-06 | 2026-11-26 |
| Q-019 | ¿Hay obligaciones de facturación electrónica DIAN hoy? ¿Qué software factura y quién (MarketCity o cada vendedor)? | A | Finanzas | Integraciones, legal | 2026-11-26 |
| Q-020 | ¿Ventana de migración, presupuesto y región de nube preferida? | M | Propietario | Fase 1 (no Fase 0) | Fase 1 |
| Q-025 | (PARCIAL 2026-11-13: base de datos recibida; falta `wp-content` y `.htaccess`) ¿Cómo se entrega la copia para el clon: acceso de solo lectura al hosting (SFTP/panel), o un backup completo (archivos + base de datos) descargable? | B | Propietario | Clon (staging-clone-plan) | 2026-11-19 |
| Q-026 | ¿Por qué se cancelan 38 % de los pedidos (49 de 129)? ¿Rechazo de crédito, falta de stock, pedidos de prueba, cliente no contesta? | A | Propietario / Ops | Métricas de conversión | 2026-11-26 |
| Q-027 | ¿Cuáles de los 70 snippets activos marcados "vista previa" están realmente en uso en producción y cuáles se pueden apagar? | M | Propietario | Inventario de código | 2026-11-26 |
