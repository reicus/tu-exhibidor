"""
seo.py — Generación de nombres de archivo optimizados para SEO.

Formato: [proyecto]-[uso]-[palabras-clave]-[detalle].webp
Ejemplo:  fitness-app-hero-entrenamiento-fuerza-mujer.webp

Incluye: slugify sin acentos/ñ, limpieza de nombres originales tipo
"IMG_20240101", extracción de keywords desde descripciones (captions),
deduplicación de palabras, tope de longitud y manejo de colisiones.
"""

from __future__ import annotations

import re
import unicodedata

# Longitud máxima recomendada del slug (sin extensión) para URLs limpias
MAX_SLUG_LEN = 70

# Tokens basura típicos de cámaras/celulares que no aportan al SEO
JUNK_TOKENS = {
    "img", "image", "dsc", "dscn", "dscf", "pxl", "mvimg", "photo", "foto",
    "picture", "pic", "screenshot", "captura", "pantalla", "whatsapp", "wa",
    "copia", "copy", "final", "edit", "edited", "new", "nuevo", "sin", "titulo",
    "untitled", "scan", "resized", "compressed",
}

# Palabras vacías para filtrar keywords (inglés — BLIP describe en inglés)
STOP_EN = {
    "a", "an", "the", "of", "on", "in", "with", "and", "or", "at", "is", "are",
    "to", "for", "its", "his", "her", "their", "there", "this", "that", "these",
    "those", "from", "by", "be", "as", "it", "up", "down", "over", "under",
    "near", "next", "front", "back", "some", "two", "three", "four", "very",
    "image", "photo", "picture", "photograph", "closeup", "close", "view",
    "shot", "background", "standing", "sitting", "looking",
}

# Palabras vacías en español (para captions traducidas y keywords del usuario)
STOP_ES = {
    "de", "del", "la", "el", "en", "con", "y", "o", "u", "un", "una", "unos",
    "unas", "los", "las", "para", "por", "que", "se", "su", "sus", "es", "esta",
    "estan", "este", "hay", "al", "lo", "como", "mas", "muy", "sobre", "cerca",
    "junto", "frente", "detras", "imagen", "foto", "fotografia", "primer",
    "plano", "vista", "fondo", "dos", "tres", "cuatro",
}

_WORD_RE = re.compile(r"[a-záéíóúüñ]+", re.IGNORECASE)


# ----------------------------------------------------------------------
def slugify(text: str) -> str:
    """Convierte texto libre a slug web: minúsculas, sin acentos/ñ, guiones.

    'Entrenamiento Fuerza Ñandú' -> 'entrenamiento-fuerza-nandu'
    """
    text = unicodedata.normalize("NFKD", str(text))
    text = text.encode("ascii", "ignore").decode("ascii").lower()
    text = re.sub(r"[^a-z0-9]+", "-", text)
    return text.strip("-")


# ----------------------------------------------------------------------
def clean_stem_words(stem: str, max_words: int = 3) -> list[str]:
    """Extrae palabras útiles del nombre original de archivo.

    Descarta tokens basura (img, dsc, whatsapp...), números puros y tokens
    muy cortos. 'IMG_20240101_playa Ñoño' -> ['playa', 'nono']
    """
    words: list[str] = []
    for tok in slugify(stem).split("-"):
        if not tok or tok.isdigit() or len(tok) < 3:
            continue
        if tok in JUNK_TOKENS or tok in words:
            continue
        words.append(tok)
        if len(words) >= max_words:
            break
    return words


# ----------------------------------------------------------------------
def extract_keywords(text: str, lang: str = "en", max_words: int = 4) -> list[str]:
    """Extrae keywords de una descripción, filtrando palabras vacías."""
    stop = STOP_ES if lang == "es" else STOP_EN
    out: list[str] = []
    for w in _WORD_RE.findall((text or "").lower()):
        w_slug = slugify(w)
        if len(w_slug) < 3 or w_slug in stop or w_slug in out:
            continue
        out.append(w_slug)
        if len(out) >= max_words:
            break
    return out


# ----------------------------------------------------------------------
def parse_user_keywords(raw: str | list) -> list[str]:
    """Normaliza keywords del usuario ('a, b c' o lista) a slugs individuales."""
    if isinstance(raw, str):
        raw = re.split(r"[,;]+", raw)
    out: list[str] = []
    for item in raw or []:
        s = slugify(item)
        if s and s not in out:
            out.append(s)
    return out


# ----------------------------------------------------------------------
def build_seo_name(
    project: str,
    use_type: str,
    user_keywords: list[str],
    detail_words: list[str],
    used: set,
    max_len: int = MAX_SLUG_LEN,
) -> str:
    """Construye el slug final (sin extensión), único dentro de `used`.

    - Deduplica palabras entre segmentos (evita 'fitness-app-hero-fitness-...').
    - Recorta por el final si supera max_len (respetando palabras completas).
    - Ante colisión agrega sufijo numérico -02, -03...
    """
    words: list[str] = []
    for chunk in [project, use_type, *user_keywords, *detail_words]:
        for w in slugify(chunk).split("-"):
            if w and w not in words:
                words.append(w)

    if not words:
        words = ["imagen"]

    base = "-".join(words)
    while len(base) > max_len and len(words) > 2:
        words.pop()
        base = "-".join(words)

    name, n = base, 2
    while name in used:
        name = f"{base}-{n:02d}"
        n += 1
    used.add(name)
    return name


# ----------------------------------------------------------------------
def build_alt_text(
    caption: str | None,
    project: str,
    use_type: str,
    keywords: list[str],
    lang: str = "es",
) -> str:
    """Genera un alt text sugerido (clave para SEO, más que el filename)."""
    if caption:
        cap = caption.strip().rstrip(".")
        return cap[:1].upper() + cap[1:]
    kws = ", ".join(keywords[:4])
    if lang == "es":
        return f"Imagen {use_type} del proyecto {project}" + (f": {kws}" if kws else "")
    return f"{use_type} image for {project}" + (f": {kws}" if kws else "")
