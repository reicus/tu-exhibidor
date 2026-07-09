/**
 * Replace hero/gallery/catalog/premium/home image refs with brand logo,
 * delete local export copies + server image folders, deploy updated JS/HTML.
 * Run: node scripts/replace_images_with_logo.mjs
 */
import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';
const LOGO = 'public/images/brand/logo-tuexhibidor-ink-96.png';
const LOGO_URL = 'https://tuexhibidor.cl/public/images/brand/logo-tuexhibidor-ink-96.png';
const ASSET_VER = String(Math.floor(Date.now() / 1000));

const SERVER_IMAGE_DIRS = [
  'public/images/hero',
  'public/images/gallery',
  'public/images/catalog',
  'public/images/premium',
  'public/images/home',
];

const LOCAL_EXPORT_DIRS = [
  'export/imagenes-en-uso/categorias',
  'export/imagenes-en-uso/catalogo',
  'export/imagenes-en-uso/galeria',
  'export/imagenes-en-uso/hero',
  'export/imagenes-en-uso/home',
];

const DEPLOY_FILES = [
  'site/site-data.js',
  'site/catalog-data.js',
  'site/index.html',
];

function parseSiteData(content) {
  const m = content.match(/window\.SITE_DATA\s*=\s*(\{.*\})\s*;?\s*$/s);
  if (!m) throw new Error('Could not parse site-data.js');
  return JSON.parse(m[1]);
}

function parseCatalogData(content) {
  const m = content.match(/window\.CATALOG_DATA\s*=\s*(\{.*?\})\s*;/s);
  if (!m) throw new Error('Could not parse catalog-data.js');
  return JSON.parse(m[1]);
}

function logoCategoryAsset(alt) {
  return { base: LOGO, alt };
}

function updateSiteData() {
  const file = path.join(ROOT, 'site/site-data.js');
  const site = parseSiteData(fs.readFileSync(file, 'utf8'));

  site.assetVersion = ASSET_VER;
  site.hero = [logoCategoryAsset('Tu Exhibidor — exhibidores de alta joyería en Chile')];
  site.gallery = [LOGO];

  const labels = site.displayLabels || {};
  for (const key of Object.keys(site.categoryImages || {})) {
    site.categoryImages[key] = logoCategoryAsset(`${labels[key] || key} — Tu Exhibidor`);
  }

  if (site.homeStatic?.medida) {
    site.homeStatic.medida = logoCategoryAsset('Sets Vitrina Modular — Tu Exhibidor');
  }

  fs.writeFileSync(file, `window.SITE_DATA=${JSON.stringify(site)};`);
  console.log('✓ site/site-data.js updated');
}

function updateCatalogData() {
  const file = path.join(ROOT, 'site/catalog-data.js');
  const content = fs.readFileSync(file, 'utf8');
  const catalog = parseCatalogData(content);
  const scoresMatch = content.match(/window\.CATALOG_SCORES\s*=\s*(\{.*?\})\s*;/s);
  const scores = scoresMatch ? JSON.parse(scoresMatch[1]) : {};

  for (const p of catalog.products || []) {
    p.image = LOGO;
    p.imageOk = true;
  }

  fs.writeFileSync(
    file,
    `window.CATALOG_DATA=${JSON.stringify(catalog)};\nwindow.CATALOG_SCORES=${JSON.stringify(scores)};`,
  );
  console.log(`✓ site/catalog-data.js updated (${catalog.products?.length || 0} products)`);
}

function updateIndexHtml() {
  const file = path.join(ROOT, 'site/index.html');
  let html = fs.readFileSync(file, 'utf8');

  html = html.replace(
    /https:\/\/tuexhibidor\.cl\/public\/images\/hero\/hero-slide-01-1200\.jpg/g,
    LOGO_URL,
  );
  html = html.replace(
    /\/public\/images\/hero\/hero-slide-01-800\.jpg(\?v=[^"]*)?/g,
    `/public/images/brand/logo-tuexhibidor-ink-96.png?v=${ASSET_VER}`,
  );
  html = html.replace(/(\?v=)\d+/g, `$1${ASSET_VER}`);

  fs.writeFileSync(file, html);
  console.log('✓ site/index.html updated');
}

function deleteLocalExportDirs() {
  for (const rel of LOCAL_EXPORT_DIRS) {
    const abs = path.join(ROOT, rel);
    if (!fs.existsSync(abs)) {
      console.log(`  skip (missing): ${rel}`);
      continue;
    }
    fs.rmSync(abs, { recursive: true, force: true });
    console.log(`✓ deleted local ${rel}`);
  }
}

function ftpUpload(local, remoteRel) {
  const url = `${FTP_BASE}/${remoteRel.replace(/^\/+/, '')}`;
  execFileSync('curl.exe', ['-sS', '--ftp-create-dirs', '-T', local, '--user', FTP_USER, url], {
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

function ftpList(dir) {
  const url = `${FTP_BASE}/${dir.replace(/^\/+/, '').replace(/\/$/, '')}/`;
  try {
    const out = execFileSync(
      'curl.exe',
      ['-sS', '--user', FTP_USER, '--list-only', url],
      { encoding: 'utf8' },
    );
    return out
      .trim()
      .split(/\r?\n/)
      .map((s) => s.trim())
      .filter((s) => s && s !== '.' && s !== '..');
  } catch {
    return null;
  }
}

function ftpDeleteFile(remoteRel) {
  const rel = remoteRel.replace(/^\/+/, '');
  const remote = `/public_html/${rel}`;
  execFileSync(
    'curl.exe',
    ['-sS', '--user', FTP_USER, '-Q', `DELE ${remote}`, 'ftp://rooster.hostingplus.cl/'],
    { stdio: ['ignore', 'pipe', 'pipe'] },
  );
}

function ftpRemoveDir(remoteRel) {
  const rel = remoteRel.replace(/^\/+/, '').replace(/\/$/, '');
  const remote = `/public_html/${rel}`;
  try {
    execFileSync(
      'curl.exe',
      ['-sS', '--user', FTP_USER, '-Q', `RMD ${remote}`, 'ftp://rooster.hostingplus.cl/'],
      { stdio: ['ignore', 'pipe', 'pipe'] },
    );
  } catch {
    /* may fail if not empty */
  }
}

function localFilesUnder(baseDir) {
  const abs = path.join(ROOT, baseDir);
  if (!fs.existsSync(abs)) return [];
  const out = [];
  const walk = (dir, prefix) => {
    for (const name of fs.readdirSync(dir)) {
      const full = path.join(dir, name);
      const rel = `${prefix}/${name}`.replace(/\\/g, '/');
      if (fs.statSync(full).isDirectory()) walk(full, rel);
      else out.push(rel);
    }
  };
  walk(abs, baseDir.replace(/\\/g, '/'));
  return out;
}

function remoteFilesUnder(baseDir) {
  const out = [];
  const walk = (dir) => {
    const entries = ftpList(dir);
    if (entries === null) return;
    for (const entry of entries) {
      const child = `${dir.replace(/\/$/, '')}/${entry}`;
      const sub = ftpList(child);
      if (sub !== null) walk(child);
      else out.push(child);
    }
  };
  walk(baseDir);
  return out;
}

function deleteServerImageDirs() {
  const allFiles = new Set();
  for (const dir of SERVER_IMAGE_DIRS) {
    const local = localFilesUnder(dir);
    const remote = remoteFilesUnder(dir);
    console.log(`  ${dir}: local=${local.length}, remote=${remote.length}`);
    for (const f of [...local, ...remote]) allFiles.add(f);
  }
  const fileList = [...allFiles];

  let deleted = 0;
  let failed = 0;
  for (const f of fileList) {
    try {
      ftpDeleteFile(f);
      deleted++;
      if (deleted % 50 === 0) console.log(`    deleted ${deleted}/${fileList.length}…`);
    } catch {
      failed++;
    }
  }
  console.log(`✓ server files deleted: ${deleted} OK, ${failed} failed`);

  const dirs = new Set(SERVER_IMAGE_DIRS);
  for (const dir of SERVER_IMAGE_DIRS) collectRemoteDirs(dir, dirs);
  const sortedDirs = [...dirs].sort((a, b) => b.length - a.length);
  for (const dir of sortedDirs) ftpRemoveDir(dir);
  console.log(`✓ attempted RMD on ${sortedDirs.length} server directories`);
}

function collectRemoteDirs(baseDir, acc) {
  const entries = ftpList(baseDir);
  if (entries === null) return;
  for (const entry of entries) {
    const child = `${baseDir.replace(/\/$/, '')}/${entry}`;
    const sub = ftpList(child);
    if (sub !== null) {
      acc.add(child);
      collectRemoteDirs(child, acc);
    }
  }
}

function deployFiles() {
  for (const rel of DEPLOY_FILES) {
    const local = path.join(ROOT, rel);
    ftpUpload(local, rel);
    console.log(`✓ deployed ${rel}`);
  }
}

async function verifyLive() {
  const checks = [
    { name: 'site-data assetVersion', url: `https://tuexhibidor.cl/site/site-data.js?v=${ASSET_VER}` },
    { name: 'logo PNG', url: 'https://tuexhibidor.cl/public/images/brand/logo-tuexhibidor-ink-96.png' },
    { name: 'deleted hero sample', url: 'https://tuexhibidor.cl/public/images/hero/hero-slide-01-800.jpg' },
  ];
  const results = [];
  for (const c of checks) {
    const r = await fetch(c.url, { method: 'HEAD' });
    results.push({ ...c, status: r.status, ok: r.ok });
  }
  return results;
}

console.log(`Logo path: ${LOGO}`);
console.log(`Asset version: ${ASSET_VER}\n`);

console.log('1) Updating JS/HTML references…');
updateSiteData();
updateCatalogData();
updateIndexHtml();

console.log('\n2) Deleting local export folders…');
deleteLocalExportDirs();

console.log('\n3) Deploying updated files…');
deployFiles();

console.log('\n4) Deleting server image folders (keeping brand/)…');
deleteServerImageDirs();

console.log('\n5) Verifying live…');
const verify = await verifyLive();
for (const v of verify) {
  const tag = v.name.includes('deleted') ? (v.status === 404 ? 'OK (404)' : `WARN (${v.status})`) : (v.ok ? 'OK' : `FAIL (${v.status})`);
  console.log(`  ${v.name}: ${tag}`);
}

console.log('\n=== DONE ===');
console.log(`Logo: ${LOGO}`);
console.log(`Deployed: ${DEPLOY_FILES.join(', ')}`);
console.log(`Local export deleted: ${LOCAL_EXPORT_DIRS.join(', ')}`);
console.log(`Server deleted: ${SERVER_IMAGE_DIRS.join(', ')}`);
console.log('Note: /imagenes admin uploads will recreate files in those folders on future uploads.');
