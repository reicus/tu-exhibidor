# Tu Exhibidor — Continuidad / Handoff del proyecto

**Propósito:** documento único para retomar el proyecto meses después sin perder contexto.  
**Repositorio:** https://github.com/reicus/tu-exhibidor  
**Rama:** `main`  
**Checkpoint backup:** tag `backup-2026-07-23` (23 julio 2026)  
**Commit de referencia al crear este doc:** ver `git rev-parse HEAD` / tag anterior  

> **Retomar en ~15 minutos:** leer primero [`docs/COMO-RETOMAR.md`](./COMO-RETOMAR.md).

---

## 1. Qué es este proyecto

Sitio web de **Tu Exhibidor** (fábrica chilena de exhibidores para joyería):

| Capa | URL | Tecnología | Rol |
|------|-----|------------|-----|
| Sitio estático premium | https://tuexhibidor.cl/site/ | HTML + CSS + JS | Home, catálogo visual, galería, SEO landing |
| Tienda WooCommerce | https://tuexhibidor.cl/shop/ | WP + tema Aurum Child | Productos, stock, cotización / WhatsApp |
| Panel de imágenes | https://tuexhibidor.cl/imagenes | MU-plugin `tuexhibidor-site-manager` | Cambiar fotos del sitio sin código |
| Login WP | https://tuexhibidor.cl/login | WPS Hide Login | Admin WordPress |
| Hosting | rooster.hostingplus.cl | cPanel / FTP | Producción |

**Flujo de imágenes de producto:**

```
WooCommerce (_thumbnail_id)
  → class-woocommerce-image-sync.php
  → public/images/catalog/{slug}.jpg + catalog-data.js
  → site/app.js (carrusel «Los más pedidos» vía featuredSkus)
```

Anti-loop sitio↔WC: `begin_push_to_wc()` / `end_push_to_wc()` en `class-images.php`.

---

## 2. Estado al checkpoint (julio 2026)

### Entregado y en producción

- Sitio premium en `/site/` con hero, categorías, galería, catálogo (~85 productos), «Los más pedidos»
- WooCommerce `/shop/` con logo marca, stock oculto si agotado, filtros PicsArt, WhatsApp flotante
- Panel `/imagenes` (MU-plugin v1.3.3) con pestañas: Catálogo, Hero, Home, Más pedidos, **Productos del home**, Categorías, Galería, Marca
- Sync automático WC thumbnail → catálogo estático + bump `assetVersion`
- SEO (meta, OG, canonical, schema, sitemap)
- Documentación de entrega A–D + manual completo MD/PDF
- Repo en GitHub con historial de desarrollo y scripts de deploy

### Pendiente / opcional (no bloquea operación)

| ID | Tema |
|----|------|
| R-P01 | ~31 productos WC sin foto del lote PNG jul-2026 |
| R-P02 | 7 SKUs sin match WC: TUE-S-009, TUE-RE-032/033, TUE-AR-036/039, TUE-S-037, TUE-DI-043 |
| R-P03 | Import masivo precios desde `export/catalogo-productos-precios.xlsx` |
| R-P04 | Externalizar credenciales FTP hardcodeadas en scripts → `.env` |
| R-P05 | Confirmar foto exacta categoría **aros** con el cliente |
| R-P06 | JetBackup solo si hacen falta bytes originales pre-overwrite |

---

## 3. Arquitectura de carpetas (local)

| Carpeta | Qué es |
|---------|--------|
| `site/` | Sitio estático (index.html, app.js, styles.css, site-data.js, catalog-data.js) |
| `public/images/` | Hero, catálogo, categorías, galería, marca |
| `deploy/` | Paquete WP: tema `aurum-child`, MU-plugins, `.htaccess` |
| `scripts/` | Build, sync WC, deploy FTP, backups, ImgSEO |
| `import/` | CSV catálogo, manifiestos |
| `export/` | Export operativos (imágenes en uso, manifests) |
| `docs/` | Manuales, entrega, continuidad, credenciales **locales** |
| `PhotosDrive/` | Fotos fuente (**ignorado por Git** — solo máquina local) |
| `Herramienta imagenes seo y compresor/` | Pipeline ImgSEO (Python) |

---

## 4. Credenciales (NO en GitHub)

Archivo **local** (ignorado por `.gitignore`):

```
docs/CREDENCIALES-TU-EXHIBIDOR.md
```

Ahí están FTP, WP admin, cPanel y notas de acceso.  
En documentación versionada solo se mencionan datos no secretos:

| Concepto | Valor público / no secreto |
|----------|----------------------------|
| Host FTP | `rooster.hostingplus.cl` |
| Usuario FTP | `tuexhibi` |
| Raíz web | `/public_html/` |
| Dominio | https://tuexhibidor.cl |

> **Nunca** pegar contraseñas en commits, issues, PDFs del repo ni en este archivo.

Si el archivo CREDENCIALES no está en la máquina nueva: recuperarlo de backup local seguro o regenerarlo desde el panel HostingPlus / WP (rotar claves si hubo compartición).

---

## 5. Advertencias críticas

1. **NO ejecutar a ciegas** `node scripts/sync_home_from_wp.mjs`  
   Puede **pisar** fotos subidas a mano en `/imagenes` (hero, categorías, home). Solo usarlo si producción es la fuente de verdad y hay backup.
2. **Borrar scripts one-time** del servidor tras usarlos (`deploy/one-time/*.php`).
3. Credenciales FTP aún aparecen en varios `.mjs` — no publicar el repo como público sin limpiar; ideal R-P04 → `.env`.
4. PHP 8.2 en producción: ver `docs/SEGURIDAD-Y-ACTUALIZACIONES.md` (WPBakery / WooCommerce).
5. Deploy es **manual por FTP** (scripts Node + curl); no hay CI automático.

---

## 6. Scripts clave

### npm (desde raíz del repo)

```bash
npm install                 # opcional (usa serve)
npm run preview             # http://localhost:3000/site/
npm run build:site          # Regenerar catalog-data.js + site-data.js
npm run build:hero
npm run deploy:pack
npm run pack:operativo
```

### Operación frecuente

| Acción | Comando |
|--------|---------|
| Sync catálogo WC → sitio + FTP | `node scripts/sync_all_catalog_from_wc.mjs --deploy` |
| Deploy home + fragmentos MU-plugin | `node scripts/deploy_home_static.mjs` |
| Deploy hero / site-data | `node scripts/deploy_insignia.mjs` |
| Deploy sync imágenes WC | `node scripts/deploy_wc_image_sync.mjs` |
| Verificar anti-loop hooks | `node scripts/verify_wc_image_sync_hooks.mjs` |
| Sync stock | `node scripts/sync_stock_from_wc.mjs` |
| Backup prod → carpeta local ignorada | `node scripts/pull_production_backup.mjs` |
| Limpiar metadata PicsArt | `node scripts/clean_picsart_metadata.mjs` |

Detalle completo: `docs/MANUAL-COMPLETO-TU-EXHIBIDOR.md` §15–17 y `deploy/README-DEPLOY.md`.

---

## 7. MU-plugin Site Manager

En servidor (y espejo en repo):

```
wp-content/mu-plugins/tuexhibidor-site-manager.php
wp-content/mu-plugins/tuexhibidor-site-manager/   (carpeta completa)
```

Local: `deploy/wp-content/mu-plugins/tuexhibidor-site-manager/`

También existe `tuexhibidor-security.php` (cabeceras, XML-RPC off, etc.).

Guard de bootstrap (`7a57123`): si falta un include, no tumba todo wp-admin.

---

## 8. Commits recientes útiles (historial)

| Commit | Tema |
|--------|------|
| `cf618fd` | Manual consolidado MD + PDF |
| `1b23e82` | Cache bump, assets deploy, limpieza import WC one-time |
| `cdf3bab` | Sync local desde pull de producción |
| `ad70285` | Copy «Envíos a todo Chile» + stat 100+ |
| `6912651` | Docs handoff A–D + gitignore CREDENCIALES |
| `c72ebc1` | Logo header WP + strip PicsArt |
| `8301a4b` | Restaurar imágenes categorías |
| `f1d50e1` / `c2c58c4` | Sync WC + pestaña Productos del home |
| `76e3ec9` | Panel admin imágenes en vivo |
| `ed8ffe2` | Commit inicial sitio premium |

Para el snapshot exacto de este backup: `git checkout backup-2026-07-23`.

---

## 9. Mapa de documentación

| Archivo | Audiencia |
|---------|-----------|
| [`COMO-RETOMAR.md`](./COMO-RETOMAR.md) | Cualquiera que retome en 15 min |
| [`CONTINUIDAD-PROYECTO.md`](./CONTINUIDAD-PROYECTO.md) | Este handoff |
| [`MANUAL-COMPLETO-TU-EXHIBIDOR.md`](./MANUAL-COMPLETO-TU-EXHIBIDOR.md) (+ PDF) | Usuario + técnico todo-en-uno |
| [`DOCUMENTACION-ENTREGA-TU-EXHIBIDOR.md`](./DOCUMENTACION-ENTREGA-TU-EXHIBIDOR.md) | Entrega A–D consolidada |
| [`A-LISTA-TECNICA-PROGRAMADOR.md`](./A-LISTA-TECNICA-PROGRAMADOR.md) | Lista técnica corta |
| [`B-REQUERIMIENTOS.md`](./B-REQUERIMIENTOS.md) | Requerimientos / pendientes |
| [`C-DOCUMENTO-COBRO.md`](./C-DOCUMENTO-COBRO.md) | Cobro interno |
| [`D-ENTREGA-CLIENTE-FINAL.md`](./D-ENTREGA-CLIENTE-FINAL.md) (+ PDF) | Guía cliente |
| [`SEGURIDAD-Y-ACTUALIZACIONES.md`](./SEGURIDAD-Y-ACTUALIZACIONES.md) | PHP 8.2, plugins |
| `CREDENCIALES-TU-EXHIBIDOR.md` | **Solo local** — no en Git |
| `../README.md` | Quick start |
| `../deploy/README-DEPLOY.md` | Deploy WP inicial |

---

## 10. Qué NO está (ni debe estar) en GitHub

- `docs/CREDENCIALES-TU-EXHIBIDOR.md` / `.pdf`
- `.env` y secretos
- `PhotosDrive/` (fotos fuente pesadas)
- `backup/`, `backup-produccion-*/`, `backup-sitio-operativo/`
- `tmp-*`, logs, capturas de verificación
- Zips/tars regenerables

El código, `site/`, `deploy/`, `scripts/`, `public/images/` (assets del sitio), `import/`, `docs/` públicos y este handoff **sí** van en el backup.

---

## 11. Contacto negocio

- WhatsApp Alfonso: +56 9 3749 0214  
- WhatsApp Leder: +56 9 9132 7813  
- info@tuexhibidor.cl  
- Sitio: https://tuexhibidor.cl  
