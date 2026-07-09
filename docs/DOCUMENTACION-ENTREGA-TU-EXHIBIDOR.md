# Tu Exhibidor — Documentación de entrega del proyecto

**Cliente:** Tu Exhibidor (tuexhibidor.cl)  
**Repositorio:** https://github.com/reicus/tu-exhibidor  
**Hosting:** rooster.hostingplus.cl (cPanel / FTP)  
**Fecha de documento:** 9 de julio de 2026  
**Último commit de referencia:** `8301a4b` (rama `main`)

---

# PARTE A — Lista técnica para programador  
*(Pedidos del cliente + soluciones implementadas)*

## 1. Arquitectura general

| Capa | URL / ruta | Tecnología | Rol |
|------|------------|------------|-----|
| Sitio estático premium | `/site/` | HTML + CSS + JS vanilla | Home, catálogo visual, galería, SEO landing |
| Tienda WooCommerce | `/shop/` | WordPress + WooCommerce + tema Aurum Child | Productos, cotización, checkout deshabilitado o modo consulta |
| Panel de imágenes | `/imagenes` | MU-plugin PHP `tuexhibidor-site-manager` | Edición en vivo de imágenes del sitio estático |
| Assets públicos | `/public/images/` | JPG / WebP / AVIF | Hero, catálogo, categorías, galería, marca |
| Datos estáticos | `site/site-data.js`, `site/catalog-data.js` | JS exportado | Configuración home + catálogo sin backend propio |
| Deploy | `scripts/*.mjs` | Node.js + curl FTP | Sincronización local → producción |

**Flujo de imágenes de producto:**
```
WooCommerce (_thumbnail_id)
    ↓ hooks en class-woocommerce-image-sync.php
public/images/catalog/{slug}.jpg + catalog-data.js
    ↓ resolveFeaturedProducts() en site/app.js
Carrusel «Los más pedidos» (featuredSkus en site-data.js)
```

**Prevención de bucles:** `begin_push_to_wc()` / `end_push_to_wc()` en `class-images.php` evita que un cambio desde `/imagenes` dispare reverse-sync.

---

## 2. Pedidos del cliente y soluciones técnicas

### 2.1 Sitio premium + catálogo estático

| Pedido | Solución técnica | Archivos clave |
|--------|------------------|----------------|
| Sitio visual premium separado de WP | Carpeta `site/` servida en `/site/` con rewrite en `.htaccess` | `site/index.html`, `site/app.js`, `site/styles.css` |
| Catálogo ~85 productos con fotos | `catalog-data.js` generado desde CSV/import + imágenes en `public/images/catalog/` | `import/`, `scripts/build_site_data.mjs` |
| Carruseles hero, categorías, galería | Arrays en `site-data.js` (`hero`, `categoryImages`, `gallery`, `homeStatic`) | `site/site-data.js` |
| Cache bust en navegador | `assetVersion` en `site-data.js` + `?v=` vía `withVer()` en `app.js` | `site/app.js`, `site/index.html` |

### 2.2 Panel admin `/imagenes` (MU-plugin v1.3.3)

| Pedido | Solución técnica | Archivos clave |
|--------|------------------|----------------|
| Cambiar imágenes sin tocar código | MU-plugin con pestañas AJAX + media picker WP | `deploy/wp-content/mu-plugins/tuexhibidor-site-manager/` |
| Pestañas operativas | Catálogo, Hero, Home estático, Más pedidos, **Productos del home**, Categorías, Exhibidores en acción, Marca | `includes/class-admin.php` |
| Publicación inmediata | `class-images.php` escribe disco + actualiza JS + bump `assetVersion` | `includes/class-images.php`, `includes/class-data.php` |
| Ruta amigable `/imagenes` | Router custom | `includes/class-router.php` |

**Pestaña «Productos del home»** (commit `c2c58c4`): lista solo SKUs de `featuredSkus`; reutiliza `tuex_sm_replace_image` con `item_type=catalog`.

### 2.3 Sincronización WooCommerce ↔ sitio estático

| Pedido | Solución técnica | Archivos clave |
|--------|------------------|----------------|
| Fotos de producto en WP deben verse en `/site/` | Sync WC thumbnail → JPG catálogo | `class-woocommerce-image-sync.php` |
| Sync masivo inicial | `scripts/sync_all_catalog_from_wc.mjs --deploy` (78/85 productos) | Script Node + FTP |
| Al cambiar imagen en WP, actualizar home automáticamente | Hooks `woocommerce_product_set_image_id`, `set_post_thumbnail`, `added/updated_post_meta` + `bump_cache_version()` | `class-woocommerce-image-sync.php` (v1.3.3, commit `f1d50e1`) |
| Empujar portadas del sitio hacia WC (batch) | `ajax_push_covers_to_wc` + flag `begin_push_to_wc()` | `class-woocommerce-image-sync.php` |
| Import masivo imágenes PNG a WC | Script one-time `tuex-import-wc-product-images.php` (73 productos; script eliminado del servidor post-uso) | `export/wc-image-import/manifest.json` |

### 2.4 Ocultar productos sin stock

| Pedido | Solución técnica | Archivos clave |
|--------|------------------|----------------|
| No mostrar agotados en tienda WP | Filtros `pre_get_posts`, `woocommerce_product_is_visible`, redirect 404 | `aurum-child/functions.php`, `class-woocommerce-stock-sync.php` |
| No mostrar agotados en sitio estático | Filtro por stock en `app.js` + sync stock desde WC | `site/app.js`, `scripts/sync_stock_from_wc.mjs` |

### 2.5 SEO

| Pedido | Solución técnica | Archivos clave |
|--------|------------------|----------------|
| Meta title, description, OG | Funciones `te_seo_*` en tema hijo | `aurum-child/functions.php` |
| Canonical, schema.org, sitemap | `te_seo_canonical_url()`, JSON-LD, `sitemap-site.xml` en robots | `aurum-child/functions.php`, `scripts/deploy_seo.mjs` |
| Homepage 200 en raíz | Rewrite/reglas `.htaccess` | `deploy/.htaccess` |

### 2.6 UX / marca en WordPress

| Pedido | Solución técnica | Archivos clave |
|--------|------------------|----------------|
| Logo original Tu Exhibidor en header WP (no texto plano) | JS en footer reemplaza `.text-logo` por `logo-tuexhibidor-ink-96.webp` | `aurum-child/functions.php` |
| Tipografía Poppins | CSS en `functions.php` | `aurum-child/functions.php` |
| Eliminar textos raros PicsArt en descripciones | Filtros `te_strip_picsart_*` + script batch DB `scripts/clean_picsart_metadata.mjs` | `aurum-child/functions.php`, `deploy/one-time/tuex-clean-picsart-metadata.php` |
| Footer demo / iconos falsos | `te_render_premium_footer_markup()` + eliminación filas WPBakery | `aurum-child/footer.php`, `functions.php` |
| WhatsApp flotante | `tuexhibidor_floating_whatsapp()` | `aurum-child/functions.php` |

### 2.7 Imágenes — pipeline y correcciones

| Pedido | Solución técnica | Archivos clave |
|--------|------------------|----------------|
| Hero/categorías con `object-fit: contain` | CSS carruseles | `site/styles.css` |
| Restaurar fotos manuales hero/categorías tras overwrite | Recuperación desde `git stash@{0}` + alternativas premium gallery | Commits `f475af0`, `8301a4b` |
| Galería «Exhibidores en acción» (30 fotos legacy) | `import/legacy-gallery.json` → `public/images/gallery/` | `site-data.js` |
| Limpieza media WP huérfana | Scripts one-time + manifest | `scripts/cleanup_unused_media.mjs`, `export/wp-media-en-uso/` |
| Export operativo | Carpetas `export/imagenes-en-uso/`, `export/catalogo-productos-precios.xlsx` | `scripts/export_catalog_and_images.mjs` |

### 2.8 Deploy y operación

| Operación | Comando |
|-----------|---------|
| Sync catálogo WC → sitio + FTP | `node scripts/sync_all_catalog_from_wc.mjs --deploy` |
| Deploy home estático | `node scripts/deploy_home_static.mjs` |
| Deploy insigne (hero, premium, site-data) | `node scripts/deploy_insignia.mjs` |
| Sync home desde producción (⚠️ riesgo overwrite) | `node scripts/sync_home_from_wp.mjs` — **no usar sin backup** |
| Verificar hooks WC image sync | `node scripts/verify_wc_image_sync_hooks.mjs` |
| Limpiar metadata PicsArt en DB | `node scripts/clean_picsart_metadata.mjs` |

**Credenciales:** FTP en scripts locales (usuario `tuexhibi`); rotar contraseña y mover a `.env` no versionado.

---

## 3. Productos / ítems con estado especial

| SKU | Estado |
|-----|--------|
| TUE-S-009, TUE-RE-032, TUE-RE-033, TUE-AR-036, TUE-S-037, TUE-AR-039, TUE-DI-043 | Sin match en WC — catálogo estático conserva imagen anterior |
| ~31 productos WC | Sin imagen nueva en lote PNG jul-2026 (bandejas, anillos, etc.) |
| Categoría **aros** | Posible discrepancia vs upload manual exacto — re-subir en `/imagenes` → Categorías si aplica |

---

## 4. Deuda técnica / advertencias para el programador

1. **No ejecutar `sync_home_from_wp.mjs`** sin confirmar que producción tiene el estado deseado — puede pisar uploads manuales de `/imagenes`.
2. **Scripts one-time en servidor** deben borrarse tras uso (seguridad).
3. **Credenciales FTP** están hardcodeadas en varios `.mjs` — externalizar.
4. **7 SKUs** requieren producto WC vinculado o mapeo manual para sync completo.
5. **Metadata PicsArt** puede persistir en `post_content` de productos ya limpiados parcialmente — revisar con query SQL si reaparece.
6. **PHP 8.2** + WooCommerce actualizado — ver `deploy/README-DEPLOY.md` y parches WPBakery.

---

# PARTE B — Requerimientos (cliente y técnicos)

## B.1 Requerimientos de negocio (cliente)

| ID | Requerimiento | Prioridad | Estado |
|----|---------------|-----------|--------|
| R-C01 | Sitio web premium que muestre catálogo de exhibidores con diseño profesional | Alta | ✅ Entregado (`/site/`) |
| R-C02 | Tienda WooCommerce para consultas / cotización por WhatsApp | Alta | ✅ Entregado (`/shop/`) |
| R-C03 | Poder cambiar fotos del sitio sin programador | Alta | ✅ Panel `/imagenes` |
| R-C04 | Fotos de producto actualizadas y coherentes entre tienda y sitio | Alta | ✅ Sync automático WC → catálogo |
| R-C05 | Ocultar productos sin stock en toda la web | Media | ✅ Implementado |
| R-C06 | SEO básico (Google, redes sociales, sitemap) | Media | ✅ Implementado |
| R-C07 | Galería de exhibidores en uso real | Media | ✅ 30 imágenes legacy |
| R-C08 | Logo y marca Tu Exhibidor en WordPress | Media | ✅ Header + assets marca |
| R-C09 | Eliminar textos basura en fichas de producto WP | Alta | ✅ Filtros + limpieza DB |
| R-C10 | Carrusel «Los más pedidos» editable (productos + fotos) | Media | ✅ Pestañas Más pedidos + Productos del home |
| R-C11 | Hero y categorías con fotos propias del negocio | Alta | ✅ Restaurado / editable en `/imagenes` |
| R-C12 | Export de catálogo e imágenes para respaldo | Baja | ✅ Carpetas `export/` |
| R-C13 | Repositorio GitHub con código actualizado | Media | ✅ `github.com/reicus/tu-exhibidor` |

## B.2 Requerimientos técnicos

| ID | Requerimiento | Criterio de aceptación |
|----|---------------|------------------------|
| R-T01 | MU-plugin must-use sin romper wp-admin si deploy parcial | Guard en bootstrap (`7a57123`) |
| R-T02 | Escritura atómica de `site-data.js` / `catalog-data.js` | Sin JSON corrupto tras upload |
| R-T03 | `assetVersion` incrementa en cada cambio de imagen | URLs con `?v=` nuevo en producción |
| R-T04 | Sync WC imagen no dispara loop sitio→WC→sitio | Tests `verify_wc_image_sync_hooks.mjs` |
| R-T05 | Imágenes responsive hero (400–1600, avif/webp/jpg) | Rutas `sources` en `site-data.js` |
| R-T06 | Catálogo estático filtra productos sin imagen válida | `imageOk !== false` en `app.js` |
| R-T07 | Deploy vía FTP sin dependencia de CI | Scripts `deploy_*.mjs` documentados |
| R-T08 | Tema hijo compatible PHP 8.2 | Sin fatal errors en producción |
| R-T09 | Sanitización uploads `/imagenes` | Nonce + `manage_options` |
| R-T10 | Limpieza scripts one-time post-ejecución | Sin PHP ejecutable público residual |

## B.3 Requerimientos pendientes / opcionales

| ID | Descripción |
|----|-------------|
| R-P01 | Completar imágenes de ~31 productos WC sin foto en lote jul-2026 |
| R-P02 | Vincular 7 SKUs huérfanos en WC para sync 100% |
| R-P03 | Import masivo de precios desde `catalogo-productos-precios.xlsx` (`scripts/import_prices_from_xlsx.mjs`) |
| R-P04 | Externalizar credenciales FTP a variables de entorno |
| R-P05 | Confirmar foto manual exacta de categoría **aros** con cliente |
| R-P06 | JetBackup restore si se necesitan bytes originales pre-overwrite |

---

# PARTE C — Documento de cobro (uso interno / facturación)

## C.1 Identificación del proyecto

| Campo | Valor |
|-------|-------|
| **Proyecto** | Plataforma web Tu Exhibidor — sitio premium + WooCommerce + panel de gestión de imágenes |
| **Alcance** | Diseño, desarrollo, migración de catálogo, sincronización WP, SEO, deploy producción, correcciones iterativas |
| **Período de trabajo** | Julio 2026 (múltiples iteraciones hasta commit `8301a4b`) |
| **Entregables** | Sitio en producción, repositorio Git, documentación, scripts de operación, panel `/imagenes` |

## C.2 Desglose de trabajo facturable

| # | Módulo | Descripción del trabajo | Horas ref.* |
|---|--------|-------------------------|-------------|
| 1 | **Sitio estático premium** | Home, catálogo JS, carruseles, galería, estilos responsive, cache bust | 24–32 h |
| 2 | **MU-plugin Site Manager** | Panel `/imagenes`, 8 pestañas, AJAX, router, paths, data layer | 32–40 h |
| 3 | **Sync WooCommerce** | Imágenes, stock, categorías, hooks bidireccionales con anti-loop | 16–24 h |
| 4 | **Tema Aurum Child** | Branding, SEO, footer, WhatsApp, ocultar stock, logo, PicsArt fix | 16–20 h |
| 5 | **Pipeline imágenes** | ImgSEO, hero builder, catálogo WC import, export packs, limpieza media | 20–28 h |
| 6 | **Deploy y producción** | FTP, scripts, corrección incidentes (rollback, restore stash, sync) | 12–16 h |
| 7 | **Documentación y entrega** | README, deploy docs, este documento, handoff programador | 4–6 h |
| | **TOTAL REFERENCIA** | | **124–166 h** |

*\*Horas referenciales para cotización; ajustar según tarifa y acuerdo comercial.*

## C.3 Propuesta de valor entregada

- **Autonomía del cliente:** edición de imágenes sin desarrollador vía `/imagenes`.
- **Doble canal:** sitio marketing (`/site/`) + tienda operativa (`/shop/`) unificados visualmente.
- **Automatización:** cambio de foto en WooCommerce se refleja en catálogo y carrusel del home.
- **Código versionado:** historial completo en GitHub para mantenimiento futuro.

## C.4 Modelos de cobro sugeridos

### Opción A — Proyecto cerrado (recomendado para entrega actual)

| Concepto | Monto sugerido (CLP)* |
|----------|----------------------|
| Desarrollo e implementación (alcance Parte B completo) | A convenir según tarifa × 140 h ref. |
| Deploy y puesta en producción | Incluido |
| 30 días soporte corrección bugs críticos post-entrega | Incluido / opcional |
| **Mantenimiento mensual** (opcional) | 8–12 h/mes: actualizaciones WP, imágenes, sync |

### Opción B — Por hitos (ya ejecutados)

| Hito | Entregable | % sugerido |
|------|------------|------------|
| H1 | Sitio estático + catálogo inicial | 25% |
| H2 | Panel `/imagenes` + MU-plugin | 30% |
| H3 | Sync WC + imágenes producto + stock | 25% |
| H4 | SEO, marca WP, fixes PicsArt, restauraciones | 20% |

*\*Montos en pesos chilenos a definir con el prestador; este documento no fija precio contractual.*

## C.5 Exclusiones de alcance (no incluido salvo nuevo acuerdo)

- Creación de contenido fotográfico nuevo (sesiones de producto).
- Campañas de marketing / Google Ads.
- Rediseño completo de marca.
- Migración a otro hosting sin fee adicional.
- Carga manual de los 31 productos WC sin imagen del lote PNG.
- Soporte ilimitado fuera del período acordado.

## C.6 Condiciones de pago sugeridas

- 40% anticipo al inicio de fase (si aplica proyecto futuro).
- 40% al entregar panel `/imagenes` + sitio en producción.
- 20% a 15 días de aceptación final sin bugs críticos.

---

# PARTE D — Documento para cliente final (no técnico)

## Tu Exhibidor — Resumen de lo entregado

Estimado equipo de **Tu Exhibidor**,

Se completó la modernización de su presencia web en **tuexhibidor.cl**, integrando un sitio visual de alto impacto con su tienda WooCommerce y herramientas para que ustedes mismos puedan actualizar imágenes sin depender de un programador.

### Lo que puede hacer hoy

1. **Ver su catálogo premium** en [tuexhibidor.cl/site](https://tuexhibidor.cl/site)  
   - Carrusel principal (hero) con sus exhibidores  
   - Categorías con fotos destacadas  
   - Galería «Exhibidores en acción»  
   - Catálogo completo de productos  
   - Sección «Los más pedidos»

2. **Gestionar su tienda** en [tuexhibidor.cl/shop](https://tuexhibidor.cl/shop)  
   - Productos con fotos actualizables desde WordPress  
   - Productos sin stock ocultos automáticamente  
   - Logo oficial Tu Exhibidor en el encabezado  
   - Sin textos extraños en las descripciones (metadata de apps de edición eliminada)

3. **Cambiar imágenes usted mismo** en [tuexhibidor.cl/imagenes](https://tuexhibidor.cl/imagenes)  
   - Inicie sesión en WordPress → menú **Sitio Premium**  
   - Pestañas disponibles:
     - **Catálogo** — fotos de todos los productos  
     - **Hero** — carrusel principal  
     - **Categorías** — tarjetas de collares, pulseras, anillos, etc.  
     - **Más pedidos** — qué productos salen en el carrusel del home  
     - **Productos del home** — cambiar solo la foto de esos productos destacados  
     - **Exhibidores en acción** — galería  
     - **Marca** — logo y elementos de identidad  

4. **Actualización automática**  
   Cuando cambie la foto de un producto en WooCommerce, esa misma imagen se actualiza en el sitio premium (incluido el carrusel del home si ese producto está destacado).

### Accesos importantes

| Recurso | URL |
|---------|-----|
| Sitio premium | https://tuexhibidor.cl/site/ |
| Tienda | https://tuexhibidor.cl/shop/ |
| Panel de imágenes | https://tuexhibidor.cl/imagenes |
| Login WordPress | https://tuexhibidor.cl/login |
| Código fuente (equipo técnico) | https://github.com/reicus/tu-exhibidor |

### Recomendaciones de uso

- Después de cambiar imágenes, refresque con **Ctrl+F5** si no ve el cambio de inmediato.  
- Para cambiar **qué productos** aparecen en «Los más pedidos», use la pestaña **Más pedidos**.  
- Para cambiar **la foto** de esos productos, use **Productos del home** o **Catálogo**.  
- Las fotos de producto en WordPress son la referencia principal; el sitio premium las copia automáticamente.

### Pendientes menores (si desean completar al 100%)

- Algunos productos de la tienda aún no tienen foto nueva del último lote de imágenes (~31 ítems); se pueden subir manualmente en WordPress o enviar las fotos al equipo técnico.  
- Siete códigos de producto del catálogo antiguo no tienen equivalente en WooCommerce; se pueden crear o vincular si se desea sincronización total.

### Soporte técnico

Para mantenimiento, nuevas funciones o capacitación en el panel `/imagenes`, contacte a su desarrollador con este documento y acceso al repositorio GitHub.

---

**Documento generado para handoff entre Tu Exhibidor, cliente final y equipo de desarrollo.**  
*Versión 1.0 — 9 julio 2026*
