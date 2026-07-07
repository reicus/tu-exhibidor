/**
 * Reemplaza imágenes del sitio con salida ImgSEO de PhotosDrive.
 * Run después de: python cli.py PhotosDrive --config PhotosDrive/imgseo_config.json
 *
 * node scripts/replace_site_from_imgseo.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import sharp from 'sharp';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const DRIVE = path.join(ROOT, 'PhotosDrive');
const PROCESSED = path.join(DRIVE, 'PROCESSED');
const LOG = path.join(PROCESSED, 'log_procesamiento.csv');
const CREAM = '#ddd3c8';
const SIZES = [400, 800, 1200, 1600];

function normKey(p) {
  return path.basename(String(p).replace(/\\/g, '/')).toLowerCase();
}

function readLogAt(logPath, processedDir) {
  if (!fs.existsSync(logPath)) return new Map();
  const text = fs.readFileSync(logPath, 'utf8').replace(/^\uFEFF/, '');
  const lines = text.trim().split(/\r?\n/);
  const header = lines[0].split(';');
  const idx = Object.fromEntries(header.map((h, i) => [h.trim(), i]));
  const map = new Map();
  for (let i = 1; i < lines.length; i++) {
    const cols = lines[i].split(';');
    if (cols[idx.estado] !== 'ok') continue;
    const orig = cols[idx.archivo_original];
    const nuevo = cols[idx.nombre_nuevo];
    const alt = cols[idx.alt_text] || '';
    const webp = path.join(processedDir, nuevo);
    if (!fs.existsSync(webp)) continue;
    map.set(normKey(orig), { webp, alt, orig });
  }
  return map;
}

function readLog() {
  const map = readLogAt(LOG, PROCESSED);
  const origLog = path.join(ROOT, 'public', 'images', 'catalog', 'originals', 'PROCESSED', 'log_procesamiento.csv');
  const origProc = path.join(ROOT, 'public', 'images', 'catalog', 'originals', 'PROCESSED');
  for (const [k, v] of readLogAt(origLog, origProc)) map.set(k, v);
  return map;
}

async function webpToJpg(webpPath, outJpg, size) {
  let pipe = sharp(webpPath, { failOn: 'none' }).rotate();
  if (size) {
    pipe = pipe.resize(size, size, { fit: 'inside', withoutEnlargement: true, background: CREAM });
  }
  await pipe.flatten({ background: CREAM }).jpeg({ quality: 88, mozjpeg: true }).toFile(outJpg);
}

async function exportResponsive(webpPath, basePath) {
  fs.mkdirSync(path.dirname(basePath), { recursive: true });
  for (const w of SIZES) {
    const jpg = `${basePath}-${w}.jpg`;
    const webp = `${basePath}-${w}.webp`;
    const avif = `${basePath}-${w}.avif`;
    let resized = sharp(webpPath, { failOn: 'none' }).rotate().resize(w, w, {
      fit: 'inside', withoutEnlargement: true, background: CREAM,
    });
    const flat = resized.flatten({ background: CREAM });
    await flat.clone().jpeg({ quality: 88, mozjpeg: true }).toFile(jpg);
    await flat.clone().webp({ quality: 84 }).toFile(webp);
    await flat.clone().avif({ quality: 62 }).toFile(avif);
  }
}

function warmJpg(file) {
  // inline cream tint for light pixels
  return sharp(file, { failOn: 'none' }).rotate().raw().toBuffer({ resolveWithObject: true })
    .then(async ({ data, info }) => {
      const C = { r: 221, g: 211, b: 200 };
      const ch = info.channels;
      for (let i = 0; i < data.length; i += ch) {
        const l = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
        if (l < 175) continue;
        const t = l >= 245 ? 1 : Math.min(1, ((l - 175) / 70) * 0.95);
        data[i] = Math.round(data[i] * (1 - t) + C.r * t);
        data[i + 1] = Math.round(data[i + 1] * (1 - t) + C.g * t);
        data[i + 2] = Math.round(data[i + 2] * (1 - t) + C.b * t);
      }
      const buf = await sharp(data, { raw: { width: info.width, height: info.height, channels: ch } })
        .jpeg({ quality: 88, mozjpeg: true }).toBuffer();
      fs.writeFileSync(file, buf);
    });
}

async function replaceCatalog(procMap) {
  const summary = JSON.parse(fs.readFileSync(path.join(ROOT, 'import', 'canva-match-summary.json'), 'utf8'));
  let ok = 0;
  let miss = 0;
  for (const m of summary.matches) {
    const key = normKey(m.matchedFile);
    const slugKey = normKey(`${m.slug}.jpg`);
    const hit = procMap.get(key) || procMap.get(slugKey);
    const dest = path.join(ROOT, 'public', 'images', 'catalog', `${m.slug}.jpg`);
    if (!hit) { miss++; continue; }
    await webpToJpg(hit.webp, dest, 1200);
    await warmJpg(dest);
    if (hit.alt) m.altText = hit.alt;
    ok++;
  }
  fs.writeFileSync(path.join(ROOT, 'import', 'canva-match-summary.json'), JSON.stringify(summary, null, 2));
  return { ok, miss };
}

async function replaceFromManifest(procMap) {
  const manifest = JSON.parse(fs.readFileSync(path.join(ROOT, 'import', 'premium-gallery-manifest.json'), 'utf8'));
  const touched = new Set();
  let ok = 0;
  let miss = 0;

  const allAssets = [
    ...(manifest.hero || []),
    ...(manifest.gallery || []),
    ...(manifest.assets || []),
  ];

  for (const asset of allAssets) {
    const src = asset.source || '';
    if (!src.includes('PhotosDrive')) continue;
    const key = normKey(src);
    const hit = procMap.get(key);
    if (!hit) { miss++; continue; }
    const base = path.join(ROOT, asset.base);
    await exportResponsive(hit.webp, base);
    for (const w of SIZES) {
      await warmJpg(`${base}-${w}.jpg`);
    }
    if (hit.alt) asset.alt = hit.alt;
    touched.add(asset.base);
    ok++;
  }

  fs.writeFileSync(path.join(ROOT, 'import', 'premium-gallery-manifest.json'), JSON.stringify(manifest, null, 2));
  return { ok, miss, touched: touched.size };
}

async function rebuildHero() {
  const { spawnSync } = await import('child_process');
  const r = spawnSync('node', [path.join(__dirname, 'build_hero_slider.mjs')], { cwd: ROOT, stdio: 'inherit' });
  return r.status === 0;
}

async function main() {
  console.log('Leyendo log ImgSEO…');
  const procMap = readLog();
  console.log(`Procesadas en log: ${procMap.size}`);

  console.log('\n→ Catálogo (85)…');
  const cat = await replaceCatalog(procMap);
  console.log(`  OK: ${cat.ok}, sin match: ${cat.miss}`);

  console.log('\n→ Premium / galería / hero sources…');
  const gal = await replaceFromManifest(procMap);
  console.log(`  OK: ${gal.ok}, sin match: ${gal.miss}, bases únicas: ${gal.touched}`);

  console.log('\n→ Rebuild hero slides…');
  await rebuildHero();

  console.log('\n→ Regenerar site-data…');
  const { spawnSync } = await import('child_process');
  spawnSync('node', [path.join(__dirname, 'build_site_data.mjs')], { cwd: ROOT, stdio: 'inherit' });

  console.log('\n✓ Sitio actualizado con imágenes ImgSEO de PhotosDrive');
}

main().catch((e) => { console.error(e); process.exit(1); });
