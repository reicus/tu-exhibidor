/**
 * Tu Exhibidor — generador de productos Vitrina + descarga de imágenes
 * Ejecutar: node scripts/build_vitrina_products.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT_IMG = path.join(ROOT, 'public', 'images', 'vitrina');
const OUT_CSV = path.join(ROOT, 'import', 'vitrina-products.csv');
const OUT_MAP = path.join(ROOT, 'import', 'vitrina-image-map.json');

const CATEGORY = 'Vitrinas & Bandejas';
const CATEGORY_SLUG = 'vitrina';

const COLOR_MAP = [
  [/chocolate\s*\/?\s*beige|choco\s*\/?\s*beige|chocolate-beige|cb\b/i, 'chocolate/beige'],
  [/steel\s*grey|steel-grey|steel gray|silver\s*gray|silver-gray|silver\s*grey|sg\b|sv\b/i, 'gris plata'],
  [/navy|nv\b/i, 'azul marino'],
  [/black|bk\b/i, 'negro'],
  [/white|wh\b|\(w\)/i, 'blanco'],
  [/metallic\s*beige/i, 'beige metalizado'],
  [/burlap|arpillera/i, 'arpillera'],
  [/chestnut|castaño/i, 'ante castaño'],
  [/champagne|bronce/i, 'bronce champagne'],
  [/dark\s*green|verde/i, 'verde'],
  [/grey\s*linen|gray\s*linen/i, 'lino gris'],
  [/rosewood|rw\b/i, 'madera rosa'],
  [/brown\s*trim/i, 'borde marrón'],
];

function slugify(text) {
  return text
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 80);
}

function detectColor(text) {
  for (const [re, label] of COLOR_MAP) {
    if (re.test(text)) return label;
  }
  return 'ecocuero premium';
}

function detectPieces(text) {
  const m = text.match(/(\d+)\s*[-\s]?(piece|pieces|piezas?|pc)/i) || text.match(/(\d+)-piece/i);
  if (m) return parseInt(m[1], 10);
  const m2 = text.match(/\b(4|5|6)['\u2019]?\s/i);
  if (m2 && /showcase|display|vitrina|set/i.test(text)) return null; // feet length handled separately
  const m3 = text.match(/\b(\d{1,2})\b/);
  if (m3 && /piece|pieza|pc|set/i.test(text)) return parseInt(m3[1], 10);
  return null;
}

function detectFeet(text) {
  const m = text.match(/(\d)\s*['\u2019]?\s*(showcase|display|vitrina)/i) || text.match(/(\d)\s*['\u2019]/);
  return m ? `${m[1]} pies` : null;
}

function stripHtml(html) {
  return (html || '')
    .replace(/<[^>]+>/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function extractSize(htmlOrText) {
  const t = stripHtml(htmlOrText);
  const m = t.match(/(\d+(?:\s*\d+\/\d+)?)\s*["'']?\s*[Ww]\s*x\s*(\d+(?:\s*\d+\/\d+)?)\s*["'']?\s*[Dd]\s*x\s*(\d+(?:\s*\d+\/\d+)?)/);
  if (m) return `${m[1]}" an x ${m[2]}" prof x ${m[3]}" alto`;
  const m2 = t.match(/Size:\s*([^<\n]+)/i);
  return m2 ? m2[1].trim() : '';
}

function spanishName(rawTitle, body = '') {
  const src = `${rawTitle} ${body}`;
  const pieces = detectPieces(src);
  const feet = detectFeet(src);
  const color = detectColor(src);
  let name = 'Set vitrina';
  if (pieces) name += ` ${pieces} piezas`;
  else if (feet) name += ` ${feet}`;
  else if (/combination|combinado/i.test(src)) name += ' combinado';
  else name += ' exhibidor';
  name += ` ${color}`;
  if (/leatherette|faux leather|ecocuero|cuero/i.test(src)) {
    if (!name.includes('ecocuero')) name += ' ecocuero';
  }
  return name.replace(/\s+/g, ' ').trim();
}

function spanishDescription(rawTitle, bodyHtml, competitor) {
  const size = extractSize(bodyHtml);
  const pieces = detectPieces(`${rawTitle} ${bodyHtml}`);
  const color = detectColor(`${rawTitle} ${bodyHtml}`);
  let desc = `<p>Set de vitrina fabricado en Chile por Tu Exhibidor. Ideal para joyerías y bisuterías que buscan presentación profesional sin intermediarios.</p>`;
  desc += `<ul>`;
  if (pieces) desc += `<li><strong>Piezas:</strong> ${pieces}</li>`;
  if (color) desc += `<li><strong>Acabado:</strong> ${color}</li>`;
  if (size) desc += `<li><strong>Medidas referenciales:</strong> ${size}</li>`;
  desc += `<li><strong>Material:</strong> ecocuero premium con terminaciones de fábrica</li>`;
  desc += `<li><strong>Cotización:</strong> consultar por WhatsApp — fabricación a medida disponible</li>`;
  desc += `</ul>`;
  desc += `<p>Referencia internacional: ${competitor}. Mismo estándar de calidad, precio de fábrica en Chile.</p>`;
  return desc;
}

function shortDesc(rawTitle, bodyHtml) {
  const size = extractSize(bodyHtml);
  const parts = [];
  const pieces = detectPieces(`${rawTitle} ${bodyHtml}`);
  if (pieces) parts.push(`${pieces} piezas`);
  if (size) parts.push(size);
  return parts.join(' · ') || 'Set vitrina ecocuero — cotizar por WhatsApp';
}

function parseNovelbox() {
  const data = JSON.parse(fs.readFileSync(path.join(__dirname, 'novelbox-products.json'), 'utf8'));
  return data.products.map((p) => ({
    source: 'novelbox',
    ref: p.handle,
    title: p.title,
    body: p.body_html || '',
    image: p.images?.[0]?.src || '',
    sku: p.variants?.[0]?.sku || p.handle.toUpperCase().slice(0, 12),
  }));
}

function parseJewelryDisplay() {
  const html = fs.readFileSync(path.join(__dirname, 'jewelrydisplay-p3.html'), 'utf8');
  const items = [];
  const re = /product-item-link"\s+href="([^"]+)"[^>]*>\s*([^<]+)/gi;
  let m;
  while ((m = re.exec(html))) {
    const url = m[1];
    const title = m[2].trim();
    if (!title || title.length < 5) continue;
    items.push({
      source: 'jewelrydisplay',
      ref: url.split('/').pop(),
      title,
      body: title,
      image: '',
      sku: url.split('/').pop()?.toUpperCase().slice(0, 16),
    });
  }
  return items;
}

function parseBigIllusion() {
  const html = fs.readFileSync(path.join(__dirname, 'bigillusion-p3.html'), 'utf8');
  const items = [];
  const re = /class="productnamecolor[^"]*"[^>]*title="([^"]+)"[^>]*>\s*([\s\S]*?)<\/a>/gi;
  let m;
  while ((m = re.exec(html))) {
    const title = m[1].trim();
    const imgMatch = html.slice(m.index, m.index + 800).match(/src="([^"]+\.(?:jpg|jpeg|png|webp)[^"]*)"/i);
    items.push({
      source: 'bigillusion',
      ref: title.split(',')[0].trim(),
      title: title.split(',')[0].trim(),
      body: title,
      image: imgMatch ? imgMatch[1].replace(/&amp;/g, '&') : '',
      sku: title.replace(/[^A-Z0-9]/gi, '').slice(0, 16),
    });
  }
  return items;
}

/** Mapeo código → URL imagen competencia (NovelBox Shopify CDN / JDI) */
const COMPETITOR_IMAGE_MAP = {
  'SET22-CB': 'https://www.novelbox.com/products/chocolate-beige-leatherette-showcase-display-set.json',
  'SET22-W': 'https://www.novelbox.com/products/plain-white-faux-leather-20-piece-display-set.json',
  'SET22-SG': 'https://www.novelbox.com/products/silver-gray-leatherette-showcase-display-set.json',
  'SET11-W': 'https://www.novelbox.com/products/plain-white-faux-leather-20-piece-display-set.json',
  '6133-4-CB': 'https://www.novelbox.com/products/4-chocolate-beige-leatherette-showcase-display-set.json',
  '6133-4-SV': 'https://www.novelbox.com/products/silver-gray-leatherette-4-showcase-display-set.json',
  '6133-4-NV': 'https://www.novelbox.com/products/navy-blue-leatherette-showcase-display-set.json',
  '6133-4-BK': 'https://www.novelbox.com/products/black-leatherette-showcase-display-set.json',
  '6133-4-WH': 'https://www.novelbox.com/products/white-leatherette-showcase-display-set.json',
  'SRS22-1': 'https://www.jewelrydisplay.com/white-showcase-set-srs22-1',
  'SRS22-SG': 'https://www.jewelrydisplay.com/steel-grey-showcase-set-srs22-sg',
  'SRS35-CL': 'https://www.jewelrydisplay.com/champagne-bronze-display-set-srs35-cl',
  'SRS35-N3': 'https://www.jewelrydisplay.com/burlap-natural-wood-display-srs35-n3',
  'SRS46-SG': 'https://www.jewelrydisplay.com/steel-grey-black-display-set-srs46-sg',
  'SRS54-SG': 'https://www.jewelrydisplay.com/steel-grey-black-display-set-srs54-sg',
  'SRS55-CL': 'https://www.jewelrydisplay.com/champagne-bronze-display-set-srs55-cl',
  'SRS55-GL': 'https://www.jewelrydisplay.com/grey-linen-display-set-srs55-gl',
  '6061-CB': 'https://www.bigillusionjewelrysupplies.com/product-p/6061-cb.htm',
  '6061-SV': 'https://www.bigillusionjewelrysupplies.com/product-p/6061-sv.htm',
};

function loadCanvaPage13() {
  const jsonPath = path.join(__dirname, 'canva-page13-products.json');
  if (!fs.existsSync(jsonPath)) return [];
  const data = JSON.parse(fs.readFileSync(jsonPath, 'utf8'));
  return (data.products || []).map((p, i) => ({
    source: 'canva-p13',
    ref: p.code || `canva-p13-${i + 1}`,
    title: p.title,
    body: p.title,
    image: '',
    imageRef: COMPETITOR_IMAGE_MAP[p.code] || '',
    sku: p.code ? `TE-${p.code.replace(/[^A-Z0-9]/gi, '').slice(0, 14)}` : `TE-VIT-P13-${String(i + 1).padStart(2, '0')}`,
    priority: 1,
  }));
}

function parseCanvaPage13() {
  const loaded = loadCanvaPage13();
  if (loaded.length) return loaded;
  // fallback mínimo si no se ejecutó extract_canva_page13.mjs
  return ['SET22 Chocolate/Beige 18 piezas'].map((title, i) => ({
    source: 'canva-p13',
    ref: `canva-p13-${i + 1}`,
    title,
    body: title,
    image: '',
    sku: `TE-VIT-P13-${String(i + 1).padStart(2, '0')}`,
    priority: 1,
  }));
}

async function downloadImage(url, dest) {
  if (!url || fs.existsSync(dest)) return fs.existsSync(dest);
  try {
    const res = await fetch(url, { redirect: 'follow', headers: { 'User-Agent': 'TuExhibidor-Bot/1.0' } });
    if (!res.ok) return false;
    const buf = Buffer.from(await res.arrayBuffer());
    if (buf.length < 2000) return false;
    fs.writeFileSync(dest, buf);
    return true;
  } catch {
    return false;
  }
}

async function fetchNovelboxImage(handle) {
  try {
    const res = await fetch(`https://www.novelbox.com/products/${handle}.json`);
    if (!res.ok) return '';
    const j = await res.json();
    return j.product?.images?.[0]?.src || '';
  } catch {
    return '';
  }
}

async function fetchJewelryDisplayImage(slugOrUrl) {
  const url = slugOrUrl.startsWith('http')
    ? slugOrUrl
    : `https://www.jewelrydisplay.com/${slugOrUrl}`;
  try {
    const res = await fetch(url, { headers: { 'User-Agent': 'Mozilla/5.0' } });
    if (!res.ok) return '';
    const html = await res.text();
    const m = html.match(/property="og:image"\s+content="([^"]+)"/i);
    return m ? m[1] : '';
  } catch {
    return '';
  }
}

async function fetchBigIllusionImage(url) {
  try {
    const res = await fetch(url, { headers: { 'User-Agent': 'Mozilla/5.0' } });
    if (!res.ok) return '';
    const html = await res.text();
    const m = html.match(/src="([^"]+vthumb[^"]+\.(?:jpg|jpeg|png))"/i)
      || html.match(/src="([^"]+images[^"]+\.(?:jpg|jpeg|png))"/i);
    return m ? m[1].replace(/&amp;/g, '&') : '';
  } catch {
    return '';
  }
}

async function resolveImageUrl(p) {
  if (p.image) return p.image.startsWith('http') ? p.image : `https://www.bigillusionjewelrysupplies.com${p.image}`;
  if (p.imageRef) {
    if (p.imageRef.endsWith('.json')) {
      try {
        const res = await fetch(p.imageRef);
        if (res.ok) {
          const j = await res.json();
          return j.product?.images?.[0]?.src || '';
        }
      } catch { /* fallthrough */ }
    }
    if (p.imageRef.includes('jewelrydisplay.com')) return fetchJewelryDisplayImage(p.imageRef);
    if (p.imageRef.includes('bigillusion')) return fetchBigIllusionImage(p.imageRef);
  }
  if (p.source === 'novelbox') return fetchNovelboxImage(p.ref);
  if (p.source === 'jewelrydisplay') return fetchJewelryDisplayImage(p.ref);
  if (p.source === 'bigillusion' && p.image) return p.image;
  return '';
}

function csvEscape(val) {
  const s = String(val ?? '');
  if (/[",\n\r]/.test(s)) return `"${s.replace(/"/g, '""')}"`;
  return s;
}

function dedupeProducts(items) {
  const seen = new Set();
  const out = [];
  for (const item of items) {
    const name = spanishName(item.title, item.body);
    const key = slugify(name);
    if (seen.has(key)) continue;
    seen.add(key);
    out.push({ ...item, name, slug: key });
  }
  return out;
}

async function main() {
  fs.mkdirSync(OUT_IMG, { recursive: true });
  fs.mkdirSync(path.dirname(OUT_CSV), { recursive: true });

  const raw = [
    ...parseCanvaPage13(),
    ...parseNovelbox(),
    ...parseJewelryDisplay(),
    ...parseBigIllusion(),
  ];

  const products = dedupeProducts(raw);
  console.log(`Productos únicos: ${products.length}`);

  const imageMap = {};
  let downloaded = 0;

  for (const p of products) {
    let imgUrl = p.image;
    if (!imgUrl && p.source === 'novelbox') imgUrl = await fetchNovelboxImage(p.ref);
    if (!imgUrl && p.source === 'jewelrydisplay') imgUrl = await fetchJewelryDisplayImage(p.ref);
    if (!imgUrl && p.source === 'bigillusion' && p.image) imgUrl = p.image.startsWith('http') ? p.image : `https://www.bigillusionjewelrysupplies.com${p.image}`;

    const ext = '.jpg';
    const filename = `${p.slug}${ext}`;
    const dest = path.join(OUT_IMG, filename);

    if (imgUrl) {
      const ok = await downloadImage(imgUrl, dest);
      if (ok) downloaded++;
      imageMap[p.slug] = { url: imgUrl, local: `public/images/vitrina/${filename}`, ok };
    }

    // Pausa breve para no saturar
    await new Promise((r) => setTimeout(r, 120));
  }

  // Copiar imágenes propias del backup si existen
  const backupSet = path.join(ROOT, 'backup/homedir/public_html/wp-content/uploads/2026/07/te-set-colgante.jpg');
  if (fs.existsSync(backupSet)) {
    fs.copyFileSync(backupSet, path.join(OUT_IMG, 'set-vitrina-colgante-propio.jpg'));
    downloaded++;
  }

  const headers = [
    'Type', 'SKU', 'Name', 'Published', 'Is featured?', 'Visibility in catalog',
    'Short description', 'Description', 'In stock?', 'Stock', 'Categories', 'Tags', 'Images',
  ];

  const rows = [headers.join(',')];
  for (const p of products) {
    const imgFile = path.join(OUT_IMG, `${p.slug}.jpg`);
    const imagePath = fs.existsSync(imgFile)
      ? `https://tuexhibidor.cl/wp-content/uploads/vitrina/${p.slug}.jpg`
      : '';
    const row = [
      'simple',
      p.sku || `TE-${p.slug.slice(0, 20).toUpperCase()}`,
      p.name,
      '1',
      '0',
      'visible',
      shortDesc(p.title, p.body),
      spanishDescription(p.title, p.body, p.source),
      '1',
      '',
      CATEGORY,
      'vitrina,set,exhibidor',
      imagePath,
    ].map(csvEscape);
    rows.push(row.join(','));
  }

  fs.writeFileSync(OUT_CSV, rows.join('\n'), 'utf8');
  fs.writeFileSync(OUT_MAP, JSON.stringify(imageMap, null, 2), 'utf8');

  console.log(`CSV: ${OUT_CSV}`);
  console.log(`Imágenes descargadas: ${downloaded}`);
  console.log(`Mapa: ${OUT_MAP}`);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
