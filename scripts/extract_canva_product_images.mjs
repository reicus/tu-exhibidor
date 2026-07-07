/**
 * Extrae fotos de REFERENCIA del PDF Canva (solo thumbnails en canva-refs/).
 * Para asignar fotos REALES al catálogo usar: node scripts/match_real_photos_similarity.mjs
 * Fuente: PhotosDrive/Documentos/CATALOGO 2020.pdf
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { getDocument, OPS } from 'pdfjs-dist/legacy/build/pdf.mjs';
import { createCanvas, loadImage } from '@napi-rs/canvas';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const PDF_PATH = path.join(ROOT, 'PhotosDrive', 'Documentos', 'CATALOGO 2020.pdf');
const MANIFEST = path.join(ROOT, 'import', 'canva-extraction-manifest.json');
const OUT = path.join(ROOT, 'public', 'images', 'catalog');
const RAW = path.join(ROOT, 'public', 'images', 'canva-raw');

const MIN_IMG = 120; // px — ignorar iconos pequeños

async function loadPdf() {
  const data = new Uint8Array(fs.readFileSync(PDF_PATH));
  return getDocument({ data, useSystemFonts: true }).promise;
}

async function extractEmbeddedImages(page, pageNum) {
  const ops = await page.getOperatorList();
  const imgs = [];
  const viewport = page.getViewport({ scale: 1 });

  for (let i = 0; i < ops.fnArray.length; i++) {
    const fn = ops.fnArray[i];
    if (fn !== OPS.paintImageXObject && fn !== OPS.paintInlineImageXObject) continue;

    const name = ops.argsArray[i][0];
    let imgData;
    try {
      imgData = await page.objs.get(name);
    } catch { continue; }
    if (!imgData?.data || !imgData.width || !imgData.height) continue;
    if (imgData.width < MIN_IMG || imgData.height < MIN_IMG) continue;

    // Transform matrix precedes paintImage in many PDFs
    let cm = [1, 0, 0, 1, 0, 0];
    for (let j = i - 1; j >= Math.max(0, i - 6); j--) {
      if (ops.fnArray[j] === OPS.transform) {
        cm = ops.argsArray[j];
        break;
      }
    }
    const [a, b, c, d, e, f] = cm;
    const w = Math.hypot(a, b);
    const h = Math.hypot(c, d);
    const x = e;
    const y = viewport.height - f - h;

    const canvas = createCanvas(imgData.width, imgData.height);
    const ctx = canvas.getContext('2d');
    const imgCanvas = createCanvas(imgData.width, imgData.height);
    const ictx = imgCanvas.getContext('2d');
    const clamped = new Uint8ClampedArray(imgData.data);
    const imageData = ictx.createImageData(imgData.width, imgData.height);
    imageData.data.set(clamped);
    ictx.putImageData(imageData, 0, 0);
    ctx.drawImage(imgCanvas, 0, 0);

    const buf = canvas.toBuffer('image/jpeg', 92);
    if (buf.length < 3000) continue;

    imgs.push({
      page: pageNum,
      x, y, w: imgData.width, h: imgData.height,
      drawW: w, drawH: h,
      area: imgData.width * imgData.height,
      buf,
    });
  }

  // Orden lectura: arriba→abajo, izquierda→derecha
  imgs.sort((a, b) => {
    const rowA = Math.round(a.y / 80);
    const rowB = Math.round(b.y / 80);
    if (rowA !== rowB) return rowA - rowB;
    return a.x - b.x;
  });

  // Quitar duplicados muy similares (misma imagen repetida)
  const uniq = [];
  for (const im of imgs) {
    if (!uniq.some((u) => Math.abs(u.x - im.x) < 20 && Math.abs(u.y - im.y) < 20)) uniq.push(im);
  }
  return uniq;
}

async function cropPageFallback(page, pageNum, cols, rows) {
  const scale = 2;
  const viewport = page.getViewport({ scale });
  const canvas = createCanvas(viewport.width, viewport.height);
  const ctx = canvas.getContext('2d');
  await page.render({ canvasContext: ctx, viewport }).promise;

  const crops = [];
  const marginTop = viewport.height * 0.08;
  const marginBottom = viewport.height * 0.12;
  const usableH = viewport.height - marginTop - marginBottom;
  const cellW = viewport.width / cols;
  const cellH = usableH / rows;

  for (let r = 0; r < rows; r++) {
    for (let c = 0; c < cols; c++) {
      const x = c * cellW + cellW * 0.05;
      const y = marginTop + r * cellH + cellH * 0.05;
      const w = cellW * 0.9;
      const h = cellH * 0.85;
      const crop = createCanvas(w, h);
      crop.getContext('2d').drawImage(canvas, x, y, w, h, 0, 0, w, h);
      crops.push({ page: pageNum, buf: crop.toBuffer('image/jpeg', 90) });
    }
  }
  return crops;
}

function productsOnPage(manifest, pageNum) {
  const seen = new Set();
  return manifest.products
    .filter((p) => p.page === pageNum)
    .filter((p) => { const k = p.code.toUpperCase(); if (seen.has(k)) return false; seen.add(k); return true; })
    .sort((a, b) => a.code.localeCompare(b.code));
}

function gridForPage(pageNum, count) {
  if (pageNum >= 18 && pageNum <= 22) return { cols: 3, rows: Math.ceil(count / 3) || 1 };
  if (count <= 2) return { cols: 2, rows: 1 };
  if (count <= 4) return { cols: 2, rows: 2 };
  if (count <= 6) return { cols: 3, rows: 2 };
  return { cols: 3, rows: Math.ceil(count / 3) };
}

async function main() {
  if (!fs.existsSync(PDF_PATH)) {
    console.error('PDF no encontrado:', PDF_PATH);
    process.exit(1);
  }

  fs.mkdirSync(OUT, { recursive: true });
  fs.mkdirSync(RAW, { recursive: true });

  const manifest = JSON.parse(fs.readFileSync(MANIFEST, 'utf8'));
  const doc = await loadPdf();
  const log = [];
  let assigned = 0;

  const maxPage = Math.max(...manifest.products.map((p) => p.page || 0), 22);

  for (let pageNum = 1; pageNum <= maxPage; pageNum++) {
    const products = productsOnPage(manifest, pageNum);
    if (!products.length) continue;

    const page = await doc.getPage(pageNum);
    let images = await extractEmbeddedImages(page, pageNum);

    if (images.length < products.length) {
      const { cols, rows } = gridForPage(pageNum, products.length);
      const crops = await cropPageFallback(page, pageNum, cols, rows);
      images = crops.map((c, i) => ({ ...c, idx: i }));
    }

    // Guardar raw para debug
    images.forEach((im, i) => {
      fs.writeFileSync(path.join(RAW, `p${String(pageNum).padStart(2, '0')}-${i}.jpg`), im.buf);
    });

    for (let i = 0; i < products.length; i++) {
      const p = products[i];
      const im = images[i] || images[images.length - 1];
      if (!im?.buf) continue;

      const dest = path.join(OUT, `${p.slug}.jpg`);
      fs.writeFileSync(dest, im.buf);
      p.imageLocal = `public/images/catalog/${p.slug}.jpg`;
      p.imageSource = 'canva-pdf';
      p.imageCanvaPage = pageNum;
      p.imageCanvaSlot = i;
      log.push({ code: p.code, slug: p.slug, page: pageNum, slot: i, method: im.drawW ? 'embedded' : 'crop' });
      assigned++;
    }
  }

  manifest.imageSource = 'canva-pdf-catalog-2020';
  manifest.imagesFromCanvaPdf = assigned;
  manifest.canvaImageLog = log;
  fs.writeFileSync(MANIFEST, JSON.stringify(manifest, null, 2));
  fs.writeFileSync(path.join(ROOT, 'import', 'canva-image-map.json'), JSON.stringify(log, null, 2));

  // Refrescar CSV
  const { spawnSync } = await import('child_process');
  spawnSync('node', [path.join(__dirname, 'refresh_csv_images.mjs')], { cwd: ROOT, stdio: 'inherit' });

  console.log(`Imágenes Canva asignadas: ${assigned}/${manifest.products.length}`);
  console.log(`Salida: public/images/catalog/`);
  console.log(`Raw debug: public/images/canva-raw/`);
}

main().catch((e) => { console.error(e); process.exit(1); });
