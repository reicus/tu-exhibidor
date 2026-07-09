/**
 * Pull homepage image metadata + files from live WP (/imagenes → site-data.js on server).
 * Run: node scripts/sync_home_from_wp.mjs
 */
import fs from 'fs';
import path from 'path';
import https from 'https';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const LIVE_BASE = 'https://tuexhibidor.cl';

function fetch(url) {
  return new Promise((resolve, reject) => {
    https
      .get(url, (res) => {
        if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
          fetch(res.headers.location).then(resolve).catch(reject);
          return;
        }
        const chunks = [];
        res.on('data', (c) => chunks.push(c));
        res.on('end', () => resolve({ status: res.statusCode, body: Buffer.concat(chunks) }));
      })
      .on('error', reject);
  });
}

function parseSiteData(content) {
  const m = content.toString().match(/window\.SITE_DATA\s*=\s*(\{.*\})\s*;?\s*$/s);
  if (!m) throw new Error('Could not parse site-data.js');
  return JSON.parse(m[1]);
}

function addSourcePaths(set, sources) {
  if (!sources) return;
  for (const s of Object.values(sources)) {
    if (s?.jpg) set.add(s.jpg);
    if (s?.webp) set.add(s.webp);
    if (s?.avif) set.add(s.avif);
  }
}

function collectHomePaths(site) {
  const paths = new Set();
  for (const slide of site.hero || []) addSourcePaths(paths, slide.sources);
  for (const item of site.gallery || []) {
    if (typeof item === 'string') paths.add(item);
    else addSourcePaths(paths, item.sources);
  }
  for (const cat of Object.values(site.categoryImages || {})) addSourcePaths(paths, cat.sources);
  const hs = site.homeStatic || {};
  if (hs.medida) addSourcePaths(paths, hs.medida.sources);
  for (const slot of Object.values(hs)) {
    if (slot && typeof slot === 'object' && slot.sources) addSourcePaths(paths, slot.sources);
  }
  return [...paths].filter(Boolean);
}

function bumpIndexHtml(version) {
  const file = path.join(ROOT, 'site/index.html');
  let html = fs.readFileSync(file, 'utf8');
  html = html.replace(/(\?v=)[^"'\s&>]+/g, `$1${version}`);
  fs.writeFileSync(file, html);
}

async function downloadFile(rel) {
  const url = `${LIVE_BASE}/${rel.replace(/\\/g, '/')}`;
  const { status, body } = await fetch(url);
  if (status !== 200) return { rel, ok: false, status };
  const dest = path.join(ROOT, rel.replace(/\//g, path.sep));
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  fs.writeFileSync(dest, body);
  return { rel, ok: true, bytes: body.length };
}

async function main() {
  const { body } = await fetch(`${LIVE_BASE}/site/site-data.js?v=${Date.now()}`);
  const live = parseSiteData(body);
  const localFile = path.join(ROOT, 'site/site-data.js');
  const local = parseSiteData(fs.readFileSync(localFile, 'utf8'));

  const homeKeys = ['hero', 'gallery', 'categoryImages', 'homeStatic', 'featuredSkus'];
  for (const key of homeKeys) live[key] = live[key] ?? local[key];

  const version = String(Math.floor(Date.now() / 1000));
  live.assetVersion = version;
  fs.writeFileSync(localFile, `window.SITE_DATA=${JSON.stringify(live)};\n`);
  bumpIndexHtml(version);

  const paths = collectHomePaths(live);
  console.log(`Downloading ${paths.length} home image paths from live…`);

  let ok = 0;
  let skip = 0;
  let fail = 0;
  for (const rel of paths) {
    const dest = path.join(ROOT, rel.replace(/\//g, path.sep));
    if (fs.existsSync(dest) && fs.statSync(dest).size > 0) {
      skip++;
      continue;
    }
    const r = await downloadFile(rel);
    if (r.ok) {
      ok++;
      console.log(`✓ ${rel} (${r.bytes} bytes)`);
    } else {
      fail++;
      console.log(`✗ ${rel} HTTP ${r.status}`);
    }
  }

  console.log(`\nHome sync complete. assetVersion=${version}`);
  console.log(`site-data.js updated; images downloaded=${ok}, skipped=${skip}, missing=${fail}`);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
