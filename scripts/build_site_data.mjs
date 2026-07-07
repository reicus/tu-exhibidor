/** Genera site/catalog-data.js + site-data.js */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import {
  DISPLAY_CATEGORIES,
  DISPLAY_LABELS,
  DISPLAY_INTROS,
  CATEGORY_PRODUCT_CODES,
  resolveDisplayCategory,
} from './category-mapping.mjs';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const manifest = JSON.parse(fs.readFileSync(path.join(ROOT, 'import/canva-extraction-manifest.json'), 'utf8'));
const summary = fs.existsSync(path.join(ROOT, 'import/canva-match-summary.json'))
  ? JSON.parse(fs.readFileSync(path.join(ROOT, 'import/canva-match-summary.json'), 'utf8'))
  : { matches: [] };
const media = fs.existsSync(path.join(ROOT, 'import/site-media.json'))
  ? JSON.parse(fs.readFileSync(path.join(ROOT, 'import/site-media.json'), 'utf8'))
  : { hero: [], gallery: [], categories: {} };
const premiumPath = path.join(ROOT, 'import/premium-gallery-manifest.json');
const premium = fs.existsSync(premiumPath)
  ? JSON.parse(fs.readFileSync(premiumPath, 'utf8'))
  : null;

const scores = {};
for (const m of summary.matches || []) {
  if (m.score) scores[m.code] = m.score;
}

const qualityPath = path.join(ROOT, 'import/image-quality.json');
const imageQuality = fs.existsSync(qualityPath)
  ? JSON.parse(fs.readFileSync(qualityPath, 'utf8')).quality || {}
  : {};

function productImage(p) {
  const catalog = `public/images/catalog/${p.slug}.jpg`;
  const q = imageQuality[p.code];
  if (q?.ok === false) {
    // Nunca usar canva-refs corruptos en el sitio
    return q.image || catalog;
  }
  if (fs.existsSync(path.join(ROOT, catalog))) return catalog;
  return catalog;
}

function assetRef(a) {
  if (!a) return null;
  if (typeof a === 'string') return a;
  return { base: a.base, alt: a.alt, sources: a.sources };
}

const products = manifest.products.map((p) => ({
  code: p.code,
  slug: p.slug,
  name: p.name,
  categoryKey: p.categoryKey,
  displayCategory: resolveDisplayCategory(p),
  image: productImage(p),
  score: scores[p.code] ?? null,
  imageOk: imageQuality[p.code]?.ok !== false,
}));

const featured = [...products]
  .filter((p) => (p.score ?? 0) >= 0.78 && p.imageOk)
  .sort((a, b) => (b.score ?? 0) - (a.score ?? 0))
  .slice(0, 12);

const categoryImages = {};
DISPLAY_CATEGORIES.forEach((key) => {
  const rep = products.find((p) => p.code === CATEGORY_PRODUCT_CODES[key]);
  const premiumCat = premium?.categories?.[key]?.[0];
  categoryImages[key] = premiumCat
    ? assetRef(premiumCat)
    : media.categories?.[key] || rep?.image || featured[0]?.image;
});

const siteData = {
  hero: premium?.hero?.length
    ? premium.hero.map(assetRef)
    : media.hero?.length
      ? media.hero
      : featured.slice(0, 5).map((p) => p.image),
  gallery: premium?.gallery?.length
    ? premium.gallery.map(assetRef)
    : media.gallery?.length
      ? media.gallery
      : featured.map((p) => p.image),
  categoryImages,
  displayCategories: DISPLAY_CATEGORIES,
  displayLabels: DISPLAY_LABELS,
  displayIntros: DISPLAY_INTROS,
  stats: { years: 20, products: products.length, country: 'Chile' },
  premium: !!premium,
};

const catalogJs = `window.CATALOG_DATA=${JSON.stringify({ products })};\nwindow.CATALOG_SCORES=${JSON.stringify(scores)};\n`;
const siteJs = `window.SITE_DATA=${JSON.stringify(siteData)};\n`;

fs.writeFileSync(path.join(ROOT, 'site', 'catalog-data.js'), catalogJs);
fs.writeFileSync(path.join(ROOT, 'site', 'site-data.js'), siteJs);

const counts = {};
products.forEach((p) => { counts[p.displayCategory] = (counts[p.displayCategory] || 0) + 1; });
console.log('site/catalog-data.js + site/site-data.js generados');
console.log('Premium:', premium ? `${premium.assets.length} assets` : 'no');
console.log('Categorías:', counts);
