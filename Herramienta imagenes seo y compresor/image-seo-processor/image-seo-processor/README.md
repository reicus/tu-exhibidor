# Procesador masivo de imágenes SEO

Herramienta para convertir carpetas con cientos o miles de imágenes (muchas de baja calidad) en bancos de assets web limpios, livianos y perfectamente nombrados para SEO. Redimensiona, mejora, elimina el fondo, comprime a WebP y renombra con el formato:

```
[proyecto]-[uso]-[palabras-clave]-[detalle].webp
fitness-app-hero-entrenamiento-fuerza-mujer.webp
```

Funciona con interfaz gráfica (`app_gui.py`), por línea de comandos (`cli.py`) y como **Skill de Claude Code / Cowork** (ver `SKILL.md`).

---

## Instalación

Requiere Python 3.10 o superior.

**Windows (PowerShell):**
```powershell
cd image-seo-processor
py -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
```

**macOS / Linux:**
```bash
cd image-seo-processor
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

**Opcional — descripciones automáticas con IA (BLIP):**
```bash
pip install -r requirements-vision.txt
```
Esto instala torch + transformers (pesado; el modelo BLIP baja ~1 GB la primera vez). En Linux conviene `pip install torch --index-url https://download.pytorch.org/whl/cpu` antes, para evitar el paquete CUDA gigante. Sin esta instalación todo lo demás funciona igual: los nombres usan tus keywords + palabras útiles del nombre original.

> La primera vez que elimines fondos, rembg descarga su modelo (~170 MB) automáticamente.

---

## Primer uso (GUI)

```bash
python app_gui.py
```

1. **📁 Seleccionar carpeta** con tus imágenes.
2. Escribe el **proyecto** (ej: `fitness-app`) y elige el **tipo de uso** (hero, product-mockup, feature, background, logo, icon, blog-image… o escribe uno propio). Al elegir el uso, el ancho se ajusta solo a un preset razonable (hero → 1920 px, product-mockup → 1200 px, icon → 256 px…).
3. Agrega tus **keywords** separadas por comas.
4. Ajusta sliders de **ancho máximo** y **calidad WebP**, y marca los checkboxes que quieras (eliminar fondo, mejorar calidad, descripciones IA).
5. **▶ PROCESAR TODAS LAS IMÁGENES**. Verás barra de progreso con tiempo restante, la vista previa de las primeras 5 y podrás cancelar cuando quieras.

La salida queda en `PROCESSED/` dentro de tu carpeta, junto con `log_procesamiento.csv` (nombre original, nombre nuevo, tamaños antes/después, keywords, alt text sugerido y errores). Las preferencias se guardan en `imgseo_config.json` dentro de la carpeta del proyecto y se recargan solas la próxima vez que la selecciones.

### Modo revisión (recomendado para lotes grandes)

Marca **"Solo generar propuesta de nombres"**: se crea `propuesta_nombres.csv` con los nombres, keywords y alt text sugeridos. Ábrelo en Excel, edita lo que quieras (columnas `nombre_nuevo`, `keywords`, `alt_text`) y vuelve a presionar Procesar: la app detecta la propuesta y te pregunta si quieres usarla. Editar un CSV escala mucho mejor que aprobar 1000 sugerencias una por una.

> El log CSV usa `;` como separador y codificación UTF-8 con BOM para que Excel en español lo abra en columnas directamente.

---

## Uso por línea de comandos

```bash
# Procesar carpeta completa
python cli.py ./fotos --project "Fitness App" --use hero \
    --keywords "entrenamiento, fuerza" --max-width 1920 --quality 80

# Solo propuesta de nombres (dry-run) para revisar antes
python cli.py ./fotos --project "Fitness App" --use product-mockup --dry-run

# Procesar aplicando la propuesta ya editada
python cli.py ./fotos --names-csv ./fotos/propuesta_nombres.csv

# Con IA de descripciones, límite de peso y subcarpetas
python cli.py ./fotos --ai --target-kb 150 --recursive
```

`python cli.py --help` muestra todas las opciones. Si la carpeta ya tiene `imgseo_config.json`, se carga automáticamente y los flags del CLI solo sobreescriben lo que indiques.

---

## Qué hace exactamente el pipeline

Por cada imagen: corrige la orientación EXIF → redimensiona manteniendo proporción (nunca agranda) → mejora ligera (balance de blancos gray-world, autocontraste, color/contraste/nitidez sutiles) → elimina el fondo con rembg dejándolo transparente (WebP soporta alfa); **si la segmentación falla, guarda la imagen aplanada sobre fondo blanco** → comprime a WebP con `method=6`; si defines un peso objetivo en KB, baja la calidad por pasos hasta cumplirlo (mínimo 40).

Detalles que importan:

- **EXIF eliminado** al guardar: menos peso y sin metadatos de ubicación (privacidad).
- **Reanudable**: si el proceso se corta en la imagen 800 de 1000, vuelve a correr y salta las ya procesadas (desmarca "Omitir ya procesadas" para forzar reproceso).
- **Nombres SEO**: minúsculas, sin acentos ni ñ, palabras deduplicadas entre segmentos, máximo ~70 caracteres y sufijos `-02`, `-03` ante colisiones. Nombres basura tipo `IMG_20240101` se descartan como detalle.
- **alt_text sugerido** en el CSV: para SEO de imágenes pesa incluso más que el nombre del archivo; cópialo a tu HTML/CMS.
- **Multiprocessing**: usa CPU−1 procesos (máximo 4 cuando se elimina fondo, porque cada proceso carga su copia del modelo). Ajustable con `--workers` o en la GUI.
- Formatos de entrada: JPG, PNG, WebP, BMP, TIFF, GIF (primer frame) y HEIC/HEIF de iPhone.

---

## Usarlo como Skill de Claude Code / Cowork

Copia la carpeta completa a tu directorio de skills (por ejemplo `~/.claude/skills/image-seo-processor/`). El `SKILL.md` incluido le indica a Claude cómo invocar `cli.py`. Después puedes pedirle cosas como *"optimiza las imágenes de ./assets para el proyecto tecnotix, uso product-mockup"* y Claude ejecuta el pipeline por ti. Recuerda instalar las dependencias en el Python que use ese entorno.

---

## Solución de problemas

- **`ModuleNotFoundError: rembg` / fondo no se elimina** → `pip install rembg onnxruntime` dentro del venv. Si rembg falta, la herramienta no se cae: procesa igual y lo anota en el log.
- **Error de DLL con onnxruntime en Windows** → instala "Microsoft Visual C++ Redistributable" (x64) y reintenta.
- **BLIP muy lento** → es normal en CPU (2–5 s por imagen). Úsalo con lotes moderados, con GPU, o trabaja sin IA: los nombres siguen saliendo bien con tus keywords.
- **Traducción de keywords no funciona** → `deep-translator` necesita internet; sin conexión las keywords IA quedan en inglés (válidas igual para SEO).
- **Nombres cambian entre corridas** → los sufijos `-02` dependen del orden de archivos; si agregas fotos nuevas a la carpeta, usa el modo propuesta (`--dry-run` + `--names-csv`) para fijar los nombres antes de reanudar.

---

## Estructura del proyecto

```
image-seo-processor/
├── app_gui.py               # Interfaz gráfica (customtkinter)
├── cli.py                   # Modo línea de comandos / automatización
├── core/
│   ├── config.py            # Preferencias, presets de tamaño por uso
│   ├── seo.py               # Slugs, keywords, nombres SEO, alt text
│   ├── processor.py         # Resize, mejora, rembg + fallback, WebP
│   ├── vision.py            # Captions BLIP + traducción (opcional)
│   └── pipeline.py          # Orquestación, multiprocessing, CSV
├── requirements.txt         # Dependencias base
├── requirements-vision.txt  # Dependencias opcionales de IA
├── SKILL.md                 # Definición como Skill de Claude
└── README.md
```
