/**
 * Retoque profesional catálogo Tu Exhibidor — estilo Canva (cream/white, 1:1, 1200px).
 * Ejecutar: node scripts/retouch_catalog_images.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { createCanvas, loadImage } from '@napi-rs/canvas';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const CATALOG = path.join(ROOT, 'public', 'images', 'catalog');
const ORIGINALS = path.join(CATALOG, 'originals');
const REFS = path.join(ROOT, 'public', 'images', 'canva-refs');
const SUMMARY_PATH = path.join(ROOT, 'import', 'canva-match-summary.json');
const MANIFEST_PATH = path.join(ROOT, 'import', 'canva-extraction-manifest.json');

const TARGET = 1200;
const CREAM = { r: 221, g: 211, b: 200 };
const JPEG_Q = 90;

function luminance(r, g, b) {
  return 0.299 * r + 0.587 * g + 0.114 * b;
}

function analyzeBuffer(data, w, h) {
  let sum = 0;
  let sumSq = 0;
  let n = 0;
  let minL = 255;
  let maxL = 0;
  const minDim = Math.min(w, h);

  for (let i = 0; i < data.length; i += 4) {
    const l = luminance(data[i], data[i + 1], data[i + 2]);
    sum += l;
    sumSq += l * l;
    n++;
    if (l < minL) minL = l;
    if (l > maxL) maxL = l;
  }
  const avg = sum / n;
  const variance = sumSq / n - avg * avg;
  const aspect = w / h;

  return {
    width: w,
    height: h,
    minDim,
    aspect,
    avgBrightness: avg,
    contrast: maxL - minL,
    variance,
    needsRetouch:
      minDim < 900 ||
      aspect < 0.75 || aspect > 1.33 ||
      avg < 70 || avg > 220 ||
      contrast < 40 ||
      variance < 800,
  };
}

/** Bounding box del producto (píxeles no-fondo) */
function findContentBounds(data, w, h) {
  let minX = w, minY = h, maxX = 0, maxY = 0;
  let found = false;
  const threshold = 235;

  for (let y = 0; y < h; y++) {
    for (let x = 0; x < w; x++) {
      const i = (y * w + x) * 4;
      const l = luminance(data[i], data[i + 1], data[i + 2]);
      if (l < threshold) {
        found = true;
        if (x < minX) minX = x;
        if (y < minY) minY = y;
        if (x > maxX) maxX = x;
        if (y > maxY) maxY = y;
      }
    }
  }
  if (!found) return { x: 0, y: 0, w, h };
  const padX = Math.round((maxX - minX) * 0.08);
  const padY = Math.round((maxY - minY) * 0.08);
  minX = Math.max(0, minX - padX);
  minY = Math.max(0, minY - padY);
  maxX = Math.min(w - 1, maxX + padX);
  maxY = Math.min(h - 1, maxY + padY);
  const bw = maxX - minX + 1;
  const bh = maxY - minY + 1;
  const side = Math.max(bw, bh);
  const cx = minX + bw / 2;
  const cy = minY + bh / 2;
  let sx = Math.round(cx - side / 2);
  let sy = Math.round(cy - side / 2);
  sx = Math.max(0, Math.min(sx, w - side));
  sy = Math.max(0, Math.min(sy, h - side));
  return { x: sx, y: sy, w: Math.min(side, w - sx), h: Math.min(side, h - sy) };
}

function squareCropCanvas(srcCanvas) {
  const w = srcCanvas.width;
  const h = srcCanvas.height;
  const ctx = srcCanvas.getContext('2d');
  const { data } = ctx.getImageData(0, 0, w, h);
  const bounds = findContentBounds(data, w, h);
  const side = Math.max(bounds.w, bounds.h);
  const out = createCanvas(side, side);
  const octx = out.getContext('2d');
  octx.fillStyle = `rgb(${CREAM.r},${CREAM.g},${CREAM.b})`;
  octx.fillRect(0, 0, side, side);
  const ox = Math.round((side - bounds.w) / 2);
  const oy = Math.round((side - bounds.h) / 2);
  octx.drawImage(srcCanvas, bounds.x, bounds.y, bounds.w, bounds.h, ox, oy, bounds.w, bounds.h);
  return out;
}

function autoLevels(data) {
  const hist = new Array(256).fill(0);
  const n = data.length / 4;
  for (let i = 0; i < data.length; i += 4) {
    const l = Math.round(luminance(data[i], data[i + 1], data[i + 2]));
    hist[l]++;
  }
  let lo = 0, hi = 255;
  let acc = 0;
  const clip = n * 0.02;
  for (let i = 0; i < 256; i++) { acc += hist[i]; if (acc >= clip) { lo = i; break; } }
  acc = 0;
  for (let i = 255; i >= 0; i--) { acc += hist[i]; if (acc >= clip) { hi = i; break; } }
  if (hi <= lo) return data;
  const scale = 255 / (hi - lo);
  for (let i = 0; i < data.length; i += 4) {
    for (let c = 0; c < 3; c++) {
      data[i + c] = Math.max(0, Math.min(255, (data[i + c] - lo) * scale));
    }
  }
  return data;
}

function creamBackground(data, w, h) {
  for (let i = 0; i < data.length; i += 4) {
    const r = data[i], g = data[i + 1], b = data[i + 2];
    const l = luminance(r, g, b);
    if (l > 200) {
      const t = Math.min(1, (l - 200) / 55) * 0.65;
      data[i] = Math.round(r * (1 - t) + CREAM.r * t);
      data[i + 1] = Math.round(g * (1 - t) + CREAM.g * t);
      data[i + 2] = Math.round(b * (1 - t) + CREAM.b * t);
    }
  }
  return data;
}

function sharpen(data, w, h) {
  const copy = new Uint8ClampedArray(data);
  const kernel = [0, -0.5, 0, -0.5, 3, -0.5, 0, -0.5, 0];
  for (let y = 1; y < h - 1; y++) {
    for (let x = 1; x < w - 1; x++) {
      for (let c = 0; c < 3; c++) {
        let v = 0;
        let ki = 0;
        for (let ky = -1; ky <= 1; ky++) {
          for (let kx = -1; kx <= 1; kx++) {
            const idx = ((y + ky) * w + (x + kx)) * 4 + c;
            v += copy[idx] * kernel[ki++];
          }
        }
        data[(y * w + x) * 4 + c] = Math.max(0, Math.min(255, v));
      }
    }
  }
  return data;
}

async function retouchImage(inputPath, ops = {}) {
  const img = await loadImage(inputPath);
  const src = createCanvas(img.width, img.height);
  src.getContext('2d').drawImage(img, 0, 0);

  const applied = [];
  let canvas = src;
  const pre = analyzeBuffer(
    src.getContext('2d').getImageData(0, 0, img.width, img.height).data,
    img.width,
    img.height
  );

  if (Math.abs(pre.aspect - 1) > 0.05 || ops.forceSquare) {
    canvas = squareCropCanvas(canvas);
    applied.push('square-crop');
  }

  if (canvas.width !== TARGET || canvas.height !== TARGET) {
    const resized = createCanvas(TARGET, TARGET);
    const rctx = resized.getContext('2d');
    rctx.fillStyle = `rgb(${CREAM.r},${CREAM.g},${CREAM.b})`;
    rctx.fillRect(0, 0, TARGET, TARGET);
    rctx.imageSmoothingEnabled = true;
    rctx.imageSmoothingQuality = 'high';
    rctx.drawImage(canvas, 0, 0, TARGET, TARGET);
    canvas = resized;
    applied.push(`resize-${TARGET}`);
  }

  const ctx = canvas.getContext('2d');
  let { data } = ctx.getImageData(0, 0, TARGET, TARGET);

  if (pre.avgBrightness < 95 || pre.avgBrightness > 200 || pre.contrast < 60 || ops.forceLevels) {
    data = autoLevels(data);
    applied.push('auto-levels');
  }

  data = creamBackground(data, TARGET, TARGET);
  applied.push('cream-bg');

  data = sharpen(data, TARGET, TARGET);
  applied.push('sharpen');

  const outImg = ctx.createImageData(TARGET, TARGET);
  outImg.data.set(data);
  ctx.putImageData(outImg, 0, 0);

  return { buffer: canvas.toBuffer('image/jpeg', JPEG_Q), applied, pre };
}

async function tryFallback(code, slug) {
  const refPath = path.join(REFS, `${code}.jpg`);
  if (fs.existsSync(refPath)) {
    const { buffer, applied } = await retouchImage(refPath, { forceSquare: true, forceLevels: true });
    return { buffer, applied: ['canva-ref-fallback', ...applied], source: 'canva-ref' };
  }
  const alt = path.join(ROOT, summaryMatchFile(code));
  if (alt && fs.existsSync(alt)) {
    const { buffer, applied } = await retouchImage(alt, { forceSquare: true });
    return { buffer, applied: ['re-pick-source', ...applied], source: 're-pick' };
  }
  return null;
}

let _summary;
function summaryMatchFile(code) {
  if (!_summary) _summary = JSON.parse(fs.readFileSync(SUMMARY_PATH, 'utf8'));
  const m = _summary.matches.find((x) => x.code === code);
  return m?.matchedFile ? path.join(ROOT, m.matchedFile.replace(/\//g, path.sep)) : null;
}

async function main() {
  fs.mkdirSync(ORIGINALS, { recursive: true });
  const summary = JSON.parse(fs.readFileSync(SUMMARY_PATH, 'utf8'));
  _summary = summary;
  const manifest = JSON.parse(fs.readFileSync(MANIFEST_PATH, 'utf8'));
  const bySlug = new Map(manifest.products.map((p) => [p.slug, p]));

  const stats = { retouched: 0, skipped: 0, fallback: 0, manualReview: [] };
  const samples = { before: [], after: [] };

  for (const match of summary.matches) {
    const dest = path.join(CATALOG, `${match.slug}.jpg`);
    if (!fs.existsSync(dest)) {
      stats.manualReview.push({ code: match.code, reason: 'missing-file' });
      continue;
    }

    const origBackup = path.join(ORIGINALS, `${match.slug}.jpg`);
    if (!fs.existsSync(origBackup)) fs.copyFileSync(dest, origBackup);

    const preStat = fs.statSync(dest);
    let result;
    try {
      result = await retouchImage(dest);
    } catch (e) {
      result = null;
    }

    const salvage =
      !result ||
      result.pre.minDim < 500 ||
      preStat.size < 25000 ||
      (match.score && match.score < 0.62);

    if (salvage) {
      const fb = await tryFallback(match.code, match.slug);
      if (fb) {
        fs.writeFileSync(dest, fb.buffer);
        match.retouched = true;
        match.retouchOps = fb.applied;
        match.retouchSource = fb.source;
        match.manualReview = false;
        stats.fallback++;
        stats.retouched++;
        if (samples.before.length < 3) {
          samples.before.push(`catalog/originals/${match.slug}.jpg`);
          samples.after.push(`catalog/${match.slug}.jpg`);
        }
        continue;
      }
      stats.manualReview.push({ code: match.code, reason: 'low-quality-no-fallback' });
    }

    if (!result) {
      stats.manualReview.push({ code: match.code, reason: 'process-error' });
      continue;
    }

    const alreadyGood =
      !result.pre.needsRetouch &&
      result.pre.minDim >= TARGET &&
      Math.abs(result.pre.aspect - 1) < 0.05 &&
      preStat.size > 120000;

    if (alreadyGood && match.score >= 0.75) {
      // Retoque ligero igual para consistencia de catálogo
      fs.writeFileSync(dest, result.buffer);
      match.retouched = true;
      match.retouchOps = result.applied;
      match.retouchSource = 'real-photo';
      match.manualReview = false;
      stats.retouched++;
      if (samples.before.length < 3) {
        samples.before.push(`catalog/originals/${match.slug}.jpg`);
        samples.after.push(`catalog/${match.slug}.jpg`);
      }
      continue;
    }

    fs.writeFileSync(dest, result.buffer);
    match.retouched = true;
    match.retouchOps = result.applied;
    match.retouchSource = 'real-photo';
    match.manualReview = result.pre.minDim < 600 || (match.score && match.score < 0.65);
    if (match.manualReview) stats.manualReview.push({ code: match.code, reason: 'borderline-quality' });
    stats.retouched++;
    if (samples.before.length < 3) {
      samples.before.push(`catalog/originals/${match.slug}.jpg`);
      samples.after.push(`catalog/${match.slug}.jpg`);
    }
  }

  summary.retouchRun = {
    at: new Date().toISOString(),
    retouched: stats.retouched,
    skipped: stats.skipped,
    fallback: stats.fallback,
    manualReview: stats.manualReview,
    targetSize: TARGET,
  };
  fs.writeFileSync(SUMMARY_PATH, JSON.stringify(summary, null, 2));

  const { spawnSync } = await import('child_process');
  spawnSync('node', [path.join(__dirname, 'build_site_data.mjs')], { cwd: ROOT, stdio: 'inherit' });

  console.log('\n=== RETOQUE CATÁLOGO ===');
  console.log('Retocados:', stats.retouched);
  console.log('Omitidos (ya buenos):', stats.skipped);
  console.log('Fallback Canva/re-pick:', stats.fallback);
  console.log('Revisión manual:', stats.manualReview.length);
  if (stats.manualReview.length) console.log(JSON.stringify(stats.manualReview, null, 2));
  console.log('Muestras before:', samples.before.map((p) => `public/images/${p}`));
  console.log('Muestras after:', samples.after.map((p) => `public/images/${p}`));
}

main().catch((e) => { console.error(e); process.exit(1); });
