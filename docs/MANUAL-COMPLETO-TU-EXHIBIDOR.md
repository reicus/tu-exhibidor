# Tu Exhibidor — Manual completo de uso y mantenimiento

**Cliente:** Tu Exhibidor · [tuexhibidor.cl](https://tuexhibidor.cl)  
**Repositorio:** https://github.com/reicus/tu-exhibidor  
**Versión del manual:** 1.0 · **Fecha:** 9 de julio de 2026

---

Este documento reúne en un solo lugar el **manual de usuario** (para el equipo del negocio) y el **manual técnico** (para programadores y mantenimiento). Está basado en la documentación de entrega del proyecto y en el código fuente actual.

---

# Parte I — Manual de usuario

*Para el equipo Tu Exhibidor: operadores, administradores del negocio y quienes gestionan fotos y contenido sin conocimientos de programación.*

---

## 1. Introducción

Su sitio web tiene dos partes que trabajan juntas:

1. **Catálogo premium** — la vitrina visual de alto impacto donde los clientes ven exhibidores, categorías, galería y productos destacados.
2. **Tienda WooCommerce** — donde se administran productos, stock y consultas de cotización.

Además cuenta con un **panel de imágenes** para cambiar fotos del sitio premium **sin depender de un programador**.

**Tu Exhibidor** — Exhibidores de joyería hechos en Chile. Más de 20 años equipando joyerías de referencia. Fabricación a medida, despachos ágiles y atención personalizada, directo desde el taller.

---

## 2. Enlaces principales

| Qué es | Dónde entrar |
|--------|--------------|
| **Catálogo premium** (sitio visual) | https://tuexhibidor.cl/site/ |
| **Tienda WooCommerce** | https://tuexhibidor.cl/shop/ |
| **Panel para cambiar fotos** | https://tuexhibidor.cl/imagenes |
| **Login WordPress** (administrar tienda) | https://tuexhibidor.cl/login |

Guarde estos enlaces como favoritos. El panel `/imagenes` solo es visible para usuarios con sesión iniciada en WordPress.

---

## 3. Qué incluye el sitio premium

Al entrar a **tuexhibidor.cl/site/** verá:

- **Carrusel principal (Hero)** — sus mejores exhibidores en rotación.
- **Estadísticas** — más de **100+ diseños** para vitrina fina y **envíos a todo Chile**.
- **Categorías** — tarjetas por tipo de pieza: collares, pulseras, anillos, aros, bandejas, dijes, sets vitrina, etc.
- **Galería «Exhibidores en acción»** — fotos reales de exhibidores en uso.
- **Catálogo completo** — todos los productos con fotos.
- **«Los más pedidos»** — carrusel de productos destacados en el home.
- **Sección de marca** — historia, valores y catálogo PDF descargable (100+ modelos).

Textos actuales del sitio (copy de referencia):

- *«Fabricación chilena · +20 años en alta joyería»*
- *«Ecocuero premium para joyerías y casas de alto nivel. Hecho en Chile — cotización directa con quien fabrica, sin intermediarios.»*
- *«Envíos a todo Chile»*
- *«Los preferidos por joyeros en Chile»*

---

## 4. Cómo cambiar imágenes — paso a paso

### 4.1 Acceso al panel

1. Entre a https://tuexhibidor.cl/login con su usuario y contraseña de WordPress.
2. En el menú lateral izquierdo, abra **Sitio Premium**  
   — o vaya directo a https://tuexhibidor.cl/imagenes
3. Verá pestañas en la parte superior. Elija la que necesite.
4. Haga clic en **Cambiar imagen**, seleccione una foto de la biblioteca de medios de WordPress y confirme.
5. El cambio se publica **al instante** en el sitio premium.

### 4.2 Pestañas del panel `/imagenes`

| Pestaña | Para qué sirve | Qué puede cambiar |
|---------|----------------|-------------------|
| **Catálogo** | Fotos de productos del catálogo estático | La imagen de **cualquier producto** del catálogo (~85 ítems). Busque por nombre o SKU. |
| **Hero** | Carrusel principal del home | Cada slide del carrusel superior de tuexhibidor.cl/site/ |
| **Categorías** | Tarjetas de rubros en el home | Fotos de collares, pulseras, anillos, aros, bandejas, dijes, sets vitrina, etc. |
| **Más pedidos** | Productos destacados en el home | **Qué productos** aparecen en el carrusel «Los más pedidos» (no la foto en sí). Marque o desmarque los que desea mostrar. |
| **Productos del home** | Fotos de los destacados | Cambiar **solo la foto** de los productos que están en «Los más pedidos». Lista filtrada por los SKUs destacados. |
| **Exhibidores en acción** | Galería del sitio | Las ~30 fotos de la galería «Exhibidores en acción» |
| **Marca** | Identidad visual | Logo y elementos de marca del sitio |
| **Home estático** | Bloques fijos del home | Imágenes de secciones estáticas del home (banners, fondos u otros bloques configurados) |

### 4.3 Consejos por pestaña

**Catálogo**  
Use esta pestaña cuando quiera cambiar la foto de un producto específico en el catálogo del sitio premium. Si el producto también existe en WooCommerce, considere cambiar la foto allí (ver sección 5).

**Hero**  
Ideal para actualizar el carrusel principal con fotos nuevas de temporada o lanzamientos. Suba primero las fotos a **Medios** en WordPress si aún no están en la biblioteca.

**Categorías**  
Cada tarjeta de categoría en el home tiene su propia imagen. Si una categoría (por ejemplo **aros**) no se ve como espera, vuelva a subir la foto correcta desde esta pestaña.

**Más pedidos vs Productos del home**  
- **Más pedidos** → elige **cuáles** productos salen en el carrusel.  
- **Productos del home** → cambia **la foto** de esos mismos productos destacados.  
- **Catálogo** → cambia la foto de cualquier producto, esté o no destacado.

**Exhibidores en acción**  
Galería de fotos reales de clientes o del taller. Útil para mostrar exhibidores en vitrinas reales.

**Marca**  
Logo e imágenes de identidad que aparecen en el sitio premium.

**Home estático**  
Para imágenes de bloques fijos del home que no pertenecen a hero, categorías ni catálogo.

### 4.4 Si no ve el cambio de inmediato

Presione **Ctrl + F5** (Windows) o **Cmd + Shift + R** (Mac) para forzar la recarga sin caché del navegador.

El sitio usa un sistema de versión de archivos (`assetVersion`) que actualiza las URLs automáticamente al cambiar una imagen; en la mayoría de los casos el cambio es visible al instante. Si su navegador guardó una versión antigua, el refresco forzado lo corrige.

---

## 5. Regla importante: fotos de producto en WooCommerce

**Si cambia la foto de un producto en WordPress / WooCommerce, esa misma foto se actualiza sola en el sitio premium** — incluido el carrusel «Los más pedidos» si ese producto está destacado.

No necesita subir la misma foto dos veces.

| Dónde cambia la foto | Qué se actualiza |
|----------------------|------------------|
| WooCommerce (ficha del producto) | Tienda `/shop/` + catálogo estático `/site/` + carrusel si está destacado |
| Panel `/imagenes` → Catálogo o Productos del home | Solo el sitio premium (y puede sincronizarse hacia WC en operaciones técnicas) |

**Recomendación:** para productos que existen en ambos lados, use WooCommerce como referencia principal de la foto de producto.

---

## 6. Tienda WooCommerce (`/shop/`)

En https://tuexhibidor.cl/shop/ puede:

- Ver y editar productos desde WordPress (Productos → Todos los productos).
- Los productos **sin stock** se ocultan automáticamente en la tienda y en el sitio premium.
- El **logo oficial Tu Exhibidor** aparece en el encabezado (ya no solo texto plano).
- Se eliminaron **textos extraños** en descripciones (restos de apps de edición como PicsArt).
- Hay botón de **WhatsApp** flotante para consultas rápidas.
- Los precios están ocultos; el modo es **cotización por WhatsApp**.

---

## 7. Contacto y WhatsApp

| Contacto | Detalle |
|----------|---------|
| WhatsApp Alfonso | +56 9 3749 0214 |
| WhatsApp Leder | +56 9 9132 7813 |
| Correo | info@tuexhibidor.cl |
| Sitio | https://tuexhibidor.cl |

Los botones de WhatsApp en el sitio y la tienda dirigen a estos números para cotizaciones.

---

## 8. Qué NO debe hacer (sin programador)

| Acción | Por qué evitarla |
|--------|------------------|
| Ejecutar scripts de sincronización en la computadora del desarrollador (por ejemplo `sync_home_from_wp`) | Puede **sobrescribir** fotos que usted subió manualmente en `/imagenes` |
| Borrar carpetas en el servidor (`site/`, `public/images/`) | Rompe el sitio premium |
| Instalar plugins desconocidos en WordPress | Puede afectar rendimiento, seguridad y la sincronización |
| Cambiar PHP o actualizar WooCommerce sin backup | Riesgo de dejar la tienda inoperativa |
| Compartir usuario/contraseña de admin públicamente | Riesgo de seguridad |

Si necesita una operación técnica (restaurar backup, sync masivo, deploy), contacte a su equipo técnico con acceso al repositorio GitHub.

---

## 9. Pendientes conocidos (opcional al 100%)

| Pendiente | Detalle | Qué puede hacer usted |
|-----------|---------|------------------------|
| **~31 productos WooCommerce** | Aún sin foto nueva del último lote de imágenes (bandejas, anillos, etc.) | Subir la foto en WordPress → Productos → editar producto → Imagen del producto |
| **7 SKUs del catálogo antiguo** | TUE-S-009, TUE-RE-032, TUE-RE-033, TUE-AR-036, TUE-S-037, TUE-AR-039, TUE-DI-043 — sin producto equivalente en WooCommerce | Pedir al técnico que cree o vincule el producto en WC si desea sync total |
| **Categoría aros** | Posible discrepancia con la foto deseada | Re-subir en `/imagenes` → pestaña **Categorías** |

Estos pendientes **no impiden** el uso normal del sitio; el catálogo y la tienda funcionan con el resto de productos.

---

## 10. Resumen rápido para el día a día

1. **Ver el sitio:** https://tuexhibidor.cl/site/  
2. **Cambiar una foto del home o catálogo:** https://tuexhibidor.cl/imagenes  
3. **Cambiar foto de producto en la tienda:** https://tuexhibidor.cl/login → Productos  
4. **No se ve el cambio:** Ctrl + F5  
5. **Duda técnica:** contactar al desarrollador con este manual

---

# Parte II — Manual técnico

*Para programadores, mantenedores y equipo de desarrollo con acceso al repositorio y al servidor.*

---

## 11. Arquitectura general

| Capa | URL / ruta | Tecnología | Rol |
|------|------------|------------|-----|
| Sitio estático premium | `/site/` | HTML + CSS + JS vanilla | Home, catálogo visual, galería, SEO landing |
| Tienda WooCommerce | `/shop/` | WordPress + WooCommerce + tema Aurum Child | Productos, cotización, checkout deshabilitado |
| Panel de imágenes | `/imagenes` | MU-plugin PHP `tuexhibidor-site-manager` v1.3.3 | Edición en vivo de imágenes del sitio estático |
| Assets públicos | `/public/images/` | JPG / WebP / AVIF | Hero, catálogo, categorías, galería, marca |
| Datos estáticos | `site/site-data.js`, `site/catalog-data.js` | JS exportado en disco | Configuración home + catálogo sin backend propio |
| Deploy | `scripts/*.mjs` | Node.js + curl FTP | Sincronización local → producción |

**Hosting:** rooster.hostingplus.cl (cPanel / FTP)  
**Raíz web:** `/public_html/`

### Estructura del repositorio

| Carpeta | Descripción |
|---------|-------------|
| `site/` | Sitio estático (HTML, CSS, JS, datos catálogo) |
| `public/images/` | Imágenes hero, galería, catálogo, marca |
| `scripts/` | Build, deploy, ImgSEO, sync WC |
| `import/` | CSV catálogo, manifiestos |
| `deploy/` | Paquete WordPress / WooCommerce (tema hijo, MU-plugins, .htaccess) |
| `export/` | Catálogo exportado, imágenes en uso, respaldos |

### Diagrama de flujo — imágenes de producto

```
WooCommerce (_thumbnail_id)
    ↓ hooks en class-woocommerce-image-sync.php
public/images/catalog/{slug}.jpg + catalog-data.js
    ↓ resolveFeaturedProducts() en site/app.js
Carrusel «Los más pedidos» (featuredSkus en site-data.js)
```

**Prevención de bucles:** `begin_push_to_wc()` / `end_push_to_wc()` en `class-images.php` evita que un cambio desde `/imagenes` dispare reverse-sync en cadena.

### Cache bust

- `assetVersion` en `site-data.js` + query `?v=` vía `withVer()` en `site/app.js`
- `Tuexhibidor_Site_Manager_Data::bump_cache_version()` incrementa versión en cada cambio de imagen

---

## 12. MU-plugin `tuexhibidor-site-manager`

### Ubicación en servidor

```
wp-content/mu-plugins/tuexhibidor-site-manager.php          (loader)
wp-content/mu-plugins/tuexhibidor-site-manager/           (carpeta completa)
```

### Archivos PHP principales

| Archivo | Responsabilidad |
|---------|-----------------|
| `bootstrap.php` | Inicialización, autoload, guards de deploy parcial |
| `includes/class-admin.php` | Panel admin, 8 pestañas, AJAX replace/save |
| `includes/class-images.php` | Escritura de imágenes en disco, push opcional a WC |
| `includes/class-data.php` | Lectura/escritura atómica de `site-data.js`, `catalog-data.js`, bump versión |
| `includes/class-paths.php` | Resolución de rutas `site/`, `public/images/` en servidor |
| `includes/class-router.php` | Rewrite `/imagenes`, redirects, admin bar links |
| `includes/class-woocommerce-image-sync.php` | WC thumbnail → JPG catálogo estático |
| `includes/class-woocommerce-stock-sync.php` | Sync stock WC → catálogo estático |
| `includes/class-woocommerce-sync.php` | Sync general productos WC |
| `includes/class-category-merge.php` | Merge de categorías WC |
| `assets/admin.js`, `assets/admin.css` | UI del panel |

### Pestañas (`class-admin.php`)

| Key interno | Etiqueta UI | Render |
|-------------|-------------|--------|
| `catalog` | Catálogo | `render_catalog_tab()` |
| `hero` | Hero | `render_hero_tab()` |
| `home` | Home estático | `render_home_tab()` |
| `featured` | Más pedidos | `render_featured_tab()` |
| `home-products` | Productos del home | `render_home_products_tab()` |
| `categories` | Categorías | `render_categories_tab()` |
| `gallery` | Exhibidores en acción | `render_gallery_tab()` |
| `brand` | Marca | `render_brand_tab()` |

### Endpoints AJAX

| Action | Uso |
|--------|-----|
| `tuex_sm_replace_image` | Reemplazar imagen (cualquier pestaña) |
| `tuex_sm_save_alt` | Guardar texto alternativo |
| `tuex_sm_save_featured` | Guardar lista `featuredSkus` |
| `tuex_sm_sync_images_from_wc` | Sync masivo WC → catálogo |
| `tuex_sm_push_covers_to_wc` | Push catálogo → WC (batch) |
| `tuex_sm_sync_stock_from_wc` | Sync stock |

Seguridad: `manage_options` + nonce `tuex_sm`.

---

## 13. Hooks — `class-woocommerce-image-sync.php`

```php
add_action( 'woocommerce_product_set_image_id', ... , 30, 2 );
add_action( 'set_post_thumbnail', ... , 30, 3 );
add_action( 'added_post_meta', ... , 30, 4 );      // _thumbnail_id
add_action( 'updated_post_meta', ... , 30, 4 );    // _thumbnail_id
add_action( 'wp_ajax_tuex_sm_sync_images_from_wc', ... );
add_action( 'wp_ajax_tuex_sm_push_covers_to_wc', ... );
add_action( 'init', 'maybe_run_bulk_once', 99 );
add_action( 'init', 'maybe_push_covers_to_wc_once', 100 );
```

**Flujo `maybe_sync()`:**  
1. Resuelve SKU y slug del producto WC.  
2. Exporta attachment a `public/images/catalog/{slug}.jpg`.  
3. Actualiza entrada en `catalog-data.js`.  
4. Llama `bump_cache_version()` para invalidar caché del navegador.

**Bulk sync:** `sync_all_from_wc()` — usado por script Node y por one-shot en admin.  
**Verificación:** `node scripts/verify_wc_image_sync_hooks.mjs`

---

## 14. Tema Aurum Child — SEO, stock, marca, fixes

Archivos: `deploy/wp-content/themes/aurum-child/functions.php`, `footer.php`, `style.css`

| Área | Implementación |
|------|----------------|
| **SEO** | `te_seo_*` — meta title, description, OG, canonical, JSON-LD schema |
| **Sitemap** | `sitemap-site.xml` vía `scripts/deploy_seo.mjs` |
| **Ocultar sin stock** | Filtros `pre_get_posts`, `woocommerce_product_is_visible`, redirect 404 + sync en `app.js` |
| **Logo header** | JS en footer reemplaza `.text-logo` por `logo-tuexhibidor-ink-96.webp` |
| **PicsArt fix** | Filtros `te_strip_picsart_*` + `scripts/clean_picsart_metadata.mjs` |
| **Footer** | `te_render_premium_footer_markup()` — elimina widgets demo WPBakery |
| **WhatsApp** | `tuexhibidor_floating_whatsapp()` |
| **Precios ocultos** | Modo cotización WhatsApp |
| **Tipografía** | Poppins en CSS |

---

## 15. Scripts de deploy y operación

### Comandos npm (package.json)

```bash
npm run build:site          # Regenerar catalog-data.js + site-data.js
npm run build:site:full     # Galería premium + build site
npm run build:hero          # 7 slides hero
npm run build:assets        # Assets del sitio
npm run deploy:pack         # Empaquetar para FTP/WordPress
npm run pack:operativo      # Backup limpio solo sitio operativo
npm run imgseo:drive        # Procesar PhotosDrive con ImgSEO
npm run replace:from-imgseo
npm run preview             # Servidor local → http://localhost:3000/site/
```

### Scripts Node — operación frecuente

| Script | Comando | Descripción |
|--------|---------|-------------|
| Sync catálogo WC → sitio + FTP | `node scripts/sync_all_catalog_from_wc.mjs --deploy` | 78/85 productos OK en sync masivo ref. |
| Deploy home estático + MU-plugin parcial | `node scripts/deploy_home_static.mjs` | site/, class-admin, class-images, etc. |
| Deploy insigne (hero, premium, site-data) | `node scripts/deploy_insignia.mjs` | Hero, premium gallery, site-data.js |
| Deploy SEO | `node scripts/deploy_seo.mjs` | Sitemap, meta assets |
| Deploy WC image sync | `node scripts/deploy_wc_image_sync.mjs` | class-woocommerce-image-sync.php |
| Deploy logo + PicsArt fix | `node scripts/deploy_logo_picsart_fix.mjs` | Logo marca + tema |
| Deploy stock hide | `node scripts/deploy_stock_hide.mjs` | Ocultar agotados |
| Verificar hooks WC | `node scripts/verify_wc_image_sync_hooks.mjs` | Test anti-loop |
| Limpiar PicsArt en DB | `node scripts/clean_picsart_metadata.mjs` | Metadata basura en post_content |
| Sync stock desde WC | `node scripts/sync_stock_from_wc.mjs` | Stock → catálogo estático |
| Bump asset version | `node scripts/bump_asset_version.mjs` | Forzar invalidación caché |
| Restaurar categorías | `node scripts/restore_category_images.mjs` | Recuperar fotos categorías |
| Backup producción → local | `node scripts/pull_production_backup.mjs` | Pull HTTPS + FTP → `backup-produccion-YYYYMMDD/` |
| Export catálogo e imágenes | `node scripts/export_catalog_and_images.mjs` | Carpeta `export/` |
| Import precios xlsx | `node scripts/import_prices_from_xlsx.mjs` | Pendiente opcional |
| Push covers a WC | `node scripts/push_catalog_covers_to_wc.mjs` | Sitio → WC portadas |

### ⚠️ Script peligroso

```bash
# NO usar sin backup confirmado — puede pisar uploads manuales de /imagenes
node scripts/sync_home_from_wp.mjs
```

### Build y pipeline imágenes

```bash
node scripts/build_site_data.mjs
node scripts/build_hero_slider.mjs
node scripts/rebuild_catalog_images.mjs
node scripts/curate_premium_gallery.mjs
node scripts/warm_image_backgrounds.mjs
node scripts/cleanup_unused_media.mjs
```

---

## 16. Patrón de deploy FTP

Los scripts usan **curl** contra el servidor FTP. Las credenciales están en los scripts locales y en `docs/CREDENCIALES-TU-EXHIBIDOR.md` (archivo **local**, ignorado por Git).

> **No incluir contraseñas en documentación versionada.** Consultar `docs/CREDENCIALES-TU-EXHIBIDOR.md` en la máquina del desarrollador.

### Parámetros comunes

| Parámetro | Valor |
|-----------|-------|
| Host FTP | `rooster.hostingplus.cl` |
| Usuario FTP | `tuexhibi` |
| Raíz web | `/public_html/` |
| URL base | `ftp://rooster.hostingplus.cl/public_html` |

### Patrón curl (ejemplo)

```bash
curl.exe -sS --ftp-create-dirs -T "ruta/local/archivo" \
  "ftp://rooster.hostingplus.cl/public_html/ruta/remota" \
  --user "USUARIO:CONTRASEÑA"
```

La contraseña se obtiene de `docs/CREDENCIALES-TU-EXHIBIDOR.md` o de variables de entorno (pendiente: externalizar a `.env`).

### Rutas remotas frecuentes

| Local | Remoto |
|-------|--------|
| `site/*` | `public_html/site/` |
| `public/images/*` | `public_html/public/images/` |
| `deploy/wp-content/mu-plugins/tuexhibidor-site-manager/` | `public_html/wp-content/mu-plugins/tuexhibidor-site-manager/` |
| `deploy/wp-content/themes/aurum-child/` | `public_html/wp-content/themes/aurum-child/` |

### Deploy inicial (referencia)

Ver `deploy/README-DEPLOY.md`:

1. Subir `wp-content/uploads/catalog/*.jpg` → `public_html/wp-content/uploads/catalog/`
2. Importar `import/catalogo-completo.csv` en WooCommerce
3. Subir `aurum-child/` y `tuexhibidor-security.php`
4. PHP 8.2 + WooCommerce actualizado — ver `docs/SEGURIDAD-Y-ACTUALIZACIONES.md`

---

## 17. Backup — `pull_production_backup.mjs`

```bash
node scripts/pull_production_backup.mjs
```

**Qué hace:**

1. Descarga vía HTTPS los archivos live de `site/` (index.html, app.js, styles.css, site-data.js, catalog-data.js).
2. Descarga vía FTP tema hijo, .htaccess y otros archivos configurados.
3. Guarda copia fechada en `backup-produccion-YYYYMMDD/` (carpeta ignorada por Git).
4. Opcionalmente actualiza el working tree local con el estado de producción.

**Cuándo usarlo:** antes de cambios arriesgados, para comparar prod vs local, o para recuperar estado conocido bueno.

---

## 18. GitHub y control de versiones

| Concepto | Valor |
|----------|-------|
| Repositorio | https://github.com/reicus/tu-exhibidor |
| Rama principal | `main` |
| Commits | Historial completo de sitio, MU-plugin, scripts, deploy |

### Flujo recomendado

1. `git pull` antes de trabajar.
2. Cambios locales → commit con mensaje descriptivo.
3. `git push` a `main` (o rama feature + PR).
4. Deploy a producción vía scripts FTP (no hay CI automático).

### Archivos sensibles (`.gitignore`)

- `docs/CREDENCIALES-TU-EXHIBIDOR.md`
- `.env`, `.env.*`
- `backup-produccion-*/`, `PhotosDrive/`
- Backups `*.tar.gz`, `*.zip`

---

## 19. Productos con estado especial

| SKU | Estado |
|-----|--------|
| TUE-S-009 | Sin match en WC — catálogo estático conserva imagen anterior |
| TUE-RE-032 | Sin match en WC |
| TUE-RE-033 | Sin match en WC |
| TUE-AR-036 | Sin match en WC |
| TUE-S-037 | Sin match en WC |
| TUE-AR-039 | Sin match en WC |
| TUE-DI-043 | Sin match en WC |
| ~31 productos WC | Sin imagen nueva en lote PNG jul-2026 |
| Categoría **aros** | Posible discrepancia — re-subir en `/imagenes` → Categorías |

---

## 20. Deuda técnica y advertencias

1. **No ejecutar `sync_home_from_wp.mjs`** sin confirmar que producción tiene el estado deseado — pisa uploads manuales de `/imagenes`.
2. **Scripts one-time en servidor** deben borrarse tras uso (seguridad). Ej.: `deploy/one-time/tuex-clean-picsart-metadata.php`.
3. **Credenciales FTP hardcodeadas** en varios `.mjs` — externalizar a `.env` (R-P04).
4. **7 SKUs** requieren producto WC vinculado o mapeo manual para sync 100%.
5. **Metadata PicsArt** puede persistir en `post_content` — revisar con SQL si reaparece.
6. **PHP 8.2** + WooCommerce actualizado — WPBakery 5.7 puede fallar; ver `docs/SEGURIDAD-Y-ACTUALIZACIONES.md`.
7. **MU-plugin guard** en bootstrap evita fatal si deploy parcial (`7a57123`).
8. **Escritura atómica** de JS datos — no interrumpir durante upload AJAX.

---

## 21. Troubleshooting común

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| Cambio de imagen no visible | Caché navegador | Ctrl+F5; verificar `assetVersion` subió en `site-data.js` |
| Panel `/imagenes` error «No se encontró site/» | Rutas incorrectas en servidor | Verificar `public_html/site/` y `public_html/public/images/` existen |
| Foto WC no llega al catálogo | Hooks desactivados o SKU sin slug | `verify_wc_image_sync_hooks.mjs`; revisar slug en `catalog-data.js` |
| Loop sync imagen sitio↔WC | Flag push activo | Verificar `begin_push_to_wc()` / `end_push_to_wc()` |
| Producto agotado sigue visible | Stock sync pendiente | `sync_stock_from_wc.mjs` o editar stock en WC |
| Logo texto en vez de imagen en `/shop/` | JS footer no cargó | Redeploy `aurum-child/functions.php` + assets marca |
| Textos PicsArt en descripción | Metadata en DB | `clean_picsart_metadata.mjs` + filtros tema |
| Pantalla blanca tras PHP 8.2 | WooCommerce/WPBakery viejos | Backup JetBackup; actualizar WC antes de PHP |
| 404 en producto agotado | Comportamiento esperado | `woocommerce_product_is_visible` filter |
| JSON corrupto en site-data.js | Escritura interrumpida | Restaurar desde backup; revisar permisos escritura |
| FTP upload falla | Credenciales o ruta | Verificar en `docs/CREDENCIALES-TU-EXHIBIDOR.md`; probar curl manual |
| 7 productos sin sync WC | SKU huérfano | Crear producto WC o mapeo manual en `catalog-data.js` |

---

## 22. Seguridad (resumen)

- MU-plugin `tuexhibidor-security.php` — cabeceras HSTS, X-Frame-Options, ocultar versión WP, desactivar XML-RPC.
- Login oculto vía WPS Hide Login → `/login`.
- `DISALLOW_FILE_EDIT` recomendado en `wp-config.php`.
- Rotar contraseñas si backup fue compartido.
- Detalle completo: `docs/SEGURIDAD-Y-ACTUALIZACIONES.md`.

---

## 23. Documentación relacionada

| Archivo | Contenido |
|---------|-----------|
| `docs/DOCUMENTACION-ENTREGA-TU-EXHIBIDOR.md` | Entrega consolidada A–D |
| `docs/A-LISTA-TECNICA-PROGRAMADOR.md` | Lista técnica resumida |
| `docs/D-ENTREGA-CLIENTE-FINAL.md` | Guía cliente no técnica |
| `docs/B-REQUERIMIENTOS.md` | Requerimientos y pendientes |
| `docs/SEGURIDAD-Y-ACTUALIZACIONES.md` | PHP 8.2, plugins, checklist |
| `docs/CREDENCIALES-TU-EXHIBIDOR.md` | **Local** — FTP, WP, accesos |
| `deploy/README-DEPLOY.md` | Deploy inicial WooCommerce |
| `README.md` | Quick start desarrollo |

---

## 24. Generación de este PDF

Si necesita regenerar el PDF desde este Markdown:

```bash
npx md-to-pdf docs/MANUAL-COMPLETO-TU-EXHIBIDOR.md
```

Alternativa con pandoc:

```bash
pandoc docs/MANUAL-COMPLETO-TU-EXHIBIDOR.md -o docs/MANUAL-COMPLETO-TU-EXHIBIDOR.pdf
```

---

**Tu Exhibidor** — Manual completo v1.0  
*Generado para handoff entre cliente, operadores y equipo de desarrollo.*  
*9 de julio de 2026*
