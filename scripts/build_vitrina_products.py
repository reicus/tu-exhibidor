#!/usr/bin/env python3
"""Wrapper: ejecutar build con Node (Python no requerido en Windows)."""
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
script = ROOT / "scripts" / "build_vitrina_products.mjs"

if __name__ == "__main__":
    sys.exit(subprocess.call(["node", str(script)], cwd=str(ROOT)))
