/**
 * Refresca catálogo desde PhotosDrive matchedFile + detecta imágenes malas.
 * Run: node scripts/refresh_catalog_from_photos.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import sharp from 'sharp';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const CATALOG = path.join(ROOT, 'public', 'images', 'catalog');
const SUMMARY_PATH = path.join(ROOT, 'import', 'canva-match-summary.json');
const MANIFEST_PATH = path.join(ROOT, 'import', 'image-quality.json');
const TARGET = 1200;
const CREAM = '#faf7f2';

/** Fotos incorrectas en match summary → reemplazo manual PhotosDrive */
const SOURCE_OVERRIDE = {
  'TUE-BA-003': 'PhotosDrive/IMG_20191012_174313.jpg',
  'TUE-AN-016': 'PhotosDrive/IMG_20191012_174112.jpg',
  'TUE-AR-040': 'PhotosDrive/IMG_20190720_180640.jpg',
};

export async function isCorruptImage(file) {
  if (!fs.existsSync(file)) return true;
  try {
    const { data, info } = await sharp(file)
      .resize(128, 128, { fit: 'inside' })
      .raw()
      .toBuffer({ resolveWithObject: true });
    const ch = info.channels;
    const w = info.width;
    const h = info.height;
    let satSum = 0;
    let n = 0;
    let stripeRows = 0;
    for (let y = 0; y < h; y++) {
      for (let x = 0; x < w; x++) {
        const i = (y * w + x) * ch;
        const r = data[i];
        const g = data[i + 1];
        const b = data[i + 2];
        const mx = Math.max(r, g, b);
        const mn = Math.min(r, g, b);
        satSum += mx ? (mx - mn) / mx : 0;
        n++;
      }
      if (y > 0) {
        let diff = 0;
        for (let x = 0; x < w; x++) {
          const i = (y * w + x) * ch;
          const j = ((y - 1) * w + x) * ch;
          diff += Math.abs(data[i] - data[j]);
        }
        if (diff / w < 8) stripeRows++;
      }
    }
    const avgSat = satSum / n;
    // Corrupt: casi sin color + muchas filas horizontales (marca de agua / canva)
    if (avgSat < 0.07 && stripeRows > h * 0.35) return true;
    if (avgSat < 0.04) return true;
    return false;
  } catch {
    return true;
  }
}

async function processCatalogImage(inputPath, destPath) {
  await sharp(inputPath, { failOn: 'none' })
    .rotate()
    .resize(TARGET, TARGET, { fit: 'inside', withoutEnlargement: false, background: CREAM })
    .normalize()
    .modulate({ brightness: 1.03, saturation: 1.05 })
    .sharpen({ sigma: 0.35 })
    .jpeg({ quality: 90, mozjpeg: true })
    .toFile(destPath);
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

function pickAlternatePhoto(code, photos, used) {
  const key = code.toLowerCase().replace(/[^a-z0-9]/g, '');
  const scored = photos
    .filter((p) => !used.has(p))
    .map((p) => {
      const n = path.basename(p).toLowerCase();
      let s = 0;
      if (n.includes(key)) s += 50;
      if (n.includes(code.split('-').pop()?.toLowerCase())) s += 20;
      return { p, s };
    })
    .filter((x) => x.s > 0)
    .sort((a, b) => b.s - a.s);
  return scored[0]?.p || null;
}

async function main() {
  const summary = JSON.parse(fs.readFileSync(SUMMARY_PATH, 'utf8'));
  const photos = walkPhotos(path.join(ROOT, 'PhotosDrive'));
  const quality = {};
  let fixed = 0;

  for (const m of summary.matches || []) {
    const dest = path.join(CATALOG, `${m.slug}.jpg`);
    const candidates = [];

    if (SOURCE_OVERRIDE[m.code]) {
      candidates.push(path.join(ROOT, SOURCE_OVERRIDE[m.code].replace(/\//g, path.sep)));
    }

    if (m.matchedFile?.startsWith('PhotosDrive/')) {
      candidates.push(path.join(ROOT, m.matchedFile.replace(/\//g, path.sep)));
    }

    const orig = path.join(CATALOG, 'originals', `${m.slug}.jpg`);
    if (fs.existsSync(orig)) candidates.push(orig);

    const alt = pickAlternatePhoto(m.code, photos, new Set());
    if (alt) candidates.push(alt);

    let source = null;
    for (const c of candidates) {
      if (fs.existsSync(c) && !(await isCorruptImage(c))) {
        source = c;
        break;
      }
    }

    if (!source && candidates[0] && fs.existsSync(candidates[0])) {
      source = candidates[0];
    }

    if (!source) {
      quality[m.code] = { ok: false, reason: 'no-source', image: m.slug };
      continue;
    }

    await processCatalogImage(source, dest);
    const ok = !(await isCorruptImage(dest));
    quality[m.code] = {
      ok,
      source: path.relative(ROOT, source).replace(/\\/g, '/'),
      image: `public/images/catalog/${m.slug}.jpg`,
    };
    if (ok) fixed++;
    else quality[m.code].reason = 'still-corrupt';
  }

  fs.writeFileSync(MANIFEST_PATH, JSON.stringify({ generatedAt: new Date().toISOString(), quality }, null, 2));

  const { spawnSync } = await import('child_process');
  spawnSync('node', [path.join(__dirname, 'build_site_data.mjs')], { cwd: ROOT, stdio: 'inherit' });

  const bad = Object.entries(quality).filter(([, v]) => !v.ok);
  console.log(`Catálogo refrescado: ${fixed} OK, ${bad.length} problemáticos`);
  if (bad.length) console.log(bad.map(([c]) => c).join(', '));
}

main().catch((e) => { console.error(e); process.exit(1); });
