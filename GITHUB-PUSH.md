# Subir a GitHub

El repo local ya está listo (`git init` + commit inicial ~621 MB).

## Paso 1 — Iniciar sesión en GitHub (una vez)

Abre PowerShell y ejecuta:

```powershell
gh auth login
```

Elige:
1. **GitHub.com**
2. **HTTPS**
3. **Login with a web browser** (o token si prefieres)

## Paso 2 — Crear repo y subir

```powershell
cd "C:\Users\Lenovo\Downloads\Tu Exhibidor"

gh repo create tu-exhibidor --public --source=. --remote=origin `
  --description "Sitio premium Tu Exhibidor - exhibidores joyeria Chile" --push
```

Si el nombre `tu-exhibidor` ya existe en tu cuenta, usa otro:

```powershell
gh repo create tuexhibidor-web --public --source=. --remote=origin --push
```

## Alternativa sin `gh` (manual)

1. Crear repo vacío en https://github.com/new (sin README)
2. Luego:

```powershell
cd "C:\Users\Lenovo\Downloads\Tu Exhibidor"
git remote add origin https://github.com/TU_USUARIO/tu-exhibidor.git
git branch -M main
git push -u origin main
```

> El push puede tardar varios minutos (~621 MB de imágenes).

## Qué se sube / qué no

**Incluido:** `site/`, `public/images/`, `PhotosDrive/` (originales), scripts, deploy, import, herramienta ImgSEO.

**Excluido (.gitignore):** `backup/` (3 GB), ZIPs, `node_modules/`, `.venv/`, carpetas `PROCESSED/` regenerables.
