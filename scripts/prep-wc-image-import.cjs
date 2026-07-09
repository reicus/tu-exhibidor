/**
 * Match exhibidor asset images → WooCommerce products; stage import folder + manifest.
 */
const fs = require('fs');
const path = require('path');

const ASSETS = 'C:/Users/Lenovo/.cursor/projects/c-Users-Lenovo-Downloads-Tu-Exhibidor/assets';
const WC_JSON = 'C:/Users/Lenovo/Downloads/Tu Exhibidor/export/wc-products.json';
const OUT_DIR = 'C:/Users/Lenovo/Downloads/Tu Exhibidor/export/wc-image-import';
const MANIFEST = path.join(OUT_DIR, 'manifest.json');

const products = JSON.parse(fs.readFileSync(WC_JSON, 'utf8'));

const ALIASES = {
  '7-XNL': ['E-35', 'E-XNL', 'EXNL'],
  'E-35': ['E-35', 'E-XNL', '7-XNL'],
  'E-XNL': ['E-XNL', 'E-35', '7-XNL'],
  'L-F13A': ['TUE-RE-032', 'L-F13A'],
  'L-F13B': ['TUE-RE-033', 'L-F13B'],
  'L-ML': ['TUE-S-035', 'L-ML'],
  'L-MD2': ['TUE-AR-036', 'L-MD2'],
  'L-M6': ['TUE-AR-039', 'L-M6'],
  'L-ME4': ['TUE-DI-043', 'L-ME4'],
  'HE14': ['TUE-AN-016', 'HE14'],
  '251-T2B': ['TUE-AR-041', '251-T2B'],
  '342-CB2414B': ['TUE-S-042', '342-CB2414B'],
  'P-M1H': ['TUE-AN-012', 'P-M1H'],
  'L-2442S': ['TUE-AN-013', 'L-2442S'],
  'LED3R': ['TUE-AN-014', 'LED3R'],
  'L-BS5': ['TUE-PU-029', 'L-BS5'],
  'L-CR6': ['TUE-PU-025', 'L-CR6'],
  '7-88D': ['TUE-PU-024', '7-88D'],
  '7-BB1': ['TUE-PU-027', '7-BB1'],
  'L-H234': ['TUE-019', 'L-H234'],
  'L-HE12': ['TUE-BA-002', 'L-HE12'],
  'P-SC25': ['TUE-BA-005', 'P-SC25'],
  'LDAN': ['L-DAN', 'LDAN'],
  'PNM': ['P-NM', 'PNM'],
  'DNS11': ['DNS-11', 'DNS11'],
};

function normKey(v) {
  return String(v || '').toUpperCase().replace(/[^A-Z0-9]/g, '');
}

function skuVariants(code) {
  const c = String(code || '').toUpperCase().trim();
  if (!c) return [];
  const out = new Set([c]);
  if (c.startsWith('TE-')) out.add(c.slice(3));
  else out.add('TE-' + c);
  (ALIASES[c] || []).forEach((a) => out.add(a.toUpperCase()));
  return [...out];
}

function parseStem(filename) {
  let s = filename.replace(/\.(png|jpe?g|webp)$/i, '');
  s = s.replace(/^c__Users_Lenovo_AppData_Roaming_Cursor_User_workspaceStorage_empty-window_images_exhibidor-/, '');
  s = s.replace(/^c__Users_Lenovo_AppData_Roaming_Cursor_User_workspaceStorage_empty-window_images_/, '');
  s = s.replace(/-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i, '');
  return s;
}

function extractCodes(stem) {
  const codes = [];
  const lower = stem.toLowerCase();

  const tue = lower.match(/tue-(?:stand|re|pu|co|bc|ba|an|ar|ci|di|s)-\d{3}/);
  if (tue) codes.push(tue[0].toUpperCase());

  const tueNum = lower.match(/tue-\d{3}/);
  if (tueNum) codes.push(tueNum[0].toUpperCase());

  const legacyPatterns = [
    /\b(bx-\d+)\b/,
    /\b(e-\d+)\b/,
    /\b(e-xnl)\b/,
    /\b(k-\d+)\b/,
    /\b(dns-11)\b/,
    /\b(p-nm)\b/,
    /\b(l-dan)\b/,
    /\b(7-xnl)\b/,
    /\b(7xnl)\b/,
    /\b(7-88d)\b/,
    /\b(7-bb1)\b/,
    /\b(251-t2b)\b/,
    /\b(342-cb2414b)\b/,
    /\b(he14)\b/,
    /\b(l-f13a)\b/,
    /\b(l-f13b)\b/,
    /\b(l-bs5)\b/,
    /\b(l-cr6)\b/,
    /\b(l-h234)\b/,
    /\b(l-he12)\b/,
    /\b(l-ml)\b/,
    /\b(l-md2)\b/,
    /\b(l-m6)\b/,
    /\b(l-me4)\b/,
    /\b(l-2442s)\b/,
    /\b(led3r)\b/,
    /\b(p-m1h)\b/,
    /\b(p-sc25)\b/,
    /\b(pnm)\b/,
    /\b(ldan)\b/,
    /\b(bd-t3b)\b/,
    /\b(e-t3b)\b/,
  ];
  for (const re of legacyPatterns) {
    const m = lower.match(re);
    if (m) {
      let val = m[1].toUpperCase();
      if (val === '7XNL') val = '7-XNL';
      codes.push(val);
    }
  }

  if (lower.includes('xnl') && lower.includes('e-35')) {
    codes.push('E-35', 'E-XNL', '7-XNL');
  }
  if (lower.includes('e-35')) codes.push('E-35');
  if (lower.includes('e-xnl')) codes.push('E-XNL');
  if (lower.includes('7xnl') || lower.includes('7-xnl')) codes.push('7-XNL');

  return [...new Set(codes)];
}

function fileQuality(stem) {
  const l = stem.toLowerCase();
  let score = 0;
  if (/-1(?:$|-)/.test(l) || l.endsWith('-1')) score += 30;
  if (/-01(?:$|-)/.test(l)) score += 15;
  if (/scaled/.test(l)) score -= 5;
  if (/picsart|chatgpt|img-20|image-/.test(l)) score -= 40;
  if (/^[0-9a-f-]{30,}$/.test(l.split('-')[0])) score -= 50;
  return score;
}

function productMatchesCode(product, code) {
  const sku = String(product.sku || '').toUpperCase();
  const slug = String(product.slug || '').toLowerCase();
  const name = String(product.name || '').toLowerCase();
  const codeL = code.toLowerCase();
  const nk = normKey(code);

  for (const v of skuVariants(code)) {
    if (sku && sku === v) return true;
    for (const sv of skuVariants(sku)) {
      if (sv === v) return true;
    }
  }

  if (slug && (slug === codeL || slug.startsWith(codeL + '-') || slug.includes(codeL))) return true;
  if (normKey(slug) === nk || normKey(slug).includes(nk)) return true;
  if (name.includes('(' + codeL + ')')) return true;

  for (const [a, list] of Object.entries(ALIASES)) {
    const al = list.map((x) => x.toUpperCase());
    if (al.includes(code.toUpperCase()) && al.includes(sku)) return true;
    if (al.includes(code.toUpperCase()) && normKey(slug).includes(normKey(a))) return true;
  }

  return false;
}

function scorePair(stem, product, code) {
  const l = stem.toLowerCase();
  const slug = String(product.slug || '').toLowerCase();
  const sku = String(product.sku || '').toUpperCase();
  let score = fileQuality(stem);

  if (slug && l.includes(slug)) score += 200;
  else if (slug && l.includes(slug.split('-').slice(0, 3).join('-'))) score += 120;
  else if (sku && l.includes(sku.toLowerCase())) score += 180;
  else if (code && productMatchesCode(product, code)) score += 80;
  else return -9999;

  // Prefer SKU products over legacy duplicates when filename names the new code.
  if (sku && l.includes(sku.toLowerCase())) score += 40;
  if (!sku && l.includes(slug)) score += 20;

  return score;
}

function findBestProductForStem(stem, codes) {
  let best = null;
  let bestCode = null;
  let bestScore = -9999;

  for (const product of products) {
    const slug = String(product.slug || '').toLowerCase();
    if (slug && stem.toLowerCase().includes(slug)) {
      const sc = scorePair(stem, product, '');
      if (sc > bestScore) {
        bestScore = sc;
        best = product;
        bestCode = product.sku || slug;
      }
    }
  }

  for (const code of codes) {
    for (const product of products) {
      const sc = scorePair(stem, product, code);
      if (sc > bestScore) {
        bestScore = sc;
        best = product;
        bestCode = code;
      }
    }
  }

  return { product: best, code: bestCode, score: bestScore };
}

function aliasTargets(code) {
  const c = String(code || '').toUpperCase();
  const out = new Set();
  for (const [key, list] of Object.entries(ALIASES)) {
    const all = [key, ...list].map((x) => x.toUpperCase());
    if (all.includes(c)) all.forEach((x) => out.add(x));
  }
  return [...out];
}

function findProductBySkuOrSlug(token) {
  const t = String(token || '').toUpperCase();
  const tl = String(token || '').toLowerCase();
  for (const p of products) {
    if (String(p.sku || '').toUpperCase() === t) return p;
    if (String(p.slug || '').toLowerCase() === tl) return p;
    if (String(p.slug || '').toLowerCase().startsWith(tl + '-')) return p;
  }
  return null;
}

if (!fs.existsSync(OUT_DIR)) fs.mkdirSync(OUT_DIR, { recursive: true });

const files = fs.readdirSync(ASSETS).filter((f) => /\.(png|jpe?g|webp)$/i.test(f));
const exhibidor = files.filter((f) => /exhibidor-/i.test(f));
const candidates = [];

for (const file of exhibidor) {
  const stem = parseStem(file);
  const codes = extractCodes(stem);
  const hit = findBestProductForStem(stem, codes);
  candidates.push({
    file,
    stem,
    codes,
    code: hit.code,
    product: hit.product,
    score: hit.score,
  });
}

// Best image per product; allow same file to map to multiple products only when slug matches each.
const byProduct = new Map();
for (const c of candidates) {
  if (!c.product || c.score < 0) continue;
  const key = c.product.id;
  const prev = byProduct.get(key);
  if (!prev || c.score > prev.score) byProduct.set(key, c);
}

// Second pass: legacy products (no SKU) with slug hit in filename.
for (const c of candidates) {
  if (!c.product || c.score < 0) continue;
  const slug = String(c.product.slug || '').toLowerCase();
  if (!slug || String(c.product.sku || '')) continue;
  if (!c.stem.toLowerCase().includes(slug)) continue;
  const key = c.product.id;
  const prev = byProduct.get(key);
  if (!prev || c.score > prev.score) byProduct.set(key, c);
}

const matched = [];
const unmatched = [];
const usedFiles = new Set();

for (const [pid, c] of byProduct) {
  const ext = path.extname(c.file).toLowerCase();
  const destName = (c.product.sku || c.product.slug || String(pid)).toUpperCase().replace(/[^A-Z0-9-]/g, '') + ext;
  const src = path.join(ASSETS, c.file);
  const dest = path.join(OUT_DIR, destName);
  fs.copyFileSync(src, dest);
  matched.push({
    product_id: c.product.id,
    sku: c.product.sku || '',
    slug: c.product.slug,
    name: c.product.name,
    permalink: c.product.permalink,
    source_file: c.file,
    import_file: destName,
    matched_code: c.code,
  });
  usedFiles.add(c.file);
}

// Propagate same image to aliased SKU products when only legacy filename exists.
for (const entry of [...matched]) {
  const aliases = aliasTargets(entry.matched_code || entry.sku || entry.slug);
  for (const alias of aliases) {
    const target = findProductBySkuOrSlug(alias);
    if (!target || matched.some((m) => m.product_id === target.id)) continue;
    matched.push({
      product_id: target.id,
      sku: target.sku || '',
      slug: target.slug,
      name: target.name,
      permalink: target.permalink,
      source_file: entry.source_file,
      import_file: entry.import_file,
      matched_code: alias,
      alias_of: entry.product_id,
    });
  }
}

for (const c of candidates) {
  if (!usedFiles.has(c.file)) {
    unmatched.push({ file: c.file, stem: c.stem, codes: c.codes, reason: c.product ? 'duplicate-product' : 'no-product-match' });
  }
}

fs.writeFileSync(MANIFEST, JSON.stringify({ generated: new Date().toISOString(), matched, unmatched }, null, 2));

console.log(JSON.stringify({
  total_assets: files.length,
  exhibidor_images: exhibidor.length,
  matched_products: matched.length,
  unmatched_images: unmatched.length,
  sample_matched: matched.slice(0, 5).map((m) => ({ sku: m.sku, file: m.import_file })),
  sample_unmatched: unmatched.slice(0, 15).map((u) => ({ file: u.file, codes: u.codes, reason: u.reason })),
}, null, 2));
