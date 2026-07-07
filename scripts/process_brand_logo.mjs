/**
 * Procesa logo Tu Exhibidor → public/images/brand/
 * Run: node scripts/process_brand_logo.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import sharp from 'sharp';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'public', 'images', 'brand');
const INK = '#2b2926';
const GOLD = '#b8935f';

const SOURCES = [
  path.join(ROOT, 'assets', 'logo-source.png'),
  'C:\\Users\\Lenovo\\Downloads\\the exhibidor logo.psd',
  path.join(ROOT, '..', '.cursor', 'projects', 'c-Users-Lenovo-Downloads-Tu-Exhibidor', 'assets',
    'c__Users_Lenovo_AppData_Roaming_Cursor_User_workspaceStorage_empty-window_images_the_exhibidor_logo-2e7cc173-1b26-423f-bb85-d811f7cb2ba2.png'),
];

async function extractFromPsd(psdPath) {
  try {
    const { readPsd, initializeCanvas } = await import('ag-psd');
    const { createCanvas } = await import('@napi-rs/canvas');
    initializeCanvas(createCanvas);
    const buf = fs.readFileSync(psdPath);
    const psd = readPsd(buf, { skipLayerImageData: false, skipCompositeImageData: false });
    if (psd.canvas) {
      return psd.canvas.toBuffer('image/png');
    }
  } catch (e) {
    console.warn('PSD:', e.message);
  }
  return null;
}

async function resolveInput() {
  const candidates = [
    path.join(ROOT, 'assets', 'logo-source.png'),
    path.join(ROOT, 'assets', 'logo-source.psd'),
    'C:\\Users\\Lenovo\\Downloads\\the exhibidor logo.psd',
  ];

  for (const src of candidates) {
    if (!fs.existsSync(src)) continue;
    if (/\.psd$/i.test(src)) {
      const png = await extractFromPsd(src);
      if (png) {
        console.log('Fuente: PSD', src);
        return sharp(png);
      }
    } else {
      console.log('Fuente: PNG', src);
      return sharp(src);
    }
  }
  throw new Error('No se encontró archivo de logo');
}

function hexToRgb(hex) {
  const n = parseInt(hex.slice(1), 16);
  return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
}

async function makeTransparentPng(inputSharp) {
  const { data, info } = await inputSharp
    .clone()
    .flatten({ background: '#ffffff' })
    .ensureAlpha()
    .raw()
    .toBuffer({ resolveWithObject: true });

  for (let i = 0; i < data.length; i += 4) {
    const r = data[i];
    const g = data[i + 1];
    const b = data[i + 2];
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const sat = max === 0 ? 0 : (max - min) / max;

    // Fondo blanco / casi blanco → transparente
    if (r > 248 && g > 248 && b > 248) {
      data[i + 3] = 0;
      continue;
    }

    // Grises claros del antialiasing del borde circular
    if (r > 220 && g > 220 && b > 220 && sat < 0.08) {
      data[i + 3] = Math.round((255 - (r + g + b) / 3) * 4);
      continue;
    }

    // Tinta de marca en negros y grises oscuros
    data[i] = 0x2b;
    data[i + 1] = 0x29;
    data[i + 2] = 0x26;
    data[i + 3] = 255;
  }

  return sharp(data, { raw: { width: info.width, height: info.height, channels: 4 } });
}

async function makeGoldVariant(inkSharp) {
  const { data, info } = await inkSharp.clone().ensureAlpha().raw().toBuffer({ resolveWithObject: true });
  const gold = hexToRgb(GOLD);
  for (let i = 0; i < data.length; i += 4) {
    if (data[i + 3] > 0) {
      data[i] = gold.r;
      data[i + 1] = gold.g;
      data[i + 2] = gold.b;
    }
  }
  return sharp(data, { raw: { width: info.width, height: info.height, channels: 4 } });
}

async function exportSet(baseSharp, name, sizes) {
  const trimmed = await baseSharp.clone().trim({ threshold: 10 });
  for (const size of sizes) {
    const resized = trimmed.clone().resize(size, size, { fit: 'inside', withoutEnlargement: false });
    const base = path.join(OUT, `${name}-${size}`);
    await resized.clone().png({ compressionLevel: 9 }).toFile(`${base}.png`);
    await resized.clone().webp({ quality: 90 }).toFile(`${base}.webp`);
    await resized.clone().avif({ quality: 70 }).toFile(`${base}.avif`);
  }
  const master = path.join(OUT, `${name}.png`);
  await trimmed.clone().resize(512, 512, { fit: 'inside' }).png().toFile(master);
  return master;
}

async function main() {
  fs.mkdirSync(path.join(ROOT, 'assets'), { recursive: true });
  fs.mkdirSync(OUT, { recursive: true });

  const psdPath = 'C:\\Users\\Lenovo\\Downloads\\the exhibidor logo.psd';
  if (fs.existsSync(psdPath)) {
    fs.copyFileSync(psdPath, path.join(ROOT, 'assets', 'logo-source.psd'));
    console.log('PSD copiado a assets/logo-source.psd');
  }

  const input = await resolveInput();
  const meta = await input.metadata();
  console.log(`Logo origen: ${meta.width}×${meta.height}`);

  const inkLogo = await makeTransparentPng(input);
  const goldLogo = await makeGoldVariant(inkLogo);

  const sizes = [48, 96, 192, 512];
  await exportSet(inkLogo, 'logo-tuexhibidor-ink', sizes);
  await exportSet(goldLogo, 'logo-tuexhibidor-gold', sizes);

  // Favicon 32 + apple-touch 180
  await inkLogo.clone().trim({ threshold: 10 }).resize(32, 32, { fit: 'inside', background: { r: 0, g: 0, b: 0, alpha: 0 } })
    .png().toFile(path.join(OUT, 'favicon-32.png'));
  await inkLogo.clone().trim({ threshold: 10 }).resize(180, 180, { fit: 'inside', background: { r: 0, g: 0, b: 0, alpha: 0 } })
    .png().toFile(path.join(OUT, 'apple-touch-icon.png'));

  // SVG simplificado del busto (marca para header)
  const svg = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" fill="none">
  <circle cx="60" cy="60" r="58" stroke="${INK}" stroke-width="2"/>
  <path fill="${INK}" d="M60 28c-8 0-14 6-14 14v4c0 6 4 11 9 13v22c0 4 3 7 7 7h-4c-3 0-5 2-5 5v2h26v-2c0-3-2-5-5-5h-4c4 0 7-3 7-7V59c5-2 9-7 9-13v-4c0-8-6-14-14-14z"/>
  <path stroke="${INK}" stroke-width="1.5" fill="none" d="M46 52c4 6 10 9 14 9s10-3 14-9"/>
  <circle cx="60" cy="58" r="3" fill="#faf7f2" stroke="${INK}" stroke-width="1"/>
  <text x="60" y="98" text-anchor="middle" font-family="Georgia,serif" font-size="11" font-style="italic" fill="${INK}">Tu Exhibidor</text>
</svg>`;
  fs.writeFileSync(path.join(OUT, 'logo-mark.svg'), svg);

  const manifest = {
    generatedAt: new Date().toISOString(),
    ink: 'public/images/brand/logo-tuexhibidor-ink',
    gold: 'public/images/brand/logo-tuexhibidor-gold',
    mark: 'public/images/brand/logo-mark.svg',
    favicon: 'public/images/brand/favicon-32.png',
    appleTouch: 'public/images/brand/apple-touch-icon.png',
    header: {
      src: 'public/images/brand/logo-tuexhibidor-ink-96.webp',
      srcFallback: 'public/images/brand/logo-tuexhibidor-ink-96.png',
      alt: 'Tu Exhibidor — fábrica de exhibidores para joyería',
      width: 48,
      height: 48,
    },
  };
  fs.writeFileSync(path.join(ROOT, 'import', 'brand-manifest.json'), JSON.stringify(manifest, null, 2));
  console.log('Logo exportado →', OUT);
}

main().catch((e) => { console.error(e); process.exit(1); });
