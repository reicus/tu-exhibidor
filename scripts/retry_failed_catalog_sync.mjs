/**
 * Retry failed WC→catalog syncs (Windows write workaround: buffer + unlink dest).
 */
import fs from 'fs';
import path from 'path';
import os from 'os';
import { fileURLToPath } from 'url';
import https from 'https';
import http from 'http';
import { execSync } from 'child_process';
import sharp from 'sharp';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'public', 'images', 'catalog');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';
const BG = { r: 221, g: 211, b: 200 };
const CODES = process.argv.slice(2).length
  ? process.argv.slice(2)
  : ['TUE-BC-004', 'TUE-CI-002', 'K-28', 'BX-89', 'TUE-AR-038', 'TUE-STAND-008', 'TUE-STAND-009', 'TUE-STAND-010', 'TUE-STAND-012', 'TUE-AN-022'];

function fetchJson(url) {
  return new Promise((resolve, reject) => {
    https.get(url, (res) => {
      let data = '';
      res.on('data', (c) => { data += c; });
      res.on('end', () => {
        try { resolve(JSON.parse(data)); } catch (e) { reject(e); }
      });
    }).on('error', reject);
  });
}

function download(url, dest) {
  return new Promise((resolve, reject) => {
    const mod = url.startsWith('https') ? https : http;
    mod.get(url, (res) => {
      if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
        download(res.headers.location, dest).then(resolve).catch(reject);
        return;
      }
      const file = fs.createWriteStream(dest);
      res.pipe(file);
      file.on('finish', () => file.close(() => resolve(dest)));
    }).on('error', reject);
  });
}

function upload(local, remote) {
  execSync(`curl.exe -sS --ftp-create-dirs -T "${local}" "${FTP_BASE}/${remote}" --user "${FTP_USER}"`, {
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

function norm(s) {
  return (s || '').toUpperCase().replace(/^TE-/, '').replace(/[^A-Z0-9]/g, '');
}

function matchWc(item, wc) {
  const code = (item.code || '').toUpperCase();
  const sku = (wc.sku || '').toUpperCase();
  const slug = wc.slug || '';
  if (code && sku && code === sku) return true;
  if (norm(code) && norm(sku) && norm(code) === norm(sku)) return true;
  if (item.slug && slug === item.slug) return true;
  if (code && slug.includes(code.toLowerCase())) return true;
  return false;
}

const catalogJs = fs.readFileSync(path.join(ROOT, 'site', 'catalog-data.js'), 'utf8');
const products = JSON.parse(catalogJs.match(/window\.CATALOG_DATA=(\{.*?\});/s)[1]).products;
const allWc = [];
for (let page = 1; page <= 10; page++) {
  const batch = await fetchJson(`https://tuexhibidor.cl/wp-json/wc/store/v1/products?per_page=100&page=${page}`);
  if (!batch.length) break;
  allWc.push(...batch);
}

let ok = 0;
let fail = 0;
for (const code of CODES) {
  const item = products.find((p) => p.code === code);
  if (!item) {
    console.log('NO ITEM', code);
    fail++;
    continue;
  }
  const wc = allWc.find((w) => matchWc(item, w));
  if (!wc?.images?.[0]?.src) {
    console.log('NO WC IMG', code);
    fail++;
    continue;
  }
  const slug = item.slug;
  const dest = path.join(OUT, `${slug}.jpg`);
  const tmp = path.join(os.tmpdir(), `tuex-sync-${slug}.tmp`);
  try {
    if (fs.existsSync(dest)) fs.unlinkSync(dest);
    await download(wc.images[0].src, tmp);
    const buf = await sharp(tmp)
      .flatten({ background: BG })
      .jpeg({ quality: 88, mozjpeg: true })
      .resize(1200, 1200, { fit: 'inside', withoutEnlargement: false })
      .toBuffer();
    fs.writeFileSync(dest, buf);
    fs.unlinkSync(tmp);
    upload(dest, `public/images/catalog/${slug}.jpg`);
    console.log('OK', code);
    ok++;
  } catch (e) {
    console.error('FAIL', code, e.message);
    if (fs.existsSync(tmp)) {
      try { fs.unlinkSync(tmp); } catch (_) { /* ignore */ }
    }
    fail++;
  }
}
console.log(`Retry: ${ok} ok, ${fail} fail`);
