"""
vision.py — Descripciones automáticas de imágenes (opcional).

Usa BLIP (Salesforce/blip-image-captioning-base) vía transformers para
generar un caption en inglés, extraer keywords y (opcionalmente) traducir
al español con deep-translator. Todo con imports perezosos: el resto de la
herramienta funciona aunque estas dependencias pesadas no estén instaladas.

Instalación opcional:  pip install -r requirements-vision.txt
"""

from __future__ import annotations

import importlib.util

from .seo import extract_keywords

_BLIP_MODEL_ID = "Salesforce/blip-image-captioning-base"


class CaptionEngine:
    """Motor de captions BLIP con carga perezosa (una sola vez, proceso principal)."""

    def __init__(self) -> None:
        self._processor = None
        self._model = None
        self._device = "cpu"
        self._translator = None
        self._translator_failed = False

    # ------------------------------------------------------------------
    @staticmethod
    def available() -> bool:
        """True si transformers + torch están instalados."""
        return (
            importlib.util.find_spec("transformers") is not None
            and importlib.util.find_spec("torch") is not None
        )

    # ------------------------------------------------------------------
    def load(self) -> None:
        """Descarga/carga el modelo (~1 GB la primera vez). Usa GPU si existe."""
        if self._model is not None:
            return
        import torch
        from transformers import BlipForConditionalGeneration, BlipProcessor

        self._device = "cuda" if torch.cuda.is_available() else "cpu"
        self._processor = BlipProcessor.from_pretrained(_BLIP_MODEL_ID)
        self._model = BlipForConditionalGeneration.from_pretrained(_BLIP_MODEL_ID)
        self._model.to(self._device)
        self._model.eval()

    # ------------------------------------------------------------------
    def caption(self, pil_image) -> str | None:
        """Genera una descripción corta en inglés. None si algo falla."""
        try:
            self.load()
            import torch

            img = pil_image.convert("RGB")
            img.thumbnail((384, 384))  # BLIP no necesita más resolución
            inputs = self._processor(img, return_tensors="pt").to(self._device)
            with torch.no_grad():
                out = self._model.generate(**inputs, max_new_tokens=25)
            return self._processor.decode(out[0], skip_special_tokens=True).strip()
        except Exception:
            return None

    # ------------------------------------------------------------------
    def translate_es(self, text: str) -> str | None:
        """Traduce EN->ES con deep-translator (requiere internet). None si falla."""
        if self._translator_failed:
            return None
        try:
            if self._translator is None:
                from deep_translator import GoogleTranslator

                self._translator = GoogleTranslator(source="en", target="es")
            return self._translator.translate(text)
        except Exception:
            self._translator_failed = True  # no reintentar 1000 veces sin red
            return None

    # ------------------------------------------------------------------
    def describe(self, pil_image, lang: str = "es", translate: bool = True):
        """Devuelve (caption_final, keywords) en el idioma pedido.

        Si lang='es' y la traducción falla/está desactivada, las keywords
        quedan en inglés (siguen siendo válidas para SEO internacional).
        """
        cap_en = self.caption(pil_image)
        if not cap_en:
            return None, []

        if lang == "es" and translate:
            cap_es = self.translate_es(cap_en)
            if cap_es:
                return cap_es, extract_keywords(cap_es, lang="es")
        return cap_en, extract_keywords(cap_en, lang="en")
