# Manifiesto de extracción Canva — Tu Exhibidor

**Canva:** [https://canva.link/v74u1w40pocahcs](https://canva.link/v74u1w40pocahcs)  
**Design ID:** `DAGtWc0EeJQ`  
**Extraído:** 2026-07-05

---

## Bloqueadores

| Bloqueo | Detalle |
|---------|---------|
| Canva web | HTTP 403 Cloudflare — requiere login humano en navegador |
| Automatización | No se pudo abrir pestaña browser MCP en esta sesión |
| **Solución usada** | PDF exportado en `PhotosDrive/Documentos/CATALOGO 2020.pdf` y `2021-01-09.pdf` (23 páginas c/u = **46 páginas totales**) |

---

## Resumen de extracción

| Métrica | Valor |
|---------|-------|
| Páginas procesadas | **46** (23 × 2 PDFs) |
| Productos únicos | **85** |
| Vitrinas & Bandejas | **33** |
| Aros & Anillos | **25** |
| Cadenas & Collares | **15** |
| Pulseras & Relojes | **12** |
| Sets TUE-STAND (p.18-22) | **13** |

---

## Mapa de páginas Canva (catálogo 2021)

| Página | Contenido | Categoría principal |
|--------|-----------|---------------------|
| 1 | Bandejas TUE-BA-001 a 009 | Vitrina |
| 2 | Bandejas aros colgantes / cadenas | Vitrina |
| 3 | Bandejas anillos + charms TUE-BC | Vitrina |
| 4 | Charms + sets anillos TUE-AN | Vitrina / Anillos |
| 5-6 | Cilindros y exhibidores anillos | Anillos |
| 7 | Bandeja 7 anillos + hexagonales + cintillos | Anillos / Vitrina |
| 8-10 | Pulseras y relojes TUE-PU / TUE-RE | Pulseras |
| 11 | Pecheras P-NM, L-DAN, DNS-11, K-28 | **Cadenas** |
| 12 | Pecheras K-29, TUE-CO, BX-89, BX-109 | **Cadenas** |
| **13** | **BX-159, E-35, E-40, E-XNL — cuellos y bustos** | **Cadenas** |
| 14-17 | Aretes TUE-AR, accesorios L-ML/MD2/M6 | Anillos |
| 18-22 | **TUE-STAND-001 a 013 — sets vitrina** | **Vitrina** |
| 23 | (vacía) | — |

> **Nota:** La página 13 del Canva contiene **cadenas y collares**, no sets vitrina. Los sets vitrina tipo NovelBox están en páginas **18-22** (TUE-STAND).

---

## Archivos generados

| Archivo | Descripción |
|---------|-------------|
| `import/catalogo-completo.csv` | 85 productos, todas las categorías |
| `import/vitrina-products.csv` | 33 productos categoría vitrina |
| `import/canva-extraction-manifest.json` | JSON completo con productos |
| `import/image-copy-map.json` | Mapeo imágenes locales |
| `docs/canva-copy/*.txt` | Texto crudo por página |
| `docs/canva-copy/category-intros.json` | Textos para rediseño web |
| `public/images/catalog/` | Imágenes producto |
| `docs/SEGURIDAD-Y-ACTUALIZACIONES.md` | Guía PHP/plugins |

---

## Textos Canva reutilizados en web

Desde portada (p.1):
- **Empresa:** Comercializadora Tu Exhibidor SPA — RUT 77.036.189-3
- **Contacto:** info@tuexhibidor.cl · WhatsApp +56 9 3749 0214 / +56 9 9132 7813
- **Instagram:** @tuexhibidor

Cierre (p.17):
> "Si tienes alguna duda, idea o diseño que quieras que fabriquemos no dudes en escribirnos en nuestras redes sociales y te ayudaremos con tu idea."

---

## Próximos pasos (deploy)

1. Subir `mu-plugins/tuexhibidor-security.php` y `aurum-child/functions.php` al servidor
2. Migrar PHP 7.4 → 8.2 + actualizar WooCommerce (ver doc seguridad)
3. Importar `catalogo-completo.csv` en WooCommerce
4. Subir imágenes de `public/images/catalog/` → `wp-content/uploads/catalog/`
5. Exportar Canva actualizado como PDF periódicamente para re-sincronizar
