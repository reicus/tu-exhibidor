/**
 * Re-encode catalog JPGs from WooCommerce thumbnails (fix corrupt PNG→JPG).
 * Run: node scripts/fix_catalog_from_wc.mjs
 */
import fs from 'fs';
import path from 'path';
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

function fetchJson(url, retries = 3) {
  return new Promise((resolve, reject) => {
    const attempt = (left) => {
      https.get(url, (res) => {
        let data = '';
        res.on('data', (c) => { data += c; });
        res.on('end', () => {
          if (res.statusCode !== 200) {
            if (left > 0) {
              setTimeout(() => attempt(left - 1), 1500);
              return;
            }
            reject(new Error(`HTTP ${res.statusCode} for ${url}`));
            return;
          }
          try {
            resolve(JSON.parse(data));
          } catch (e) {
            reject(new Error(`Invalid JSON from ${url}: ${data.slice(0, 80)}`));
          }
        });
      }).on('error', (e) => {
        if (left > 0) setTimeout(() => attempt(left - 1), 1500);
        else reject(e);
      });
    };
    attempt(retries);
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

function norm(s) {
  return (s || '').toUpperCase().replace(/^TE-/, '').replace(/[^A-Z0-9]/g, '');
}

function matchWc(catalogItem, wc) {
  const code = (catalogItem.code || '').toUpperCase();
  const sku = (wc.sku || '').toUpperCase();
  const slug = wc.slug || '';
  if (code && sku && code === sku) return true;
  if (norm(code) && norm(sku) && norm(code) === norm(sku)) return true;
  if (catalogItem.slug && slug === catalogItem.slug) return true;
  if (code && slug.includes(code.toLowerCase())) return true;
  if (code === 'E-35' && ['E-XNL', 'EXNL'].includes(sku)) return true;
  if (code === 'E-XNL' && sku === 'E-XNL') return true;
  return false;
}

async function main() {
  const catalogJs = fs.readFileSync(path.join(ROOT, 'site', 'catalog-data.js'), 'utf8');
  const products = JSON.parse(catalogJs.match(/window\.CATALOG_DATA=(\{.*?\});/s)[1]).products;
  const targets = products.filter((p) => ['collares', 'bandejas', 'dijes'].includes(p.displayCategory));

  const allWc = [];
  for (let page = 1; page <= 10; page++) {
    const batch = await fetchJson(`https://tuexhibidor.cl/wp-json/wc/store/v1/products?per_page=100&page=${page}`);
    if (!batch.length) break;
    allWc.push(...batch);
    await new Promise((r) => setTimeout(r, 400));
  }

  fs.mkdirSync(OUT, { recursive: true });
  let ok = 0;
  let skip = 0;

  for (const item of targets) {
    const wc = allWc.find((w) => matchWc(item, w));
    if (!wc?.images?.[0]?.src) {
      console.log('SKIP no WC:', item.code);
      skip++;
      continue;
    }
    const slug = item.slug;
    const tmp = path.join(OUT, `_tmp_${slug}`);
    const dest = path.join(OUT, `${slug}.jpg`);
    try {
      await download(wc.images[0].src, tmp);
      await sharp(tmp)
        .flatten({ background: BG })
        .jpeg({ quality: 88, mozjpeg: true })
        .resize(1200, 1200, { fit: 'inside', withoutEnlargement: false })
        .toFile(dest);
      fs.unlinkSync(tmp);
      const remote = `${FTP_BASE}/public/images/catalog/${slug}.jpg`;
      execSync(`curl.exe -sS -T "${dest}" "${remote}" --user "${FTP_USER}"`, { stdio: 'inherit' });
      console.log('OK', item.code, '->', path.basename(dest));
      ok++;
    } catch (e) {
      console.error('FAIL', item.code, e.message);
      if (fs.existsSync(tmp)) fs.unlinkSync(tmp);
    }
  }

  const ver = String(Date.now());
  const catalogPath = path.join(ROOT, 'site', 'catalog-data.js');
  let cat = fs.readFileSync(catalogPath, 'utf8');
  if (!cat.includes('CATALOG_ASSET_VER')) {
    cat = `window.CATALOG_ASSET_VER=${JSON.stringify(ver)};\n` + cat;
  } else {
    cat = cat.replace(/window\.CATALOG_ASSET_VER=.*?;\n/, `window.CATALOG_ASSET_VER=${JSON.stringify(ver)};\n`);
  }
  fs.writeFileSync(catalogPath, cat);
  execSync(`curl.exe -sS -T "${catalogPath}" "${FTP_BASE}/site/catalog-data.js" --user "${FTP_USER}"`, { stdio: 'inherit' });
  execSync(`curl.exe -sS -T "${path.join(ROOT, 'site', 'app.js')}" "${FTP_BASE}/site/app.js" --user "${FTP_USER}"`, { stdio: 'inherit' });

  console.log(`\nDone: ${ok} fixed, ${skip} skipped. Cache ver: ${ver}`);
}

main().catch((e) => { console.error(e); process.exit(1); });
