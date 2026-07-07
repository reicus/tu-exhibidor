"""
processor.py — Operaciones de imagen por unidad + worker de multiprocessing.

Pipeline por imagen:
  abrir (EXIF orientación, HEIC) -> normalizar modo -> redimensionar ->
  mejora ligera (balance de blancos, contraste, nitidez) ->
  eliminar fondo (rembg, con fallback a fondo blanco) -> guardar WebP.

Los metadatos EXIF se eliminan al guardar (menos peso + privacidad).
Este módulo es importable por los procesos hijos (Windows 'spawn' incluido).
"""

from __future__ import annotations

import os
from pathlib import Path

from PIL import Image, ImageEnhance, ImageOps, ImageStat

# Soporte HEIC/HEIF (fotos de iPhone) si pillow-heif está instalado — opcional
try:  # pragma: no cover
    from pillow_heif import register_heif_opener

    register_heif_opener()
except Exception:  # noqa: BLE001
    pass

# Extensiones que se recogen de la carpeta de origen
SUPPORTED_EXTS = {
    ".jpg", ".jpeg", ".jfif", ".png", ".webp", ".bmp",
    ".tif", ".tiff", ".gif", ".heic", ".heif",
}

# ----------------------------------------------------------------------
# Estado global de cada proceso worker (se inicializa una sola vez por worker
# para no recargar el modelo de rembg en cada imagen).
_W: dict = {"cfg": None, "session": None, "rembg_ok": False}


def worker_init(cfg: dict) -> None:
    """Initializer del ProcessPoolExecutor: guarda config y carga rembg."""
    _W["cfg"] = cfg
    _W["session"] = None
    _W["rembg_ok"] = False
    if cfg.get("remove_bg"):
        try:
            from rembg import new_session

            _W["session"] = new_session(cfg.get("bg_model", "u2net"))
            _W["rembg_ok"] = True
        except Exception:  # rembg no instalado o modelo no descargable
            _W["session"] = None


# ----------------------------------------------------------------------
def open_image(path: str | Path) -> Image.Image:
    """Abre una imagen aplicando la orientación EXIF. Lanza excepción si está corrupta."""
    img = Image.open(path)
    img.load()  # fuerza la lectura completa (detecta archivos truncados)
    try:
        img = ImageOps.exif_transpose(img)
    except Exception:  # EXIF corrupto: seguimos con la imagen tal cual
        pass
    return img


def normalize_mode(img: Image.Image) -> Image.Image:
    """Convierte a RGB/RGBA según tenga transparencia. GIF animado -> primer frame."""
    if getattr(img, "is_animated", False):
        img.seek(0)
    has_alpha = img.mode in ("RGBA", "LA") or (
        img.mode == "P" and "transparency" in img.info
    )
    return img.convert("RGBA" if has_alpha else "RGB")


def resize_keep_aspect(img: Image.Image, max_w: int, max_h: int) -> Image.Image:
    """Reduce manteniendo proporción. Nunca agranda (evita pérdida de calidad)."""
    w, h = img.size
    scale = 1.0
    if max_w and w > max_w:
        scale = min(scale, max_w / w)
    if max_h and h > max_h:
        scale = min(scale, max_h / h)
    if scale < 1.0:
        img = img.resize(
            (max(1, round(w * scale)), max(1, round(h * scale))),
            Image.Resampling.LANCZOS,
        )
    return img


# ----------------------------------------------------------------------
def _gray_world_wb(rgb: Image.Image) -> Image.Image:
    """Balance de blancos 'gray world': iguala las medias de los 3 canales.

    Los factores se limitan a ±25% para no producir tintes artificiales.
    """
    r_m, g_m, b_m = ImageStat.Stat(rgb).mean[:3]
    gray = (r_m + g_m + b_m) / 3.0

    def scale_channel(ch: Image.Image, mean: float) -> Image.Image:
        if mean <= 0:
            return ch
        s = max(0.80, min(1.25, gray / mean))
        if abs(s - 1.0) < 0.02:  # cambio irrelevante: no tocar
            return ch
        lut = [min(255, int(i * s + 0.5)) for i in range(256)]
        return ch.point(lut)

    r, g, b = rgb.split()
    return Image.merge("RGB", (scale_channel(r, r_m), scale_channel(g, g_m), scale_channel(b, b_m)))


def auto_enhance(img: Image.Image) -> Image.Image:
    """Mejora ligera: WB + autocontraste + color/contraste/nitidez sutiles."""

    def fx(rgb: Image.Image) -> Image.Image:
        rgb = _gray_world_wb(rgb)
        rgb = ImageOps.autocontrast(rgb, cutoff=1)  # corrige exposición plana
        rgb = ImageEnhance.Color(rgb).enhance(1.06)
        rgb = ImageEnhance.Contrast(rgb).enhance(1.04)
        rgb = ImageEnhance.Sharpness(rgb).enhance(1.15)
        return rgb

    if img.mode == "RGBA":  # preservar el canal alfa intacto
        alpha = img.getchannel("A")
        rgb = fx(img.convert("RGB"))
        rgb.putalpha(alpha)
        return rgb
    return fx(img)


# ----------------------------------------------------------------------
def remove_background(img: Image.Image, session) -> Image.Image:
    """Elimina el fondo con rembg y valida el resultado.

    Si el recorte deja <2% de píxeles visibles se considera un fallo
    (modelo confundido) y se lanza excepción para activar el fallback.
    """
    from rembg import remove

    out = remove(img, session=session)
    if out.mode != "RGBA":
        out = out.convert("RGBA")

    alpha_hist = out.getchannel("A").histogram()
    total = out.size[0] * out.size[1]
    visible = sum(alpha_hist[16:]) / max(1, total)
    if visible < 0.02:
        raise ValueError("segmentación vacía (recorte inválido)")
    return out


def flatten_on_white(img: Image.Image) -> Image.Image:
    """Aplana una imagen con alfa sobre fondo blanco (fallback)."""
    if img.mode != "RGBA":
        return img.convert("RGB")
    bg = Image.new("RGB", img.size, (255, 255, 255))
    bg.paste(img, mask=img.getchannel("A"))
    return bg


# ----------------------------------------------------------------------
def save_webp(img: Image.Image, dst: str | Path, quality: int, target_max_kb: int = 0):
    """Guarda WebP (method=6 = máxima compresión). Soporta transparencia.

    Si target_max_kb > 0, baja la calidad iterativamente (mín. 40) hasta
    cumplir el peso objetivo.
    """
    if img.mode not in ("RGB", "RGBA"):
        img = img.convert("RGB")
    q = int(quality)
    kb = 0.0
    for _ in range(7):
        img.save(dst, "WEBP", quality=q, method=6)
        kb = os.path.getsize(dst) / 1024
        if not target_max_kb or kb <= target_max_kb or q <= 40:
            break
        q = max(40, q - 8)
    return kb, q


# ----------------------------------------------------------------------
def process_one(task: tuple) -> dict:
    """Procesa UNA imagen (se ejecuta dentro de un proceso worker).

    task = (ruta_origen, ruta_destino). Devuelve una fila para el log CSV.
    Nunca lanza: todo error queda registrado en la fila con estado='error'.
    """
    src, dst = task
    cfg = _W["cfg"] or {}
    row: dict = {"fondo": "no", "error": ""}
    try:
        kb_before = os.path.getsize(src) / 1024

        img = open_image(src)
        img = normalize_mode(img)
        img = resize_keep_aspect(img, cfg.get("max_width", 0), cfg.get("max_height", 0))

        if cfg.get("enhance"):
            img = auto_enhance(img)

        if cfg.get("remove_bg"):
            if _W["session"] is not None:
                try:
                    img = remove_background(img, _W["session"])
                    row["fondo"] = "sí (transparente)"
                except Exception:
                    img = flatten_on_white(img)  # fallback pedido: fondo blanco
                    row["fondo"] = "fallback fondo blanco"
            else:
                row["fondo"] = "no (rembg no disponible)"

        kb_after, q_used = save_webp(
            img, dst, cfg.get("webp_quality", 82), cfg.get("target_max_kb", 0)
        )
        row.update(
            estado="ok",
            ancho=img.size[0],
            alto=img.size[1],
            kb_antes=round(kb_before, 1),
            kb_despues=round(kb_after, 1),
            reduccion_pct=round((1 - kb_after / kb_before) * 100, 1) if kb_before else "",
            calidad_webp=q_used,
        )
    except Exception as exc:  # imagen corrupta, formato raro, disco lleno, etc.
        row.update(estado="error", error=f"{type(exc).__name__}: {exc}")
    return row
