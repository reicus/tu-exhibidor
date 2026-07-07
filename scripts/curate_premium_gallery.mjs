/**
 * Curación premium PhotosDrive → public/images/premium/
 * Run: node scripts/curate_premium_gallery.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath, pathToFileURL } from 'url';
import sharp from 'sharp';
import { DISPLAY_CATEGORIES, DISPLAY_LABELS, resolveDisplayCategory } from './category-mapping.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'public', 'images', 'premium');
const MANIFEST_PATH = path.join(ROOT, 'import', 'premium-gallery-manifest.json');
const SIZES = [400, 800, 1200, 1600];
const CREAM = '#ddd3c8';

const SKIP_RE = /picsart|screenshot|whatsapp|wa00|collage|colage|meme|logo|catalogo|\.png$/i;
const SOURCE_DIRS = [
  path.join(ROOT, 'PhotosDrive'),
  path.join(ROOT, 'backup/homedir/public_html/wp-content/uploads/2019/05'),
  path.join(ROOT, 'backup/homedir/public_html/wp-content/uploads/2026/07'),
];

const HERO_COUNT = 7;
const PER_CATEGORY = 10;

function walkImages(dir, acc = []) {
  if (!fs.existsSync(dir)) return acc;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) {
      if (e.name === 'Documentos' || e.name === 'node_modules') continue;
      walkImages(p, acc);
    } else if (/\.jpe?g$/i.test(e.name)) acc.push(p);
  }
  return acc;
}

function relPath(abs) {
  return path.relative(ROOT, abs).replace(/\\/g, '/');
}

function aHashFromBuffer(data, w, h) {
  const bits = [];
  let sum = 0;
  const px = [];
  for (let i = 0; i < data.length; i++) {
    px.push(data[i]);
    sum += data[i];
  }
  const avg = sum / px.length;
  for (const v of px) bits.push(v > avg ? 1 : 0);
  return bits.join('');
}

function hamming(a, b) {
  let d = 0;
  for (let i = 0; i < Math.min(a.length, b.length); i++) if (a[i] !== b[i]) d++;
  return d;
}

async function analyze(filePath) {
  const base = sharp(filePath, { failOn: 'none' }).rotate();
  const meta = await base.metadata();
  if (!meta.width || !meta.height) return null;

  const { data, info } = await base
    .clone()
    .resize(256, 256, { fit: 'inside' })
    .greyscale()
    .raw()
    .toBuffer({ resolveWithObject: true });

  const w = info.width;
  const h = info.height;
  let sum = 0;
  let sumSq = 0;
  for (let i = 0; i < data.length; i++) {
    sum += data[i];
    sumSq += data[i] * data[i];
  }
  const n = data.length;
  const mean = sum / n;
  const std = Math.sqrt(Math.max(0, sumSq / n - mean * mean));

  let lap = 0;
  let lapN = 0;
  for (let y = 1; y < h - 1; y++) {
    for (let x = 1; x < w - 1; x++) {
      const i = y * w + x;
      const v = -4 * data[i] + data[i - 1] + data[i + 1] + data[i - w] + data[i + w];
      lap += v * v;
      lapN++;
    }
  }
  const laplacianVar = lapN ? lap / lapN : 0;

  const { data: hashData, info: hashInfo } = await base
    .clone()
    .resize(16, 16, { fit: 'fill' })
    .greyscale()
    .raw()
    .toBuffer({ resolveWithObject: true });
  const hash = aHashFromBuffer(hashData, hashInfo.width, hashInfo.height);

  return {
    width: meta.width,
    height: meta.height,
    mean,
    std,
    laplacianVar,
    hash,
    aspect: meta.width / meta.height,
  };
}

function scoreCandidate(filePath, stats) {
  const name = path.basename(filePath);
  if (SKIP_RE.test(name)) return -999;

  const minDim = Math.min(stats.width, stats.height);
  if (minDim < 500) return -999;

  let s = 0;
  if (minDim >= 1600) s += 30;
  else if (minDim >= 1200) s += 24;
  else if (minDim >= 800) s += 16;
  else s += 8;

  if (stats.mean >= 95 && stats.mean <= 185) s += 22;
  else if (stats.mean >= 75 && stats.mean <= 205) s += 12;
  else if (stats.mean < 55 || stats.mean > 235) s -= 35;

  if (stats.std >= 22 && stats.std <= 75) s += 18;
  else if (stats.std < 12) s -= 15;

  if (stats.laplacianVar >= 120) s += 22;
  else if (stats.laplacianVar >= 60) s += 10;
  else if (stats.laplacianVar < 25) s -= 25;

  if (stats.aspect >= 1.25 && stats.aspect <= 2.1) s += 8;
  if (stats.aspect >= 0.85 && stats.aspect <= 1.15) s += 5;

  if (/^IMG_20(19|20|21|22)/i.test(name)) s += 6;

  return s;
}

function buildPhotoCategoryMap() {
  const map = new Map();
  const summaryPath = path.join(ROOT, 'import/canva-match-summary.json');
  const manifestPath = path.join(ROOT, 'import/canva-extraction-manifest.json');
  if (!fs.existsSync(summaryPath) || !fs.existsSync(manifestPath)) return map;

  const summary = JSON.parse(fs.readFileSync(summaryPath, 'utf8'));
  const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
  const byCode = new Map(manifest.products.map((p) => [p.code.toUpperCase(), p]));

  for (const m of summary.matches || []) {
    if (!m.matchedFile) continue;
    const prod = byCode.get(m.code.toUpperCase());
    if (!prod) continue;
    const cat = resolveDisplayCategory(prod);
    map.set(m.matchedFile.replace(/\\/g, '/'), cat);
  }
  return map;
}

function guessCategory(filePath, photoCatMap) {
  const rel = relPath(filePath);
  if (photoCatMap.has(rel)) return photoCatMap.get(rel);

  const n = filePath.toLowerCase();
  if (/collar|caden|pecher|cuello|bx-|e-35|e-40/.test(n)) return 'collares';
  if (/pulser|reloj|pu-|t-bar/.test(n)) return 'pulseras';
  if (/anillo|cilindro|tue-an/.test(n)) return 'anillos';
  if (/aro|arete|zarcillo|ar-/.test(n)) return 'aros';
  if (/bandej|charms|charm|bc-|dije|di-/.test(n)) return 'dijes';
  if (/stand|vitrina|set/.test(n)) return 'sets-vitrina';
  if (/bandej|ba-|base/.test(n)) return 'bandejas';
  return 'premium';
}

function seoSlug(category, index, aspect) {
  const prefix = {
    collares: 'exhibidor-collares-cadenas',
    pulseras: 'exhibidor-pulseras-relojes',
    anillos: 'exhibidor-anillos-ecocuero',
    aros: 'exhibidor-aros-zarcillos',
    bandejas: 'bandeja-exhibidor-vitrina',
    dijes: 'exhibidor-dijes-charms',
    'sets-vitrina': 'set-vitrina-modular',
    premium: 'exhibidor-joyeria-premium',
  }[category] || 'exhibidor-joyeria';
  const orient = aspect >= 1.2 ? 'horizontal' : aspect <= 0.9 ? 'vertical' : 'cuadrado';
  return `${prefix}-${orient}-${String(index).padStart(2, '0')}`;
}

function altText(category, index) {
  const label = DISPLAY_LABELS[category] || 'Exhibidor para joyería';
  return `${label} en ecocuero — fabricación Tu Exhibidor Chile (${index})`;
}

function dedupe(candidates, threshold = 6) {
  const kept = [];
  for (const c of candidates.sort((a, b) => b.score - a.score)) {
    if (kept.some((k) => hamming(k.hash, c.hash) <= threshold)) continue;
    kept.push(c);
  }
  return kept;
}

async function processSelected(item, outDir) {
  const slug = item.slug;
  const basePath = path.join(outDir, slug);
  fs.mkdirSync(path.dirname(basePath), { recursive: true });

  let pipeline = sharp(item.filePath, { failOn: 'none' })
    .rotate()
    .normalize()
    .modulate({ brightness: 1.02, saturation: 1.04 })
    .sharpen({ sigma: 0.6, m1: 0.5, m2: 0.4 });

  const sources = {};
  for (const w of SIZES) {
    const resized = pipeline.clone().resize(w, w, {
      fit: 'inside',
      withoutEnlargement: true,
      background: CREAM,
    });
    const jpgPath = `${basePath}-${w}.jpg`;
    const webpPath = `${basePath}-${w}.webp`;
    const avifPath = `${basePath}-${w}.avif`;

    await resized.clone().jpeg({ quality: 88, mozjpeg: true }).toFile(jpgPath);
    await resized.clone().webp({ quality: 84, effort: 4 }).toFile(webpPath);
    await resized.clone().avif({ quality: 62, effort: 4 }).toFile(avifPath);

    // Re-comprimir JPG si supera 200 KB
    const jpgStat = fs.statSync(jpgPath);
    if (jpgStat.size > 200_000) {
      await sharp(jpgPath).jpeg({ quality: 78, mozjpeg: true }).toFile(jpgPath);
    }

    const rel = (p) => path.relative(ROOT, p).replace(/\\/g, '/');
    sources[w] = { jpg: rel(jpgPath), webp: rel(webpPath), avif: rel(avifPath) };
  }

  return sources;
}

async function main() {
  console.log('Escaneando imágenes…');
  const files = [...new Set(SOURCE_DIRS.flatMap((d) => walkImages(d)))];
  console.log(`Encontradas: ${files.length}`);

  const photoCatMap = buildPhotoCategoryMap();
  const analyzed = [];

  for (let i = 0; i < files.length; i++) {
    if (i % 100 === 0) console.log(`Analizando ${i}/${files.length}…`);
    try {
      const stats = await analyze(files[i]);
      if (!stats) continue;
      const score = scoreCandidate(files[i], stats);
      if (score < 20) continue;
      analyzed.push({
        filePath: files[i],
        rel: relPath(files[i]),
        score,
        category: guessCategory(files[i], photoCatMap),
        ...stats,
      });
    } catch {
      /* skip corrupt */
    }
  }

  console.log(`Pasaron filtro calidad: ${analyzed.length} (${((analyzed.length / files.length) * 100).toFixed(1)}%)`);

  const deduped = dedupe(analyzed);
  console.log(`Tras deduplicar: ${deduped.length}`);

  const byCat = {};
  for (const c of DISPLAY_CATEGORIES) byCat[c] = [];
  byCat.premium = [];

  for (const c of deduped) {
    const bucket = DISPLAY_CATEGORIES.includes(c.category) ? c.category : 'premium';
    if (byCat[bucket].length < PER_CATEGORY) byCat[bucket].push(c);
  }

  // Asegurar mínimo 6 por categoría de catálogo si hay stock
  for (const cat of DISPLAY_CATEGORIES) {
    if (byCat[cat].length >= 6) continue;
    const extra = deduped
      .filter((c) => c.category === cat && !byCat[cat].some((x) => x.rel === c.rel))
      .slice(0, 6 - byCat[cat].length);
    byCat[cat].push(...extra);
  }

  const selectedMap = new Map();
  for (const [cat, list] of Object.entries(byCat)) {
    for (const item of list) selectedMap.set(item.rel, { ...item, pickCategory: cat });
  }

  const heroPool = deduped
    .filter((c) => c.aspect >= 1.12)
    .sort((a, b) => b.score - a.score)
    .slice(0, HERO_COUNT);

  for (const h of heroPool) {
    const existing = selectedMap.get(h.rel);
    if (existing) {
      existing.hero = true;
    } else {
      selectedMap.set(h.rel, { ...h, pickCategory: h.category, hero: true });
    }
  }

  const selected = [...selectedMap.values()];
  console.log(`Seleccionadas para exportar: ${selected.length}`);

  if (fs.existsSync(OUT)) {
    fs.rmSync(OUT, { recursive: true, force: true });
  }

  const manifest = {
    generatedAt: new Date().toISOString(),
    totalScanned: files.length,
    totalSelected: selected.length,
    hero: [],
    gallery: [],
    categories: {},
    assets: [],
  };

  const catCounters = {};
  let idx = 0;
  for (const item of selected.sort((a, b) => b.score - a.score)) {
    idx++;
    const cat = item.pickCategory || item.category;
    catCounters[cat] = (catCounters[cat] || 0) + 1;
    const slug = seoSlug(cat, catCounters[cat], item.aspect);
    const outDir = path.join(OUT, cat);
    const asset = {
      slug,
      alt: altText(cat, catCounters[cat]),
      category: cat,
      hero: !!item.hero,
      aspect: item.aspect >= 1.2 ? 'landscape' : item.aspect <= 0.9 ? 'portrait' : 'square',
      score: item.score,
      source: item.rel,
      base: `public/images/premium/${cat}/${slug}`,
    };

    console.log(`Procesando ${idx}/${selected.length}: ${slug}`);
    asset.sources = await processSelected({ ...item, slug, filePath: item.filePath }, outDir);
    manifest.assets.push(asset);

    if (item.hero) manifest.hero.push(asset);
    manifest.gallery.push(asset);
    if (!manifest.categories[cat]) manifest.categories[cat] = [];
    manifest.categories[cat].push(asset);
  }

  manifest.hero = manifest.hero.slice(0, HERO_COUNT);
  fs.writeFileSync(MANIFEST_PATH, JSON.stringify(manifest, null, 2));

  const siteMedia = {
    hero: manifest.hero.map((a) => a.base),
    gallery: manifest.gallery.map((a) => a.base),
    categories: {},
    premiumManifest: 'import/premium-gallery-manifest.json',
    generatedAt: manifest.generatedAt,
  };
  DISPLAY_CATEGORIES.forEach((cat) => {
    siteMedia.categories[cat] = manifest.categories[cat]?.[0]?.base;
  });

  fs.writeFileSync(path.join(ROOT, 'import/site-media.json'), JSON.stringify(siteMedia, null, 2));
  console.log(`\nListo: ${manifest.assets.length} assets → ${OUT}`);
  console.log(`Manifiesto: ${MANIFEST_PATH}`);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
