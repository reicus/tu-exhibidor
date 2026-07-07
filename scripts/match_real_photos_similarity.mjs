/**
 * Empareja fotos REALES (PhotosDrive + backup) con productos Canva por similitud visual.
 * Referencia: recortes del PDF CATALOGO 2020.pdf (estilo catálogo).
 * Ejecutar: node scripts/match_real_photos_similarity.mjs
 *
 * NOTA: match_photosdrive_bulk.mjs está obsoleto — usar este script como fuente canónica.
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { getDocument, OPS } from 'pdfjs-dist/legacy/build/pdf.mjs';
import { createCanvas, loadImage } from '@napi-rs/canvas';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const PDF_PATH = path.join(ROOT, 'PhotosDrive', 'Documentos', 'CATALOGO 2020.pdf');
const MANIFEST_PATH = path.join(ROOT, 'import', 'canva-extraction-manifest.json');
const CROSSWALK_PATH = path.join(ROOT, 'import', 'tue-legacy-crosswalk.json');
const COPY_DIR = path.join(ROOT, 'docs', 'canva-copy');
const OUT = path.join(ROOT, 'public', 'images', 'catalog');
const REF_CACHE = path.join(ROOT, 'public', 'images', 'canva-refs');
const SUMMARY_PATH = path.join(ROOT, 'import', 'canva-match-summary.json');

const HASH_SIZE = 16;
const MIN_IMG = 100;
const LOW_CONFIDENCE = 0.6;

const IMAGE_SOURCES = [
  path.join(ROOT, 'PhotosDrive'),
  path.join(ROOT, 'backup/homedir/public_html/wp-content/uploads/2019/05'),
  path.join(ROOT, 'backup/homedir/public_html/wp-content/uploads/2026/07'),
];

function dedupeProducts(products) {
  const map = new Map();
  for (const p of products) map.set(p.code.toUpperCase(), p);
  return [...map.values()];
}

function productOrderOnPage(pageNum) {
  const file = path.join(COPY_DIR, `catalogo-2020-page-${String(pageNum).padStart(2, '0')}.txt`);
  if (!fs.existsSync(file)) return [];
  const text = fs.readFileSync(file, 'utf8');
  const codes = [];
  const re = /C[ÓO]DIGO:\s*([A-Z0-9\-\/\(\)]+)/gi;
  let m;
  while ((m = re.exec(text))) codes.push(m[1].toUpperCase());
  const stands = text.match(/\bTUE-STAND-\d{3}\b/gi) || [];
  for (const s of stands) if (!codes.includes(s.toUpperCase())) codes.push(s.toUpperCase());
  return codes;
}

function productsByPage(products) {
  const byPage = new Map();
  for (const p of products) {
    if (!p.page || p.page > 22) continue;
    if (!byPage.has(p.page)) byPage.set(p.page, []);
    byPage.get(p.page).push(p);
  }
  for (const [page, list] of byPage) {
    const order = productOrderOnPage(page);
    list.sort((a, b) => {
      const ia = order.indexOf(a.code.toUpperCase());
      const ib = order.indexOf(b.code.toUpperCase());
      if (ia === -1 && ib === -1) return a.code.localeCompare(b.code);
      if (ia === -1) return 1;
      if (ib === -1) return -1;
      return ia - ib;
    });
  }
  return byPage;
}

async function loadPdf() {
  const data = new Uint8Array(fs.readFileSync(PDF_PATH));
  return getDocument({ data, useSystemFonts: true }).promise;
}

async function renderPage(page) {
  const scale = 2;
  const viewport = page.getViewport({ scale });
  const canvas = createCanvas(viewport.width, viewport.height);
  await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
  return canvas;
}

async function extractEmbedded(page, pageNum) {
  const ops = await page.getOperatorList();
  const viewport = page.getViewport({ scale: 1 });
  const imgs = [];

  for (let i = 0; i < ops.fnArray.length; i++) {
    const fn = ops.fnArray[i];
    if (fn !== OPS.paintImageXObject && fn !== OPS.paintInlineImageXObject) continue;
    const name = ops.argsArray[i][0];
    let imgData;
    try { imgData = await page.objs.get(name); } catch { continue; }
    if (!imgData?.data || imgData.width < MIN_IMG || imgData.height < MIN_IMG) continue;

    let cm = [1, 0, 0, 1, 0, 0];
    for (let j = i - 1; j >= Math.max(0, i - 8); j--) {
      if (ops.fnArray[j] === OPS.transform) { cm = ops.argsArray[j]; break; }
    }
    const [, , , , e, f] = cm;
    const h = Math.hypot(cm[2], cm[3]);
    const x = e;
    const y = viewport.height - f - h;

    const c = createCanvas(imgData.width, imgData.height);
    const ctx = c.getContext('2d');
    const id = ctx.createImageData(imgData.width, imgData.height);
    id.data.set(new Uint8ClampedArray(imgData.data));
    ctx.putImageData(id, 0, 0);
    const buf = c.toBuffer('image/jpeg', 88);
    if (buf.length < 2500) continue;
    imgs.push({ x, y, area: imgData.width * imgData.height, buf });
  }

  imgs.sort((a, b) => {
    const rA = Math.round(a.y / 60), rB = Math.round(b.y / 60);
    return rA !== rB ? rA - rB : a.x - b.x;
  });

  const uniq = [];
  for (const im of imgs) {
    if (!uniq.some((u) => Math.abs(u.x - im.x) < 25 && Math.abs(u.y - im.y) < 25)) uniq.push(im);
  }
  return uniq;
}

function cropGrid(canvas, count, pageNum) {
  const cols = pageNum >= 18 && pageNum <= 22 ? 3 : count <= 4 ? 2 : 3;
  const rows = Math.ceil(count / cols);
  const w = canvas.width;
  const h = canvas.height;
  const marginTop = h * 0.07;
  const marginBottom = h * 0.1;
  const usableH = h - marginTop - marginBottom;
  const cellW = w / cols;
  const cellH = usableH / rows;
  const crops = [];
  for (let r = 0; r < rows; r++) {
    for (let c = 0; c < cols; c++) {
      const x = c * cellW + cellW * 0.04;
      const y = marginTop + r * cellH + cellH * 0.04;
      const cw = cellW * 0.92;
      const ch = cellH * 0.88;
      const crop = createCanvas(cw, ch);
      crop.getContext('2d').drawImage(canvas, x, y, cw, ch, 0, 0, cw, ch);
      crops.push({ buf: crop.toBuffer('image/jpeg', 88) });
    }
  }
  return crops.slice(0, count);
}

async function buildReferences(products) {
  fs.mkdirSync(REF_CACHE, { recursive: true });
  const doc = await loadPdf();
  const byPage = productsByPage(products);
  const refs = new Map();

  for (const [pageNum, pageProducts] of byPage) {
    const page = await doc.getPage(pageNum);
    let slots = await extractEmbedded(page, pageNum);
    if (slots.length < pageProducts.length) {
      const rendered = await renderPage(page);
      slots = cropGrid(rendered, pageProducts.length, pageNum);
    }

    for (let i = 0; i < pageProducts.length; i++) {
      const p = pageProducts[i];
      const slot = slots[i] || slots[slots.length - 1];
      if (!slot?.buf) continue;
      const refPath = path.join(REF_CACHE, `${p.code}.jpg`);
      fs.writeFileSync(refPath, slot.buf);
      refs.set(p.code, { path: refPath, page: pageNum, slot: i });
    }
  }
  return refs;
}

function walkPhotos(dir, acc = []) {
  if (!fs.existsSync(dir)) return acc;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const f = path.join(dir, e.name);
    if (e.isDirectory() && !e.name.startsWith('.')) walkPhotos(f, acc);
    else if (/\.(jpg|jpeg|png|webp)$/i.test(e.name) && !/-\d+x\d+\./i.test(e.name)) {
      try {
        if (fs.statSync(f).size > 4000) acc.push(f);
      } catch { /* skip */ }
    }
  }
  return acc;
}

async function bufferToHash(buf) {
  const img = await loadImage(buf);
  const size = HASH_SIZE;
  const c = createCanvas(size, size);
  const ctx = c.getContext('2d');
  ctx.drawImage(img, 0, 0, size, size);
  const { data } = ctx.getImageData(0, 0, size, size);
  let sum = 0;
  const gray = [];
  for (let i = 0; i < data.length; i += 4) {
    const g = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
    gray.push(g);
    sum += g;
  }
  const avg = sum / gray.length;
  let hash = '';
  for (const g of gray) hash += g >= avg ? '1' : '0';
  return hash;
}

async function fileToHash(filePath) {
  return bufferToHash(fs.readFileSync(filePath));
}

function hamming(a, b) {
  let d = 0;
  for (let i = 0; i < Math.min(a.length, b.length); i++) if (a[i] !== b[i]) d++;
  return d;
}

function filenameBoost(filePath, code, crosswalk, description) {
  const base = path.basename(filePath).toUpperCase();
  let boost = 0;
  const hints = [code, code.replace(/^TUE-/, ''), ...(crosswalk[code] || [])];
  if (!code.startsWith('TUE-')) hints.push(code);
  for (const h of hints) {
    const H = h.toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (H.length >= 3 && base.replace(/[^A-Z0-9]/g, '').includes(H)) boost = Math.max(boost, 0.2);
    if (base.includes(h.toUpperCase())) boost = Math.max(boost, 0.15);
  }
  const words = (description || '').toLowerCase().split(/\s+/).filter((w) => w.length > 5);
  for (const w of words.slice(0, 4)) {
    if (base.toLowerCase().includes(w.slice(0, 6))) boost = Math.max(boost, 0.05);
  }
  return boost;
}

async function main() {
  if (!fs.existsSync(PDF_PATH)) {
    console.error('PDF no encontrado:', PDF_PATH);
    process.exit(1);
  }

  const manifest = JSON.parse(fs.readFileSync(MANIFEST_PATH, 'utf8'));
  const crosswalk = fs.existsSync(CROSSWALK_PATH)
    ? JSON.parse(fs.readFileSync(CROSSWALK_PATH, 'utf8'))
    : {};
  const products = dedupeProducts(manifest.products);

  console.log('Generando referencias Canva desde PDF...');
  const refs = await buildReferences(products);
  console.log(`Referencias: ${refs.size}/${products.length}`);

  console.log('Indexando fotos reales...');
  const photoPaths = [];
  for (const src of IMAGE_SOURCES) photoPaths.push(...walkPhotos(src));
  console.log(`Fotos reales: ${photoPaths.length}`);

  console.log('Calculando hashes...');
  const photoHashes = [];
  for (const fp of photoPaths) {
    try {
      const hash = await fileToHash(fp);
      photoHashes.push({ path: fp, hash, rel: path.relative(ROOT, fp).replace(/\\/g, '/') });
    } catch { /* corrupt */ }
  }

  const refHashes = new Map();
  for (const [code, ref] of refs) {
    try {
      refHashes.set(code, await fileToHash(ref.path));
    } catch { /* skip */ }
  }

  const pairs = [];
  for (const p of products) {
    const refHash = refHashes.get(p.code);
    if (!refHash) continue;
    for (const ph of photoHashes) {
      const dist = hamming(refHash, ph.hash);
      const sim = 1 - dist / refHash.length;
      const boost = filenameBoost(ph.path, p.code, crosswalk, p.description);
      const score = Math.min(1, sim + boost);
      pairs.push({
        code: p.code,
        slug: p.slug,
        product: p,
        photo: ph.path,
        photoRel: ph.rel,
        similarity: sim,
        boost,
        score,
      });
    }
  }
  pairs.sort((a, b) => b.score - a.score);

  const usedPhotos = new Set();
  const assigned = new Map();
  for (const pair of pairs) {
    if (assigned.has(pair.code)) continue;
    if (usedPhotos.has(pair.photo)) continue;
    assigned.set(pair.code, pair);
    usedPhotos.add(pair.photo);
  }

  // Segunda pasada: productos sin match — permitir reutilizar foto si score alto
  for (const p of products) {
    if (assigned.has(p.code)) continue;
    const refHash = refHashes.get(p.code);
    if (!refHash) continue;
    let best = null;
    for (const ph of photoHashes) {
      const dist = hamming(refHash, ph.hash);
      const sim = 1 - dist / refHash.length;
      const boost = filenameBoost(ph.path, p.code, crosswalk, p.description);
      const score = Math.min(1, sim + boost);
      if (!best || score > best.score) best = { ...ph, similarity: sim, boost, score };
    }
    if (best) {
      assigned.set(p.code, {
        code: p.code,
        slug: p.slug,
        product: p,
        photo: best.path,
        photoRel: best.rel,
        similarity: best.similarity,
        boost: best.boost,
        score: best.score,
        reused: usedPhotos.has(best.path),
      });
      usedPhotos.add(best.path);
    }
  }

  fs.mkdirSync(OUT, { recursive: true });
  const summary = [];
  let totalScore = 0;
  let lowConf = 0;

  for (const p of products) {
    const match = assigned.get(p.code);
    const dest = path.join(OUT, `${p.slug}.jpg`);
    if (match) {
      fs.copyFileSync(match.photo, dest);
      p.imageLocal = `public/images/catalog/${p.slug}.jpg`;
      p.imageSource = 'real-photo-similarity';
      p.imageMatchScore = Math.round(match.score * 1000) / 1000;
      p.imageMatchFrom = match.photoRel;
      totalScore += match.score;
      if (match.score < LOW_CONFIDENCE) lowConf++;
      summary.push({
        code: p.code,
        slug: p.slug,
        name: p.name,
        matchedFile: match.photoRel,
        similarity: Math.round(match.similarity * 1000) / 1000,
        filenameBoost: match.boost,
        score: Math.round(match.score * 1000) / 1000,
        lowConfidence: match.score < LOW_CONFIDENCE,
        method: match.reused ? 'similarity-reused-photo' : 'similarity-unique',
      });
    } else {
      summary.push({ code: p.code, slug: p.slug, score: 0, lowConfidence: true, method: 'unmatched' });
    }
  }

  const matchedCount = summary.filter((s) => s.score > 0).length;
  const avgScore = matchedCount ? totalScore / matchedCount : 0;

  manifest.imageSource = 'real-photo-similarity';
  manifest.imagesMatchedReal = matchedCount;
  manifest.imagesMatchAvgScore = Math.round(avgScore * 1000) / 1000;
  manifest.imagesLowConfidence = lowConf;
  fs.writeFileSync(MANIFEST_PATH, JSON.stringify(manifest, null, 2));

  fs.writeFileSync(SUMMARY_PATH, JSON.stringify({
    generatedAt: new Date().toISOString(),
    matched: matchedCount,
    total: products.length,
    averageScore: Math.round(avgScore * 1000) / 1000,
    lowConfidenceCount: lowConf,
    lowConfidenceThreshold: LOW_CONFIDENCE,
    matches: summary,
  }, null, 2));

  const { spawnSync } = await import('child_process');
  spawnSync('node', [path.join(__dirname, 'refresh_csv_images.mjs')], { cwd: ROOT, stdio: 'inherit' });
  spawnSync('node', [path.join(__dirname, 'build_site_data.mjs')], { cwd: ROOT, stdio: 'inherit' });

  // Limpiar imágenes huérfanas del catálogo
  const validSlugs = new Set(products.map((p) => `${p.slug}.jpg`));
  for (const f of fs.readdirSync(OUT)) {
    if (f.endsWith('.jpg') && !validSlugs.has(f)) fs.unlinkSync(path.join(OUT, f));
  }

  console.log(`\n=== MATCH REAL FOTOS ===`);
  console.log(`Asignados: ${matchedCount}/${products.length}`);
  console.log(`Score promedio: ${avgScore.toFixed(3)}`);
  console.log(`Baja confianza (<${LOW_CONFIDENCE}): ${lowConf}`);
  console.log(`Resumen: import/canva-match-summary.json`);
}

main().catch((e) => { console.error(e); process.exit(1); });
