/**
 * Curates PhotosDrive → public/images/site/ for hero, gallery, categories.
 * Run: node scripts/build_site_assets.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const PHOTOS = path.join(ROOT, 'PhotosDrive');
const OUT = path.join(ROOT, 'public', 'images', 'site');
const MANIFEST = path.join(ROOT, 'import', 'site-media.json');

const SKIP = /picsart|screenshot|whatsapp|wa00|colage|collage|meme|logo|catalogo|\.png$/i;

function walk(dir, acc = []) {
  if (!fs.existsSync(dir)) return acc;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, e.name);
    if (e.isDirectory()) walk(p, acc);
    else if (/\.jpe?g$/i.test(e.name)) acc.push(p);
  }
  return acc;
}

function scorePhoto(file) {
  const name = path.basename(file);
  const size = fs.statSync(file).size;
  if (size < 120_000) return -1;
  if (SKIP.test(name)) return -1;
  let s = Math.min(size / 5000, 200);
  if (/^IMG_20(19|20|21)/.test(name)) s += 30;
  if (name.includes('1567685')) s += 20;
  return s;
}

function pickSpread(candidates, count) {
  const sorted = [...candidates].sort((a, b) => b.score - a.score);
  const picked = [];
  const used = new Set();
  const step = Math.max(1, Math.floor(sorted.length / count));
  for (let i = 0; i < sorted.length && picked.length < count; i += step) {
    const c = sorted[i];
    if (!used.has(c.file)) {
      picked.push(c);
      used.add(c.file);
    }
  }
  for (const c of sorted) {
    if (picked.length >= count) break;
    if (!used.has(c.file)) {
      picked.push(c);
      used.add(c.file);
    }
  }
  return picked;
}

function copySet(files, subdir, prefix) {
  const dir = path.join(OUT, subdir);
  fs.mkdirSync(dir, { recursive: true });
  return files.map((f, i) => {
    const dest = `${prefix}-${String(i + 1).padStart(2, '0')}.jpg`;
    fs.copyFileSync(f.file, path.join(dir, dest));
    return `public/images/site/${subdir}/${dest}`;
  });
}

function main() {
  const all = walk(PHOTOS)
    .map((file) => ({ file, score: scorePhoto(file) }))
    .filter((x) => x.score > 0);

  if (all.length < 10) {
    console.warn('Pocas fotos en PhotosDrive; usando catálogo como respaldo.');
  }

  const hero = pickSpread(all, 5);
  const gallery = pickSpread(all.filter((x) => !hero.some((h) => h.file === x.file)), 24);
  const categories = pickSpread(
    all.filter((x) => !hero.some((h) => h.file === x.file) && !gallery.some((g) => g.file === x.file)),
    7,
  );

  const media = {
    hero: copySet(hero, 'hero', 'hero'),
    gallery: copySet(gallery, 'gallery', 'gal'),
    categories: copySet(categories, 'categories', 'cat'),
    generatedAt: new Date().toISOString(),
  };

  fs.writeFileSync(MANIFEST, JSON.stringify(media, null, 2));
  console.log(`Site media: hero ${media.hero.length}, gallery ${media.gallery.length}, categories ${media.categories.length}`);
}

main();
