# Repositorios relacionados de MarketCity (revisión de solo lectura)

- **Fecha:** 2026-11-13
- **Método:** clon superficial (`--depth 1`) de solo lectura de la rama por defecto; lectura de README, docs, estructura y configuración. No se ejecutó código, no se instalaron dependencias, no se escribió en estos repositorios. Búsqueda de patrones de secretos productivos (llaves `prv_prod_`, `sk_live`, AWS, llaves privadas, keystores, `.env`): **sin coincidencias**.
- **Alcance:** responde parcialmente Q-002 ("¿existe código propio fuera del servidor?").

## 1. Resumen

| Repositorio | Qué es | Estado observado | Relación con marketcity.co (WooCommerce) |
|---|---|---|---|
| `marketcitycol/marketcity-go` | Marketplace de entregas bajo demanda tipo Rappi: cliente, comercio, repartidor, admin. API + web + app móvil Flutter (Android/iOS) | MVP funcional con pruebas; 35 ramas; último commit 2026-10-29; despliegue previsto en Render (`marketcity-go.onrender.com`); datos legales con marcadores sin completar | **Producto distinto.** No reemplaza WooCommerce, pero comparte marca, dominio de app (`co.marketcity.marketcity_go`) y capacidades reutilizables |
| `marketcitycol/marketcity-turbo` | SaaS B2B2C multitenant de orquestación de última milla (Cabify primero) con plugin para WooCommerce | Código y 10 ADR del 2026-11-07; PRD v0.1 "borrador para revisión"; sandbox de Cabify pendiente | **Producto independiente** (decisión D-01 del propio repo). Podría ser el proveedor logístico del nuevo MarketCity |

Ninguno de los dos contiene el plugin `marketcity-envios`, el child theme ni código del sitio WordPress actual. **Q-002 sigue abierta** para ese código.

## 2. marketcity-go — HECHOS relevantes

- **Stack:** Node.js 22 + TypeScript, Express 5, PostgreSQL con 73 migraciones SQL, Socket.IO, Zod, JWT, bcrypt; web React 18 + Vite; app Flutter. CI en GitHub Actions (`ci.yml`, `mobile.yml`, `backup.yml`).
- **Pagos:** Wompi (tarjeta, PSE, Nequi, Bancolombia) con webhook firmado, tabla `payment_events`, devoluciones automáticas con reintentos. **No usa Bold, Addi ni Sistecrédito**, que son los que anuncia marketcity.co.
- **Dinero:** comisión 15 % y tarifa de servicio 5 % configurables; módulo `ledger.ts`; liquidaciones.
- **Logística:** módulos propios de domicilio, despacho de repartidores, proveedores logísticos, integración con Rappi.
- **Catálogo:** importación desde WooCommerce/Shopify (`shopImport.ts`, `catalogSync.ts`) con protección SSRF (`safeFetch.ts`).
- **Seguridad documentada:** rate limits de login/registro, roles verificados en base, precios calculados en servidor, CSV sin fórmulas, Helmet/CSP.
- **Privacidad:** versiones de términos con re-aceptación, eliminación de cuenta en la app, `docs/legal-pendientes.md` con decisiones para abogado (Ley 2466 de 2025 sobre repartidores, responsabilidad de plataforma ante la SIC, RNBD, facturación electrónica).
- **Respaldos:** flujo diario cifrado definido pero **apagado** hasta configurar secretos.

## 3. marketcity-turbo — HECHOS relevantes

- **Stack:** monorepo pnpm + Turborepo, API NestJS, portal Next.js, worker, PostgreSQL 16 con **RLS por tenant**, outbox + cola, ledger, idempotencia, auditoría encadenada, SDK público, plugin WooCommerce en PHP.
- **ADR aceptadas (2026-11-07):** monolito modular, monorepo, multitenancy, autenticación, outbox, Drizzle, **AWS ECS Fargate** (sujeta a T-01), adaptadores de proveedor, dinero y ledger, observabilidad.
- **Pendientes propios:** cuenta sandbox de Cabify, cuenta AWS, dominio, correo, DIAN (`docs/EXTERNAL_DEPENDENCIES.md`).
- Su pregunta abierta D-02 ("¿qué sistema hace el checkout de MarketCity?") es la misma que resuelve este programa.

## 4. Inferencias para el programa (no son decisiones)

| ID | Inferencia | Confianza |
|---|---|---|
| I-009 | Turbo ya implementa varios principios del prompt maestro §8 (monolito modular, RLS, outbox, adaptadores, ledger, idempotencia). Es candidato fuerte de **reutilización de patrones o código** para la Fase 1. | Media |
| I-010 | MarketCity opera o planea **tres líneas de producto** (marketplace B2C en WooCommerce, MarketCity.go de entregas, Turbo SaaS logístico) y se menciona una cuarta, "MarketCity-Pay". Sin una decisión de portafolio, hay riesgo de duplicar identidad, pagos, catálogo y ledger. | Media |
| I-011 | La app Android mencionada en `/eliminar-mi-cuenta/` **podría** ser la de marketcity-go, pero el texto del sitio (favoritos y carrito en el dispositivo) no coincide del todo con el README de la app. No confirmado. | Baja |
| I-012 | Hay dos pasarelas en juego (Bold en el sitio, Wompi en marketcity-go). La Fase 1 deberá decidir si se unifica. | Media |

## 5. Lo que no se hizo
No se ejecutaron pruebas ni builds; no se revisaron todas las ramas; no se leyó el código fuente línea por línea. La calidad real del código se evaluará en Fase 1 si se decide reutilizarlo.
