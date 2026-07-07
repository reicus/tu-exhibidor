/**
 * Extracción completa catálogo Canva (PDF export)
 * Fuente: PhotosDrive/Documentos/CATALOGO 2020.pdf + 2021-01-09.pdf
 * Canva design: DAGtWc0EeJQ — https://canva.link/v74u1w40pocahcs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT_DIR = path.join(ROOT, 'import');
const MANIFEST = path.join(OUT_DIR, 'canva-extraction-manifest.json');
const COPY_DIR = path.join(ROOT, 'docs', 'canva-copy');
const PHOTOS = path.join(ROOT, 'PhotosDrive');

const CANVA = {
  link: 'https://canva.link/v74u1w40pocahcs',
  designId: 'DAGtWc0EeJQ',
  note: 'Canva web bloqueado (403); extracción vía PDF exportado',
};

const PDFS = [
  { file: path.join(PHOTOS, 'Documentos', 'CATALOGO 2020.pdf'), label: 'CATALOGO 2020', year: 2020 },
  { file: path.join(PHOTOS, 'Documentos', '2021-01-09.pdf'), label: 'CATALOGO 2021', year: 2021 },
];

/** Categorías WooCommerce */
const CATEGORIES = {
  vitrina: {
    name: 'Vitrinas & Bandejas',
    slug: 'vitrina',
    re: /vitrina|bandeja|set|showcase|display set|combinado|hexagonal|cuadros|dije|accesorio para/i,
  },
  cadenas: {
    name: 'Cadenas & Collares',
    slug: 'cadenas-y-collares',
    re: /cadena|collar|cuello|pechera|busto|bx-|e-|xnl|pnm|dns/i,
  },
  anillos: {
    name: 'Aros & Anillos',
    slug: 'aretes-y-anillos',
    re: /anillo|aro|arete|pendiente|cilindro|hexagonal para \d+ anillos|set.*anillo/i,
  },
  pulseras: {
    name: 'Pulseras & Relojes',
    slug: 'pulseras-y-relojes',
    re: /pulsera|reloj|brazalete|t-bar|tbar|cilindro diagonal/i,
  },
};

function slugify(t) {
  return t.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
    .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 72);
}

function sentenceCase(s) {
  const t = (s || '').trim().toLowerCase();
  if (!t) return t;
  return t.charAt(0).toUpperCase() + t.slice(1);
}

function normalizePdfText(text) {
  return text
    .replace(/CA"DIGO/gi, 'CÓDIGO')
    .replace(/DESCRIPCIA"N/gi, 'DESCRIPCIÓN')
    .replace(/DESCRIPCION/gi, 'DESCRIPCIÓN')
    .replace(/TAMAA`OS/gi, 'TAMAÑOS')
    .replace(/HEXA\?GONOS/gi, 'HEXÁGONOS')
    .replace(/\s+/g, ' ')
    .trim();
}

function categorize(desc, code) {
  const text = `${code} ${desc}`.toUpperCase();
  if (/^TUE-BA|^TUE-BC|^TUE-DI|^TUE-STAND|^SET|^6\d{3}|^SRS|^CJ|^6061|^6145|^6007|^6133|^342-CB/i.test(code)) return 'vitrina';
  if (/^TUE-AN|^TUE-AR|^TUE-S-|^TUE-S$|^L-M|^L-MD|^L-ML|^L-M6|^L-M7|^P-M|^P-SC/i.test(code)) return 'anillos';
  if (/^TUE-PU|^TUE-RE|^L-CR|^L-BS|^L-F|^7-/i.test(code)) return 'pulseras';
  if (/^TUE-CI|^TUE-CO|^BX-|^E-|^P-NM|^L-DAN|^DNS|^K-|^PNM|^LDAN|^XNL/i.test(code)) return 'cadenas';
  if (/bandeja|vitrina|charms|encantos|dije|stand|showcase|set vitrina/i.test(text)) return 'vitrina';
  if (/cadena|collar|cuello|pechera|busto/i.test(text)) return 'cadenas';
  if (/anillo|aro|arete|pendiente|cilindro/i.test(text)) return 'anillos';
  if (/pulsera|reloj|brazalete|media luna/i.test(text)) return 'pulseras';
  return 'vitrina';
}

function parseProductsFromPageText(text, pageNum, pdfLabel) {
  const products = [];
  const clean = normalizePdfText(text);
  if (!clean || clean.length < 8) return products;

  // TUE-STAND showcase sets (páginas 18-22, mayormente imágenes)
  const standCodes = clean.match(/\bTUE-STAND-\d{3}\b/gi) || [];
  const uniqStand = [...new Set(standCodes.map((c) => c.toUpperCase()))];
  for (const code of uniqStand) {
    const num = code.match(/\d+/)?.[0] || '';
    products.push({
      source: 'canva-pdf-stand',
      pdf: pdfLabel,
      page: pageNum,
      code,
      description: `Set vitrina exhibidor ${num} piezas ecocuero`,
      alto: '', ancho: '', largo: '', diametro: '',
      categoryKey: 'vitrina',
      category: CATEGORIES.vitrina.name,
      categorySlug: CATEGORIES.vitrina.slug,
    });
  }

  // Formato Canva: CÓDIGO ... DESCRIPCIÓN ... medidas ... PRECIO
  const blockRe = /C[ÓO]DIGO:\s*([A-Z0-9\-\/\(\)]+)\s*DESCRIPCI[ÓO]N:\s*(.*?)(?=C[ÓO]DIGO:|$)/gi;
  let m;
  while ((m = blockRe.exec(clean))) {
    const code = m[1].trim().toUpperCase();
    let block = m[2].trim();
    const precioIdx = block.search(/PRECIO:/i);
    if (precioIdx > -1) block = block.slice(0, precioIdx).trim();

    const descMatch = block.match(/^(.+?)(?=\s*(?:ALTO|ALTURA|ANCHO|LARGO|DIAMETRO|DIÁMETRO):)/i);
    const desc = (descMatch ? descMatch[1] : block.split(/(?:ALTO|ALTURA):/i)[0]).trim();

    const alto = block.match(/(?:ALTO|ALTURA)\s*:\s*([^A-Z\n]+?)(?=\s*(?:ANCHO|LARGO|DIAMETRO|DIÁMETRO|PRECIO|$))/i)?.[1]?.trim() || '';
    const ancho = block.match(/ANCHO\s*:\s*([^A-Z\n]+?)(?=\s*(?:LARGO|ALTO|ALTURA|DIAMETRO|PRECIO|$))/i)?.[1]?.trim() || '';
    const largo = block.match(/LARGO\s*:\s*([^A-Z\n]+?)(?=\s*(?:ANCHO|ALTO|ALTURA|DIAMETRO|PRECIO|$))/i)?.[1]?.trim() || '';
    const diametro = block.match(/DI(?:A|Á)METRO\s*:\s*([^A-Z\n]+?)(?=\s*(?:PRECIO|$))/i)?.[1]?.trim() || '';

    const catKey = categorize(desc, code);
    products.push({
      source: 'canva-pdf',
      pdf: pdfLabel,
      page: pageNum,
      code,
      description: sentenceCase(desc.replace(/\s*PRECIO.*$/i, '')),
      alto, ancho, largo, diametro,
      categoryKey: catKey,
      category: CATEGORIES[catKey].name,
      categorySlug: CATEGORIES[catKey].slug,
    });
  }

  return products;
}

async function extractAllPages(pdfPath) {
  const data = new Uint8Array(fs.readFileSync(pdfPath));
  const doc = await getDocument({ data, useSystemFonts: true }).promise;
  const pages = [];
  for (let i = 1; i <= doc.numPages; i++) {
    const page = await doc.getPage(i);
    const content = await page.getTextContent();
    const text = content.items.map((it) => it.str).join(' ').replace(/\s+/g, ' ').trim();
    pages.push({ page: i, text });
  }
  return pages;
}

function buildSpanishName(p) {
  let name = p.description;
  if (!name.toLowerCase().includes(p.code.toLowerCase()) && p.code.length < 20) {
    name = `${p.description} (${p.code})`;
  }
  return sentenceCase(name.replace(/\s+/g, ' '));
}

function buildShortDesc(p) {
  const dims = [
    p.alto && `Alto: ${p.alto}`,
    p.ancho && `Ancho: ${p.ancho}`,
    p.largo && `Largo: ${p.largo}`,
    p.diametro && `Diámetro: ${p.diametro}`,
  ].filter(Boolean).join(' · ');
  return dims || 'Fabricación chilena — cotizar por WhatsApp';
}

function buildDescription(p) {
  let html = `<p>${p.description}. Exhibidor fabricado en Chile por Tu Exhibidor, con materiales nobles y terminaciones de fábrica.</p><ul>`;
  if (p.alto) html += `<li><strong>Alto:</strong> ${p.alto}</li>`;
  if (p.ancho) html += `<li><strong>Ancho:</strong> ${p.ancho}</li>`;
  if (p.largo) html += `<li><strong>Largo:</strong> ${p.largo}</li>`;
  if (p.diametro) html += `<li><strong>Diámetro:</strong> ${p.diametro}</li>`;
  html += `<li><strong>Código:</strong> ${p.code}</li>`;
  html += `<li><strong>Cotización:</strong> consultar por WhatsApp</li></ul>`;
  html += `<p>Catálogo Canva (${CANVA.link}), página ${p.page}.</p>`;
  return html;
}

function matchPhotosDriveImage(code, desc, photoIndex) {
  const codeNorm = code.replace(/[^A-Z0-9]/gi, '').toLowerCase();
  const codeParts = code.replace(/^TUE-/, '').toLowerCase().split('-');
  const descWords = desc.toLowerCase().split(/\s+/).filter((w) => w.length > 4);

  let best = null;
  let bestScore = 0;
  for (const [file, meta] of photoIndex) {
    const fn = file.toLowerCase();
    let score = 0;
    if (fn.includes(codeNorm)) score += 15;
    if (fn.includes(code.toLowerCase())) score += 12;
    for (const part of codeParts) {
      if (part.length >= 3 && fn.includes(part)) score += 4;
    }
    // Mapeo códigos legacy WooCommerce en nombres de archivo
    const legacyMap = {
      'p-nm': ['pnm', 'pechera'],
      'l-dan': ['ldan', 'pechera'],
      'dns-11': ['dns', 'dns11'],
      'e-xnl': ['xnl', 'cuello'],
      'e-35': ['e35'],
      'bx-159': ['bx159', 'bx-159'],
      'l-md2': ['l-md2', 'lmd2', 'arete'],
      'l-ml': ['l-ml', 'lml'],
      'l-m6': ['l-m6', 'lm6'],
      'p-m1h': ['p-m1h', 'pm1h', 'anillo'],
    };
    const legacy = legacyMap[code.toLowerCase()];
    if (legacy) {
      for (const k of legacy) if (fn.includes(k)) score += 6;
    }
    for (const w of descWords) {
      if (fn.includes(w.slice(0, 6))) score += 1;
    }
    if (score > bestScore) { bestScore = score; best = meta; }
  }
  return bestScore >= 5 ? best : null;
}

function indexPhotosDrive() {
  const index = [];
  if (!fs.existsSync(PHOTOS)) return index;
  const walk = (dir) => {
    for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
      const full = path.join(dir, ent.name);
      if (ent.isDirectory()) walk(full);
      else if (/\.(jpg|jpeg|png|webp)$/i.test(ent.name)) {
        index.push([ent.name, { path: full, name: ent.name, rel: path.relative(ROOT, full).replace(/\\/g, '/') }]);
      }
    }
  };
  walk(PHOTOS);
  return index;
}

function csvEscape(v) {
  const s = String(v ?? '');
  return /[",\n\r]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
}

function dedupeProducts(all) {
  const map = new Map();
  for (const p of all) {
    const key = p.code.toUpperCase();
    const existing = map.get(key);
    if (!existing || (p.pdf || '').includes('2021')) map.set(key, p);
  }
  return [...map.values()];
}

async function fetchCompetitorImage(code, desc) {
  // Solo imágenes — competencia como fallback
  const q = encodeURIComponent(code.replace(/-/g, ' '));
  try {
    const res = await fetch(`https://www.novelbox.com/search/suggest.json?q=${q}&resources[type]=product`);
    if (res.ok) {
      const j = await res.json();
      const prod = j.resources?.results?.products?.[0];
      if (prod?.image) return prod.image;
    }
  } catch { /* ignore */ }
  return '';
}

async function main() {
  fs.mkdirSync(OUT_DIR, { recursive: true });
  fs.mkdirSync(COPY_DIR, { recursive: true });
  fs.mkdirSync(path.join(ROOT, 'public', 'images', 'catalog'), { recursive: true });

  const photoIndex = indexPhotosDrive();
  console.log(`PhotosDrive indexadas: ${photoIndex.length} imágenes`);

  const allPages = [];
  const allProducts = [];

  for (const pdf of PDFS) {
    if (!fs.existsSync(pdf.file)) { console.warn('Missing', pdf.file); continue; }
    const pages = await extractAllPages(pdf.file);
    console.log(`${pdf.label}: ${pages.length} páginas`);
    for (const pg of pages) {
      allPages.push({ pdf: pdf.label, page: pg.page, textLength: pg.text.length, preview: pg.text.slice(0, 200) });
      const prods = parseProductsFromPageText(pg.text, pg.page, pdf.label);
      allProducts.push(...prods);
      fs.writeFileSync(path.join(COPY_DIR, `${slugify(pdf.label)}-page-${String(pg.page).padStart(2, '0')}.txt`), pg.text, 'utf8');
    }
  }

  const products = dedupeProducts(allProducts).map((p) => ({
    ...p,
    name: buildSpanishName(p),
    slug: slugify(`${p.code}-${p.description}`),
    sku: p.code.startsWith('TUE-') ? p.code : `TE-${p.code.replace(/[^A-Z0-9]/gi, '').slice(0, 16)}`,
    shortDescription: buildShortDesc(p),
    htmlDescription: buildDescription(p),
  }));

  // Match images + download competitor fallback
  let matchedPhotos = 0;
  let downloadedComp = 0;
  for (const p of products) {
    const local = matchPhotosDriveImage(p.code, p.description, photoIndex);
    if (local) {
      p.imageLocal = local.rel;
      p.imageSource = 'photosdrive';
      matchedPhotos++;
      const dest = path.join(ROOT, 'public', 'images', 'catalog', `${p.slug}.jpg`);
      if (!fs.existsSync(dest)) fs.copyFileSync(local.path, dest);
    } else {
      const url = await fetchCompetitorImage(p.code, p.description);
      if (url) {
        p.imageUrl = url;
        p.imageSource = 'competitor-fallback';
        const dest = path.join(ROOT, 'public', 'images', 'catalog', `${p.slug}.jpg`);
        try {
          const res = await fetch(url);
          if (res.ok) {
            fs.writeFileSync(dest, Buffer.from(await res.arrayBuffer()));
            p.imageLocal = `public/images/catalog/${p.slug}.jpg`;
            downloadedComp++;
          }
        } catch { /* ignore */ }
      }
      await new Promise((r) => setTimeout(r, 80));
    }
  }

  const byCategory = {};
  for (const p of products) {
    byCategory[p.categoryKey] = (byCategory[p.categoryKey] || 0) + 1;
  }

  const headers = ['Type','SKU','Name','Published','Visibility in catalog','Short description','Description','In stock?','Categories','Tags','Images'];
  const rows = [headers.join(',')];
  for (const p of products) {
    const img = p.imageLocal
      ? `https://tuexhibidor.cl/wp-content/uploads/catalog/${p.slug}.jpg`
      : (p.imageUrl || '');
    rows.push([
      'simple', p.sku, p.name, '1', 'visible', p.shortDescription, p.htmlDescription,
      '1', p.category, `${p.categoryKey},${p.code}`, img,
    ].map(csvEscape).join(','));
  }
  fs.writeFileSync(path.join(OUT_DIR, 'catalogo-completo.csv'), rows.join('\n'), 'utf8');

  const vitrina = products.filter((p) => p.categoryKey === 'vitrina');
  const vRows = [headers.join(',')];
  for (const p of vitrina) {
    const img = p.imageLocal ? `https://tuexhibidor.cl/wp-content/uploads/vitrina/${p.slug}.jpg` : (p.imageUrl || '');
    vRows.push([
      'simple', p.sku, p.name, '1', 'visible', p.shortDescription, p.htmlDescription,
      '1', p.category, `vitrina,set,${p.code}`, img,
    ].map(csvEscape).join(','));
  }
  fs.writeFileSync(path.join(OUT_DIR, 'vitrina-products.csv'), vRows.join('\n'), 'utf8');

  // Category intro copy for redesign
  const intros = {
    vitrina: 'Sets y bandejas para vitrinas completas. Fabricamos en Chile con ecocuero premium — cotiza directo con el taller.',
    cadenas: 'Bustos, cuellos y pecheras para cadenas y collares. Diseño que guía la mirada del cliente hacia tus piezas.',
    anillos: 'Exhibidores para anillos y aretes: cilindros, bandejas y sets combinados. Terminaciones impecables.',
    pulseras: 'Soportes para pulseras y relojes: T-bars, cilindros y presentaciones horizontales o verticales.',
  };
  fs.writeFileSync(path.join(COPY_DIR, 'category-intros.json'), JSON.stringify(intros, null, 2));

  const manifest = {
    canva: CANVA,
    extractedAt: new Date().toISOString(),
    blockers: ['Canva web devuelve 403 (Cloudflare) — no login automatizable', 'Extracción primaria desde PDF exportado en PhotosDrive/Documentos'],
    pdfs: PDFS.map((p) => ({ label: p.label, path: path.relative(ROOT, p.file), exists: fs.existsSync(p.file) })),
    totalPages: allPages.length,
    pages: allPages,
    totalProducts: products.length,
    productsByCategory: byCategory,
    imagesMatchedPhotosDrive: matchedPhotos,
    imagesFromCompetitor: downloadedComp,
    files: {
      catalogCsv: 'import/catalogo-completo.csv',
      vitrinaCsv: 'import/vitrina-products.csv',
      manifest: 'import/canva-extraction-manifest.json',
      pageText: 'docs/canva-copy/*.txt',
      categoryIntros: 'docs/canva-copy/category-intros.json',
      images: 'public/images/catalog/',
    },
    products,
  };
  fs.writeFileSync(MANIFEST, JSON.stringify(manifest, null, 2));

  console.log('\n=== RESUMEN ===');
  console.log('Páginas:', allPages.length);
  console.log('Productos:', products.length);
  console.log('Por categoría:', byCategory);
  console.log('Imágenes PhotosDrive:', matchedPhotos);
  console.log('Imágenes competencia:', downloadedComp);
}

main().catch((e) => { console.error(e); process.exit(1); });
