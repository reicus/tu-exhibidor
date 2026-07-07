# Handoff — Tu Exhibidor → Claude Code (Fable5)

> **Propósito:** Este documento contiene TODO lo pedido y el estado actual del proyecto para que Claude Code (modelo Fable5) continúe el trabajo, complete lo pendiente y devuelva resultados estructurados a Cursor.

---

## Instrucciones para Claude Code

1. **Workspace:** `C:\Users\Lenovo\Downloads\Tu Exhibidor`
2. **Preview local:** `npx serve . -l 3000` → http://localhost:3000/site/?v=21
3. **No crear commits** salvo que el usuario lo pida explícitamente.
4. **Al terminar**, devolver un reporte con el formato de la sección [Formato de respuesta](#formato-de-respuesta) al final.
5. **Ejecutar comandos tú mismo** — no solo describir pasos.
6. **Idioma del sitio:** español chileno, tono premium para joyeros (no “bisutería” genérica).

---

## Contexto del negocio

| Campo | Valor |
|-------|-------|
| **Marca** | Tu Exhibidor |
| **Dominio** | tuexhibidor.cl |
| **Negocio** | Fábrica chilena de exhibidores para joyería |
| **Cotización** | WhatsApp — **sin precios** en web |
| **Colores marca** | gold `#b8935f`, cream `#faf7f2`, ink `#2b2926` |
| **Contacto WA** | Alfonso +56 9 3749 0214 · Leder +56 9 9132 7813 |
| **Email** | info@tuexhibidor.cl |
| **RUT** | 77.036.189-3 |

**Referencias de estilo:** [exhibidoresdejoyas.com](https://www.exhibidoresdejoyas.com/), [jewelrydisplay.com](https://jewelrydisplay.com/), [tuexhibidor.cl](https://tuexhibidor.cl/)

---

## Lo que YA está hecho (no rehacer)

### Sitio preview (`site/`)

- [x] Rediseño premium crema/dorado
- [x] Copy élite joyeros chilenos
- [x] Stats: **100% · Fabricación en Chile**, **85+ · Diseños para vitrina fina**
- [x] Catálogo 85 productos (7 categorías) — `site/catalog-data.js`
- [x] Galería premium curada (67 assets, ~268 JPG + WebP/AVIF)
- [x] Logo en header/footer/favicon — `public/images/brand/`
- [x] Fix imágenes corruptas catálogo (85/85 OK)
- [x] **7 imágenes hero** — `public/images/hero/` + `npm run build:hero`
- [x] Carruseles con loop infinito (hero, destacados, nosotros, galería, catálogo)
- [x] Carrusel hero: carga eager, preload, `object-fit: cover` en todo slider
- [x] WhatsApp flotante con menú Alfonso/Leder (sin WA en tarjetas producto)
- [x] Iconos sociales Facebook + Instagram (contacto + footer)
- [x] Crédito footer: **Made by Tecnotix Solutions** → https://tecnotix.cl

### Scripts npm (raíz)

```json
"curate:gallery": "node scripts/curate_premium_gallery.mjs",
"build:hero": "node scripts/build_hero_slider.mjs",
"brand:logo": "node scripts/process_brand_logo.mjs",
"refresh:catalog": "node scripts/refresh_catalog_from_photos.mjs",
"rebuild:catalog": "node scripts/rebuild_catalog_images.mjs",
"build:site": "node scripts/build_site_data.mjs",
"build:site:full": "node scripts/curate_premium_gallery.mjs && node scripts/build_site_data.mjs",
"deploy:pack": "node scripts/prepare_deploy.mjs",
"preview": "npx serve ."
```

### Archivos clave

| Área | Rutas |
|------|-------|
| Preview | `site/index.html`, `site/app.js`, `site/styles.css` |
| Datos generados | `site/catalog-data.js`, `site/site-data.js` |
| Hero 7 slides | `public/images/hero/`, `scripts/build_hero_slider.mjs` |
| Premium galería | `public/images/premium/`, `import/premium-gallery-manifest.json` |
| Catálogo fotos | `public/images/catalog/`, `import/image-quality.json` |
| Canva/CSV | `import/catalogo-completo.csv`, `import/canva-extraction-manifest.json` |
| Categorías/copy | `scripts/category-mapping.mjs` |
| Build sitio | `scripts/build_site_data.mjs` |
| Logo | `public/images/brand/`, `assets/logo-source.psd` |

---

## Lo que FALTA por hacer (prioridad)

### P0 — Deploy live

- [ ] Empaquetar assets + sitio para producción (`npm run deploy:pack` si existe, o crear script)
- [ ] Subir a tuexhibidor.cl (FTP/cPanel — credenciales las tiene el usuario)
- [ ] Verificar hero, carruseles, catálogo e imágenes en producción
- [ ] Cache bust / versiones CSS/JS en deploy

### P1 — WooCommerce

- [ ] 7 categorías alineadas con `scripts/category-mapping.mjs`
- [ ] Reimportar 85 productos desde `import/catalogo-completo.csv`
- [ ] Imágenes producto desde `public/images/catalog/{slug}.jpg`
- [ ] Sin precios — solo cotización WA

### P2 — Servidor

- [ ] PHP 8.2+
- [ ] Actualizar WooCommerce
- [ ] Auditoría seguridad plugins WordPress
- [ ] Confirmar backup JetBackup configurado

### P3 — QA visual (verificar en browser)

- [ ] Hero: 7 imágenes visibles, cargan rápido, llenan marco 4:3
- [ ] Carruseles: loop infinito sin quedarse pegados
- [ ] Redes sociales + Tecnotix en footer
- [ ] WhatsApp flotante funcional
- [ ] Catálogo: 85 productos, fotos OK, sin corruptas

---

## Tareas originales del usuario (checklist histórico)

Usar como referencia de alcance total:

1. [x] Auditar y rediseñar tuexhibidor.cl (premium, competencia, paleta)
2. [x] Integrar catálogo Canva 85 productos + fotos PhotosDrive
3. [x] Preview local antes de publicar
4. [x] Paleta crema/dorado unificada
5. [x] Sitio dinámico: carruseles, galería, lightbox
6. [x] Curación premium ~1.500 fotos → 67 assets optimizados
7. [x] Logo desde PSD integrado
8. [x] Fix imágenes raras/corruptas catálogo
9. [x] WhatsApp flotante Alfonso/Leder
10. [x] Copy premium Chile joyeros
11. [x] Textos: 100% Fabricación Chile · 85+ Diseños vitrina fina
12. [x] 7 imágenes hero slider (PhotosDrive, estilo vitrina fina)
13. [x] Loop infinito en todos los carruseles
14. [x] Iconos Facebook + Instagram
15. [x] Crédito Made by Tecnotix Solutions
16. [x] Fix carga lenta hero + object-fit cover en sliders
17. [ ] **Deploy live**
18. [ ] **WooCommerce producción**
19. [ ] **PHP 8.2 + seguridad**

---

## Cómo agregar imagen al hero (para futuras ediciones)

1. Editar `scripts/build_hero_slider.mjs` → array `HERO_PICKS`
2. Ejecutar:
   ```bash
   npm run build:hero
   npm run build:site
   ```
3. Recorte automático 4:3 + `object-fit: cover` en CSS

**Slides actuales (7):**

| # | ID | Categoría |
|---|-----|-----------|
| 1 | hero-slide-01 | Collares — bustos |
| 2 | hero-slide-02 | Anillos — maniquí |
| 3 | hero-slide-03 | Sets vitrina — pedestales |
| 4 | hero-slide-04 | Sets — hexagonales |
| 5 | hero-slide-05 | Aros — exhibidor T |
| 6 | hero-slide-06 | Bandejas/dijes |
| 7 | hero-slide-07 | Pulseras |

---

## URLs sociales (exactas)

- Facebook: https://facebook.com/tuexhibidor.cl
- Instagram: https://www.instagram.com/tuexhibidor/
- Tecnotix: https://tecnotix.cl

---

## Problemas conocidos / notas técnicas

- **No scrapear** imágenes de competidores — usar PhotosDrive/premium propias
- Catálogo: usar `refresh:catalog` no `rebuild:catalog` (false positives glitch)
- Hero assets usan `isResponsiveAsset()` (objeto con `base` + `sources`), no solo `/premium/`
- Carrusel: clones para loop; imágenes con `loading="eager"` en sliders
- TUE-BA-003 puede tener foto sustituta — mejorar si hay mejor match en PhotosDrive
- Servidor preview: reiniciar con `npx serve . -l 3000` si cache viejo

---

## Comandos útiles

```bash
cd "C:\Users\Lenovo\Downloads\Tu Exhibidor"

# Preview
npx serve . -l 3000

# Regenerar todo el sitio
npm run build:site:full

# Solo hero + datos
npm run build:hero && npm run build:site

# Refrescar fotos catálogo
npm run refresh:catalog && npm run build:site
```

---

## Formato de respuesta

**Al terminar, Claude Code debe devolver este reporte** (copiar/pegar a Cursor):

```markdown
# Reporte Tu Exhibidor — [fecha]

## Resumen ejecutivo
[2-3 oraciones: qué se hizo]

## Completado en esta sesión
- [ ] item 1
- [ ] item 2

## Archivos creados/modificados
| Archivo | Cambio |
|---------|--------|
| ruta | descripción |

## Comandos ejecutados
\`\`\`bash
[comandos con exit code OK/FAIL]
\`\`\`

## URLs verificadas
- Preview local: http://localhost:3000/site/?v=XX
- Producción: [si aplica]

## Screenshots / evidencia
[describir qué se verificó visualmente]

## Pendiente
- [ ] ...

## Blockers / necesito del usuario
- [credenciales FTP, acceso cPanel, etc.]

## Para Cursor — diff mental
[Qué cambió respecto al estado de este HANDOFF]
```

---

## Prompt corto para pegar en Claude Code

Copia esto como primer mensaje:

```
Lee y ejecuta HANDOFF-CLAUDE-CODE.md en C:\Users\Lenovo\Downloads\Tu Exhibidor

Prioridad:
1. Verificar QA visual (hero 7 imgs, carruseles loop, social, Tecnotix, WA)
2. Completar deploy live si hay credenciales
3. WooCommerce: 7 categorías + 85 productos desde CSV
4. PHP 8.2 + seguridad si hay acceso servidor

Usa Fable5. Ejecuta comandos tú mismo. Al final devuelve el reporte del formato del MD.
```

---

*Generado para handoff Cursor → Claude Code · Tu Exhibidor · tuexhibidor.cl*
