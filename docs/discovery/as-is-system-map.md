# Mapa del sistema actual (as-is)

Fecha: 2026-11-12. Base: observación pública (ver `evidence-index.md`). Lo que no está marcado HECHO es inferencia o pendiente.

## 1. Contexto

```mermaid
flowchart TB
  subgraph Actores
    B[Comprador web]
    A[Comprador app Android]
    S[Vendedor / tienda]
    O[Operación MarketCity<br/>WhatsApp + wp-admin]
  end
  subgraph Plataforma["marketcity.co — WordPress (I-001)"]
    WC[WooCommerce]
    WCFM[WCFM Marketplace + Membership<br/>(I-002)]
    TH[Tema Wolmart (I-003)]
    ENV[Plugin propio marketcity-envios (I-006)]
    SEC[Hardening REST (I-007)]
  end
  subgraph Terceros
    PAY[Addi · Sistecrédito · Bold · PSE · Nequi · Daviplata]
    LOG[Transportadoras / agregadores]
    WA[WhatsApp]
    META[Meta Pixel]
    JP[Jetpack Photon CDN]
    GSC[Google Search Console]
  end
  B --> Plataforma
  A -->|API ?| Plataforma
  S --> WCFM
  O --> Plataforma
  Plataforma --> PAY
  Plataforma --> LOG
  B & S --> WA
  Plataforma --> META
  Plataforma --> JP
```

## 2. Componentes conocidos

| Componente | Estado | Evidencia | Nota |
|---|---|---|---|
| WordPress (sitemaps nativos, `?page_id=`, `/wp-json/`) | HECHO | E-002, E-013 | Versión desconocida. |
| WooCommerce | INFERENCIA alta | E-002, E-003 | Versión desconocida. |
| WCFM Marketplace | INFERENCIA alta | E-009 | Ids `wcfm_*`. |
| Tema Wolmart | INFERENCIA alta | E-009 | Licencia comercial. |
| Plugin propio `marketcity-envios` | HECHO (ruta) | E-006 | Activo propio a migrar o reimplementar. |
| Jetpack | INFERENCIA alta | E-001 | Imágenes vía `i0.wp.com`. |
| Página de estadísticas interna | HECHO | E-018 | Protegida con contraseña de página. |
| App Android | HECHO (mención) | E-022 | Fuente y backend desconocidos. |
| Hosting, base de datos, PHP, DNS, backups, correo | PENDIENTE | E-027 | Requiere acceso (access-request). |

## 3. Modelo de negocio observado

- Marketplace B2C multivendedor con MarketCity como vendedor principal (la mayoría de productos del catálogo dicen "MarketCity") y tiendas de terceros (IMPOWINNER 9, STARKTEC 6, DAVSHOP 5, davidshop 2, Alltronix 1) (E-001, E-003). **INFERENCIA:** el GMV actual depende mayoritariamente del inventario propio.
- Propuesta de valor central: "compra ahora, paga después" con Addi y Sistecrédito (E-001).
- Operación física/comercial en Funza y C.C. Sabana Plaza (E-007, E-011).
- MarketCity se declara intermediario y traslada la relación de consumo al vendedor (E-007) — requiere validación jurídica para el caso en que MarketCity es también el vendedor (Q-008).

## 4. Fronteras y dependencias críticas

1. **Dinero:** confirmación y liquidación desconocidas → bloqueo (integration-map §2).
2. **Inventario:** stock numérico por producto visible; reserva/sobreventa no observadas.
3. **Pedidos:** estados con paso manual "Validado" probable.
4. **Personas:** soporte, onboarding y PQRS dependen de WhatsApp sin trazabilidad (I-008).
5. **Licencias:** Wolmart, WCFM (Ultimate/Membership), Jetpack — no transferibles al nuevo sistema (ver `plugin-capability-matrix.md`).
6. **Conocimiento:** repositorio vacío; no hay código, runbooks ni IaC en control de MarketCity (E-028).
