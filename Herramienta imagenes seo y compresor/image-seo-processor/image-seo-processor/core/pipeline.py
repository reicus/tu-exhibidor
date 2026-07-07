"""
pipeline.py — Orquestación del procesamiento masivo.

Fases:
  1) collect_images()  : recoge las imágenes de la carpeta origen.
  2) plan_processing() : calcula nombre SEO, keywords y alt text por imagen
                         (fase secuencial; aquí corre la IA de captions si está activa).
  3) run_processing()  : procesa en paralelo (ProcessPoolExecutor) y escribe
                         log_procesamiento.csv de forma incremental (resistente a cortes).

Extras: modo dry-run (propuesta_nombres.csv editable), overrides desde CSV,
reanudación (skip_existing) y cancelación cooperativa.
"""

from __future__ import annotations

import csv
import time
from concurrent.futures import ProcessPoolExecutor, as_completed
from pathlib import Path

from .config import PROJECT_CONFIG_NAME, ProcessConfig
from .processor import SUPPORTED_EXTS, open_image, process_one, worker_init
from .seo import (
    build_alt_text,
    build_seo_name,
    clean_stem_words,
    parse_user_keywords,
    slugify,
)

LOG_NAME = "log_procesamiento.csv"
PLAN_NAME = "propuesta_nombres.csv"
CSV_DELIM = ";"  # amigable con Excel en español (Chile/LatAm)

LOG_FIELDS = [
    "archivo_original", "nombre_nuevo", "estado", "fondo", "ancho", "alto",
    "kb_antes", "kb_despues", "reduccion_pct", "calidad_webp",
    "keywords", "alt_text", "error",
]


# ----------------------------------------------------------------------
def collect_images(source: Path, recursive: bool, output_dirname: str) -> list[Path]:
    """Lista ordenada de imágenes soportadas, excluyendo la carpeta de salida."""
    source = Path(source)
    out_dir = source / output_dirname
    it = source.rglob("*") if recursive else source.iterdir()
    files = [
        p for p in it
        if p.is_file()
        and p.suffix.lower() in SUPPORTED_EXTS
        and out_dir not in p.parents
    ]
    return sorted(files)


# ----------------------------------------------------------------------
def _emit(cb, **event) -> None:
    """Notifica progreso al llamador (GUI/CLI) sin romper el pipeline si falla."""
    if cb:
        try:
            cb(event)
        except Exception:
            pass


# ----------------------------------------------------------------------
def plan_processing(files, source: Path, cfg: ProcessConfig, progress_cb=None, cancel=None):
    """Genera el plan: por imagen -> nombre SEO final, keywords y alt text.

    Devuelve (plan, warnings). Cada ítem del plan:
      {src, archivo_original, nombre_nuevo, keywords, alt_text}
    """
    warnings: list[str] = []
    engine = None

    if cfg.ai_captions:
        from .vision import CaptionEngine

        if CaptionEngine.available():
            engine = CaptionEngine()
            _emit(progress_cb, phase="plan", msg="Cargando modelo de visión (BLIP)…")
            try:
                engine.load()
            except Exception as exc:
                engine = None
                warnings.append(f"No se pudo cargar BLIP ({exc}); sigo sin IA.")
        else:
            warnings.append(
                "Descripciones IA activadas pero falta instalar requirements-vision.txt; "
                "sigo sin IA."
            )

    user_kws = parse_user_keywords(cfg.keywords)
    used: set = set()
    plan: list[dict] = []
    total = len(files)
    t0 = time.time()

    for i, path in enumerate(files):
        if cancel is not None and cancel.is_set():
            break

        caption, ai_kws = None, []
        if engine is not None:
            try:
                thumb = open_image(path)
                caption, ai_kws = engine.describe(
                    thumb, lang=cfg.language, translate=cfg.translate_keywords
                )
            except Exception:
                pass  # imagen ilegible: el error real se registra al procesar

        # Detalle del nombre: keywords IA > palabras útiles del nombre original
        detail = ai_kws or clean_stem_words(path.stem)
        slug = build_seo_name(cfg.project, cfg.use_type, user_kws, detail, used)
        all_kws = list(dict.fromkeys(user_kws + ai_kws))  # únicas, en orden

        plan.append({
            "src": str(path),
            "archivo_original": str(path.relative_to(source)),
            "nombre_nuevo": f"{slug}.webp",
            "keywords": ", ".join(all_kws),
            "alt_text": build_alt_text(caption, cfg.project, cfg.use_type, all_kws, cfg.language),
        })

        done = i + 1
        eta = (time.time() - t0) / done * (total - done) if done else 0
        _emit(progress_cb, phase="plan", done=done, total=total, eta=eta)

    return plan, warnings


# ----------------------------------------------------------------------
def write_plan_csv(plan: list[dict], path: Path) -> Path:
    """Escribe la propuesta editable (dry-run). Columnas editables:
    nombre_nuevo, keywords, alt_text."""
    with open(path, "w", newline="", encoding="utf-8-sig") as fh:
        w = csv.DictWriter(
            fh,
            fieldnames=["archivo_original", "nombre_nuevo", "keywords", "alt_text"],
            delimiter=CSV_DELIM,
            extrasaction="ignore",
        )
        w.writeheader()
        w.writerows(plan)
    return path


def apply_plan_overrides(plan: list[dict], csv_path: Path) -> int:
    """Aplica nombres/keywords editados por el usuario en propuesta_nombres.csv.

    Sanea los nombres (slugify), garantiza extensión .webp y luego hace un
    pase final de deduplicación global. Devuelve cuántas filas cambiaron.
    """
    with open(csv_path, newline="", encoding="utf-8-sig") as fh:
        rows = {r["archivo_original"]: r for r in csv.DictReader(fh, delimiter=CSV_DELIM)}

    changed = 0
    for item in plan:
        edit = rows.get(item["archivo_original"])
        if not edit:
            continue
        new_name = (edit.get("nombre_nuevo") or "").strip()
        if new_name:
            stem = slugify(Path(new_name).stem)
            if stem and stem != Path(item["nombre_nuevo"]).stem:
                item["nombre_nuevo"] = f"{stem}.webp"
                changed += 1
        if (edit.get("keywords") or "").strip():
            item["keywords"] = edit["keywords"].strip()
        if (edit.get("alt_text") or "").strip():
            item["alt_text"] = edit["alt_text"].strip()

    # Pase final: garantizar unicidad global aunque el usuario repita nombres
    used: set = set()
    for item in plan:
        base = Path(item["nombre_nuevo"]).stem or "imagen"
        name, n = base, 2
        while name in used:
            name = f"{base}-{n:02d}"
            n += 1
        used.add(name)
        item["nombre_nuevo"] = f"{name}.webp"
    return changed


# ----------------------------------------------------------------------
def run_processing(plan, source: Path, cfg: ProcessConfig, progress_cb=None, cancel=None) -> dict:
    """Procesa el plan en paralelo y escribe el log CSV fila a fila."""
    out_dir = Path(source) / cfg.output_dirname
    out_dir.mkdir(parents=True, exist_ok=True)

    # Separar lo que se procesa de lo que se omite (reanudación)
    todo, skipped = [], []
    for item in plan:
        dst = out_dir / item["nombre_nuevo"]
        if cfg.skip_existing and dst.exists():
            skipped.append({**item, "estado": "omitida (ya existía)", "dst": str(dst)})
        else:
            todo.append((item, dst))

    total = len(plan)
    summary = {"total": total, "ok": 0, "error": 0, "omitidas": len(skipped),
               "fallback_blanco": 0, "mb_antes": 0.0, "mb_despues": 0.0,
               "cancelado": False, "out_dir": str(out_dir),
               "log": str(out_dir / LOG_NAME)}

    t0 = time.time()
    done = 0

    with open(out_dir / LOG_NAME, "w", newline="", encoding="utf-8-sig") as log_fh:
        writer = csv.DictWriter(log_fh, fieldnames=LOG_FIELDS,
                                delimiter=CSV_DELIM, extrasaction="ignore")
        writer.writeheader()

        for row in skipped:  # registrar omitidas de una vez
            writer.writerow(row)
            done += 1
            _emit(progress_cb, phase="process", done=done, total=total, eta=0, row=row)
        log_fh.flush()

        if todo:
            workers = min(cfg.resolved_workers(), len(todo))
            with ProcessPoolExecutor(
                max_workers=workers,
                initializer=worker_init,
                initargs=(cfg.to_worker_dict(),),
            ) as pool:
                fut_map = {
                    pool.submit(process_one, (it["src"], str(dst))): (it, dst)
                    for it, dst in todo
                }
                t_proc = time.time()
                proc_done = 0
                for fut in as_completed(fut_map):
                    item, dst = fut_map[fut]
                    try:
                        result = fut.result()
                    except Exception as exc:  # crash del worker
                        result = {"estado": "error", "error": f"{type(exc).__name__}: {exc}"}

                    row = {**item, **result, "dst": str(dst)}
                    writer.writerow(row)
                    log_fh.flush()

                    # Estadísticas
                    if row.get("estado") == "ok":
                        summary["ok"] += 1
                        summary["mb_antes"] += float(row.get("kb_antes") or 0) / 1024
                        summary["mb_despues"] += float(row.get("kb_despues") or 0) / 1024
                        if "fallback" in str(row.get("fondo", "")):
                            summary["fallback_blanco"] += 1
                    else:
                        summary["error"] += 1

                    done += 1
                    proc_done += 1
                    eta = (time.time() - t_proc) / proc_done * (len(todo) - proc_done)
                    _emit(progress_cb, phase="process", done=done, total=total, eta=eta, row=row)

                    if cancel is not None and cancel.is_set():
                        summary["cancelado"] = True
                        pool.shutdown(wait=False, cancel_futures=True)
                        break

    summary["duracion_s"] = round(time.time() - t0, 1)
    return summary


# ----------------------------------------------------------------------
def run(source, cfg: ProcessConfig, progress_cb=None, cancel=None,
        names_csv=None, dry_run: bool = False) -> dict:
    """Punto de entrada único usado por la GUI, el CLI y la Skill de Claude."""
    source = Path(source)
    files = collect_images(source, cfg.recursive, cfg.output_dirname)
    if not files:
        return {"total": 0, "msg": "No se encontraron imágenes en la carpeta."}

    plan, warnings = plan_processing(files, source, cfg, progress_cb, cancel)

    if names_csv:
        try:
            n = apply_plan_overrides(plan, Path(names_csv))
            warnings.append(f"Se aplicaron {n} nombres editados desde {Path(names_csv).name}.")
        except Exception as exc:
            warnings.append(f"No se pudo leer {names_csv}: {exc}")

    if dry_run:
        path = write_plan_csv(plan, source / PLAN_NAME)
        return {"dry_run": True, "total": len(plan), "plan_csv": str(path),
                "warnings": warnings}

    summary = run_processing(plan, source, cfg, progress_cb, cancel)
    summary["warnings"] = warnings

    # Guardar preferencias del proyecto junto a la carpeta origen
    try:
        cfg.save(source / PROJECT_CONFIG_NAME)
        summary["config"] = str(source / PROJECT_CONFIG_NAME)
    except Exception:
        pass
    return summary
