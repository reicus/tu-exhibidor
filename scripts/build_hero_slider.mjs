/**
 * Curación manual de 7 slides hero → public/images/hero/
 * Run: node scripts/build_hero_slider.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import sharp from 'sharp';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'public', 'images', 'hero');
const MANIFEST_PATH = path.join(ROOT, 'import', 'premium-gallery-manifest.json');
const SIZES = [400, 800, 1200, 1600];
const CREAM = '#ddd3c8';
const HERO_ASPECT = 4 / 3;

/** Las 7 mejores fotos reales (estilo vitrina premium, sin competidores) */
const HERO_PICKS = [
  {
    id: 'hero-slide-01',
    source: 'public/images/premium/collares/exhibidor-collares-cadenas-horizontal-09-1600.jpg',
    alt: 'Línea de bustos para collares en ecocuero — vitrina fina Tu Exhibidor Chile',
    category: 'collares',
  },
  {
    id: 'hero-slide-02',
    source: 'public/images/premium/sets-vitrina/set-vitrina-modular-horizontal-01-1600.jpg',
    alt: 'Exhibidor de anillos estilo maniquí con joyas — fabricación chilena',
    category: 'anillos',
  },
  {
    id: 'hero-slide-03',
    source: 'public/images/premium/premium/exhibidor-joyeria-premium-horizontal-11-1600.jpg',
    alt: 'Set modular de pedestales para vitrina de joyería — Tu Exhibidor',
    category: 'sets-vitrina',
  },
  {
    id: 'hero-slide-04',
    source: 'public/images/premium/collares/exhibidor-collares-cadenas-horizontal-10-1600.jpg',
    alt: 'Busto premium para collares con cadena dorada — vitrina selecta',
    category: 'collares',
    trimPct: { left: 0.15, top: 0, right: 0.15, bottom: 0.05 },
  },
  {
    id: 'hero-slide-05',
    source: 'public/images/premium/sets-vitrina/set-vitrina-modular-horizontal-02-1600.jpg',
    alt: 'Exhibidor de aros en ecocuero con perlas y piedras — vitrina selecta',
    category: 'aros',
  },
  {
    id: 'hero-slide-06',
    source: 'public/images/premium/aros/exhibidor-aros-zarcillos-horizontal-01-1600.jpg',
    alt: 'Bandeja exhibidora premium en ecocuero crema para anillos y dijes',
    category: 'dijes',
  },
  {
    id: 'hero-slide-07',
    source: 'public/images/premium/pulseras/exhibidor-pulseras-relojes-horizontal-10-1600.jpg',
    alt: 'Exhibidor de pulseras en ecocuero con pulsera dorada — vitrina fina',
    category: 'pulseras',
    trimPct: { left: 0, top: 0, right: 0.06, bottom: 0.12 },
  },
];

function rel(p) {
  return path.relative(ROOT, p).replace(/\\/g, '/');
}

async function processHero(pick) {
  const srcPath = path.join(ROOT, pick.source);
  if (!fs.existsSync(srcPath)) throw new Error(`No existe: ${pick.source}`);

  const basePath = path.join(OUT, pick.id);
  fs.mkdirSync(OUT, { recursive: true });

  let pipeline = sharp(srcPath, { failOn: 'none' })
    .rotate()
    .normalize()
    .modulate({ brightness: 1.03, saturation: 1.05 })
    .sharpen({ sigma: 0.5, m1: 0.4, m2: 0.35 });

  if (pick.trimPct) {
    const meta = await pipeline.clone().metadata();
    const { width, height } = meta;
    const t = pick.trimPct;
    const left = Math.round(width * (t.left || 0));
    const top = Math.round(height * (t.top || 0));
    const right = Math.round(width * (1 - (t.right || 0)));
    const bottom = Math.round(height * (1 - (t.bottom || 0)));
    pipeline = pipeline.extract({
      left,
      top,
      width: Math.max(1, right - left),
      height: Math.max(1, bottom - top),
    });
  }

  const sources = {};
  for (const w of SIZES) {
    const h = Math.round(w / HERO_ASPECT);
    const resized = pipeline.clone().resize(w, h, {
      fit: 'cover',
      position: sharp.strategy.attention,
      background: CREAM,
    });

    const jpgPath = `${basePath}-${w}.jpg`;
    const webpPath = `${basePath}-${w}.webp`;
    const avifPath = `${basePath}-${w}.avif`;

    await resized.clone().jpeg({ quality: 82, mozjpeg: true }).toFile(jpgPath);
    await resized.clone().webp({ quality: 84, effort: 4 }).toFile(webpPath);
    await resized.clone().avif({ quality: 62, effort: 4 }).toFile(avifPath);

    sources[w] = { jpg: rel(jpgPath), webp: rel(webpPath), avif: rel(avifPath) };
  }

  return {
    slug: pick.id,
    alt: pick.alt,
    category: pick.category,
    hero: true,
    aspect: 'landscape',
    score: 120,
    source: pick.source,
    base: rel(basePath),
    sources,
  };
}

async function main() {
  console.log('Generando 7 slides hero…');
  const hero = [];
  for (const pick of HERO_PICKS) {
    console.log(`  → ${pick.id}`);
    hero.push(await processHero(pick));
  }

  const manifest = fs.existsSync(MANIFEST_PATH)
    ? JSON.parse(fs.readFileSync(MANIFEST_PATH, 'utf8'))
    : { categories: {}, gallery: [] };

  manifest.hero = hero;
  manifest.heroCount = hero.length;
  manifest.heroUpdated = new Date().toISOString();
  fs.writeFileSync(MANIFEST_PATH, JSON.stringify(manifest, null, 2));

  console.log(`\n✓ ${hero.length} slides en public/images/hero/`);
  console.log('✓ premium-gallery-manifest.json actualizado');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
