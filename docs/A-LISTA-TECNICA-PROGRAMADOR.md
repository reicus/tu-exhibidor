# Tu Exhibidor — Lista técnica para programador

**Cliente:** Tu Exhibidor · tuexhibidor.cl  
**Repo:** https://github.com/reicus/tu-exhibidor  
**Commit ref.:** `8301a4b` · **Fecha:** 9 julio 2026

---

## 1. Arquitectura

| Capa | Ruta | Stack |
|------|------|-------|
| Sitio estático | `/site/` | HTML, CSS, JS |
| WooCommerce | `/shop/` | WP + Aurum Child |
| Admin imágenes | `/imagenes` | MU-plugin `tuexhibidor-site-manager` v1.3.3 |
| Assets | `/public/images/` | JPG / WebP / AVIF |
| Datos | `site/site-data.js`, `site/catalog-data.js` | JS en disco |
| Deploy | `scripts/*.mjs` | Node + curl FTP |

```
WC _thumbnail_id → class-woocommerce-image-sync.php
  → public/images/catalog/{slug}.jpg + catalog-data.js
  → site/app.js resolveFeaturedProducts() ← featuredSkus
```

Anti-loop: `begin_push_to_wc()` / `end_push_to_wc()` en `class-images.php`.

---

## 2. Pedidos → soluciones

### Sitio estático
| Pedido | Solución | Archivos |
|--------|----------|----------|
| Catálogo premium | `site/` + rewrite | `index.html`, `app.js`, `styles.css` |
| ~85 productos | `catalog-data.js` + JPGs | `import/`, `build_site_data.mjs` |
| Hero / categorías / galería | Arrays en `site-data.js` | `site/site-data.js` |
| Cache bust | `assetVersion` + `withVer()` | `app.js`, `index.html` |

### Panel `/imagenes`
| Pedido | Solución | Archivos |
|--------|----------|----------|
| Edición sin código | MU-plugin + media picker | `tuexhibidor-site-manager/` |
| 8 pestañas | catalog, hero, home, featured, home-products, categories, gallery, brand | `class-admin.php` |
| Publish inmediato | write disk + bump version | `class-images.php`, `class-data.php` |

### Sync WC ↔ sitio
| Pedido | Solución | Archivos |
|--------|----------|----------|
| WC → catálogo estático | Hooks thumbnail + JPG | `class-woocommerce-image-sync.php` |
| Sync masivo | `sync_all_catalog_from_wc.mjs --deploy` | 78/85 OK |
| Auto-update home al cambiar WP | `bump_cache_version()` + hooks | commit `f1d50e1` |
| Import PNG masivo WC | one-time PHP (eliminado post-uso) | `export/wc-image-import/` |

### Stock, SEO, marca WP
| Área | Implementación |
|------|----------------|
| Ocultar agotados | `functions.php` + `class-woocommerce-stock-sync.php` + `app.js` |
| SEO | meta, OG, canonical, schema, sitemap |
| Logo header | JS reemplaza text-logo → `logo-tuexhibidor-ink-96.webp` |
| PicsArt metadata | filtros + `clean_picsart_metadata.mjs` |
| Footer / WhatsApp | `footer.php`, floating WA |

---

## 3. Comandos operativos

```bash
node scripts/sync_all_catalog_from_wc.mjs --deploy
node scripts/deploy_home_static.mjs
node scripts/deploy_insignia.mjs
node scripts/verify_wc_image_sync_hooks.mjs
node scripts/clean_picsart_metadata.mjs
# ⚠️ NO usar sin backup:
node scripts/sync_home_from_wp.mjs
```

---

## 4. Excepciones

| Item | Nota |
|------|------|
| 7 SKUs sin WC | TUE-S-009, TUE-RE-032/033, TUE-AR-036/039, TUE-S-037, TUE-DI-043 |
| ~31 WC sin foto nueva | bandejas, anillos, etc. |
| Categoría aros | re-subir en `/imagenes` si no coincide |

---

## 5. Deuda técnica

1. No ejecutar `sync_home_from_wp.mjs` sin backup — pisa uploads manuales.
2. Borrar scripts one-time del servidor tras uso.
3. Externalizar credenciales FTP de `.mjs`.
4. PHP 8.2 — ver `deploy/README-DEPLOY.md`.
