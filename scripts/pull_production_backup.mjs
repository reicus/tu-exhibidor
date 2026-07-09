/**
 * Pull production state → local working tree + dated backup folder.
 * Run: node scripts/pull_production_backup.mjs
 */
import fs from 'fs';
import path from 'path';
import https from 'https';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const LIVE = 'https://tuexhibidor.cl';
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';
const DATE = new Date().toISOString().slice(0, 10).replace(/-/g, '');
const BACKUP_DIR = path.join(ROOT, `backup-produccion-${DATE}`);

const SITE_FILES = [
  'site/index.html',
  'site/app.js',
  'site/styles.css',
  'site/site-data.js',
  'site/catalog-data.js',
];

const FTP_PULL = [
  ['wp-content/themes/aurum-child/functions.php', 'deploy/wp-content/themes/aurum-child/functions.php'],
  ['wp-content/themes/aurum-child/footer.php', 'deploy/wp-content/themes/aurum-child/footer.php'],
  ['wp-content/themes/aurum-child/style.css', 'deploy/wp-content/themes/aurum-child/style.css'],
  ['deploy/.htaccess', 'deploy/.htaccess'],
];

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

function parseCatalogData(content) {
  const m = content.toString().match(/window\.CATALOG_DATA\s*=\s*(\{.*?\})\s*;\s*window/s);
  if (!m) throw new Error('Could not parse catalog-data.js');
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

function collectImagePaths(site, catalog) {
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
  for (const p of catalog.products || []) {
    if (p.image) paths.add(p.image);
  }
  // brand logos
  for (const rel of [
    'public/images/brand/logo-tuexhibidor-ink-96.png',
    'public/images/brand/logo-tuexhibidor-ink-96.webp',
    'public/images/brand/logo-tuexhibidor-gold-96.webp',
    'public/images/brand/favicon.ico',
  ]) {
    paths.add(rel);
  }
  return [...paths].filter(Boolean);
}

function cpFile(src, dest) {
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  fs.copyFileSync(src, dest);
}

function mirrorToBackup(relPath) {
  const src = path.join(ROOT, relPath);
  if (!fs.existsSync(src)) return;
  const dest = path.join(BACKUP_DIR, relPath);
  cpFile(src, dest);
}

async function downloadFile(rel, bust) {
  const url = `${LIVE}/${rel.replace(/\\/g, '/')}?v=${bust}`;
  const { status, body } = await fetch(url);
  if (status !== 200) return { rel, ok: false, status };
  const dest = path.join(ROOT, rel.replace(/\//g, path.sep));
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  fs.writeFileSync(dest, body);
  return { rel, ok: true, bytes: body.length };
}

function ftpDownload(remote, localRel) {
  const url = `${FTP_BASE}/${remote.replace(/^\/+/, '')}`;
  const local = path.join(ROOT, localRel);
  fs.mkdirSync(path.dirname(local), { recursive: true });
  execFileSync('curl.exe', ['-sS', '--user', FTP_USER, '-o', local, url], { stdio: 'pipe' });
  const size = fs.statSync(local).size;
  if (size < 50) throw new Error(`FTP download too small: ${remote} (${size} bytes)`);
  return size;
}

function ftpListMuPlugin() {
  const base = `${FTP_BASE}/wp-content/mu-plugins/tuexhibidor-site-manager/`;
  const out = execFileSync('curl.exe', ['-sS', '--user', FTP_USER, '--list-only', base], {
    encoding: 'utf8',
  });
  return out
    .trim()
    .split(/\r?\n/)
    .map((s) => s.trim())
    .filter((s) => s && s !== '.' && s !== '..');
}

function ftpPullMuPlugin() {
  const remoteBase = 'wp-content/mu-plugins/tuexhibidor-site-manager';
  const localBase = 'deploy/wp-content/mu-plugins/tuexhibidor-site-manager';
  const entries = ftpListMuPlugin();
  let n = 0;
  for (const entry of entries) {
    const subUrl = `${FTP_BASE}/${remoteBase}/${entry}/`;
    let sub;
    try {
      sub = execFileSync('curl.exe', ['-sS', '--user', FTP_USER, '--list-only', subUrl], {
        encoding: 'utf8',
      })
        .trim()
        .split(/\r?\n/)
        .map((s) => s.trim())
        .filter((s) => s && s !== '.' && s !== '..');
    } catch {
      sub = null;
    }
    if (sub && sub.length > 0) {
      for (const f of sub) {
        const remote = `${remoteBase}/${entry}/${f}`;
        const local = `${localBase}/${entry}/${f}`;
        ftpDownload(remote, local);
        n++;
      }
    } else {
      const remote = `${remoteBase}/${entry}`;
      const local = `${localBase}/${entry}`;
      ftpDownload(remote, local);
      n++;
    }
  }
  return n;
}

async function main() {
  const bust = Date.now();
  console.log(`=== Pull production backup (${DATE}) ===\n`);

  // 1. Site JS/HTML/CSS from live
  for (const rel of SITE_FILES) {
    const url = `${LIVE}/${rel}?v=${bust}`;
    const { status, body } = await fetch(url);
    if (status !== 200) {
      console.warn(`SKIP ${rel} HTTP ${status}`);
      continue;
    }
    const dest = path.join(ROOT, rel);
    fs.mkdirSync(path.dirname(dest), { recursive: true });
    fs.writeFileSync(dest, body);
    console.log(`✓ ${rel} (${body.length} bytes)`);
  }

  const site = parseSiteData(fs.readFileSync(path.join(ROOT, 'site/site-data.js')));
  const catalog = parseCatalogData(fs.readFileSync(path.join(ROOT, 'site/catalog-data.js')));
  const imagePaths = collectImagePaths(site, catalog);
  console.log(`\nDownloading ${imagePaths.length} images from production…`);

  let ok = 0;
  let fail = 0;
  for (const rel of imagePaths) {
    const r = await downloadFile(rel, bust);
    if (r.ok) {
      ok++;
      if (ok % 25 === 0) console.log(`  … ${ok}/${imagePaths.length}`);
    } else {
      fail++;
      console.log(`✗ ${rel} HTTP ${r.status}`);
    }
  }
  console.log(`Images: ${ok} OK, ${fail} failed\n`);

  // 2. FTP: theme + htaccess
  console.log('FTP pull theme + htaccess…');
  for (const [remote, local] of FTP_PULL) {
    try {
      const size = ftpDownload(remote, local);
      console.log(`✓ ${local} (${size} bytes)`);
    } catch (e) {
      console.warn(`✗ ${remote}: ${e.message}`);
    }
  }

  // 3. FTP: MU-plugin folder
  console.log('\nFTP pull MU-plugin…');
  const muCount = ftpPullMuPlugin();
  console.log(`✓ ${muCount} MU-plugin files\n`);

  // 4. Dated backup mirror
  console.log(`Creating backup mirror: ${path.basename(BACKUP_DIR)}/`);
  fs.mkdirSync(BACKUP_DIR, { recursive: true });
  for (const rel of SITE_FILES) mirrorToBackup(rel);
  for (const rel of imagePaths) mirrorToBackup(rel);
  for (const [, local] of FTP_PULL) mirrorToBackup(local);
  const muLocal = 'deploy/wp-content/mu-plugins/tuexhibidor-site-manager';
  const walk = (dir, prefix) => {
    for (const name of fs.readdirSync(dir)) {
      const full = path.join(dir, name);
      const rel = `${prefix}/${name}`.replace(/\\/g, '/');
      if (fs.statSync(full).isDirectory()) walk(full, rel);
      else mirrorToBackup(rel);
    }
  };
  if (fs.existsSync(path.join(ROOT, muLocal))) walk(path.join(ROOT, muLocal), muLocal);

  const manifest = {
    date: new Date().toISOString(),
    live: LIVE,
    assetVersion: site.assetVersion,
    products: catalog.products?.length ?? 0,
    imagesDownloaded: ok,
    imagesFailed: fail,
    muPluginFiles: muCount,
  };
  fs.writeFileSync(path.join(BACKUP_DIR, 'manifest.json'), JSON.stringify(manifest, null, 2));
  fs.writeFileSync(
    path.join(BACKUP_DIR, 'README.txt'),
    `Backup producción Tu Exhibidor — ${manifest.date}\n` +
      `assetVersion: ${manifest.assetVersion}\n` +
      `Productos catálogo: ${manifest.products}\n` +
      `Imágenes: ${manifest.imagesDownloaded} OK, ${manifest.imagesFailed} fallidas\n`,
  );

  console.log('Done.');
  console.log(JSON.stringify(manifest, null, 2));
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
