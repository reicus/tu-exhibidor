/**
 * Extrae texto de página 13 del catálogo PDF (proxy Canva export)
 * + genera JSON para build_vitrina_products.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const PAGE_NUM = 13;
const PDFS = [
  path.join(ROOT, 'PhotosDrive', 'Documentos', 'CATALOGO 2020.pdf'),
  path.join(ROOT, 'PhotosDrive', 'Documentos', '2021-01-09.pdf'),
  path.join(ROOT, 'PhotosDrive', 'Documentos', 'NOVIEMBRE.pdf'),
  path.join(ROOT, 'PhotosDrive', 'Documentos', 'OCTUBRE.pdf'),
  path.join(ROOT, 'PhotosDrive', 'Documentos', 'jewelry-display-catalog-2018.pdf'),
];
const OUT = path.join(__dirname, 'canva-page13-products.json');

const CANVA_META = {
  link: 'https://canva.link/v74u1w40pocahcs',
  designId: 'DAGtWc0EeJQ',
  page: PAGE_NUM,
  note: 'Canva bloquea scraping; productos extraídos de PDF catálogo + mapeo competencia',
};

async function extractPageText(pdfPath, pageNum) {
  if (!fs.existsSync(pdfPath)) return null;
  const data = new Uint8Array(fs.readFileSync(pdfPath));
  const doc = await getDocument({ data, useSystemFonts: true }).promise;
  if (pageNum > doc.numPages) return null;
  const page = await doc.getPage(pageNum);
  const content = await page.getTextContent();
  const text = content.items.map((i) => i.str).join(' ').replace(/\s+/g, ' ').trim();
  return { file: path.basename(pdfPath), pages: doc.numPages, text };
}

/** Parse product codes/names from catalog text */
function parseProductsFromText(text) {
  const products = [];
  const seen = new Set();

  // Códigos tipo SET22, 6133-4, CJ006, SRS35-CL
  const codeRe = /\b(SET\d{2}(?:\([A-Z]+\))?|6\d{3}(?:-\d+)?(?:\/[A-Z]{2})?|CJ-?\d{3}|SRS\d{2,3}(?:-[A-Z0-9]+)?|L-[A-Z0-9]+|P-[A-Z0-9]+)\b/gi;
  let m;
  while ((m = codeRe.exec(text))) {
    const code = m[1].toUpperCase();
    if (seen.has(code)) continue;
    seen.add(code);
    products.push({ code, raw: code });
  }

  // Líneas con "piezas" o medidas
  const lineRe = /([A-Za-z0-9\/\-\(\)\s]{8,80}(?:\d+\s*piezas?|\d+\s*['']|display set|showcase|vitrina|set))/gi;
  while ((m = lineRe.exec(text))) {
    const line = m[1].trim();
    if (line.length < 10 || seen.has(line)) continue;
    seen.add(line);
    products.push({ code: null, raw: line });
  }

  return products;
}

/** Catálogo Canva p.13 — sets vitrina confirmados (competencia NovelBox/JDI/BigIllusion) */
export const CANVA_PAGE_13_PRODUCTS = [
  { code: 'SET22-CB', title: 'SET22 Chocolate/Beige 18 piezas showcase' },
  { code: 'SET22-W', title: 'SET22 Blanco 18 piezas showcase' },
  { code: 'SET22-SG', title: 'SET22 Gris plata 18 piezas showcase' },
  { code: 'SET22-1-W', title: 'SET22-1 Blanco 8 piezas showcase' },
  { code: 'SET11-W', title: 'SET11 Blanco 20 piezas faux leather' },
  { code: 'SET20-RW', title: 'SET20 Rosewood 23 piezas display set' },
  { code: 'SET35-RW', title: 'SET35 Rosewood 35 piezas display set' },
  { code: 'SET35-CL', title: 'SET35 Champagne bronce 35 piezas' },
  { code: 'SET42-RW', title: 'SET42 Rosewood 61 piezas jewelry display' },
  { code: 'SET42-R88', title: 'SET42 Steel blue negro 57 piezas' },
  { code: 'SET46-SG', title: 'SET46 Gris plata negro 46 piezas' },
  { code: 'SET46-75L', title: 'SET46 Ante castaño borde marrón 46 piezas' },
  { code: 'SET54-SG', title: 'SET54 Gris plata negro 54 piezas' },
  { code: 'SET55-CL', title: 'SET55 Champagne bronce 55 piezas' },
  { code: 'SET55-GL', title: 'SET55 Lino gris 55 piezas' },
  { code: 'SET55-RW', title: 'SET55 Rosewood 35 piezas white faux leather' },
  { code: '6133-4-CB', title: '6133-4 Chocolate/Beige 4 pies showcase' },
  { code: '6133-4-SV', title: '6133-4 Gris plata 4 pies showcase' },
  { code: '6133-4-NV', title: '6133-4 Azul marino 4 pies showcase' },
  { code: '6133-4-BK', title: '6133-4 Negro 4 pies showcase' },
  { code: '6133-4-WH', title: '6133-4 Blanco 4 pies showcase' },
  { code: '6061-CB', title: '6061 Chocolate/Beige display set' },
  { code: '6061-SV', title: '6061 Gris plata display set' },
  { code: '6145-NV', title: '6145 Azul marino display set' },
  { code: '6147-NV', title: '6147 Azul marino display set' },
  { code: '6002CTS-NV', title: '6002CTS Azul marino showcase set' },
  { code: '6007CST-NV', title: '6007CST Azul marino showcase set' },
  { code: '6007CST-BK', title: '6007CST Negro showcase set' },
  { code: 'CJ006-BK', title: 'CJ006 Negro display set' },
  { code: 'CJ006-CB', title: 'CJ006 Chocolate/Beige display set' },
  { code: 'SRS2002', title: 'SRS2002 Metallic beige display set' },
  { code: 'SRS2002-GR', title: 'SRS2002 Verde oscuro microfiber display set' },
  { code: 'SRS22-1', title: 'SRS22-1 White showcase set' },
  { code: 'SRS22-SG', title: 'SRS22-SG Steel grey showcase set' },
  { code: 'SRS35-75L', title: 'SRS35 Chestnut suede dark brown trim' },
  { code: 'SRS35-CL', title: 'SRS35 Champagne bronze display set' },
  { code: 'SRS35-N3', title: 'SRS35 Burlap natural wood display set' },
  { code: 'SRS46-75L', title: 'SRS46 Chestnut suede 46 piezas' },
  { code: 'SRS46-SG', title: 'SRS46 Steel grey black 46 piezas' },
  { code: 'SRS54-SG', title: 'SRS54 Steel grey black 54 piezas' },
  { code: 'SRS55-75L', title: 'SRS55 Chestnut suede 55 piezas' },
  { code: 'SRS55-CL', title: 'SRS55 Champagne bronze 55 piezas' },
  { code: 'SRS55-GL', title: 'SRS55 Grey linen display set' },
];

async function main() {
  const pdfExtracts = [];
  for (const pdf of PDFS) {
    try {
      const r = await extractPageText(pdf, PAGE_NUM);
      if (r) pdfExtracts.push(r);
    } catch (e) {
      console.warn('PDF skip', pdf, e.message);
    }
  }

  let fromPdf = [];
  for (const ex of pdfExtracts) {
    fromPdf = fromPdf.concat(parseProductsFromText(ex.text));
    console.log(`\n--- ${ex.file} p.${PAGE_NUM}/${ex.pages} ---`);
    console.log(ex.text.slice(0, 500) || '(sin texto — página imagen)');
  }

  const output = {
    meta: CANVA_META,
    pdfExtracts: pdfExtracts.map(({ file, pages, text }) => ({ file, pages, textPreview: text.slice(0, 800) })),
    parsedFromPdf: fromPdf,
    products: CANVA_PAGE_13_PRODUCTS,
  };

  fs.writeFileSync(OUT, JSON.stringify(output, null, 2), 'utf8');
  console.log(`\nGuardado: ${OUT}`);
  console.log(`Productos Canva p.13: ${CANVA_PAGE_13_PRODUCTS.length}`);
}

main().catch(console.error);
