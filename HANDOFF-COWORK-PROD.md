# Handoff Cowork → Claude Opus — Deploy producción Tu Exhibidor

> **Objetivo:** Publicar en **tuexhibidor.cl** todo el trabajo hecho en Cursor (sitio premium, carruseles, imágenes, paleta crema, catálogo 85 productos). Ejecutar tú mismo; no solo documentar.

---

## PROMPT PARA PEGAR EN COWORK (copiar desde aquí)

```
Eres el agente de deploy para Tu Exhibidor (tuexhibidor.cl). Workspace:

C:\Users\Lenovo\Downloads\Tu Exhibidor

Lee HANDOFF-COWORK-PROD.md completo y EJECUTA el deploy a producción. Usa Claude Opus.
Lee también SKILL.md de la herramienta ImgSEO:
Herramienta imagenes seo y compresor/image-seo-processor/image-seo-processor/SKILL.md

No crees commits git salvo que el usuario lo pida. Ejecuta comandos tú mismo.

## Prioridad deploy

1. **ImgSEO** — instalar venv, comprimir/renombrar/alt-text imágenes (catalog, premium, hero, brand)
   - SIEMPRE --no-bg (el fallback rembg es blanco y rompe la paleta crema)
   - dry-run → revisar propuesta_nombres.csv → procesar con --names-csv
2. **npm run warm:images** — fondos JPG → crema #ddd3c8
3. **npm run build:site && npm run deploy:pack**
4. Subir site/ + public/images/ + deploy/ a tuexhibidor.cl
5. WooCommerce 85 productos + tema aurum-child + PHP 8.2
6. QA visual en prod

Preview local: http://localhost:3000/site/?v=29
Cache bust: styles v29, app v24, data v22

Pregúntame credenciales FTP/cPanel si no las tienes.
Al terminar: reporte del formato al final del MD.
```

---

## Contexto del negocio

Fábrica chilena de exhibidores para joyería. Sitio premium en español chileno. Sin precios — cotización WhatsApp.
- Alfonso: +56 9 3749 0214 → wa.me/56937490214
- Leder: +56 9 9132 7813 → wa.me/56991327813
- Email: info@tuexhibidor.cl · RUT 77.036.189-3

## Lo que YA está listo en local (verificado)

Preview: npx serve . -l 3000 → http://localhost:3000/site/?v=29

### Sitio estático nuevo (`site/`)
- index.html, app.js (v24), styles.css (v29), catalog-data.js, site-data.js
- Hero 7 slides, galería 67 assets, catálogo 85 productos en 7 categorías
- Carruseles reescritos desde cero (setupCarousel) — loop infinito, sin bugs de clones/animating
- object-fit: contain en todas las imágenes (producto completo visible, sin zoom recortado)
- Paleta crema cálida unificada (NO blanco):
  --cream: #ebe3d8, --surface/--img-well: #ddd3c8, --gold: #b8935f, --ink: #2b2926
- Scrollbars estilizadas crema/dorado (no blanco del browser)
- WhatsApp flotante Alfonso/Leder
- Facebook + Instagram en contacto y footer
- Footer: Made by Tecnotix Solutions → https://tecnotix.cl
- Stats: 100% Fabricación Chile, 85+ Diseños vitrina fina

### Imágenes (`public/images/`)
- catalog/ — 85 JPG catálogo (fondos calentados a crema)
- premium/ — galería curada (~268 JPG + WebP/AVIF)
- hero/ — 7 slides × 4 tamaños
- brand/ — logo, favicon, apple-touch-icon

### Procesamiento ya corrido
- npm run warm:images — 919 JPGs con fondo blanco → crema #ddd3c8
- Scripts de build usan CREAM = #ddd3c8 (no #faf7f2)

## HERRAMIENTA IMÁGENES SEO + COMPRESOR (OBLIGATORIO EN DEPLOY)

**Ruta:** `Herramienta imagenes seo y compresor/image-seo-processor/image-seo-processor/`

Herramienta Python (GUI + CLI + Skill) para: redimensionar, comprimir WebP, renombrar SEO, generar alt text, eliminar fondos (rembg). **Úsala ANTES de subir imágenes a producción.**

### Instalación (una vez)

```powershell
cd "C:\Users\Lenovo\Downloads\Tu Exhibidor\Herramienta imagenes seo y compresor\image-seo-processor\image-seo-processor"
py -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
# Opcional IA (lento): pip install -r requirements-vision.txt
```

**Skill Cowork:** leer `SKILL.md` en esa carpeta O copiar la carpeta a `~/.claude/skills/image-seo-processor/`

### Pipeline recomendado Tu Exhibidor (en orden)

```
1. ImgSEO (compresión + nombres + alt text)
2. warm:images (npm run warm:images) — fondos crema #ddd3c8 en JPG
3. build:site / build:hero — regenerar site-data.js con alt del CSV
4. deploy:pack + subir a servidor
```

### Comandos CLI por tipo de asset

**Ruta CLI:** `...\image-seo-processor\image-seo-processor\cli.py`

```powershell
$IMGSEO = "C:\Users\Lenovo\Downloads\Tu Exhibidor\Herramienta imagenes seo y compresor\image-seo-processor\image-seo-processor"
$PY = "$IMGSEO\.venv\Scripts\python.exe"
$CLI = "$IMGSEO\cli.py"
```

#### Catálogo (85 productos) — `public/images/catalog/`
```powershell
# 1) Propuesta nombres SEO (revisar antes)
& $PY $CLI "C:\Users\Lenovo\Downloads\Tu Exhibidor\public\images\catalog" `
  --project "tuexhibidor" --use product-mockup `
  --keywords "exhibidor, joyeria, ecocuero, vitrina, chile" `
  --max-width 1200 --quality 85 --target-kb 180 `
  --no-bg --lang es --dry-run

# 2) Editar propuesta_nombres.csv (columnas nombre_nuevo, alt_text)

# 3) Procesar
& $PY $CLI "C:\Users\Lenovo\Downloads\Tu Exhibidor\public\images\catalog" `
  --names-csv "...\catalog\propuesta_nombres.csv"
```

#### Hero (7 slides) — origen PhotosDrive o `public/images/premium/`
```powershell
& $PY $CLI "C:\Users\Lenovo\Downloads\Tu Exhibidor\public\images\hero" `
  --project "tuexhibidor" --use hero `
  --keywords "exhibidor, joyeria, fabricacion chilena, vitrina fina" `
  --max-width 1600 --quality 85 --target-kb 200 `
  --no-bg --lang es
```
Luego: `npm run build:hero` si cambian fuentes, o mapear PROCESSED/ → hero/

#### Galería premium — `public/images/premium/` (67 assets, subcarpetas)
```powershell
& $PY $CLI "C:\Users\Lenovo\Downloads\Tu Exhibidor\public\images\premium" `
  --project "tuexhibidor" --use gallery `
  --keywords "exhibidor, joyeria, ecocuero, vitrina" `
  --max-width 1200 --quality 82 --target-kb 150 `
  --no-bg --recursive --lang es --dry-run
# Revisar propuesta → procesar con --names-csv
```

#### Logo / brand — `public/images/brand/`
```powershell
& $PY $CLI "C:\Users\Lenovo\Downloads\Tu Exhibidor\public\images\brand" `
  --project "tuexhibidor" --use logo `
  --keywords "tu exhibidor, logo, joyeria" `
  --max-width 512 --quality 90 --no-bg --no-enhance
```

### Qué genera la herramienta

| Salida | Ubicación | Uso |
|--------|-----------|-----|
| WebP optimizados | `{carpeta}/PROCESSED/*.webp` | Subir a prod o convertir a JPG |
| `log_procesamiento.csv` | Misma carpeta | alt_text, tamaños, errores |
| `propuesta_nombres.csv` | Tras `--dry-run` | Revisar nombres SEO antes de procesar |

**Formato nombre SEO:** `tuexhibidor-product-mockup-exhibidor-joyeria-ecocuero-anillos.webp`

### Integración con el sitio

1. **alt_text del CSV** → actualizar `site/site-data.js` (hero, gallery) y `catalog-data.js` (productos)
2. **WebP vs JPG:** el sitio actual usa JPG en carruseles (`app.js` → `carouselSlideHtml`). Opciones:
   - A) Convertir PROCESSED/*.webp → JPG crema con sharp/warm:images
   - B) Extender `app.js` para servir WebP donde exista (mejor performance)
3. **NO usar `--bg` (rembg)** en fotos de vitrina/taller — el fallback es **fondo blanco** y rompe la paleta crema. Usar `--no-bg` siempre salvo productos aislados sobre blanco que quieras transparentes.
4. Tras ImgSEO en JPG: correr `npm run warm:images` para unificar fondos crema.
5. Revisar columna `fondo` del log: si dice "fallback fondo blanco", reprocesar con `--no-bg` o warm:images.

### Atención — conflictos conocidos

| Herramienta | Hace | Conflicto |
|-------------|------|-----------|
| ImgSEO `--bg` | rembg → transparente o **blanco** fallback | Rompe paleta crema |
| npm warm:images | Píxeles claros → crema #ddd3c8 | Solo JPG, no WebP |
| build:hero / curate:gallery | Genera JPG/WebP/AVIF | Correr DESPUÉS de definir assets finales |

**Regla:** ImgSEO para comprimir + SEO + alt → warm:images en JPG → build:site → deploy.


| Área | Antes | Ahora |
|------|-------|-------|
| Carrusel | initCarousel complejo, clones dobles, async | setupCarousel() simple ~120 líneas |
| Imágenes slider | object-fit: cover | object-fit: contain |
| Fondos | #faf7f2 casi blanco + JPEG blanco | #ebe3d8/#ddd3c8 + warm:images |
| CSS tricks | mix-blend-mode multiply | Eliminado — fix en archivos JPG |
| Scrollbars | default browser blanco | crema track + dorado thumb |
| Cache bust | v21 | styles v29, app v24, data v22 |

## TU MISIÓN — Deploy producción

### P0 — Decidir arquitectura (preguntar al usuario si no está claro)

**Opción A (recomendada si quieren landing nueva):** Sitio estático como homepage
- Subir `site/` + `public/` manteniendo estructura relativa (site usa `../public/images/...`)
- En servidor: `public_html/site/` + `public_html/public/` O ajustar rutas si va en raíz
- Redirigir tuexhibidor.cl → /site/ o reemplazar index WP

**Opción B:** Integrar en WordPress existente (WooCommerce + tema aurum-child)
- Actualizar `deploy/wp-content/themes/aurum-child/` con estilos/paleta nuevos
- Portar CSS de site/styles.css a functions.php o child theme
- Reemplazar object-fit:cover por contain en functions.php (líneas ~232, 277, 479)
- Cambiar fondos #fff → var(--cream) en secciones WP

**Opción C:** Híbrido — static landing en / + shop WooCommerce en /tienda/

### P0 — Pasos deploy (ejecutar en orden)

0. **Optimizar imágenes con ImgSEO** (ver sección HERRAMIENTA arriba):
   - Instalar venv + pip install -r requirements.txt
   - dry-run catálogo + galería → revisar propuesta_nombres.csv
   - Procesar con --names-csv
   - npm run warm:images (fondos crema en JPG)
   - Mapear alt_text del log a site-data.js / catalog-data.js

1. **Regenerar datos si hace falta:**
   cd "C:\Users\Lenovo\Downloads\Tu Exhibidor"
   npm run build:site
   npm run deploy:pack

2. **Empaquetar para subir:**
   - site/ (completo)
   - public/images/ (catalog, premium, hero, brand)
   - deploy/ (tema WP, CSV, imágenes catálogo WP)

3. **Subir vía FTP/cPanel** (pedir credenciales al usuario si no las tienes):
   - Catálogo WP: deploy/wp-content/uploads/catalog/*.jpg → public_html/wp-content/uploads/catalog/
   - Tema: deploy/wp-content/themes/aurum-child/ → servidor
   - MU-plugin: deploy/wp-content/mu-plugins/tuexhibidor-security.php
   - Sitio nuevo: site/ + public/ según arquitectura elegida

4. **Cache bust en producción** — subir con versiones actuales:
   - styles.css?v=29
   - app.js?v=24
   - site-data.js?v=22, catalog-data.js?v=22
   Incrementar ?v= si re-subes archivos.

5. **WooCommerce** (si aplica):
   - Importar deploy/import/catalogo-completo.csv
   - 7 categorías según scripts/category-mapping.mjs
   - Sin precios

6. **Servidor:** PHP 8.2+, actualizar WooCommerce, ver deploy/LEER-PRIMERO-SEGURIDAD.md

### P0 — QA en producción (browser real)

- [ ] Hero: 7 imágenes cargan, marco crema, sin blanco puro
- [ ] Carruseles: hero, destacados, nosotros, galería, catálogo — loop sin pegarse
- [ ] Imágenes: contain, producto completo visible
- [ ] Galería + lightbox + thumbs sincronizados
- [ ] Scrollbars crema/dorado (no blanco)
- [ ] WA flotante Alfonso/Leder
- [ ] Facebook, Instagram, Tecnotix en footer
- [ ] 85 productos catálogo, fotos OK
- [ ] Mobile responsive

### Rutas de archivos clave

| Qué | Ruta local |
|-----|------------|
| Sitio | site/index.html, app.js, styles.css |
| Datos JS | site/catalog-data.js, site/site-data.js |
| Hero build | scripts/build_hero_slider.mjs, npm run build:hero |
| Galería | scripts/curate_premium_gallery.mjs, public/images/premium/ |
| Warm fondos | scripts/warm_image_backgrounds.mjs, npm run warm:images |
| **ImgSEO compresor** | `Herramienta imagenes seo y compresor/image-seo-processor/image-seo-processor/` |
| ImgSEO CLI/Skill | cli.py, SKILL.md, app_gui.py |
| Deploy pack | scripts/prepare_deploy.mjs, npm run deploy:pack |
| CSV productos | import/catalogo-completo.csv |
| Categorías | scripts/category-mapping.mjs |

### Comandos npm

npm run preview          # local :3000
npm run build:site       # regenerar catalog-data + site-data
npm run build:site:full  # galería + sitio
npm run build:hero       # 7 slides hero
npm run warm:images      # fondos JPG → crema (correr si suben fotos nuevas)
npm run deploy:pack      # empaquetar deploy/

# ImgSEO (Python — ver sección HERRAMIENTA)
cd "Herramienta imagenes seo y compresor\image-seo-processor\image-seo-processor"
.venv\Scripts\python.exe cli.py <carpeta> --project tuexhibidor --use gallery --no-bg --dry-run
npm run refresh:catalog  # refrescar fotos catálogo (NO rebuild:catalog)

### URLs sociales (exactas)

- Facebook: https://facebook.com/tuexhibidor.cl
- Instagram: https://www.instagram.com/tuexhibidor/
- Tecnotix: https://tecnotix.cl

### Problemas conocidos / no reintroducir

- NO volver a object-fit:cover en carruseles/tarjetas
- NO usar #faf7f2 ni fondos blancos en marcos de imagen
- NO reintroducir initCarousel viejo con flag animating
- Hero usa isResponsiveAsset() (objeto base+sources), rutas /hero/ no /premium/
- ERR_CONNECTION_RESET en local = servidor caído → npx serve . -l 3000
- warm:images necesita servidor detenido (archivos bloqueados en Windows)

### Al terminar — reporte obligatorio

# Reporte Tu Exhibidor Deploy — [fecha]

## Resumen
[qué se publicó y dónde]

## Arquitectura elegida
[A / B / C y URL final]

## Archivos subidos
| Ruta servidor | Origen local |

## Comandos ejecutados
[con exit OK/FAIL]

## URLs verificadas en prod
- https://tuexhibidor.cl/...
- [screenshots o descripción visual]

## QA checklist
- [ ] items marcados

## Pendiente / blockers
[credenciales, DNS, etc.]
```

---

*Generado Cursor → Cowork/Claude Opus · Jul 2026*
