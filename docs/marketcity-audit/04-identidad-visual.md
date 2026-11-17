# 04 · Identidad visual de MarketCity

- **Fecha:** 2026-11-17. **Estado:** Fase E iniciada — tokens extraídos del sitio público; falta aprobar logos oficiales y aplicar a v1.
- **Fuente:** estilos calculados y variables CSS de `https://marketcity.co/` leídos con un navegador (AUD-E-007). No se descargaron imágenes de producto.

## 1. Activos de marca encontrados (solo referencias)

| Activo | Ruta en producción | Tamaño | Nota |
|---|---|---|---|
| Logo horizontal grande | `/wp-content/uploads/2023/09/mc-largeLOGO.png` | 532×167 | PNG. Falta versión vectorial (SVG) — pedir al propietario. |
| Logo horizontal reducido | `/wp-content/uploads/2023/09/mc-largeLOGOmin.png` | 179×57 | Usado en el encabezado. |
| Isotipo / favicon | `/wp-content/uploads/2025/05/logo-1.png` (servido vía Jetpack a 32, 180 y 192 px) | — | Favicon depende de Jetpack (`i0.wp.com`). |
| Logo 2026 | `/wp-content/uploads/2026/05/mcLOGO.png` | — | Cargado de forma diferida; confirmar si es la versión vigente. |

**Decisión pendiente (D-05):** cuál es el logo oficial vigente y si existe archivo vectorial. Hasta entonces v1 no copia los PNG.

## 2. Paleta (variables `--mc-*` del sitio)

| Token propuesto | Valor | Uso observado |
|---|---|---|
| `brand` | `#0A4BFF` | Color principal (botones, enlaces, foco) |
| `brand-deep` | `#0B2A6F` | Fondos oscuros de marca |
| `brand-soft` | `#EEF3FF` | Fondos suaves |
| `credit` | `#5B43C0` | Mensajes de "paga después" / cuotas |
| `credit-soft` | `#F1EEFC` | Fondo de bloques de crédito |
| `offer` | `#C23D0B` (vivo `#FF5A1F`) | Descuentos y ofertas |
| `offer-soft` | `#FFF1EB` | Fondo de ofertas |
| `success` | `#0E7A3D` / `#E8F6EE` | Envío gratis, en stock |
| `danger` | `#C9252C` | Errores |
| `warning` | `#F5A524` | Avisos |
| `accent` (amarillo) | `#FCD116` | Fondos destacados (31 elementos en el inicio) |
| `ink` / `ink-2` / `ink-3` | `#111827` / `#4A5068` / `#6B7189` | Texto |
| `border` / `border-strong` | `#E3E6EC` / `#8A90A6` | Bordes |
| `surface` / `surface-2` / `bg` | `#FFFFFF` / `#EEF1F5` / `#F7F8FA` | Superficies |

**Inconsistencias:** conviven al menos 6 sistemas de variables (`--mc`, `--mc54`, `--mcft`, `--mcn`, `--mcce`, `--wolmart`) con azules distintos: `#0A4BFF`, `#2563EB`, `#3843D0` (Wolmart), `#0B5CFF`, `#7AA2FF`. La propuesta unifica en `brand = #0A4BFF`.

## 3. Tipografía

| Rol | Fuente | Pesos | Licencia |
|---|---|---|---|
| Texto y títulos | **Poppins** | 300, 400, 500, 600, 700 | SIL Open Font License (libre; se puede autoalojar) |
| Secundaria vista | Satoshi (126 elementos) | — | Licencia de Indian Type Foundry: **verificar** antes de usar en v1 |
| Íconos | `wolmart` (fuente de íconos del tema) | — | Del tema comercial: **no se lleva** |

**Legibilidad:** 495 elementos del inicio usan 10 px y 221 usan 12 px. En móvil está por debajo de lo recomendable; v1 usará mínimo 12 px para metadatos y 14–16 px para texto.

## 4. Forma, espacio y movimiento

- Radios: `sm 8px`, `md 12px` (el más usado), `lg 20px`, `pill 999px` (botones).
- Espaciado: escala de 4 px (`4, 8, 12, 16, 20, 24, 32, 40, 48, 64, 80, 96`).
- Sombras: 3 niveles (`shadow-1..3`).
- Duraciones: 150 / 200 / 250 ms.
- Capas: header 50, barra inferior móvil 60, hoja 80, toast 90.

## 5. Estado de v1 frente a la marca

| Aspecto | Antes (2026-11-16) | Ahora (2026-11-17, PR #4) |
|---|---|---|
| Color principal | Índigo genérico (`#4338CA`) | `#0A4BFF` (escala `brand-50…900`) |
| Acento | Ámbar `#FBBF24` | Amarillo `#FCD116` |
| Cuotas / crédito | Azul | Morado de crédito `#5B43C0` en tarjetas |
| Descuento | Rojo genérico | `#C23D0B` (5,3:1 con blanco) |
| Fuente | Del sistema | Poppins 400–700, autoalojada (`@fontsource/poppins`, OFL), solo subconjunto latino |
| Texto secundario | `slate-400` (2,6:1, no cumple AA) | `slate-500` (4,8:1) |
| Logo | Texto | Sigue en texto hasta D-05 |

Verificado con capturas locales a 375 px y 1280 px: Poppins cargada y sin desborde horizontal (se corrigió una tarjeta de categoría que desbordaba 4 px con la fuente nueva).

## 6. Próximos pasos (Fase E)
1. ~~Llevar estos tokens a `app.css`~~ (hecho). Versionarlos también como `design-tokens.json` para la app móvil.
2. Ajustar botones, tarjetas de producto, badges de cuota/oferta y encabezado.
3. Revisar contraste AA de cada par texto/fondo (ej.: blanco sobre `#0A4BFF` = 6,1:1 ✓; blanco sobre `#FF5A1F` = 3,1:1 ✗ → usar `#C23D0B` (5,3:1) para texto; blanco sobre `#5B43C0` = 7,0:1 ✓).
4. Capturas de referencia legado vs. v1 en 375 px y 1280 px y regresión visual.
