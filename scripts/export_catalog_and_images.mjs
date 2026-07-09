/**
 * Exporta imágenes en uso + Excel de catálogo con precios.
 * Uso: node scripts/export_catalog_and_images.mjs
 */
import fs from 'fs';
import path from 'path';
import https from 'https';
import { fileURLToPath } from 'url';
import ExcelJS from 'exceljs';
import sharp from 'sharp';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const EXPORT = path.join(ROOT, 'export');
const IMG_ROOT = path.join(EXPORT, 'imagenes-en-uso');
const XLSX_PATH = path.join(EXPORT, 'catalogo-productos-precios.xlsx');
const README_PATH = path.join(EXPORT, 'README.txt');
const WC_API = 'https://tuexhibidor.cl/wp-json/wc/store/v1/products';
const SHOP_BASE = 'https://tuexhibidor.cl';

const SUBFOLDERS = ['catalogo', 'hero', 'galeria', 'categorias', 'home', 'marca'];
const counts = Object.fromEntries(SUBFOLDERS.map((k) => [k, 0]));
const copied = new Set();

function loadCatalogData() {
  const line = fs.readFileSync(path.join(ROOT, 'site/catalog-data.js'), 'utf8').split('\n')[0];
  const json = line.slice('window.CATALOG_DATA='.length).replace(/;\s*$/, '');
  return JSON.parse(json);
}

function loadSiteData() {
  const content = fs.readFileSync(path.join(ROOT, 'site/site-data.js'), 'utf8');
  const json = content.replace(/^window\.SITE_DATA=/, '').replace(/;\s*$/, '');
  return JSON.parse(json);
}

function collectHeroPaths(siteData) {
  const paths = new Set();
  for (const slide of siteData.hero || []) {
    if (slide.base) paths.add(`${slide.base}-1200.jpg`);
    for (const size of Object.values(slide.sources || {})) {
      for (const p of Object.values(size)) paths.add(p);
    }
  }
  return [...paths];
}

function collectCategoryPaths(siteData) {
  const paths = new Set();
  for (const cat of Object.values(siteData.categoryImages || {})) {
    if (cat.base) paths.add(`${cat.base}-1200.jpg`);
    for (const size of Object.values(cat.sources || {})) {
      for (const p of Object.values(size)) paths.add(p);
    }
  }
  return [...paths];
}

function collectHomePaths(siteData) {
  const paths = new Set();
  const hs = siteData.homeStatic || {};
  for (const block of Object.values(hs)) {
    if (block?.base) paths.add(`${block.base}-1200.jpg`);
    for (const size of Object.values(block?.sources || {})) {
      for (const p of Object.values(size)) paths.add(p);
    }
  }
  return [...paths];
}

function collectBrandPaths() {
  const html = fs.readFileSync(path.join(ROOT, 'site/index.html'), 'utf8');
  const paths = new Set();
  const re = /(?:public\/images\/brand\/[^"'\s?]+|\/public\/images\/brand\/[^"'\s?]+)/g;
  for (const m of html.matchAll(re)) {
    let p = m[0].replace(/^\//, '');
    if (!p.startsWith('public/')) p = `public/${p}`;
    paths.add(p.split('?')[0]);
  }
  return [...paths];
}

function copyImage(relPath, subfolder) {
  const normalized = relPath.replace(/^\//, '');
  const key = `${subfolder}:${normalized}`;
  if (copied.has(key)) {
    return path.join(IMG_ROOT, subfolder, path.basename(normalized));
  }
  const src = path.join(ROOT, normalized);
  if (!fs.existsSync(src)) {
    console.warn(`  [omitido] no existe: ${normalized}`);
    return null;
  }
  const destDir = path.join(IMG_ROOT, subfolder);
  fs.mkdirSync(destDir, { recursive: true });
  const dest = path.join(destDir, path.basename(normalized));
  fs.copyFileSync(src, dest);
  copied.add(key);
  counts[subfolder]++;
  return dest;
}

function fetchJson(url) {
  return new Promise((resolve, reject) => {
    https.get(url, (res) => {
      let data = '';
      res.on('data', (c) => { data += c; });
      res.on('end', () => {
        if (res.statusCode !== 200) {
          reject(new Error(`HTTP ${res.statusCode}: ${url}`));
          return;
        }
        resolve(JSON.parse(data));
      });
    }).on('error', reject);
  });
}

async function fetchAllWcProducts() {
  const all = [];
  let page = 1;
  while (true) {
    const batch = await fetchJson(`${WC_API}?per_page=100&page=${page}`);
    if (!Array.isArray(batch) || batch.length === 0) break;
    all.push(...batch);
    if (batch.length < 100) break;
    page++;
  }
  return all;
}

function normSku(s) {
  return (s || '').toUpperCase().replace(/\s+/g, '').replace(/^TE-/, 'TUE-');
}

function wcPriceDisplay(prices) {
  if (!prices) return '';
  const raw = prices.price || prices.regular_price || '';
  if (!raw || raw === '0') return '';
  const minor = prices.currency_minor_unit ?? 0;
  const num = Number(raw);
  if (Number.isNaN(num)) return '';
  const value = minor > 0 ? num / 10 ** minor : num;
  return Math.round(value);
}

function relativeExportPath(absPath) {
  if (!absPath) return '';
  return path.relative(EXPORT, absPath).replace(/\\/g, '/');
}

function cellText(value) {
  if (value === null || value === undefined) return '';
  if (typeof value === 'object') return String(value.result ?? value.text ?? value.richText?.map((r) => r.text).join('') ?? '').trim();
  return String(value).trim();
}

function parseEditablePrice(value) {
  const text = cellText(value);
  if (!text) return '';
  const n = Number(text.replace(/[^\d.,]/g, '').replace(',', '.'));
  if (Number.isNaN(n) || n <= 0) return '';
  return Math.round(n);
}

async function readExistingManualEdits() {
  if (!fs.existsSync(XLSX_PATH)) return { bySku: new Map(), stats: { precioComp: 0, notas: 0 } };

  const wb = new ExcelJS.Workbook();
  await wb.xlsx.readFile(XLSX_PATH);
  const ws = wb.getWorksheet('Catálogo');
  if (!ws) return { bySku: new Map(), stats: { precioComp: 0, notas: 0 } };

  const headerRow = ws.getRow(1);
  const colIndex = {};
  headerRow.eachCell((cell, col) => {
    colIndex[String(cell.value).trim()] = col;
  });

  const skuCol = colIndex.SKU;
  const priceCol = colIndex['Precio competencia (editable)'];
  const notesCol = colIndex.Notas;
  if (!skuCol || !priceCol) return { bySku: new Map(), stats: { precioComp: 0, notas: 0 } };

  const bySku = new Map();
  const stats = { precioComp: 0, notas: 0 };

  ws.eachRow((row, rowNum) => {
    if (rowNum === 1) return;
    const sku = cellText(row.getCell(skuCol).value);
    if (!sku) return;

    const precioComp = parseEditablePrice(row.getCell(priceCol).value);
    const notas = notesCol ? cellText(row.getCell(notesCol).value) : '';
    if (precioComp === '' && notas === '') return;

    bySku.set(normSku(sku), { precioComp, notas });
    bySku.set(sku.toUpperCase(), { precioComp, notas });
    if (precioComp !== '') stats.precioComp++;
    if (notas !== '') stats.notas++;
  });

  return { bySku, stats };
}

function clearExportImageFolders() {
  for (const sub of SUBFOLDERS) {
    const dir = path.join(IMG_ROOT, sub);
    if (!fs.existsSync(dir)) continue;
    for (const entry of fs.readdirSync(dir)) {
      fs.unlinkSync(path.join(dir, entry));
    }
  }
  copied.clear();
  for (const k of SUBFOLDERS) counts[k] = 0;
}

async function buildWorkbook(products, wcBySku, wcBySlug, imageMap, manualEdits) {
  const wb = new ExcelJS.Workbook();
  wb.creator = 'Tu Exhibidor export';
  wb.created = new Date();

  const ws = wb.addWorksheet('Catálogo', {
    views: [{ state: 'frozen', ySplit: 1 }],
  });

  const headers = [
    'SKU',
    'Nombre',
    'Categoría',
    'Imagen (ruta local)',
    'URL producto',
    'Precio actual WC',
    'Precio competencia (editable)',
    'Notas',
  ];

  ws.columns = [
    { key: 'sku', width: 14 },
    { key: 'nombre', width: 52 },
    { key: 'categoria', width: 18 },
    { key: 'imagen', width: 42 },
    { key: 'url', width: 55 },
    { key: 'precioWc', width: 16 },
    { key: 'precioComp', width: 22 },
    { key: 'notas', width: 28 },
  ];

  const headerRow = ws.addRow(headers);
  headerRow.font = { bold: true };
  headerRow.fill = {
    type: 'pattern',
    pattern: 'solid',
    fgColor: { argb: 'FFE8DDD0' },
  };

  const sorted = [...products].sort((a, b) => a.code.localeCompare(b.code));

  for (const p of sorted) {
    const sku = p.code;
    const wc =
      wcBySku.get(normSku(sku)) ||
      wcBySku.get(sku.toUpperCase()) ||
      wcBySlug.get(p.slug);

    const url = wc?.permalink || `${SHOP_BASE}/product/${p.slug}/`;
    const precioWc = wc ? wcPriceDisplay(wc.prices) : '';
    const imgAbs = imageMap.get(sku);
    const imgRel = relativeExportPath(imgAbs);

    const saved = manualEdits.bySku.get(normSku(sku)) || manualEdits.bySku.get(sku.toUpperCase());
    const autoNotas = p.inStock === false ? 'Sin stock' : '';
    const notas = saved?.notas !== undefined && saved.notas !== ''
      ? saved.notas
      : autoNotas;

    const row = ws.addRow({
      sku,
      nombre: p.name,
      categoria: p.displayCategory || p.categoryKey || '',
      imagen: imgRel,
      url,
      precioWc: precioWc === '' ? '' : precioWc,
      precioComp: saved?.precioComp ?? '',
      notas,
    });

    row.getCell('url').value = { text: url, hyperlink: url };
    row.getCell('url').font = { color: { argb: 'FF0563C1' }, underline: true };

    if (imgAbs && fs.existsSync(imgAbs)) {
      try {
        const thumbBuf = await sharp(imgAbs)
          .resize(80, 80, { fit: 'inside', withoutEnlargement: true })
          .jpeg({ quality: 80 })
          .toBuffer();
        const imageId = wb.addImage({
          buffer: thumbBuf,
          extension: 'jpeg',
        });
        ws.addImage(imageId, {
          tl: { col: 3, row: row.number - 1 },
          ext: { width: 80, height: 80 },
        });
        row.height = 62;
      } catch {
        // thumbnail opcional
      }
    }
  }

  ws.autoFilter = { from: 'A1', to: `H${ws.rowCount}` };

  const instr = wb.addWorksheet('Instrucciones');
  instr.getColumn(1).width = 90;
  const lines = [
    'INSTRUCCIONES — Catálogo Tu Exhibidor',
    '',
    '1. Complete la columna "Precio competencia (editable)" en la hoja Catálogo.',
    '2. Use números enteros en pesos chilenos (CLP), sin símbolo $. Ejemplo: 15900',
    '3. Deje vacío si no tiene referencia de competencia.',
    '4. No modifique los encabezados de columna ni la columna SKU.',
    '5. Guarde el archivo y devuélvalo para publicar precios en WooCommerce.',
    '',
    `Generado: ${new Date().toLocaleString('es-CL')}`,
    `Productos: ${sorted.length}`,
    `Imágenes exportadas: ${Object.values(counts).reduce((a, b) => a + b, 0)}`,
    `Precios competencia conservados: ${manualEdits.stats.precioComp}`,
    `Notas conservadas: ${manualEdits.stats.notas}`,
  ];
  for (const line of lines) instr.addRow([line]);

  await wb.xlsx.writeFile(XLSX_PATH + '.tmp');
  try {
    if (fs.existsSync(XLSX_PATH)) fs.unlinkSync(XLSX_PATH);
    fs.renameSync(XLSX_PATH + '.tmp', XLSX_PATH);
  } catch (err) {
    if (err.code === 'EBUSY' || err.code === 'EPERM') {
      const fallback = XLSX_PATH.replace(/\.xlsx$/i, '-regenerado.xlsx');
      fs.renameSync(XLSX_PATH + '.tmp', fallback);
      console.warn(`\n[aviso] ${path.basename(XLSX_PATH)} está abierto en Excel.`);
      console.warn(`         Export guardado como: ${fallback}`);
      return fallback;
    }
    throw err;
  }
  return XLSX_PATH;
}

function writeReadme(productCount, manualEdits) {
  const totalImages = Object.values(counts).reduce((a, b) => a + b, 0);
  const text = `EXPORTACIÓN TU EXHIBIDOR
==========================

Fecha: ${new Date().toLocaleString('es-CL')}

CONTENIDO
---------
1. imagenes-en-uso/   — Copia de todas las imágenes referenciadas en el sitio
   - catalogo/         ${counts.catalogo} archivos (productos del catálogo)
   - hero/             ${counts.hero} archivos (slider principal)
   - galeria/          ${counts.galeria} archivos (galería premium)
   - categorias/       ${counts.categorias} archivos (tarjetas de categoría)
   - home/             ${counts.home} archivos (imágenes estáticas del home)
   - marca/            ${counts.marca} archivos (logo, favicon, etc.)

2. catalogo-productos-precios.xlsx — Lista de ${productCount} productos con:
   - SKU, nombre, categoría
   - Ruta local de imagen + miniatura en Excel
   - URL del producto en tuexhibidor.cl
   - Precio actual en WooCommerce (si existe)
   - Columna editable "Precio competencia" para su investigación

CÓMO COMPLETAR PRECIOS DE COMPETENCIA
-------------------------------------
1. Abra catalogo-productos-precios.xlsx en Excel.
2. En la hoja "Catálogo", llene la columna G: "Precio competencia (editable)".
3. Use montos en CLP sin símbolo (ej: 12500).
4. Agregue notas en columna H si lo desea.
5. Guarde el archivo.

CÓMO DEVOLVER PARA PUBLICAR EN LA TIENDA
----------------------------------------
Cuando quiera publicar precios en WooCommerce, devuelva este archivo Excel
(o indique la ruta) y podremos importarlos con:

   node scripts/import_prices_from_xlsx.mjs export/catalogo-productos-precios.xlsx

Columna clave para importación: "Precio competencia (editable)"
Identificador de producto: columna "SKU"

NOTAS
-----
- Solo se copiaron imágenes referenciadas en catalog-data.js, site-data.js e index.html.
- No se modificó el sitio en vivo.
- Ediciones manuales conservadas del Excel anterior: ${manualEdits.stats.precioComp} precios competencia, ${manualEdits.stats.notas} notas.
- Regenerar exportación: node scripts/export_catalog_and_images.mjs
`;
  fs.writeFileSync(README_PATH, text, 'utf8');
}

async function main() {
  console.log('Exportando catálogo e imágenes...\n');

  fs.mkdirSync(EXPORT, { recursive: true });
  for (const sub of SUBFOLDERS) {
    fs.mkdirSync(path.join(IMG_ROOT, sub), { recursive: true });
  }

  const manualEdits = await readExistingManualEdits();
  if (manualEdits.stats.precioComp || manualEdits.stats.notas) {
    console.log(`Conservando ediciones previas: ${manualEdits.stats.precioComp} precios competencia, ${manualEdits.stats.notas} notas`);
  }

  clearExportImageFolders();

  const catalog = loadCatalogData();
  const site = loadSiteData();
  const products = catalog.products || [];

  console.log(`Productos en catálogo: ${products.length}`);

  const imageMap = new Map();
  for (const p of products) {
    if (!p.image) continue;
    const dest = copyImage(p.image, 'catalogo');
    if (dest) imageMap.set(p.code, dest);
  }

  for (const rel of collectHeroPaths(site)) copyImage(rel, 'hero');
  for (const rel of site.gallery || []) copyImage(rel, 'galeria');
  for (const rel of collectCategoryPaths(site)) copyImage(rel, 'categorias');
  for (const rel of collectHomePaths(site)) copyImage(rel, 'home');
  for (const rel of collectBrandPaths()) copyImage(rel, 'marca');

  console.log('\nConsultando WooCommerce Store API...');
  const wcProducts = await fetchAllWcProducts();
  console.log(`Productos WC: ${wcProducts.length}`);

  const wcBySku = new Map();
  const wcBySlug = new Map();
  for (const w of wcProducts) {
    if (w.sku) wcBySku.set(normSku(w.sku), w);
    if (w.slug) wcBySlug.set(w.slug, w);
  }

  const xlsxOut = await buildWorkbook(products, wcBySku, wcBySlug, imageMap, manualEdits);
  writeReadme(products.length, manualEdits);

  const totalImages = Object.values(counts).reduce((a, b) => a + b, 0);
  console.log('\n=== EXPORTACIÓN COMPLETA ===');
  console.log(`Carpeta: ${IMG_ROOT}`);
  for (const sub of SUBFOLDERS) {
    console.log(`  ${sub}/: ${counts[sub]} archivos`);
  }
  console.log(`Total imágenes: ${totalImages}`);
  console.log(`Excel: ${xlsxOut}`);
  console.log(`README: ${README_PATH}`);
  console.log(`Filas en Excel: ${products.length} productos`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
