# Cómo retomar Tu Exhibidor en ~15 minutos

Checklist rápido. Detalle completo: [`CONTINUIDAD-PROYECTO.md`](./CONTINUIDAD-PROYECTO.md).

---

## 1. Clonar / actualizar (2 min)

```bash
git clone https://github.com/reicus/tu-exhibidor.git
cd "tu-exhibidor"
# o si ya existe:
git fetch origin
git checkout main
git pull origin main
```

Snapshot de este backup:

```bash
git checkout backup-2026-07-23
```

---

## 2. Credenciales (1 min)

1. Verificar que exista **localmente** `docs/CREDENCIALES-TU-EXHIBIDOR.md` (no está en GitHub).
2. Si no está: recuperarlo de backup seguro o regenerar FTP/WP desde HostingPlus.
3. Confirmar host FTP `rooster.hostingplus.cl`, usuario `tuexhibi`, dominio https://tuexhibidor.cl

---

## 3. Preview local (2 min)

```bash
npm install    # opcional
npm run preview
```

Abrir: http://localhost:3000/site/

---

## 4. Verificar producción (3 min)

Abrir en el navegador:

| URL | Qué comprobar |
|-----|----------------|
| https://tuexhibidor.cl/site/ | Home, hero, catálogo |
| https://tuexhibidor.cl/shop/ | Tienda, logo, productos |
| https://tuexhibidor.cl/login → `/imagenes` | Panel Sitio Premium |

---

## 5. Alinear local con producción (opcional, 5 min)

**Antes de cambios arriesgados:**

```bash
node scripts/pull_production_backup.mjs
```

Crea `backup-produccion-YYYYMMDD/` (ignorado por Git).

---

## 6. Reglas de oro al retomar

| Hacer | Evitar |
|-------|--------|
| `git pull` antes de trabajar | Ejecutar `sync_home_from_wp.mjs` sin backup |
| Cambiar fotos vía `/imagenes` o WC | Dejar PHP one-time en el servidor |
| Deploy con scripts `deploy_*.mjs` documentados | Commitear `CREDENCIALES` o `.env` |
| Leer pendientes R-P01…R-P06 en CONTINUIDAD | Asumir que PhotosDrive está en el clone (no lo está) |

---

## 7. Primeros comandos útiles

```bash
# Sync catálogo WC → sitio + subir
node scripts/sync_all_catalog_from_wc.mjs --deploy

# Deploy home estático
node scripts/deploy_home_static.mjs

# Verificar hooks de imagen WC
node scripts/verify_wc_image_sync_hooks.mjs
```

---

## 8. Dónde leer más

1. Este archivo (checklist)  
2. [`CONTINUIDAD-PROYECTO.md`](./CONTINUIDAD-PROYECTO.md) — arquitectura, pendientes, warnings  
3. [`MANUAL-COMPLETO-TU-EXHIBIDOR.md`](./MANUAL-COMPLETO-TU-EXHIBIDOR.md) — operación día a día  
4. [`A-LISTA-TECNICA-PROGRAMADOR.md`](./A-LISTA-TECNICA-PROGRAMADOR.md) — resumen técnico  
