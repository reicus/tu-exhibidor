/**
 * @deprecated Usar match_real_photos_similarity.mjs (similitud visual + códigos Canva).
 * Emparejamiento masivo: TUE codes → imágenes (PhotosDrive, backup, live, PDF Canva)
 * Ejecutar: node scripts/match_photosdrive_bulk.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';
import { createCanvas } from '@napi-rs/canvas';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'public', 'images', 'catalog');
const PDF_PAGES_OUT = path.join(ROOT, 'public', 'images', 'pdf-pages');
const MANIFEST = path.join(ROOT, 'import', 'canva-extraction-manifest.json');
const COPY_DIR = path.join(ROOT, 'docs', 'canva-copy');
const PDF_PATH = path.join(ROOT, 'PhotosDrive', 'Documentos', 'CATALOGO 2020.pdf');

const IMAGE_SOURCES = [
  path.join(ROOT, 'backup/homedir/public_html/wp-content/uploads/2019/05'),
  path.join(ROOT, 'backup/homedir/public_html/wp-content/uploads/2026/07'),
  path.join(ROOT, 'PhotosDrive'),
];

const LIVE_BASE = 'https://tuexhibidor.cl/wp-content/uploads';

/** TUE → códigos legacy WooCommerce / nombres de archivo */
const MANUAL_CROSSWALK = {
  'TUE-AN-012': ['P-M1H', 'PM1H'],
  'TUE-AN-021': ['P-M1H', 'PM1H'],
  'TUE-AN-022': ['P-M1H', 'PM1H'],
  'TUE-AN-019': ['P-SC25', 'PSC25'],
  'TUE-AN-013': ['P-SC25', 'PSC25', '251-T2B'],
  'TUE-AN-011': ['251-T2B', '251T2B'],
  'TUE-AN-020': ['251-T2B'],
  'TUE-AN-010': ['L-HE12', 'LHE12'],
  'TUE-AN-016': ['HE14', 'HE-14'],
  'TUE-AN-017': ['L-H234', 'LH234'],
  'TUE-AN-018': ['L-H234'],
  'TUE-S-009': ['L-ME3', 'LME3'],
  'TUE-S-035': ['L-ML', 'LML'],
  'TUE-AR-036': ['L-MD2', 'LMD2'],
  'TUE-S-037': ['L-MD2', 'L-ME4'],
  'TUE-AR-038': ['L-M7', 'LM7'],
  'TUE-AR-039': ['L-M6', 'LM6'],
  'TUE-RE-032': ['L-CR6', 'LCR6', 'L-BS5'],
  'TUE-RE-033': ['L-MD2', 'L-F13'],
  'TUE-PU-022': ['L-BS5', 'LBS5'],
  'TUE-PU-026': ['L-F13A', 'LF13A'],
  'TUE-PU-027': ['L-F13B'],
  'TUE-BC-004': ['342-CB2414B', '342CB'],
  'TUE-BA-001': ['te-set-colgante', 'LHP13'],
  'TUE-019': ['LHP12', 'LHP13'],
};

const TUE_STAND_PROD = {};
for (let i = 1; i <= 13; i++) {
  TUE_STAND_PROD[`TUE-STAND-${String(i).padStart(3, '0')}`] = [
    `prod_${String(i).padStart(2, '0')}`,
    `prod_${i}`,
  ];
}

function buildCrosswalkFromCanvaCopy() {
  const map = { ...MANUAL_CROSSWALK };
  if (!fs.existsSync(COPY_DIR)) return map;
  for (const file of fs.readdirSync(COPY_DIR).filter((f) => f.endsWith('.txt'))) {
    const text = fs.readFileSync(path.join(COPY_DIR, file), 'utf8');
    const re = /C[ÓO]DIGO:\s*(TUE-[A-Z0-9\-]+)[^]*?DESCRIPCI[ÓO]N:[^(\n]*\(([A-Z0-9\-]+)\)/gi;
    let m;
    while ((m = re.exec(text))) {
      const tue = m[1].toUpperCase();
      const leg = m[2].toUpperCase();
      if (!map[tue]) map[tue] = [];
      if (!map[tue].includes(leg)) map[tue].push(leg);
    }
  }
  return map;
}

function walkImages(dir, acc = []) {
  if (!fs.existsSync(dir)) return acc;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const f = path.join(dir, e.name);
    if (e.isDirectory() && !e.name.startsWith('.')) walkImages(f, acc);
    else if (/\.(jpg|jpeg|png|webp)$/i.test(e.name)) acc.push(f);
  }
  return acc;
}

function fileScore(filePath, hints) {
  const base = path.basename(filePath).toUpperCase();
  if (/-\d+x\d+\./.test(base)) return -1;
  let score = 0;
  for (const h of hints) {
    const H = h.toUpperCase();
    if (base === `${H}.JPG` || base.startsWith(`${H}-`) || base.includes(H)) score += 10;
    if (base.replace(/[^A-Z0-9]/g, '').includes(H.replace(/[^A-Z0-9]/g, ''))) score += 5;
  }
  if (/-01\.(JPG|JPEG|PNG)$/i.test(base)) score += 3;
  if (/-02\.(JPG|JPEG|PNG)$/i.test(base)) score += 1;
  const stat = fs.statSync(filePath);
  score += Math.min(stat.size / 50000, 5);
  return score;
}

function findBestLocal(hints, files) {
  let best = null;
  let bestScore = 0;
  for (const f of files) {
    const s = fileScore(f, hints);
    if (s > bestScore) { bestScore = s; best = f; }
  }
  return bestScore >= 5 ? best : null;
}

function hintsForProduct(code, crosswalk) {
  const hints = [code, code.replace(/^TUE-/, ''), code.replace(/-/g, '')];
  const leg = crosswalk[code] || crosswalk[code.toUpperCase()];
  if (leg) hints.push(...leg);
  if (TUE_STAND_PROD[code]) hints.push(...TUE_STAND_PROD[code]);
  // Códigos legacy directos (BX-159, P-NM, E-35…)
  if (!code.startsWith('TUE-')) {
    hints.push(code, code.replace(/-/g, ''));
  }
  return [...new Set(hints.filter(Boolean))];
}

async function downloadLive(code, hints, dest) {
  const tries = [];
  for (const h of hints) {
    const safe = h.replace(/[^A-Za-z0-9\-]/g, '');
    tries.push(`${LIVE_BASE}/2019/05/${safe}-01.jpg`);
    tries.push(`${LIVE_BASE}/2019/05/${safe}-02.jpg`);
    tries.push(`${LIVE_BASE}/2019/05/${safe}.jpg`);
    tries.push(`${LIVE_BASE}/2026/07/${safe}.jpg`);
    tries.push(`${LIVE_BASE}/2026/07/${safe.toLowerCase()}.jpg`);
  }
  if (code.startsWith('TUE-STAND-')) {
    const n = code.match(/\d+/)?.[0];
    if (n) tries.unshift(`${LIVE_BASE}/2026/07/prod_${String(+n).padStart(2, '0')}.jpg`);
  }
  for (const url of [...new Set(tries)]) {
    try {
      const res = await fetch(url, { headers: { 'User-Agent': 'Mozilla/5.0' } });
      if (!res.ok) continue;
      const buf = Buffer.from(await res.arrayBuffer());
      if (buf.length < 2000) continue;
      fs.writeFileSync(dest, buf);
      return url;
    } catch { /* next */ }
  }
  return null;
}

async function renderPdfPage(pageNum, dest) {
  if (!fs.existsSync(PDF_PATH)) return false;
  const data = new Uint8Array(fs.readFileSync(PDF_PATH));
  const doc = await getDocument({ data, useSystemFonts: true }).promise;
  if (pageNum > doc.numPages) return false;
  const page = await doc.getPage(pageNum);
  const viewport = page.getViewport({ scale: 2 });
  const canvas = createCanvas(viewport.width, viewport.height);
  const ctx = canvas.getContext('2d');
  await page.render({ canvasContext: ctx, viewport }).promise;
  fs.writeFileSync(dest, canvas.toBuffer('image/jpeg', 90));
  return true;
}

async function extractPdfImages(pageNum, outDir) {
  if (!fs.existsSync(PDF_PATH)) return [];
  const data = new Uint8Array(fs.readFileSync(PDF_PATH));
  const doc = await getDocument({ data, useSystemFonts: true }).promise;
  if (pageNum > doc.numPages) return [];
  const page = await doc.getPage(pageNum);
  const ops = await page.getOperatorList();
  const imgs = [];
  for (const id of ops.fnArray) { /* images via objs */ }
  // Fallback: render full page
  const dest = path.join(outDir, `page-${String(pageNum).padStart(2, '0')}.jpg`);
  if (await renderPdfPage(pageNum, dest)) return [dest];
  return [];
}

function escapeHtml(s) {
  return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
}

async function main() {
  fs.mkdirSync(OUT, { recursive: true });
  fs.mkdirSync(PDF_PAGES_OUT, { recursive: true });

  const crosswalk = buildCrosswalkFromCanvaCopy();
  fs.writeFileSync(
    path.join(ROOT, 'import', 'tue-legacy-crosswalk.json'),
    JSON.stringify(crosswalk, null, 2)
  );

  const allFiles = [];
  for (const src of IMAGE_SOURCES) allFiles.push(...walkImages(src));
  console.log(`Archivos imagen indexados: ${allFiles.length}`);

  const manifest = JSON.parse(fs.readFileSync(MANIFEST, 'utf8'));
  const results = [];
  const pageCache = new Map();

  for (const p of manifest.products) {
    const dest = path.join(OUT, `${p.slug}.jpg`);
    const hints = hintsForProduct(p.code, crosswalk);
    let source = null;
    let method = null;

    if (fs.existsSync(dest) && fs.statSync(dest).size > 8000) {
      source = dest;
      method = 'existing';
    }

    if (!source) {
      const local = findBestLocal(hints, allFiles);
      if (local) {
        fs.copyFileSync(local, dest);
        source = local;
        method = 'local-file';
      }
    }

    if (!source) {
      const url = await downloadLive(p.code, hints, dest);
      if (url) { source = url; method = 'live-download'; }
      await new Promise((r) => setTimeout(r, 60));
    }

    if (!source && p.page) {
      let pageImg = pageCache.get(p.page);
      if (!pageImg) {
        const pd = path.join(PDF_PAGES_OUT, `page-${String(p.page).padStart(2, '0')}.jpg`);
        if (!fs.existsSync(pd)) await renderPdfPage(p.page, pd);
        if (fs.existsSync(pd)) pageImg = pd;
        pageCache.set(p.page, pageImg);
      }
      if (pageImg) {
        fs.copyFileSync(pageImg, dest);
        source = pageImg;
        method = 'pdf-page-fallback';
      }
    }

    results.push({
      code: p.code,
      slug: p.slug,
      name: p.name,
      page: p.page,
      hints,
      method: method || 'unmatched',
      source: source ? path.relative(ROOT, String(source)).replace(/\\/g, '/') : null,
      hasImage: fs.existsSync(dest) && fs.statSync(dest).size > 1500,
    });
  }

  // Actualizar manifest
  for (const p of manifest.products) {
    const r = results.find((x) => x.code === p.code);
    const dest = path.join(OUT, `${p.slug}.jpg`);
    if (r?.hasImage) {
      p.imageLocal = `public/images/catalog/${p.slug}.jpg`;
      p.imageSource = r.method;
      p.imageMatchHints = r.hints;
    }
  }
  manifest.imagesMatchedBulk = results.filter((r) => r.hasImage).length;
  manifest.imagesMatchedAt = new Date().toISOString();
  fs.writeFileSync(MANIFEST, JSON.stringify(manifest, null, 2));
  fs.writeFileSync(path.join(ROOT, 'import', 'photo-match-results.json'), JSON.stringify(results, null, 2));

  // HTML revisión
  const matched = results.filter((r) => r.hasImage).length;
  const html = `<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Revisión emparejamiento fotos</title>
<style>body{font-family:system-ui;background:#faf7f2;padding:24px}table{border-collapse:collapse;width:100%;background:#fff}
th,td{border:1px solid #ddd;padding:8px;font-size:13px}th{background:#2b2926;color:#fff}
img{width:80px;height:80px;object-fit:cover;border-radius:8px}.ok{color:green}.miss{color:#c00}
</style></head><body><h1>Emparejamiento PhotosDrive + backup (${matched}/${results.length})</h1>
<table><tr><th>Foto</th><th>Código</th><th>Método</th><th>Página</th><th>Nombre</th><th>Hints</th></tr>
${results.map((r) => `<tr><td>${r.hasImage ? `<img src="../public/images/catalog/${r.slug}.jpg">` : '—'}</td>
<td>${escapeHtml(r.code)}</td><td class="${r.hasImage ? 'ok' : 'miss'}">${escapeHtml(r.method)}</td>
<td>${r.page || ''}</td><td>${escapeHtml(r.name)}</td><td>${escapeHtml(r.hints.slice(0, 4).join(', '))}</td></tr>`).join('')}
</table></body></html>`;
  fs.writeFileSync(path.join(ROOT, 'import', 'photo-match-review.html'), html);

  // Refrescar CSV
  const { spawnSync } = await import('child_process');
  spawnSync('node', ['scripts/refresh_csv_images.mjs'], { cwd: ROOT, stdio: 'inherit', shell: true });

  console.log(`\n=== EMPAREJAMIENTO ===`);
  console.log(`Con imagen: ${matched}/${results.length}`);
  console.log(`Crosswalk: import/tue-legacy-crosswalk.json`);
  console.log(`Revisión: import/photo-match-review.html`);
  const byMethod = {};
  for (const r of results) byMethod[r.method] = (byMethod[r.method] || 0) + 1;
  console.log('Por método:', byMethod);
}

main().catch((e) => { console.error(e); process.exit(1); });
