/**
 * Reemplaza fondos blancos/claros en JPGs por crema cálido del sitio.
 * Run: node scripts/warm_image_backgrounds.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import sharp from 'sharp';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const IMG_ROOT = path.join(ROOT, 'public', 'images');

/** Mismo tono que --img-well en styles.css */
const CREAM = { r: 221, g: 211, b: 200 };

function luminance(r, g, b) {
  return 0.299 * r + 0.587 * g + 0.114 * b;
}

function warmPixels(data, channels) {
  for (let i = 0; i < data.length; i += channels) {
    const r = data[i];
    const g = data[i + 1];
    const b = data[i + 2];
    const l = luminance(r, g, b);
    if (l < 175) continue;
    const t = l >= 245 ? 1 : Math.min(1, ((l - 175) / 70) * 0.95);
    data[i] = Math.round(r * (1 - t) + CREAM.r * t);
    data[i + 1] = Math.round(g * (1 - t) + CREAM.g * t);
    data[i + 2] = Math.round(b * (1 - t) + CREAM.b * t);
  }
}

function walkJpgs(dir, acc = []) {
  if (!fs.existsSync(dir)) return acc;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) walkJpgs(p, acc);
    else if (/\.jpe?g$/i.test(e.name)) acc.push(p);
  }
  return acc;
}

async function warmFile(file) {
  const img = sharp(file, { failOn: 'none' }).rotate();
  const { data, info } = await img.raw().toBuffer({ resolveWithObject: true });
  warmPixels(data, info.channels);
  const buf = await sharp(data, { raw: { width: info.width, height: info.height, channels: info.channels } })
    .jpeg({ quality: 88, mozjpeg: true })
    .toBuffer();
  const tmp = `${file}.tmp`;
  fs.writeFileSync(tmp, buf);
  try { fs.unlinkSync(file); } catch { /* nuevo */ }
  fs.renameSync(tmp, file);
}

async function main() {
  const dirs = ['catalog', 'premium', 'hero', 'canva-refs'].map((d) => path.join(IMG_ROOT, d));
  const files = [...new Set(dirs.flatMap((d) => walkJpgs(d)))];
  console.log(`Calentando ${files.length} JPGs…`);
  let ok = 0;
  let fail = 0;
  for (let i = 0; i < files.length; i++) {
    if (i % 50 === 0) console.log(`  ${i}/${files.length}`);
    try {
      await warmFile(files[i]);
      ok++;
    } catch (e) {
      fail++;
      console.warn('Skip:', path.relative(ROOT, files[i]), e.message);
    }
  }
  console.log(`Listo: ${ok} OK, ${fail} omitidos`);
}

main().catch((e) => { console.error(e); process.exit(1); });
