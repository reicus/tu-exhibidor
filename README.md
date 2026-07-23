# Tu Exhibidor — Sitio premium + catálogo

Sitio web para [tuexhibidor.cl](https://tuexhibidor.cl) — fábrica chilena de exhibidores para joyería.

## Preview local

```bash
npm install   # opcional, solo usa serve
npm run preview
```

Abrir: http://localhost:3000/site/

## Estructura

| Carpeta | Descripción |
|---------|-------------|
| `site/` | Sitio estático (HTML, CSS, JS, datos catálogo) |
| `public/images/` | Imágenes hero, galería, catálogo, marca |
| `scripts/` | Build, deploy, ImgSEO, warm backgrounds |
| `import/` | CSV catálogo, manifiestos |
| `deploy/` | Paquete WordPress / WooCommerce |
| `PhotosDrive/` | Fotos fuente del taller |
| `Herramienta imagenes seo y compresor/` | Compresor y SEO de imágenes (Python) |

## Comandos útiles

```bash
npm run build:site        # Regenerar catalog-data.js + site-data.js
npm run build:hero        # 7 slides hero
npm run imgseo:drive      # Procesar PhotosDrive con ImgSEO
npm run replace:from-imgseo
npm run pack:operativo    # Backup limpio solo sitio operativo
npm run deploy:pack       # Empaquetar para FTP/WordPress
```

## Admin de imágenes (WordPress)

Tras subir el mu-plugin `tuexhibidor-site-manager` al servidor:

1. Entra a **https://tuexhibidor.cl/login**
2. Menú lateral → **Sitio Premium**
3. Pestañas: **Catálogo** | **Hero** | **Galería** | **Marca**
4. Clic en **Cambiar imagen** → biblioteca de medios → publicación inmediata en `/site/`

Archivos en servidor:
- `wp-content/mu-plugins/tuexhibidor-site-manager.php`
- `wp-content/mu-plugins/tuexhibidor-site-manager/` (carpeta completa)


## Continuidad / backup

Para retomar el proyecto más adelante:

1. [`docs/COMO-RETOMAR.md`](docs/COMO-RETOMAR.md) — checklist ~15 minutos  
2. [`docs/CONTINUIDAD-PROYECTO.md`](docs/CONTINUIDAD-PROYECTO.md) — handoff completo  
3. Tag de snapshot: `backup-2026-07-23` en https://github.com/reicus/tu-exhibidor  

Credenciales: solo en `docs/CREDENCIALES-TU-EXHIBIDOR.md` (local, no versionado).

## Contacto negocio

- WhatsApp Alfonso: +56 9 3749 0214
- WhatsApp Leder: +56 9 9132 7813
- info@tuexhibidor.cl
