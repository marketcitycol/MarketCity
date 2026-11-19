# Matriz de capacidades — as-is y disposición preliminar

Disposición: `MIGRAR`, `REIMPLEMENTAR`, `MEJORAR`, `CONSERVAR_TEMPORALMENTE`, `RETIRAR_CON_APROBACION`, `FUERA_DE_ALCANCE`. Prioridad: P0 (dinero, datos, pedido, legal), P1 (crecimiento, operación), P2 (conveniencia).
**Propietario:** todo propietario de negocio está `POR DEFINIR` hasta que se nombre (Q-001). Se propone un rol responsable para cada capacidad; la persona la asigna el propietario del producto.
La disposición es **preliminar** y no es una decisión aprobada.

| ID | Capacidad | As-is | Evidencia | Prioridad | Rol propietario propuesto | Disposición | Razón |
|---|---|---|---|---|---|---|---|
| C-01 | Catálogo y PDP (precio, SKU, stock, imágenes) | WooCommerce | E-003, E-009 | P0 | Product + Catálogo | REIMPLEMENTAR (datos MIGRAR) | Núcleo del negocio |
| C-02 | Categorías y marcas | 15 cat., 14 marcas | E-003, E-017 | P1 | Catálogo | MIGRAR + MEJORAR | Limpiar `sin-categorizar`, "Genérico" |
| C-03 | Filtros y orden (precio, cuota, color, marca) | Filtro de cuota mal mapeado | E-003 | P1 | Product | MEJORAR | Defecto observado |
| C-04 | Búsqueda textual | Header/hero | E-001 | P1 | Search | REIMPLEMENTAR | Relevancia no medida |
| C-05 | Búsqueda por imagen | "En el dispositivo" | E-001 | P2 | Search | Por validar (CONSERVAR_TEMPORALMENTE en legado) | Uso y licencia desconocidos |
| C-06 | Presentación de cuotas BNPL en catálogo/PDP | Precio/4 Addi/Sistecrédito | E-009 | P0 | Payments | REIMPLEMENTAR | Propuesta de valor; reglas a confirmar con proveedores |
| C-07 | Carrito | `/cart-2/` | E-010 | P0 | Commerce | REIMPLEMENTAR | Duplicado de rutas |
| C-08 | Checkout multivendedor | No observado | — | P0 | Commerce | REIMPLEMENTAR | **Bloqueo de evidencia** |
| C-09 | Pagos Bold/PSE/Nequi/Daviplata/tarjetas | Anunciado | E-001, E-007 | P0 | Payments | REIMPLEMENTAR vía adaptadores | Contratos por confirmar |
| C-10 | BNPL Addi / Sistecrédito ("MarketCityPay") | Anunciado | E-001 | P0 | Payments | REIMPLEMENTAR vía adaptadores | Diferenciador |
| C-11 | Cupones | Cupón "2026" anunciado | E-001 | P1 | Growth | MIGRAR (reglas) | Por inventariar |
| C-12 | Ofertas relámpago con temporizador | Plantilla con vacío correcto | E-012 | P1 | Growth | REIMPLEMENTAR | Buen patrón de estado vacío |
| C-13 | Campañas (Black City) | Landing | E-021 | P2 | Growth / CMS | REIMPLEMENTAR (CMS) | — |
| C-14 | Cuenta de comprador (login/registro/recuperación) | WooCommerce, sin CAPTCHA visible | E-024 | P0 | Identity | REIMPLEMENTAR + MEJORAR | Hashes de contraseña: evaluar compatibilidad (§11 prompt) |
| C-15 | Pedidos del comprador e historial | No observado | — | P0 | Commerce | MIGRAR (histórico) | Obligación contable 10 años (E-022) |
| C-16 | Tracking de pedido por guía | Plugin propio | E-006 | P0 | Logistics | MEJORAR | Hoy redirige a la transportadora |
| C-17 | Estados de pedido (Recibido/Validado/Despachado/Entregado) | Público | E-005 | P0 | Commerce + Ops | REIMPLEMENTAR | Paso "Validado" manual a confirmar |
| C-18 | Cálculo de envío y cobertura | "32 departamentos y Bogotá" | E-009 | P0 | Logistics | REIMPLEMENTAR | Tarifas desconocidas |
| C-19 | Generación de guías | Desconocido | — | P0 | Logistics | Por descubrir | Bloqueo |
| C-20 | Retracto, garantía, reversión del pago | Texto legal; proceso por WhatsApp | E-007, E-011 | P0 | Legal + Ops | MEJORAR | Contradicción de plazos; sin radicado |
| C-21 | PQRS / soporte | Formulario → WhatsApp | E-011 | P0 | Ops | MEJORAR | Sin trazabilidad ni SLA |
| C-22 | Alta de vendedor (CC/NIT, planes) | WCFM Membership + WhatsApp | E-004 | P1 | Marketplace Ops | REIMPLEMENTAR + MEJORAR | KYC/KYB por definir |
| C-23 | Seller Center (catálogo, stock, pedidos) | WCFM | E-005 | P0 | Marketplace | REIMPLEMENTAR | Bloqueo de evidencia |
| C-24 | Comisiones, retiros y liquidación a vendedores | Desconocido | — | P0 | Finance | REIMPLEMENTAR | **Bloqueo** (flujo de dinero) |
| C-25 | Tiendas públicas y directorio | `/tienda/{slug}` OK, `/tiendas/` falla | E-020, E-023 | P1 | Marketplace | REIMPLEMENTAR | Defecto observado |
| C-26 | Reseñas y preguntas | 0 registros | E-009, E-023 | P2 | Marketplace | REIMPLEMENTAR (sin datos que migrar) | — |
| C-27 | Wishlist / comparar | Páginas existen | E-014 | P2 | Product | Por validar uso | — |
| C-28 | Contenido legal (privacidad, T&C, retracto, cookies) | Una sola página | E-007 | P0 | Legal | MEJORAR | Separar documentos y versionar |
| C-29 | Consentimiento (cookies, datos, marketing Ley 2300) | Banner + opt-in | E-001, E-021 | P0 | Privacy | MEJORAR | Registro auditable no verificado |
| C-30 | Derechos del titular / eliminación de cuenta | Manual, 15 días hábiles | E-022 | P0 | Privacy | MEJORAR | Automatizar con trazabilidad |
| C-31 | Notificaciones (correo, WhatsApp) | Desconocido | E-021 | P1 | Comms | REIMPLEMENTAR | — |
| C-32 | Analítica (Meta Pixel, GSC) | Pixel sin GA4 | E-001 | P1 | Analytics | MEJORAR | Sin medición de conversión |
| C-33 | SEO (URLs, sitemaps, OG) | Nativo WP | E-008 | P0 | SEO | MIGRAR (URLs) + MEJORAR (JSON-LD) | Proteger tráfico |
| C-34 | Blog / CMS | `/blog/` | E-014 | P2 | Content | Por validar tráfico | — |
| C-35 | Reporte interno de estadísticas | Página con contraseña | E-018 | P1 | Data | RETIRAR_CON_APROBACION + REIMPLEMENTAR en backoffice | Riesgo de exposición |
| C-36 | App Android | Existe | E-022 | P1 | Mobile | Por descubrir | Fuente desconocida |
| C-37 | Backoffice / operación | wp-admin | — | P0 | Ops | REIMPLEMENTAR | Bloqueo de evidencia |
| C-38 | Páginas demo/duplicadas | ~15 | E-014 | P2 | SEO | RETIRAR_CON_APROBACION (301) | Limpieza |
| C-39 | SaaS multitenant para comercios independientes | No existe | — | P2 | Product | FUERA_DE_ALCANCE en Fase 0–3 | Fase 8 |
