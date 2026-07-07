/**
 * Reconstruye catálogo desde originals/ con sharp (sin kernel destructivo).
 * Run: node scripts/rebuild_catalog_images.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import sharp from 'sharp';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const CATALOG = path.join(ROOT, 'public', 'images', 'catalog');
const ORIGINALS = path.join(CATALOG, 'originals');
const SUMMARY_PATH = path.join(ROOT, 'import', 'canva-match-summary.json');
const PHOTOS = path.join(ROOT, 'PhotosDrive');
const TARGET = 1200;
const CREAM = '#ddd3c8';

async function isGlitchImage(file) {
  const { data, info } = await sharp(file)
    .resize(128, 128, { fit: 'inside' })
    .greyscale()
    .raw()
    .toBuffer({ resolveWithObject: true });
  const w = info.width;
  const h = info.height;
  let stripeRows = 0;
  for (let y = 1; y < h; y++) {
    let diff = 0;
    for (let x = 0; x < w; x++) {
      diff += Math.abs(data[(y - 1) * w + x] - data[y * w + x]);
    }
    if (diff / w < 6) stripeRows++;
  }
  const { channels } = await sharp(file).stats();
  const avgSat = channels.slice(0, 3).reduce((s, c) => s + (c.stdev / (c.mean || 1)), 0) / 3;
  return stripeRows > h * 0.45 && avgSat < 0.35;
}

function walkPhotos(dir, acc = []) {
  if (!fs.existsSync(dir)) return acc;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (e.name === 'Documentos') continue;
      walkPhotos(p, acc);
    } else if (/\.jpe?g$/i.test(e.name) && !/picsart|screenshot|whatsapp|wa00|collage/i.test(e.name)) {
      acc.push(p);
    }
  }
  return acc;
}

async function photoHash(file) {
  const { data, info } = await sharp(file)
    .resize(16, 16, { fit: 'fill' })
    .greyscale()
    .raw()
    .toBuffer({ resolveWithObject: true });
  const avg = data.reduce((a, b) => a + b, 0) / data.length;
  return [...data].map((v) => (v > avg ? 1 : 0)).join('');
}

function hamming(a, b) {
  let d = 0;
  for (let i = 0; i < a.length; i++) if (a[i] !== b[i]) d++;
  return d;
}

async function findPhotosDriveAlternative(refFile, photos) {
  const refHash = await photoHash(refFile);
  let best = null;
  for (const p of photos) {
    try {
      const h = await photoHash(p);
      const dist = hamming(refHash, h);
      if (!best || dist < best.dist) best = { path: p, dist };
    } catch { /* skip */ }
  }
  return best?.dist <= 6 ? best.path : null;
}

async function processCatalogImage(inputPath, destPath) {
  await sharp(inputPath, { failOn: 'none' })
    .rotate()
    .resize(TARGET, TARGET, {
      fit: 'inside',
      withoutEnlargement: false,
      background: CREAM,
    })
    .normalize()
    .modulate({ brightness: 1.03, saturation: 1.05 })
    .sharpen({ sigma: 0.4, m1: 0.5, m2: 0.3 })
    .jpeg({ quality: 90, mozjpeg: true })
    .toFile(destPath);
}

async function main() {
  const summary = JSON.parse(fs.readFileSync(SUMMARY_PATH, 'utf8'));
  const photos = walkPhotos(PHOTOS);
  let fixed = 0;
  let alt = 0;

  for (const m of summary.matches || []) {
    const dest = path.join(CATALOG, `${m.slug}.jpg`);
    const orig = path.join(ORIGINALS, `${m.slug}.jpg`);
    let source = fs.existsSync(orig) ? orig : null;

    if (source && (await isGlitchImage(source))) source = null;

    if (!source && m.matchedFile?.startsWith('PhotosDrive/')) {
      const p = path.join(ROOT, m.matchedFile.replace(/\//g, path.sep));
      if (fs.existsSync(p) && !(await isGlitchImage(p))) source = p;
    }

    if (!source && fs.existsSync(dest) && !(await isGlitchImage(dest))) {
      continue;
    }

    if (!source) {
      const ref = fs.existsSync(orig) ? orig : dest;
      const pick = await findPhotosDriveAlternative(ref, photos);
      if (pick) {
        source = pick;
        alt++;
        m.matchedFile = path.relative(ROOT, pick).replace(/\\/g, '/');
      }
    }

    if (!source) {
      console.warn('Sin fuente:', m.code);
      continue;
    }

    await processCatalogImage(source, dest);
    fixed++;
  }

  summary.rebuildRun = { at: new Date().toISOString(), fixed, photosDriveAlt: alt };
  fs.writeFileSync(SUMMARY_PATH, JSON.stringify(summary, null, 2));

  const { spawnSync } = await import('child_process');
  spawnSync('node', [path.join(__dirname, 'build_site_data.mjs')], { cwd: ROOT, stdio: 'inherit' });

  console.log(`Catálogo reconstruido: ${fixed} imágenes (${alt} alternativas PhotosDrive)`);
}

main().catch((e) => { console.error(e); process.exit(1); });
