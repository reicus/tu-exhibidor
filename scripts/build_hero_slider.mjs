/**
 * Genera 7 slides hero 4:3 desde fuentes curadas (galería legacy + premium).
 * Evita PhotosDrive con letterboxing negro. Run: node scripts/build_hero_slider.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import sharp from 'sharp';
import { DISPLAY_LABELS } from './category-mapping.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'public', 'images', 'hero');
const MANIFEST_PATH = path.join(ROOT, 'import', 'premium-gallery-manifest.json');
const SIZES = [400, 800, 1200, 1600];
const CREAM = '#ddd3c8';
const HERO_ASPECT = 4 / 3;

const HERO_ALTS = {
  collares: 'Línea de bustos para collares en ecocuero — vitrina fina Tu Exhibidor Chile',
  anillos: 'Exhibidor de anillos estilo maniquí con joyas — fabricación chilena',
  'sets-vitrina': 'Set modular de pedestales para vitrina de joyería — Tu Exhibidor',
  aros: 'Exhibidor de aros en ecocuero con perlas y piedras — vitrina selecta',
  dijes: 'Bandeja exhibidora premium en ecocuero crema para anillos y dijes',
  pulseras: 'Exhibidor T-bar para pulseras en ecocuero — vitrina fina Tu Exhibidor',
  bandejas: 'Bandeja exhibidora en ecocuero para vitrina de joyería — Tu Exhibidor',
};

/** Fuentes curadas: galería WP legacy + premium horizontales sin letterboxing negro. */
const HERO_CURATED = [
  { category: 'collares', src: 'public/images/gallery/te-bustos-collares.jpg' },
  { category: 'pulseras', src: 'public/images/gallery/te-tbar-triple.jpg' },
  { category: 'anillos', src: 'public/images/premium/premium/exhibidor-joyeria-premium-horizontal-11-1600.jpg' },
  { category: 'aros', src: 'public/images/gallery/te-aretes-tstand.jpg' },
  { category: 'bandejas', src: 'public/images/premium/premium/exhibidor-joyeria-premium-horizontal-12-1600.jpg' },
  { category: 'dijes', src: 'public/images/gallery/te-collar-busto.jpg' },
  { category: 'sets-vitrina', src: 'public/images/premium/sets-vitrina/set-vitrina-modular-horizontal-01-1600.jpg' },
];

function rel(p) {
  return path.relative(ROOT, p).replace(/\\/g, '/');
}

async function processHero(pick, index) {
  const id = `hero-slide-${String(index + 1).padStart(2, '0')}`;
  const srcPath = path.join(ROOT, pick.src);
  if (!fs.existsSync(srcPath)) throw new Error(`No existe fuente para ${id}: ${pick.src}`);

  const basePath = path.join(OUT, id);
  fs.mkdirSync(OUT, { recursive: true });

  const sources = {};
  for (const w of SIZES) {
    const h = Math.round(w / HERO_ASPECT);
    const make = () => sharp(srcPath, { failOn: 'none' })
      .rotate()
      .trim({ threshold: 18 })
      .normalize()
      .modulate({ brightness: 1.03, saturation: 1.05 })
      .sharpen({ sigma: 0.5, m1: 0.4, m2: 0.35 })
      .resize(w, h, { fit: 'cover', position: 'centre', background: CREAM });

    const jpgPath = `${basePath}-${w}.jpg`;
    const webpPath = `${basePath}-${w}.webp`;
    const avifPath = `${basePath}-${w}.avif`;

    await make().jpeg({ quality: 82, mozjpeg: true }).toFile(jpgPath);
    await make().webp({ quality: 84, effort: 4 }).toFile(webpPath);
    try {
      await make().avif({ quality: 62, effort: 4 }).toFile(avifPath);
    } catch (err) {
      console.warn(`  avif omitido ${path.basename(avifPath)}: ${err.message}`);
    }

    sources[w] = { jpg: rel(jpgPath), webp: rel(webpPath), avif: rel(avifPath) };
  }

  const cat = pick.category;
  return {
    slug: id,
    alt: HERO_ALTS[cat] || `${DISPLAY_LABELS[cat] || 'Exhibidor'} — Tu Exhibidor Chile`,
    category: cat,
    hero: true,
    aspect: 'landscape',
    score: 100,
    source: pick.src,
    base: rel(basePath),
    sources,
  };
}

async function main() {
  const missing = HERO_CURATED.filter((p) => !fs.existsSync(path.join(ROOT, p.src)));
  if (missing.length) {
    throw new Error(`Faltan fuentes hero: ${missing.map((m) => m.src).join(', ')}`);
  }

  const manifest = fs.existsSync(MANIFEST_PATH)
    ? JSON.parse(fs.readFileSync(MANIFEST_PATH, 'utf8'))
    : {};

  console.log(`Generando ${HERO_CURATED.length} slides hero (fuentes curadas)…`);
  const hero = [];
  for (let i = 0; i < HERO_CURATED.length; i++) {
    const pick = HERO_CURATED[i];
    console.log(`  → hero-slide-${String(i + 1).padStart(2, '0')} (${pick.category}) ← ${pick.src}`);
    hero.push(await processHero(pick, i));
  }

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
