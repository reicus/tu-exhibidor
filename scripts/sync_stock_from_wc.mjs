/**
 * Sync inStock flags in site/catalog-data.js from WooCommerce Store API.
 * Run: node scripts/sync_stock_from_wc.mjs [--deploy]
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import https from 'https';
import { execSync } from 'child_process';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';
const DEPLOY = process.argv.includes('--deploy');

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

function wcInStock(wc) {
  return wc.is_in_stock !== false && wc.stock_availability?.class !== 'out-of-stock';
}

function upload(local, remote) {
  execSync(`curl.exe -sS --ftp-create-dirs -T "${local}" "${FTP_BASE}/${remote}" --user "${FTP_USER}"`, {
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

async function main() {
  const catalogPath = path.join(ROOT, 'site', 'catalog-data.js');
  const catalogJs = fs.readFileSync(catalogPath, 'utf8');
  const data = JSON.parse(catalogJs.match(/window\.CATALOG_DATA=(\{.*?\});/s)[1]);

  const allWc = [];
  for (let page = 1; page <= 10; page++) {
    const batch = await fetchJson(`https://tuexhibidor.cl/wp-json/wc/store/v1/products?per_page=100&page=${page}`);
    if (!batch.length) break;
    allWc.push(...batch);
    await new Promise((r) => setTimeout(r, 300));
  }
  console.log(`WC products fetched: ${allWc.length}`);

  let updated = 0;
  let outOfStock = 0;
  for (const p of data.products) {
    const wc = allWc.find((w) => matchWc(p, w));
    const inStock = wc ? wcInStock(wc) : (p.inStock !== false);
    if (p.inStock !== inStock) updated++;
    if (!inStock) {
      outOfStock++;
      console.log('OUT OF STOCK:', p.code, wc ? '(WC)' : '(no WC match, default in)');
    }
    p.inStock = inStock;
  }

  const scoresMatch = catalogJs.match(/window\.CATALOG_SCORES=(\{.*?\});/s);
  const scores = scoresMatch ? scoresMatch[1] : '{}';
  const assetVer = catalogJs.match(/window\.CATALOG_ASSET_VER=(.*?);\n/);
  const prefix = assetVer ? `window.CATALOG_ASSET_VER=${assetVer[1]};\n` : '';
  const out = `${prefix}window.CATALOG_DATA=${JSON.stringify(data)};\nwindow.CATALOG_SCORES=${scores};\n`;
  fs.writeFileSync(catalogPath, out);

  if (DEPLOY) {
    upload(catalogPath, 'site/catalog-data.js');
  }

  console.log(`\nStock sync: ${outOfStock} agotados, ${updated} cambios, ${data.products.length} productos`);
}

main().catch((e) => { console.error(e); process.exit(1); });
