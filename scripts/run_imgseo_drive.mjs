/**
 * Ejecuta ImgSEO sobre PhotosDrive completo.
 * node scripts/run_imgseo_drive.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const IMGSEO = path.join(ROOT, 'Herramienta imagenes seo y compresor', 'image-seo-processor', 'image-seo-processor');
const VENV_PY = path.join(IMGSEO, '.venv', 'Scripts', 'python.exe');
const CLI = path.join(IMGSEO, 'cli.py');
const DRIVE = path.join(ROOT, 'PhotosDrive');
const CONFIG = path.join(DRIVE, 'imgseo_config.json');

function run(cmd, args, opts = {}) {
  const r = spawnSync(cmd, args, { stdio: 'inherit', ...opts });
  if (r.status !== 0) process.exit(r.status ?? 1);
}

function findPython() {
  const candidates = [
    process.env.PYTHON,
    path.join(process.env.LOCALAPPDATA || '', 'Programs/Python/Python312/python.exe'),
    path.join(process.env.LOCALAPPDATA || '', 'Programs/Python/Python313/python.exe'),
    'py',
    'python3',
    'python',
  ].filter(Boolean);
  for (const c of candidates) {
    if (c.includes(path.sep) && !fs.existsSync(c)) continue;
    const r = spawnSync(c, ['--version'], { encoding: 'utf8' });
    if (r.status === 0) return c;
  }
  throw new Error('Python 3.10+ no encontrado. Instala Python o define PYTHON.');
}

function ensureVenv(py) {
  if (!fs.existsSync(VENV_PY)) {
    console.log('Creando venv ImgSEO…');
    run(py, ['-m', 'venv', path.join(IMGSEO, '.venv')], { cwd: IMGSEO });
    run(VENV_PY, ['-m', 'pip', 'install', '-r', path.join(IMGSEO, 'requirements.txt')], { cwd: IMGSEO });
  }
}

function main() {
  if (!fs.existsSync(DRIVE)) {
    console.error('No existe PhotosDrive/');
    process.exit(1);
  }
  ensureVenv(findPython());

  const count = fs.readdirSync(DRIVE, { recursive: true })
    .filter((f) => typeof f === 'string' && /\.(jpe?g|png|webp|bmp|tiff?|heic|heif)$/i.test(f)).length;
  console.log(`\nProcesando ~${count} imágenes en PhotosDrive (puede tardar varios minutos)…\n`);

  run(VENV_PY, [CLI, DRIVE, '--config', CONFIG], {
    cwd: IMGSEO,
    env: { ...process.env, PYTHONIOENCODING: 'utf-8', PYTHONUTF8: '1' },
  });

  const log = path.join(DRIVE, 'PROCESSED', 'log_procesamiento.csv');
  if (!fs.existsSync(log)) {
    console.error('No se generó log_procesamiento.csv');
    process.exit(1);
  }
  console.log('\n✓ ImgSEO terminado. Log:', log);
}

main();
