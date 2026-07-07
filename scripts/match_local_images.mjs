/**
 * Copia imágenes del backup y PhotosDrive hacia public/images/catalog/
 * Mapeo por código de producto WooCommerce / Canva
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'public', 'images', 'catalog');
const MANIFEST = path.join(ROOT, 'import', 'canva-extraction-manifest.json');

const IMAGE_SOURCES = [
  path.join(ROOT, 'backup/homedir/public_html/wp-content/uploads/2019/05'),
  path.join(ROOT, 'backup/homedir/public_html/wp-content/uploads/2026/07'),
  path.join(ROOT, 'PhotosDrive'),
];

const CODE_TO_FILE = {
  'P-NM': ['P-NM-01', 'PNM'],
  'L-DAN': ['L-DAN', 'LDAN'],
  'DNS-11': ['DNS-11', 'DNS'],
  'E-XNL': ['7-XNL', 'XNL'],
  'E-35': ['E-35'],
  'E-40': ['E-40'],
  'BX-159': ['BX-159'],
  'BX-89': ['BX-89'],
  'BX-109': ['BX-109'],
  'L-MD2': ['L-MD2'],
  'L-ML': ['L-ML'],
  'L-M6': ['L-M6'],
  'L-M7': ['L-M7'],
  'P-M1H': ['P-M1H'],
  'L-CR6': ['L-CR6'],
  'L-ME4': ['L-ME4'],
  'L-HE12': ['L-HE12'],
  'LHP13': ['LHP13'],
  'LHP12': ['LHP12'],
  'P-SC25': ['P-SC25'],
};

function walk(dir, acc = []) {
  if (!fs.existsSync(dir)) return acc;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const f = path.join(dir, e.name);
    if (e.isDirectory()) walk(f, acc);
    else if (/\.(jpg|jpeg|png|webp)$/i.test(e.name) && !/-\d+x\d+\./.test(e.name)) acc.push(f);
  }
  return acc;
}

function findImage(code, files) {
  const hints = CODE_TO_FILE[code] || [code.replace(/-/g, ''), code];
  for (const h of hints) {
    const hit = files.find((f) => path.basename(f).toUpperCase().includes(h.toUpperCase()));
    if (hit) return hit;
  }
  const norm = code.replace(/[^A-Z0-9]/gi, '').toLowerCase();
  return files.find((f) => path.basename(f).replace(/[^a-z0-9]/gi, '').toLowerCase().includes(norm));
}

async function main() {
  fs.mkdirSync(OUT, { recursive: true });
  const files = [];
  for (const src of IMAGE_SOURCES) files.push(...walk(src));

  const manifest = JSON.parse(fs.readFileSync(MANIFEST, 'utf8'));
  let copied = 0;
  const map = {};

  for (const p of manifest.products) {
    const dest = path.join(OUT, `${p.slug}.jpg`);
    if (fs.existsSync(dest)) { copied++; continue; }
    const src = findImage(p.code, files);
    if (src) {
      fs.copyFileSync(src, dest);
      map[p.code] = { from: path.relative(ROOT, src), to: `public/images/catalog/${p.slug}.jpg` };
      copied++;
    }
  }

  // Copiar te-set-colgante y prod_* como genéricos vitrina
  const extras = ['te-set-colgante.jpg', 'prod_14.jpg', 'prod_01.jpg'];
  for (const ex of extras) {
    const src = files.find((f) => f.endsWith(ex));
    if (src) fs.copyFileSync(src, path.join(OUT, ex.replace('.jpg', '-hero.jpg')));
  }

  fs.writeFileSync(path.join(ROOT, 'import', 'image-copy-map.json'), JSON.stringify(map, null, 2));
  console.log(`Imágenes copiadas: ${copied}/${manifest.products.length}`);
  console.log(`Mapeo guardado: import/image-copy-map.json`);
}

main();
