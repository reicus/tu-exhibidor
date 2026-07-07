---
name: image-seo-processor
description: Procesa lotes de imágenes para web de forma masiva - redimensiona manteniendo proporción, elimina el fondo (rembg con fallback a fondo blanco), mejora exposición/contraste/nitidez, comprime a WebP y renombra con nombres SEO en formato [proyecto]-[uso]-[keywords]-[detalle].webp, generando log CSV con alt text sugerido. Usa esta skill siempre que el usuario pida optimizar, comprimir, renombrar o limpiar imágenes en lote, preparar assets o mockups para web, eliminar fondos de varias fotos, convertir carpetas a WebP, o generar nombres SEO para imágenes — aunque no mencione la palabra "skill" ni "SEO" explícitamente.
---

# Image SEO Processor

Skill para procesar carpetas completas de imágenes (cientos o miles) y dejarlas listas para web: livianas, con fondo transparente y nombres SEO.

## Cómo usarla

Todo se invoca a través de `cli.py` (dentro de esta carpeta). Requiere que las dependencias base estén instaladas una vez:

```bash
pip install -r <ruta-de-esta-skill>/requirements.txt
```

### Comando principal

```bash
python <ruta-de-esta-skill>/cli.py <carpeta_imagenes> \
    --project "<nombre-proyecto>" \
    --use <hero|product-mockup|feature|background|banner|blog-image|og-image|gallery|testimonial|thumbnail|logo|icon|texto-libre> \
    --keywords "<kw1, kw2, kw3>" \
    --max-width <px> --quality <40-95>
```

La salida queda en `<carpeta_imagenes>/PROCESSED/` junto con `log_procesamiento.csv` (separador `;`, UTF-8 BOM) que incluye: nombre original, nombre nuevo, tamaños antes/después, % de reducción, estado del fondo, keywords y **alt_text sugerido** (compártelo con el usuario: para SEO importa más que el filename).

### Flags útiles

| Flag | Efecto |
|---|---|
| `--dry-run` | Solo genera `propuesta_nombres.csv` editable (no procesa). Úsalo cuando el usuario quiera revisar/aprobar nombres antes. |
| `--names-csv <ruta>` | Procesa aplicando una propuesta ya editada. |
| `--no-bg` / `--bg` | Desactiva / activa la eliminación de fondo. |
| `--model u2netp\|u2net\|isnet-general-use` | Modelo de segmentación (rápido → preciso). |
| `--no-enhance` | Sin mejora automática. |
| `--ai` | Descripciones automáticas BLIP (solo si `requirements-vision.txt` está instalado; es lento en CPU). |
| `--target-kb <n>` | Baja calidad hasta cumplir un peso máximo por imagen. |
| `--recursive` | Incluye subcarpetas. |
| `--no-skip` | Reprocesa aunque la salida ya exista (por defecto reanuda saltando lo hecho). |
| `--workers <n>` | Procesos en paralelo (0 = automático). |
| `--lang es\|en` | Idioma de keywords IA y alt text. |

## Flujo recomendado

1. Si el usuario no indicó proyecto/uso/keywords, pregúntaselos (son los segmentos del nombre SEO).
2. Para lotes grandes o si el usuario quiere control: primero `--dry-run`, muéstrale la propuesta, deja que edite, luego corre con `--names-csv`.
3. Ejecuta el comando y reporta el resumen final: ok/errores/omitidas, MB ahorrados y ruta de salida. Si hubo filas con `estado=error`, revisa la columna `error` del log y coméntalas.
4. Si la carpeta ya tiene `imgseo_config.json` (preferencias guardadas de corridas anteriores), el CLI lo carga solo; tus flags solo sobreescriben lo que pases explícitamente.

## Notas y límites

- Primer uso con eliminación de fondo: rembg descarga su modelo (~170 MB); avisa al usuario si la primera corrida tarda en arrancar.
- Si rembg no está instalado, el pipeline NO falla: procesa sin quitar fondo y lo anota en el log.
- `--ai` sin las dependencias de visión tampoco falla: sigue sin IA y lo reporta como advertencia.
- Fondos fallidos se guardan aplanados sobre blanco (columna `fondo` = "fallback fondo blanco").
- No agranda imágenes pequeñas (evita pixelado); solo reduce.
- La GUI (`app_gui.py`) existe para uso humano de escritorio; como skill usa siempre `cli.py`.
