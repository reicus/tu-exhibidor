"""
config.py — Configuración del procesador de imágenes SEO.

Define la dataclass ProcessConfig (todas las preferencias del proyecto),
los tipos de uso soportados y los tamaños sugeridos por tipo de uso.
Las preferencias se guardan/cargan como JSON (config por proyecto).
"""

from __future__ import annotations

import json
import os
from dataclasses import dataclass, field, asdict
from pathlib import Path

# Tipos de uso comunes para assets web (el usuario también puede escribir uno propio)
USE_TYPES = [
    "hero", "product-mockup", "feature", "background", "banner",
    "blog-image", "og-image", "gallery", "testimonial", "thumbnail",
    "logo", "icon",
]

# Ancho máximo sugerido (px) según el tipo de uso — pensado para mockups/web
SIZE_PRESETS = {
    "hero": 1920, "background": 1920, "banner": 1600,
    "product-mockup": 1200, "blog-image": 1200, "og-image": 1200,
    "feature": 1000, "gallery": 1000, "testimonial": 800,
    "thumbnail": 600, "logo": 512, "icon": 256,
}

# Modelos de rembg disponibles (de más rápido a más preciso)
BG_MODELS = ["u2netp", "u2net", "isnet-general-use"]

# Nombre del archivo de preferencias que se guarda junto a cada carpeta de proyecto
PROJECT_CONFIG_NAME = "imgseo_config.json"


@dataclass
class ProcessConfig:
    """Preferencias completas de un proyecto de procesamiento."""

    # --- SEO / nombres ---
    project: str = "mi-proyecto"          # nombre del proyecto (primer segmento del filename)
    use_type: str = "product-mockup"      # uso del asset (segundo segmento)
    keywords: list = field(default_factory=list)  # palabras clave principales del usuario
    language: str = "es"                  # 'es' o 'en' (keywords IA y alt text)

    # --- Tamaño / compresión ---
    max_width: int = 1600                 # 0 = sin límite
    max_height: int = 0                   # 0 = sin límite
    webp_quality: int = 82                # calidad WebP (40–95 recomendado)
    target_max_kb: int = 0                # peso objetivo por imagen (0 = desactivado)

    # --- Pipeline ---
    remove_bg: bool = True                # eliminar fondo con rembg (fallback: fondo blanco)
    bg_model: str = "u2net"               # modelo de segmentación
    enhance: bool = True                  # mejora ligera (WB, contraste, nitidez)
    ai_captions: bool = False             # descripciones automáticas con BLIP (opcional, pesado)
    translate_keywords: bool = True       # traducir keywords IA al español (deep-translator)

    # --- Ejecución ---
    recursive: bool = False               # incluir subcarpetas
    skip_existing: bool = True            # omitir imágenes ya procesadas (reanudar)
    workers: int = 0                      # 0 = automático
    output_dirname: str = "PROCESSED"     # carpeta de salida dentro de la carpeta origen

    # ------------------------------------------------------------------
    def resolved_workers(self) -> int:
        """Número real de procesos a usar.

        Con eliminación de fondo activa se limita a 4 por defecto porque cada
        proceso carga su propia copia del modelo ONNX (consumo de RAM).
        """
        n = int(self.workers or 0)
        if n <= 0:
            cpu = os.cpu_count() or 2
            n = max(1, cpu - 1)
            if self.remove_bg:
                n = min(n, 4)
        return max(1, n)

    # ------------------------------------------------------------------
    def to_worker_dict(self) -> dict:
        """Subconjunto liviano y serializable que necesitan los workers."""
        return {
            "max_width": int(self.max_width),
            "max_height": int(self.max_height),
            "webp_quality": int(self.webp_quality),
            "target_max_kb": int(self.target_max_kb),
            "remove_bg": bool(self.remove_bg),
            "bg_model": self.bg_model,
            "enhance": bool(self.enhance),
        }

    # ------------------------------------------------------------------
    def save(self, path: Path | str) -> None:
        """Guarda las preferencias como JSON legible."""
        Path(path).write_text(
            json.dumps(asdict(self), indent=2, ensure_ascii=False),
            encoding="utf-8",
        )

    @classmethod
    def load(cls, path: Path | str) -> "ProcessConfig":
        """Carga un JSON de preferencias tolerando claves faltantes o extra."""
        data = json.loads(Path(path).read_text(encoding="utf-8"))
        valid = {f for f in cls.__dataclass_fields__}  # type: ignore[attr-defined]
        return cls(**{k: v for k, v in data.items() if k in valid})
