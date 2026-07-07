#!/usr/bin/env python3
"""
cli.py — Modo línea de comandos (sin GUI).

Ideal para automatización, servidores sin pantalla y para usarlo como
Skill de Claude Code / Cowork. Ejemplos:

  # Procesar carpeta completa
  python cli.py ./fotos --project "Fitness App" --use hero \
      --keywords "entrenamiento, fuerza" --max-width 1920 --quality 80

  # Solo generar la propuesta de nombres para revisarla antes (dry-run)
  python cli.py ./fotos --project "Fitness App" --use product-mockup --dry-run

  # Procesar usando la propuesta ya editada
  python cli.py ./fotos --names-csv ./fotos/propuesta_nombres.csv

  # Reutilizar las preferencias guardadas del proyecto
  python cli.py ./fotos --config ./fotos/imgseo_config.json
"""

from __future__ import annotations

import argparse
import multiprocessing
import sys
import time
from pathlib import Path

# Permite ejecutar el script desde cualquier directorio (p. ej. desde una Skill)
sys.path.insert(0, str(Path(__file__).resolve().parent))

from core.config import BG_MODELS, PROJECT_CONFIG_NAME, USE_TYPES, ProcessConfig  # noqa: E402
from core.pipeline import run  # noqa: E402
from core.seo import parse_user_keywords  # noqa: E402


# ----------------------------------------------------------------------
def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(
        description="Procesador masivo de imágenes con nombres SEO (WebP + rembg + mejora).",
        formatter_class=argparse.ArgumentDefaultsHelpFormatter,
    )
    p.add_argument("folder", help="Carpeta con las imágenes de origen")

    seo = p.add_argument_group("SEO")
    seo.add_argument("--project", help="Nombre del proyecto (primer segmento del nombre)")
    seo.add_argument("--use", dest="use_type",
                     help=f"Tipo de uso: {', '.join(USE_TYPES)} o uno propio")
    seo.add_argument("--keywords", help="Palabras clave separadas por comas")
    seo.add_argument("--lang", dest="language", choices=["es", "en"],
                     help="Idioma para keywords IA y alt text")

    size = p.add_argument_group("Tamaño y compresión")
    size.add_argument("--max-width", type=int, help="Ancho máximo en px (0 = sin límite)")
    size.add_argument("--max-height", type=int, help="Alto máximo en px (0 = sin límite)")
    size.add_argument("--quality", type=int, dest="webp_quality", help="Calidad WebP (40–95)")
    size.add_argument("--target-kb", type=int, dest="target_max_kb",
                      help="Peso objetivo por imagen en KB (0 = desactivado)")

    pipe = p.add_argument_group("Pipeline")
    bg = pipe.add_mutually_exclusive_group()
    bg.add_argument("--bg", dest="remove_bg", action="store_true", default=None,
                    help="Eliminar fondo (rembg)")
    bg.add_argument("--no-bg", dest="remove_bg", action="store_false",
                    help="No eliminar fondo")
    pipe.add_argument("--model", dest="bg_model", choices=BG_MODELS,
                      help="Modelo de segmentación de fondo")
    en = pipe.add_mutually_exclusive_group()
    en.add_argument("--enhance", dest="enhance", action="store_true", default=None,
                    help="Mejora automática ligera")
    en.add_argument("--no-enhance", dest="enhance", action="store_false",
                    help="Sin mejora automática")
    pipe.add_argument("--ai", dest="ai_captions", action="store_true", default=None,
                      help="Descripciones automáticas con BLIP (requiere requirements-vision.txt)")
    pipe.add_argument("--no-translate", dest="translate_keywords", action="store_false",
                      default=None, help="No traducir keywords IA al español")

    execg = p.add_argument_group("Ejecución")
    execg.add_argument("--recursive", action="store_true", default=None,
                       help="Incluir subcarpetas")
    execg.add_argument("--no-skip", dest="skip_existing", action="store_false",
                       default=None, help="Reprocesar aunque el archivo de salida ya exista")
    execg.add_argument("--workers", type=int, help="Procesos en paralelo (0 = automático)")
    execg.add_argument("--dry-run", action="store_true",
                       help="Solo generar propuesta_nombres.csv para revisar/editar")
    execg.add_argument("--names-csv", help="CSV de propuesta editado a aplicar")
    execg.add_argument("--config", help="Cargar preferencias desde un JSON")
    execg.add_argument("--save-config", help="Guardar las preferencias resultantes en un JSON")
    return p


# ----------------------------------------------------------------------
def config_from_args(args: argparse.Namespace) -> ProcessConfig:
    """Config base (JSON del proyecto si existe / --config) + overrides del CLI."""
    folder = Path(args.folder)
    if args.config:
        cfg = ProcessConfig.load(args.config)
    elif (folder / PROJECT_CONFIG_NAME).exists():
        cfg = ProcessConfig.load(folder / PROJECT_CONFIG_NAME)
        print(f"· Preferencias del proyecto cargadas: {folder / PROJECT_CONFIG_NAME}")
    else:
        cfg = ProcessConfig()

    overridable = [
        "project", "use_type", "language", "max_width", "max_height",
        "webp_quality", "target_max_kb", "remove_bg", "bg_model", "enhance",
        "ai_captions", "translate_keywords", "recursive", "skip_existing", "workers",
    ]
    for name in overridable:
        val = getattr(args, name, None)
        if val is not None:
            setattr(cfg, name, val)
    if args.keywords is not None:
        cfg.keywords = parse_user_keywords(args.keywords)
    return cfg


# ----------------------------------------------------------------------
def _progress(event: dict) -> None:
    """Barra de progreso simple en consola con ETA."""
    if event.get("msg"):
        print(f"\n· {event['msg']}")
        return
    done, total = event.get("done", 0), event.get("total", 0)
    if not total:
        return
    phase = "Analizando" if event.get("phase") == "plan" else "Procesando"
    eta = int(event.get("eta") or 0)
    bar = "█" * int(30 * done / total) + "░" * (30 - int(30 * done / total))
    sys.stdout.write(f"\r{phase} [{bar}] {done}/{total}  ETA {eta // 60}:{eta % 60:02d} ")
    sys.stdout.flush()


# ----------------------------------------------------------------------
def main() -> int:
    args = build_parser().parse_args()
    folder = Path(args.folder)
    if not folder.is_dir():
        print(f"ERROR: la carpeta no existe: {folder}")
        return 2

    cfg = config_from_args(args)
    if args.save_config:
        cfg.save(args.save_config)
        print(f"· Preferencias guardadas en {args.save_config}")

    t0 = time.time()
    result = run(folder, cfg, progress_cb=_progress,
                 names_csv=args.names_csv, dry_run=args.dry_run)
    print()

    for w in result.get("warnings", []):
        print(f"⚠ {w}")

    if result.get("msg"):
        print(result["msg"])
        return 1

    if result.get("dry_run"):
        print(f"✓ Propuesta generada: {result['plan_csv']}  ({result['total']} imágenes)")
        print("  Edita las columnas nombre_nuevo / keywords / alt_text y vuelve a correr con:")
        print(f"  python cli.py \"{folder}\" --names-csv \"{result['plan_csv']}\"")
        return 0

    ahorro = result["mb_antes"] - result["mb_despues"]
    print(
        f"✓ Listo en {time.time() - t0:.1f}s — ok: {result['ok']}, "
        f"errores: {result['error']}, omitidas: {result['omitidas']}, "
        f"fallback blanco: {result['fallback_blanco']}"
    )
    if result["mb_antes"]:
        print(f"  Peso: {result['mb_antes']:.1f} MB → {result['mb_despues']:.1f} MB "
              f"(ahorro {ahorro:.1f} MB)")
    print(f"  Salida: {result['out_dir']}\n  Log:    {result['log']}")
    return 0


if __name__ == "__main__":
    multiprocessing.freeze_support()  # necesario si se empaqueta como .exe
    raise SystemExit(main())
