# Tu Exhibidor — Requerimientos

**Versión:** 1.0 · **Fecha:** 9 julio 2026

---

## Requerimientos de negocio (cliente)

| ID | Requerimiento | Prioridad | Estado |
|----|---------------|-----------|--------|
| R-C01 | Sitio premium con catálogo profesional | Alta | ✅ `/site/` |
| R-C02 | Tienda WooCommerce + cotización WhatsApp | Alta | ✅ `/shop/` |
| R-C03 | Cambiar fotos sin programador | Alta | ✅ `/imagenes` |
| R-C04 | Fotos coherentes tienda ↔ sitio | Alta | ✅ Sync auto |
| R-C05 | Ocultar sin stock | Media | ✅ |
| R-C06 | SEO básico | Media | ✅ |
| R-C07 | Galería exhibidores en acción | Media | ✅ 30 fotos |
| R-C08 | Logo marca en WP | Media | ✅ |
| R-C09 | Sin textos basura en productos | Alta | ✅ |
| R-C10 | Carrusel «Los más pedidos» editable | Media | ✅ |
| R-C11 | Hero y categorías con fotos propias | Alta | ✅ |
| R-C12 | Export respaldo catálogo/imágenes | Baja | ✅ `export/` |
| R-C13 | Código en GitHub | Media | ✅ |

---

## Requerimientos técnicos

| ID | Requerimiento | Aceptación |
|----|---------------|------------|
| R-T01 | MU-plugin seguro deploy parcial | Guard bootstrap |
| R-T02 | Escritura atómica JS datos | JSON válido post-upload |
| R-T03 | Bump `assetVersion` por cambio | `?v=` nuevo en prod |
| R-T04 | Sin loop sync imagen | `verify_wc_image_sync_hooks.mjs` |
| R-T05 | Hero responsive 400–1600 | `sources` en site-data |
| R-T06 | Filtrar productos sin imagen | `imageOk` en app.js |
| R-T07 | Deploy FTP documentado | scripts `deploy_*.mjs` |
| R-T08 | PHP 8.2 compatible | Sin fatals |
| R-T09 | Seguridad `/imagenes` | nonce + capabilities |
| R-T10 | Limpieza one-time scripts | Sin PHP público residual |

---

## Pendientes / opcionales

| ID | Descripción |
|----|-------------|
| R-P01 | ~31 productos WC sin foto lote jul-2026 |
| R-P02 | 7 SKUs huérfanos → vincular en WC |
| R-P03 | Import precios desde xlsx |
| R-P04 | FTP credentials → `.env` |
| R-P05 | Confirmar foto categoría aros |
| R-P06 | JetBackup si faltan bytes originales |
