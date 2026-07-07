/**
 * Descarga imágenes desde tuexhibidor.cl + backup (incluye thumbnails)
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'public', 'images', 'catalog');
const MANIFEST = path.join(ROOT, 'import', 'canva-extraction-manifest.json');

const LIVE_MAP = {
  'P-NM': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/P-NM-01.jpg',
  'L-DAN': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/L-DAN-02.jpg',
  'DNS-11': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/DNS-11-02.jpg',
  'E-XNL': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/7-XNL-01.jpg',
  'E-35': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/E-35-01.jpg',
  'BX-159': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/BX-159-01.jpg',
  'L-MD2': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/L-MD2-02.jpg',
  'L-ML': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/L-ML-01.jpg',
  'L-M6': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/L-M6-02.jpg',
  'P-M1H': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/P-M1H-01.jpg',
  'L-ME4': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/L-ME4-01.jpg',
  'L-CR6': 'https://tuexhibidor.cl/wp-content/uploads/2019/05/L-CR6-02.jpg',
  'TUE-BA-001': 'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-set-colgante.jpg',
};

function walk(dir, acc = []) {
  if (!fs.existsSync(dir)) return acc;
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    const f = path.join(dir, e.name);
    if (e.isDirectory()) walk(f, acc);
    else if (/\.(jpg|jpeg|png|webp)$/i.test(e.name)) acc.push(f);
  }
  return acc;
}

function findLocal(code, files) {
  const patterns = [
    code,
    code.replace(/-/g, ''),
    ...( { 'E-XNL': ['7-XNL', 'XNL'], 'P-NM': ['PNM'], 'L-DAN': ['LDAN'] }[code] || []),
  ].map((p) => p.toUpperCase());
  for (const p of patterns) {
    const hits = files.filter((f) => path.basename(f).toUpperCase().includes(p));
    if (hits.length) {
      hits.sort((a, b) => {
        const score = (f) => (/-\d+x\d+\./.test(f) ? 0 : 10) + (f.includes('-01') ? 5 : 0);
        return score(b) - score(a);
      });
      return hits[0];
    }
  }
  return null;
}

async function download(url, dest) {
  try {
    const res = await fetch(url, { headers: { 'User-Agent': 'Mozilla/5.0' } });
    if (!res.ok) return false;
    const buf = Buffer.from(await res.arrayBuffer());
    if (buf.length < 1500) return false;
    fs.writeFileSync(dest, buf);
    return true;
  } catch { return false; }
}

async function main() {
  fs.mkdirSync(OUT, { recursive: true });
  const files = walk(path.join(ROOT, 'backup/homedir/public_html/wp-content/uploads'));
  files.push(...walk(path.join(ROOT, 'PhotosDrive')));

  const manifest = JSON.parse(fs.readFileSync(MANIFEST, 'utf8'));
  let n = 0;
  const log = {};

  for (const p of manifest.products) {
    const dest = path.join(OUT, `${p.slug}.jpg`);
    if (fs.existsSync(dest) && fs.statSync(dest).size > 5000) { n++; continue; }

    const local = findLocal(p.code, files);
    if (local) {
      fs.copyFileSync(local, dest);
      log[p.code] = { source: 'local', path: local };
      n++;
      continue;
    }

    const live = LIVE_MAP[p.code];
    if (live && await download(live, dest)) {
      log[p.code] = { source: 'live', url: live };
      n++;
      await new Promise((r) => setTimeout(r, 100));
    }
  }

  // Descargar galería prod_* para TUE-STAND
  for (let i = 1; i <= 20; i++) {
    const num = String(i).padStart(2, '0');
    const url = `https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_${num}.jpg`;
    const dest = path.join(OUT, `canva-prod-${num}.jpg`);
    if (!fs.existsSync(dest)) await download(url, dest);
  }

  fs.writeFileSync(path.join(ROOT, 'import', 'image-download-log.json'), JSON.stringify({ matched: n, total: manifest.products.length, log }, null, 2));
  console.log(`Imágenes listas: ${n}/${manifest.products.length}`);
  console.log(`Archivos en catalog/: ${fs.readdirSync(OUT).length}`);
}

main();
