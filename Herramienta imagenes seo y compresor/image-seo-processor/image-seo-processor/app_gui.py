#!/usr/bin/env python3
"""
app_gui.py — Interfaz gráfica del procesador masivo de imágenes SEO.

Construida con customtkinter. El procesamiento corre en un hilo de fondo
que a su vez lanza el pool de procesos; la GUI recibe el progreso por una
cola (queue) y nunca se congela. Incluye: selección de carpeta, nombre de
proyecto, tipo de uso (con preset de tamaño), keywords, sliders de ancho y
calidad, checkboxes del pipeline, barra de progreso con ETA, vista previa
de las primeras 5 imágenes, visor de log y botón de cancelar.

Ejecutar:  python app_gui.py
"""

from __future__ import annotations

import multiprocessing
import os
import queue
import subprocess
import sys
import threading
from pathlib import Path

# Permite ejecutar desde cualquier directorio
sys.path.insert(0, str(Path(__file__).resolve().parent))

try:
    import customtkinter as ctk
except ImportError:  # mensaje claro si faltan dependencias
    print("Falta customtkinter. Instala las dependencias base con:\n"
          "  pip install -r requirements.txt")
    raise SystemExit(1)

from tkinter import filedialog, messagebox  # noqa: E402

from PIL import Image  # noqa: E402

from core.config import (  # noqa: E402
    BG_MODELS, PROJECT_CONFIG_NAME, SIZE_PRESETS, USE_TYPES, ProcessConfig,
)
from core.pipeline import PLAN_NAME, run  # noqa: E402
from core.seo import parse_user_keywords  # noqa: E402

# Preferencias globales (última sesión), aparte del config por proyecto
GLOBAL_CONFIG = Path.home() / ".image_seo_processor" / "config.json"


# ----------------------------------------------------------------------
def open_path(path) -> None:
    """Abre un archivo o carpeta con la app por defecto del sistema."""
    try:
        if sys.platform == "win32":
            os.startfile(str(path))  # type: ignore[attr-defined]
        elif sys.platform == "darwin":
            subprocess.Popen(["open", str(path)])
        else:
            subprocess.Popen(["xdg-open", str(path)])
    except Exception:
        pass


def fmt_eta(seconds: float) -> str:
    s = max(0, int(seconds or 0))
    return f"{s // 60}:{s % 60:02d}"


# ======================================================================
class App(ctk.CTk):
    def __init__(self) -> None:
        super().__init__()
        self.title("Procesador masivo de imágenes SEO")
        self.geometry("920x820")
        self.minsize(820, 700)

        # --- Estado ---
        self.source_dir: str | None = None
        self.q: queue.Queue = queue.Queue()
        self.cancel_event = threading.Event()
        self.last_out: str | None = None
        self.last_log: str | None = None
        self._preview_refs: list = []  # evita que el GC borre las miniaturas

        self._build_ui()
        self._load_global_config()
        self.after(120, self._poll_queue)

    # ==================================================================
    # Construcción de la interfaz
    # ==================================================================
    def _build_ui(self) -> None:
        self.grid_rowconfigure(0, weight=1)
        self.grid_columnconfigure(0, weight=1)

        # ---------- Zona de configuración (con scroll) ----------
        cfgf = ctk.CTkScrollableFrame(self, label_text="Configuración")
        cfgf.grid(row=0, column=0, sticky="nsew", padx=12, pady=(12, 6))
        for col in range(4):
            cfgf.grid_columnconfigure(col, weight=1 if col in (1, 3) else 0)

        r = 0
        self.folder_btn = ctk.CTkButton(cfgf, text="📁  Seleccionar carpeta…",
                                        command=self._choose_folder)
        self.folder_btn.grid(row=r, column=0, padx=8, pady=8, sticky="w")
        self.folder_label = ctk.CTkLabel(cfgf, text="(ninguna carpeta seleccionada)",
                                         anchor="w", text_color="gray")
        self.folder_label.grid(row=r, column=1, columnspan=3, padx=8, pady=8, sticky="ew")

        r += 1
        ctk.CTkLabel(cfgf, text="Proyecto").grid(row=r, column=0, padx=8, sticky="w")
        self.project_entry = ctk.CTkEntry(cfgf, placeholder_text="ej: fitness-app")
        self.project_entry.grid(row=r, column=1, padx=8, pady=4, sticky="ew")
        ctk.CTkLabel(cfgf, text="Idioma keywords").grid(row=r, column=2, padx=8, sticky="w")
        self.lang_menu = ctk.CTkOptionMenu(cfgf, values=["es", "en"], width=90)
        self.lang_menu.grid(row=r, column=3, padx=8, pady=4, sticky="w")

        r += 1
        ctk.CTkLabel(cfgf, text="Tipo de uso").grid(row=r, column=0, padx=8, sticky="w")
        self.use_combo = ctk.CTkComboBox(cfgf, values=USE_TYPES,
                                         command=self._on_use_change)
        self.use_combo.grid(row=r, column=1, padx=8, pady=4, sticky="ew")
        ctk.CTkLabel(cfgf, text="Modelo de fondo").grid(row=r, column=2, padx=8, sticky="w")
        self.model_menu = ctk.CTkOptionMenu(cfgf, values=BG_MODELS, width=170)
        self.model_menu.grid(row=r, column=3, padx=8, pady=4, sticky="w")

        r += 1
        ctk.CTkLabel(cfgf, text="Keywords").grid(row=r, column=0, padx=8, sticky="w")
        self.kw_entry = ctk.CTkEntry(
            cfgf, placeholder_text="palabras clave separadas por comas: entrenamiento, fuerza")
        self.kw_entry.grid(row=r, column=1, columnspan=3, padx=8, pady=4, sticky="ew")

        # --- Sliders ---
        r += 1
        ctk.CTkLabel(cfgf, text="Ancho máximo").grid(row=r, column=0, padx=8, sticky="w")
        self.width_slider = ctk.CTkSlider(cfgf, from_=0, to=3840,
                                          command=self._on_width_change)
        self.width_slider.grid(row=r, column=1, columnspan=2, padx=8, pady=6, sticky="ew")
        self.width_label = ctk.CTkLabel(cfgf, text="1600 px", width=110, anchor="w")
        self.width_label.grid(row=r, column=3, padx=8, sticky="w")

        r += 1
        ctk.CTkLabel(cfgf, text="Calidad WebP").grid(row=r, column=0, padx=8, sticky="w")
        self.quality_slider = ctk.CTkSlider(cfgf, from_=40, to=95, number_of_steps=55,
                                            command=self._on_quality_change)
        self.quality_slider.grid(row=r, column=1, columnspan=2, padx=8, pady=6, sticky="ew")
        self.quality_label = ctk.CTkLabel(cfgf, text="82", width=110, anchor="w")
        self.quality_label.grid(row=r, column=3, padx=8, sticky="w")

        # --- Campos numéricos ---
        r += 1
        ctk.CTkLabel(cfgf, text="Alto máx. px (0 = sin límite)").grid(row=r, column=0, padx=8, sticky="w")
        self.height_entry = ctk.CTkEntry(cfgf, width=90)
        self.height_entry.grid(row=r, column=1, padx=8, pady=4, sticky="w")
        ctk.CTkLabel(cfgf, text="Peso objetivo KB (0 = off)").grid(row=r, column=2, padx=8, sticky="w")
        self.target_entry = ctk.CTkEntry(cfgf, width=90)
        self.target_entry.grid(row=r, column=3, padx=8, pady=4, sticky="w")

        r += 1
        ctk.CTkLabel(cfgf, text="Procesos en paralelo (0 = auto)").grid(row=r, column=0, padx=8, sticky="w")
        self.workers_entry = ctk.CTkEntry(cfgf, width=90)
        self.workers_entry.grid(row=r, column=1, padx=8, pady=4, sticky="w")

        # --- Checkboxes del pipeline ---
        r += 1
        self.var_bg = ctk.BooleanVar(value=True)
        self.var_enh = ctk.BooleanVar(value=True)
        self.var_ai = ctk.BooleanVar(value=False)
        self.var_tr = ctk.BooleanVar(value=True)
        self.var_rec = ctk.BooleanVar(value=False)
        self.var_skip = ctk.BooleanVar(value=True)
        self.var_dry = ctk.BooleanVar(value=False)

        checks = [
            ("Eliminar fondo (transparente)", self.var_bg),
            ("Mejorar calidad (WB + nitidez)", self.var_enh),
            ("Descripciones automáticas (IA)", self.var_ai),
            ("Traducir keywords IA a ES", self.var_tr),
            ("Incluir subcarpetas", self.var_rec),
            ("Omitir ya procesadas (reanudar)", self.var_skip),
        ]
        for i, (txt, var) in enumerate(checks):
            cb = ctk.CTkCheckBox(cfgf, text=txt, variable=var)
            cb.grid(row=r + i // 2, column=(i % 2) * 2, columnspan=2,
                    padx=8, pady=4, sticky="w")
        r += (len(checks) + 1) // 2

        self.dry_cb = ctk.CTkCheckBox(
            cfgf, variable=self.var_dry,
            text="Solo generar propuesta de nombres (CSV para revisar/editar antes)")
        self.dry_cb.grid(row=r, column=0, columnspan=4, padx=8, pady=(8, 4), sticky="w")

        # ---------- Zona inferior fija: acciones + progreso + preview ----------
        bottom = ctk.CTkFrame(self)
        bottom.grid(row=1, column=0, sticky="ew", padx=12, pady=(6, 12))
        bottom.grid_columnconfigure(0, weight=1)

        actions = ctk.CTkFrame(bottom, fg_color="transparent")
        actions.grid(row=0, column=0, sticky="ew", pady=(8, 4))
        actions.grid_columnconfigure(0, weight=1)
        self.process_btn = ctk.CTkButton(
            actions, text="▶  PROCESAR TODAS LAS IMÁGENES", height=44,
            font=ctk.CTkFont(size=16, weight="bold"), command=self._start)
        self.process_btn.grid(row=0, column=0, padx=(10, 6), sticky="ew")
        self.cancel_btn = ctk.CTkButton(
            actions, text="✖ Cancelar", height=44, width=120,
            fg_color="#8a3333", hover_color="#a94444",
            state="disabled", command=self._cancel)
        self.cancel_btn.grid(row=0, column=1, padx=(6, 10))

        self.progress = ctk.CTkProgressBar(bottom)
        self.progress.set(0)
        self.progress.grid(row=1, column=0, sticky="ew", padx=10, pady=(6, 2))
        self.status_label = ctk.CTkLabel(bottom, text="Listo.", anchor="w")
        self.status_label.grid(row=2, column=0, sticky="ew", padx=10)

        ctk.CTkLabel(bottom, text="Vista previa (primeras 5 procesadas):",
                     anchor="w").grid(row=3, column=0, sticky="w", padx=10, pady=(8, 0))
        self.preview_frame = ctk.CTkFrame(bottom, height=150)
        self.preview_frame.grid(row=4, column=0, sticky="ew", padx=10, pady=4)

        tools = ctk.CTkFrame(bottom, fg_color="transparent")
        tools.grid(row=5, column=0, sticky="ew", pady=(4, 8))
        ctk.CTkButton(tools, text="📄 Ver log completo",
                      command=self._show_log).pack(side="left", padx=10)
        ctk.CTkButton(tools, text="📂 Abrir carpeta PROCESSED",
                      command=self._open_output).pack(side="left", padx=10)
        ctk.CTkButton(tools, text="💾 Guardar preferencias",
                      command=self._save_prefs).pack(side="left", padx=10)

    # ==================================================================
    # Callbacks de widgets
    # ==================================================================
    def _on_width_change(self, value: float) -> None:
        v = int(value)
        self.width_label.configure(text="Sin límite" if v == 0 else f"{v} px")

    def _on_quality_change(self, value: float) -> None:
        self.quality_label.configure(text=str(int(value)))

    def _on_use_change(self, choice: str) -> None:
        """Al elegir un tipo de uso, sugiere el ancho preset correspondiente."""
        preset = SIZE_PRESETS.get(choice)
        if preset:
            self.width_slider.set(preset)
            self._on_width_change(preset)

    def _choose_folder(self) -> None:
        d = filedialog.askdirectory(title="Selecciona la carpeta con imágenes")
        if not d:
            return
        self.source_dir = d
        self.folder_label.configure(text=d, text_color=("black", "white"))
        pcfg = Path(d) / PROJECT_CONFIG_NAME
        if pcfg.exists():  # cargar preferencias guardadas de este proyecto
            try:
                self._apply_cfg_to_ui(ProcessConfig.load(pcfg))
                self.status_label.configure(
                    text="Preferencias del proyecto cargadas ✓")
            except Exception:
                pass

    # ==================================================================
    # Config <-> UI
    # ==================================================================
    def _int_from(self, entry: ctk.CTkEntry, default: int = 0) -> int:
        try:
            return max(0, int(float(entry.get().strip() or default)))
        except Exception:
            return default

    def _set_entry(self, entry: ctk.CTkEntry, value) -> None:
        entry.delete(0, "end")
        entry.insert(0, str(value))

    def _cfg_from_ui(self) -> ProcessConfig:
        return ProcessConfig(
            project=self.project_entry.get().strip() or "proyecto",
            use_type=self.use_combo.get().strip() or "imagen",
            keywords=parse_user_keywords(self.kw_entry.get()),
            language=self.lang_menu.get(),
            max_width=int(self.width_slider.get()),
            max_height=self._int_from(self.height_entry, 0),
            webp_quality=int(self.quality_slider.get()),
            target_max_kb=self._int_from(self.target_entry, 0),
            remove_bg=bool(self.var_bg.get()),
            bg_model=self.model_menu.get(),
            enhance=bool(self.var_enh.get()),
            ai_captions=bool(self.var_ai.get()),
            translate_keywords=bool(self.var_tr.get()),
            recursive=bool(self.var_rec.get()),
            skip_existing=bool(self.var_skip.get()),
            workers=self._int_from(self.workers_entry, 0),
        )

    def _apply_cfg_to_ui(self, cfg: ProcessConfig) -> None:
        self._set_entry(self.project_entry, cfg.project)
        self.use_combo.set(cfg.use_type)
        self._set_entry(self.kw_entry, ", ".join(cfg.keywords))
        self.lang_menu.set(cfg.language)
        self.width_slider.set(cfg.max_width)
        self._on_width_change(cfg.max_width)
        self.quality_slider.set(cfg.webp_quality)
        self._on_quality_change(cfg.webp_quality)
        self._set_entry(self.height_entry, cfg.max_height)
        self._set_entry(self.target_entry, cfg.target_max_kb)
        self._set_entry(self.workers_entry, cfg.workers)
        self.model_menu.set(cfg.bg_model)
        self.var_bg.set(cfg.remove_bg)
        self.var_enh.set(cfg.enhance)
        self.var_ai.set(cfg.ai_captions)
        self.var_tr.set(cfg.translate_keywords)
        self.var_rec.set(cfg.recursive)
        self.var_skip.set(cfg.skip_existing)

    def _load_global_config(self) -> None:
        if GLOBAL_CONFIG.exists():
            try:
                self._apply_cfg_to_ui(ProcessConfig.load(GLOBAL_CONFIG))
            except Exception:
                pass
        else:  # valores por defecto visibles
            self._apply_cfg_to_ui(ProcessConfig())

    def _save_prefs(self) -> None:
        cfg = self._cfg_from_ui()
        try:
            GLOBAL_CONFIG.parent.mkdir(parents=True, exist_ok=True)
            cfg.save(GLOBAL_CONFIG)
            if self.source_dir:
                cfg.save(Path(self.source_dir) / PROJECT_CONFIG_NAME)
            self.status_label.configure(text="Preferencias guardadas ✓")
        except Exception as exc:
            messagebox.showerror("Error", f"No se pudieron guardar: {exc}")

    # ==================================================================
    # Procesamiento (hilo de fondo + cola)
    # ==================================================================
    def _start(self) -> None:
        if not self.source_dir:
            messagebox.showwarning("Falta la carpeta",
                                   "Primero selecciona la carpeta con las imágenes.")
            return
        cfg = self._cfg_from_ui()
        dry = bool(self.var_dry.get())

        # Si existe una propuesta editada y NO estamos en dry-run, ofrecer usarla
        names_csv = None
        plan_path = Path(self.source_dir) / PLAN_NAME
        if not dry and plan_path.exists():
            if messagebox.askyesno(
                "Propuesta encontrada",
                f"Existe {PLAN_NAME} en la carpeta.\n"
                "¿Usar los nombres/keywords editados de esa propuesta?"):
                names_csv = str(plan_path)

        self._save_prefs()  # persistir preferencias antes de arrancar

        # Reset de estado visual
        self.cancel_event = threading.Event()
        self._clear_previews()
        self.progress.set(0)
        self.status_label.configure(text="Preparando…")
        self.process_btn.configure(state="disabled")
        self.cancel_btn.configure(state="normal")

        threading.Thread(
            target=self._job, args=(self.source_dir, cfg, names_csv, dry),
            daemon=True,
        ).start()

    def _job(self, source: str, cfg: ProcessConfig, names_csv, dry: bool) -> None:
        """Corre en un hilo: lanza el pipeline y reenvía eventos a la cola."""
        try:
            result = run(source, cfg,
                         progress_cb=lambda e: self.q.put(("progress", e)),
                         cancel=self.cancel_event,
                         names_csv=names_csv, dry_run=dry)
            self.q.put(("done", result))
        except Exception as exc:
            self.q.put(("fatal", f"{type(exc).__name__}: {exc}"))

    def _cancel(self) -> None:
        self.cancel_event.set()
        self.status_label.configure(text="Cancelando… (termina lo que está en curso)")

    # ==================================================================
    # Cola de eventos -> UI (siempre en el hilo principal)
    # ==================================================================
    def _poll_queue(self) -> None:
        try:
            while True:
                kind, payload = self.q.get_nowait()
                if kind == "progress":
                    self._on_progress(payload)
                elif kind == "done":
                    self._on_done(payload)
                elif kind == "fatal":
                    self._finish_ui()
                    self.status_label.configure(text=f"Error: {payload}")
                    messagebox.showerror("Error inesperado", payload)
        except queue.Empty:
            pass
        self.after(120, self._poll_queue)

    def _on_progress(self, e: dict) -> None:
        if e.get("msg"):
            self.status_label.configure(text=e["msg"])
            return
        done, total = e.get("done", 0), e.get("total", 0)
        if total:
            self.progress.set(done / total)
            phase = ("Analizando nombres" if e.get("phase") == "plan"
                     else "Procesando")
            self.status_label.configure(
                text=f"{phase}: {done}/{total}  ·  Tiempo restante ≈ {fmt_eta(e.get('eta'))}")
        row = e.get("row") or {}
        if (row.get("estado") == "ok" and row.get("dst")
                and len(self._preview_refs) < 5):
            self._add_preview(row["dst"], row.get("nombre_nuevo", ""))

    def _on_done(self, res: dict) -> None:
        self._finish_ui()
        if res.get("msg"):
            self.status_label.configure(text=res["msg"])
            messagebox.showinfo("Sin imágenes", res["msg"])
            return

        warns = "".join(f"\n⚠ {w}" for w in res.get("warnings", []))

        if res.get("dry_run"):
            self.status_label.configure(
                text=f"Propuesta generada ({res['total']} imágenes) ✓")
            if messagebox.askyesno(
                "Propuesta lista",
                f"Se generó:\n{res['plan_csv']}\n\nEdita las columnas nombre_nuevo / "
                "keywords / alt_text en Excel y vuelve a presionar Procesar "
                f"(te preguntaré si quieres usarla).{warns}\n\n¿Abrirla ahora?"):
                open_path(res["plan_csv"])
            return

        self.progress.set(1.0)
        self.last_out, self.last_log = res.get("out_dir"), res.get("log")
        ahorro = res.get("mb_antes", 0) - res.get("mb_despues", 0)
        estado = "Cancelado" if res.get("cancelado") else "Completado"
        texto = (f"{estado} en {res.get('duracion_s', 0)}s\n\n"
                 f"OK: {res.get('ok', 0)}   Errores: {res.get('error', 0)}   "
                 f"Omitidas: {res.get('omitidas', 0)}\n"
                 f"Fallback fondo blanco: {res.get('fallback_blanco', 0)}\n"
                 f"Peso: {res.get('mb_antes', 0):.1f} MB → "
                 f"{res.get('mb_despues', 0):.1f} MB  (ahorro {ahorro:.1f} MB)\n\n"
                 f"Salida: {res.get('out_dir')}")
        self.status_label.configure(
            text=f"{estado} ✓  ok {res.get('ok', 0)} · errores {res.get('error', 0)} "
                 f"· omitidas {res.get('omitidas', 0)}")
        messagebox.showinfo(estado, texto + warns)

    def _finish_ui(self) -> None:
        self.process_btn.configure(state="normal")
        self.cancel_btn.configure(state="disabled")

    # ==================================================================
    # Vista previa y utilidades
    # ==================================================================
    def _clear_previews(self) -> None:
        for child in self.preview_frame.winfo_children():
            child.destroy()
        self._preview_refs = []

    def _add_preview(self, path: str, name: str) -> None:
        try:
            img = Image.open(path)
            img.thumbnail((130, 130))
            cimg = ctk.CTkImage(light_image=img, dark_image=img, size=img.size)
            short = name if len(name) <= 22 else name[:20] + "…"
            lbl = ctk.CTkLabel(self.preview_frame, image=cimg, text=short,
                               compound="top", font=ctk.CTkFont(size=10))
            lbl.pack(side="left", padx=6, pady=6)
            self._preview_refs.append(cimg)
        except Exception:
            pass

    def _show_log(self) -> None:
        log = self.last_log
        if not log and self.source_dir:
            candidate = Path(self.source_dir) / "PROCESSED" / "log_procesamiento.csv"
            log = str(candidate) if candidate.exists() else None
        if not log or not Path(log).exists():
            messagebox.showinfo("Sin log", "Todavía no hay un log de procesamiento.")
            return
        win = ctk.CTkToplevel(self)
        win.title("Log de procesamiento")
        win.geometry("980x520")
        box = ctk.CTkTextbox(win, wrap="none", font=ctk.CTkFont(family="Courier", size=12))
        box.pack(fill="both", expand=True, padx=10, pady=10)
        box.insert("1.0", Path(log).read_text(encoding="utf-8-sig"))
        box.configure(state="disabled")
        win.after(200, win.lift)

    def _open_output(self) -> None:
        out = self.last_out or (Path(self.source_dir) / "PROCESSED"
                                if self.source_dir else None)
        if out and Path(out).exists():
            open_path(out)
        else:
            messagebox.showinfo("Sin salida", "Todavía no existe la carpeta PROCESSED.")


# ======================================================================
def main() -> None:
    ctk.set_appearance_mode("System")
    ctk.set_default_color_theme("blue")
    App().mainloop()


if __name__ == "__main__":
    multiprocessing.freeze_support()  # imprescindible en Windows / .exe
    main()
