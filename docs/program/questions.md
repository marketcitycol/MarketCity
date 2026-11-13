# Preguntas abiertas

Impacto: **B** bloquea compuerta de Fase 0, **A** alto (cambia dinero/datos/legal), **M** medio.

| ID | Pregunta | Impacto | Para quién | Bloquea | Fecha objetivo |
|---|---|---|---|---|---|
| Q-001 | ¿Quién es el propietario de producto, el responsable técnico y el DRI de esta misión? | B | Propietario | Asignar propietarios a capacidades y riesgos | 2026-11-19 |
| Q-002 | (PARCIAL 2026-11-13: existen `marketcity-go` y `marketcity-turbo`, pero no contienen el código del sitio WordPress) ¿Existe código propio (plugin `marketcity-envios`, child theme, snippets, app Android) en algún otro repositorio o solo en el servidor? ¿Este repo es el destino oficial? | B | Responsable técnico | Inventario técnico | 2026-11-19 |
| Q-003 | ¿Proveedor de hosting, plan, acceso SSH/SFTP/panel y quién lo administra? | B | Responsable técnico | A-01, A-05 | 2026-11-19 |
| Q-004 | ¿A nombre de quién están los contratos y cuentas de comercio de Bold, Addi, Sistecrédito, PSE/Nequi/Daviplata? ¿Qué es "MarketCityPay"? | B | Propietario / Finanzas | Flujo de dinero | 2026-11-19 |
| Q-005 | ¿El dinero de una venta de un vendedor tercero llega a MarketCity? ¿Cómo y cada cuánto se liquida, con qué comisión y retenciones? | B | Finanzas | Flujo de dinero, ledger | 2026-11-19 |
| Q-006 | ¿Cómo se registran hoy reembolsos, reversiones y contracargos? ¿Quién los aprueba? | A | Finanzas / Ops | Postventa | 2026-11-26 |
| Q-007 | ¿Quién es titular del dominio, DNS, correo `@marketcity.co`, Meta Business, Search Console y Play Console? | A | Propietario | Riesgo de dependencia | 2026-11-26 |
| Q-008 | ¿Revisión jurídica de los textos legales? (intermediario vs. vendedor directo; retracto 5 días calendario vs. hábiles; datos crediticios) | A | Asesor legal | Riesgo legal R-004 | 2026-12-03 |
| Q-009 | ¿Por qué la tienda muestra 639 productos y el sitemap ~570? | M | Catálogo | Calidad de datos | Con exportación |
| Q-010 | ¿Páginas `/home/`, `/inicio-marketcity/`, `/7736-2/`, `/flas/`, `/juegos/` y las demo de 2021 están en uso o se pueden retirar? | M | Contenido | Mapa de redirecciones | Fase 1 |
| Q-011 | ¿Cómo funciona la búsqueda por imagen (librería, modelo, licencia, se envían imágenes a terceros)? ¿Cuánto se usa? | M | Responsable técnico | C-05 | Fase 1 |
| Q-012 | ¿Se autoriza un entorno clonado (staging) con datos anonimizados para observar checkout, Seller Center y backoffice? ¿O un pedido de prueba controlado? | B | Propietario | J-03, J-04, J-09 | 2026-11-19 |
| Q-013 | ¿Qué ocurre en el estado "Validado"? ¿Validación manual por llamada/WhatsApp, antifraude, aprobación BNPL? | A | Ops | Flujo de pedido | 2026-11-26 |
| Q-014 | ¿Dónde está la app Android (código, tienda, versión, usuarios) y cómo se autentica contra el sitio? | A | Responsable técnico | J-12, T-11 | 2026-11-26 |
| Q-015 | ¿Se aprueba ejecutar un rastreo de solo lectura (1 req/s, respeta robots) y Lighthouse desde una red sin restricciones para capturar HTML, cabeceras, hashes y rendimiento? | M | Propietario | Línea base de rendimiento | 2026-11-26 |
| Q-016 | ¿DAVSHOP y davidshop son tiendas reales de terceros o internas/de prueba? | M | Marketplace Ops | Métrica de vendedores | 2026-11-26 |
| Q-017 | ¿Wishlist, comparar y blog tienen uso real? | M | Growth | C-27, C-34 | Fase 1 |
| Q-018 | ¿El Meta Pixel se carga antes de que el usuario acepte cookies? ¿Hay Conversions API? | A | Analytics / Privacy | T-06 | 2026-11-26 |
| Q-019 | ¿Hay obligaciones de facturación electrónica DIAN hoy? ¿Qué software factura y quién (MarketCity o cada vendedor)? | A | Finanzas | Integraciones, legal | 2026-11-26 |
| Q-020 | ¿Ventana de migración, presupuesto y región de nube preferida? | M | Propietario | Fase 1 (no Fase 0) | Fase 1 |
| Q-021 | ¿Cuál es la relación de portafolio entre el nuevo MarketCity, MarketCity.go, Turbo y "MarketCity-Pay"? ¿Comparten identidad, pagos, catálogo o ledger, o son productos separados? | A | Propietario | Alcance de Fase 1 | 2026-11-26 |
| Q-022 | ¿MarketCity.go está en producción con usuarios reales? ¿En qué URL y desde cuándo? | M | Propietario | Inventario de sistemas | 2026-11-26 |
| Q-023 | ¿La app Android de `/eliminar-mi-cuenta/` es la de MarketCity.go (`co.marketcity.marketcity_go`) u otra? | M | Propietario | J-12, R-010 | 2026-11-26 |
| Q-024 | ¿Pasarela principal: Bold (sitio actual) o Wompi (MarketCity.go)? ¿Hay contrato con ambas? | A | Finanzas | Flujo de dinero | 2026-11-26 |
