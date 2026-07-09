/**
 * Lee precios desde catalogo-productos-precios.xlsx (columna "Precio competencia (editable)")
 * y muestra un resumen. Con --apply y credenciales WC podría actualizar la tienda.
 *
 * Uso:
 *   node scripts/import_prices_from_xlsx.mjs [ruta.xlsx]
 *   node scripts/import_prices_from_xlsx.mjs export/catalogo-productos-precios.xlsx --dry-run
 *
 * Columnas esperadas (hoja "Catálogo"):
 *   - SKU
 *   - Precio competencia (editable)
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import ExcelJS from 'exceljs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const DEFAULT_XLSX = path.join(ROOT, 'export', 'catalogo-productos-precios.xlsx');

const SKU_COL = 'SKU';
const PRICE_COL = 'Precio competencia (editable)';
const SHEET = 'Catálogo';

function parseArgs() {
  const args = process.argv.slice(2);
  const xlsx = args.find((a) => !a.startsWith('--')) || DEFAULT_XLSX;
  const dryRun = !args.includes('--apply');
  return { xlsx: path.resolve(xlsx), dryRun };
}

function parsePrice(value) {
  if (value === null || value === undefined || value === '') return null;
  const n = Number(String(value).replace(/[^\d.,]/g, '').replace(',', '.'));
  if (Number.isNaN(n) || n <= 0) return null;
  return Math.round(n);
}

async function readPrices(xlsxPath) {
  if (!fs.existsSync(xlsxPath)) {
    throw new Error(`No se encontró: ${xlsxPath}`);
  }

  const wb = new ExcelJS.Workbook();
  await wb.xlsx.readFile(xlsxPath);
  const ws = wb.getWorksheet(SHEET);
  if (!ws) throw new Error(`Falta la hoja "${SHEET}"`);

  const headerRow = ws.getRow(1);
  const colIndex = {};
  headerRow.eachCell((cell, col) => {
    colIndex[String(cell.value).trim()] = col;
  });

  if (!colIndex[SKU_COL]) throw new Error(`Falta columna "${SKU_COL}"`);
  if (!colIndex[PRICE_COL]) throw new Error(`Falta columna "${PRICE_COL}"`);

  const rows = [];
  ws.eachRow((row, rowNum) => {
    if (rowNum === 1) return;
    const sku = String(row.getCell(colIndex[SKU_COL]).value || '').trim();
    if (!sku) return;
    const raw = row.getCell(colIndex[PRICE_COL]).value;
    const price = parsePrice(raw?.result ?? raw);
    if (price !== null) rows.push({ sku, price, rowNum });
  });

  return rows;
}

async function main() {
  const { xlsx, dryRun } = parseArgs();
  console.log(`Leyendo: ${xlsx}`);
  console.log(dryRun ? 'Modo: vista previa (sin publicar)' : 'Modo: --apply (publicación pendiente de implementar)\n');

  const rows = await readPrices(xlsx);
  console.log(`Productos con precio competencia: ${rows.length}\n`);

  if (rows.length === 0) {
    console.log('No hay precios para importar. Complete la columna "' + PRICE_COL + '".');
    return;
  }

  const preview = rows.slice(0, 10);
  for (const r of preview) {
    console.log(`  ${r.sku.padEnd(16)} → $${r.price.toLocaleString('es-CL')} CLP`);
  }
  if (rows.length > 10) console.log(`  ... y ${rows.length - 10} más`);

  if (dryRun) {
    console.log('\nPara publicar en WooCommerce en el futuro, ejecute con --apply');
    console.log('(requiere configurar credenciales REST API de WooCommerce).');
    return;
  }

  console.log('\n[INFO] Publicación WC no implementada aún.');
  console.log('Devuelva este archivo al asistente para aplicar precios de forma asistida.');
  console.log(`Datos listos: ${rows.length} SKU con precio en columna "${PRICE_COL}".`);
}

main().catch((err) => {
  console.error(err.message || err);
  process.exit(1);
});
