/** Actualiza columnas Images en CSV según archivos locales */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const IMG = path.join(ROOT, 'public/images/catalog');
const manifest = JSON.parse(fs.readFileSync(path.join(ROOT, 'import/canva-extraction-manifest.json'), 'utf8'));

function csvEscape(v) {
  const s = String(v ?? '');
  return /[",\n\r]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
}

const headers = ['Type','SKU','Name','Published','Visibility in catalog','Short description','Description','In stock?','Categories','Tags','Images'];
function toRow(p, catOverride) {
  const imgFile = path.join(IMG, `${p.slug}.jpg`);
  const img = fs.existsSync(imgFile) ? `https://tuexhibidor.cl/wp-content/uploads/catalog/${p.slug}.jpg` : '';
  return [
    'simple', p.sku, p.name, '1', 'visible', p.shortDescription, p.htmlDescription,
    '1', catOverride || p.category, `${p.categoryKey},${p.code}`, img,
  ].map(csvEscape).join(',');
}

const all = [headers.join(',')];
const vit = [headers.join(',')];
for (const p of manifest.products) {
  all.push(toRow(p));
  if (p.categoryKey === 'vitrina') vit.push(toRow(p, p.category));
}
fs.writeFileSync(path.join(ROOT, 'import/catalogo-completo.csv'), all.join('\n'));
fs.writeFileSync(path.join(ROOT, 'import/vitrina-products.csv'), vit.join('\n'));
const withImg = manifest.products.filter((p) => fs.existsSync(path.join(IMG, `${p.slug}.jpg`))).length;
console.log(`CSV actualizado. Productos con imagen: ${withImg}/${manifest.products.length}`);
