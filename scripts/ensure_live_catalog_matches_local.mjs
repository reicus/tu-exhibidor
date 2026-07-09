/**
 * Compare live catalog JPG Content-Length vs local; re-upload mismatched files via FTP.
 * Run: node scripts/ensure_live_catalog_matches_local.mjs [--deploy]
 */
import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const CATALOG_DIR = path.join(ROOT, 'public', 'images', 'catalog');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';
const deploy = process.argv.includes('--deploy');

const catalog = JSON.parse(
  fs.readFileSync(path.join(ROOT, 'site', 'catalog-data.js'), 'utf8').match(/window\.CATALOG_DATA=(\{.*?\});/s)[1]
);

async function headLength(url) {
  const r = await fetch(url, { method: 'HEAD' });
  if (!r.ok) return null;
  const len = r.headers.get('content-length');
  return len ? Number(len) : null;
}

function ftpUpload(local, remoteRel) {
  const url = `${FTP_BASE}/${remoteRel.replace(/^\/+/, '')}`;
  execFileSync('curl.exe', ['-sS', '--ftp-create-dirs', '-T', local, '--user', FTP_USER, url], {
    stdio: 'inherit',
  });
}

const need = [];
for (const p of catalog.products) {
  const local = path.join(CATALOG_DIR, `${p.slug}.jpg`);
  if (!fs.existsSync(local)) {
    console.log('MISSING LOCAL', p.code, p.slug);
    continue;
  }
  const localSize = fs.statSync(local).size;
  const url = `https://tuexhibidor.cl/public/images/catalog/${p.slug}.jpg`;
  const liveSize = await headLength(url);
  if (liveSize !== localSize) {
    need.push({ code: p.code, slug: p.slug, localSize, liveSize, local });
  }
}

console.log(`Catalog files needing sync: ${need.length}/${catalog.products.length}`);
for (const n of need.slice(0, 15)) {
  console.log(` ${n.code}: local=${n.localSize} live=${n.liveSize}`);
}
if (need.length > 15) console.log(` ... +${need.length - 15} more`);

if (deploy && need.length) {
  for (const n of need) {
    const remote = `public/images/catalog/${n.slug}.jpg`;
    console.log('UPLOAD', n.code, remote);
    ftpUpload(n.local, remote);
  }
  console.log('Done uploading', need.length);
}
