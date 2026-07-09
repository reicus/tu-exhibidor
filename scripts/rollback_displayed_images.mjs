/**
 * Rollback production to real displayed images (pre logo-placeholder).
 * Restores site-data.js / catalog-data.js from git HEAD, uploads only referenced files.
 * Run: node scripts/rollback_displayed_images.mjs [--dry-run]
 */
import fs from 'fs';
import path from 'path';
import { execFileSync, execSync } from 'child_process';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';
const dryRun = process.argv.includes('--dry-run');
const ASSET_VER = String(Math.floor(Date.now() / 1000));

function gitShow(rel) {
  return execSync(`git show HEAD:${rel.replace(/\\/g, '/')}`, { cwd: ROOT, encoding: 'utf8' });
}

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

function collectPathsFromValue(val, out) {
  if (!val) return;
  if (typeof val === 'string' && val.startsWith('public/images/') && !val.includes('brand/')) {
    out.add(val);
    return;
  }
  if (typeof val === 'object') {
    if (val.base && typeof val.base === 'string' && val.base.startsWith('public/images/')) {
      out.add(val.base);
    }
    if (val.sources) {
      for (const size of Object.values(val.sources)) {
        for (const p of Object.values(size || {})) {
          if (typeof p === 'string' && p.startsWith('public/images/')) out.add(p);
        }
      }
    }
    for (const v of Object.values(val)) collectPathsFromValue(v, out);
  }
}

function expandAssetPaths(paths) {
  const expanded = new Set();
  for (const p of paths) {
    if (/\.(jpg|jpeg|webp|avif|png)$/i.test(p)) {
      expanded.add(p);
      continue;
    }
    // Base path without extension → responsive variants (hero-slide-01 → hero-slide-01-800.jpg)
    for (const w of [400, 800, 1200, 1600]) {
      for (const ext of ['jpg', 'webp', 'avif']) {
        expanded.add(`${p}-${w}.${ext}`);
      }
    }
  }
  return expanded;
}

function ftpUpload(local, remoteRel) {
  const url = `${FTP_BASE}/${remoteRel.replace(/^\/+/, '')}`;
  execFileSync('curl.exe', ['-sS', '--ftp-create-dirs', '-T', local, '--user', FTP_USER, url], {
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

function restoreJsonFiles() {
  const siteContent = gitShow('site/site-data.js');
  const catalogContent = gitShow('site/catalog-data.js');
  const indexContent = gitShow('site/index.html');

  const site = parseSiteData(siteContent);
  const catalog = parseCatalogData(catalogContent);

  // Preserve inStock from current working catalog if present
  const currentCatalogPath = path.join(ROOT, 'site/catalog-data.js');
  if (fs.existsSync(currentCatalogPath)) {
    try {
      const current = parseCatalogData(fs.readFileSync(currentCatalogPath, 'utf8'));
      const stockByCode = Object.fromEntries(
        (current.products || []).filter((p) => 'inStock' in p).map((p) => [p.code, p.inStock]),
      );
      for (const p of catalog.products || []) {
        if (p.code in stockByCode) p.inStock = stockByCode[p.code];
      }
    } catch {
      /* ignore */
    }
  }

  // homeStatic medida section (added after HEAD commit, images exist locally)
  site.homeStatic = {
    medida: {
      base: 'public/images/home/medida',
      alt: site.categoryImages?.['sets-vitrina']?.alt || 'Set vitrina modular Tu Exhibidor',
      sources: {
        400: { jpg: 'public/images/home/medida-400.jpg' },
        800: { jpg: 'public/images/home/medida-800.jpg' },
        1200: { jpg: 'public/images/home/medida-1200.jpg' },
        1600: { jpg: 'public/images/home/medida-1600.jpg' },
      },
    },
  };
  site.assetVersion = ASSET_VER;

  const scoresMatch = catalogContent.match(/window\.CATALOG_SCORES\s*=\s*(\{.*?\})\s*;/s);
  const scores = scoresMatch ? JSON.parse(scoresMatch[1]) : {};

  let indexHtml = indexContent;
  indexHtml = indexHtml.replace(/(\?v=)\d+/g, `$1${ASSET_VER}`);

  if (!dryRun) {
    fs.writeFileSync(path.join(ROOT, 'site/site-data.js'), `window.SITE_DATA=${JSON.stringify(site)};`);
    fs.writeFileSync(
      path.join(ROOT, 'site/catalog-data.js'),
      `window.CATALOG_DATA=${JSON.stringify(catalog)};\nwindow.CATALOG_SCORES=${JSON.stringify(scores)};`,
    );
    fs.writeFileSync(path.join(ROOT, 'site/index.html'), indexHtml);
  }

  return { site, catalog, indexHtml };
}

function main() {
  console.log(dryRun ? 'DRY RUN — no uploads or file writes\n' : 'Rolling back displayed images…\n');

  const { site, catalog } = restoreJsonFiles();
  console.log('✓ Restored site-data.js + catalog-data.js + index.html from git HEAD');
  console.log(`  assetVersion: ${ASSET_VER}`);
  console.log(`  products: ${catalog.products?.length || 0}`);

  const rawPaths = new Set();
  collectPathsFromValue(site, rawPaths);
  for (const p of catalog.products || []) collectPathsFromValue(p.image, rawPaths);

  const allPaths = expandAssetPaths(rawPaths);
  const byFolder = {};
  const toUpload = [];
  const missing = [];

  for (const rel of [...allPaths].sort()) {
    const local = path.join(ROOT, rel.replace(/\//g, path.sep));
    const folder = rel.split('/').slice(0, 3).join('/');
    byFolder[folder] = byFolder[folder] || { needed: 0, found: 0, uploaded: 0 };
    byFolder[folder].needed++;

    if (fs.existsSync(local)) {
      byFolder[folder].found++;
      toUpload.push({ local, remote: rel });
    } else {
      missing.push(rel);
    }
  }

  console.log('\nImage inventory (referenced in JSON):');
  for (const [folder, c] of Object.entries(byFolder).sort()) {
    console.log(`  ${folder}: ${c.found}/${c.needed} available locally`);
  }

  if (missing.length) {
    console.log(`\nMissing locally (${missing.length}):`);
    for (const m of missing.slice(0, 20)) console.log(`  ${m}`);
    if (missing.length > 20) console.log(`  … +${missing.length - 20} more`);
  }

  if (dryRun) {
    console.log(`\nWould upload ${toUpload.length} files`);
    return;
  }

  let ok = 0;
  let fail = 0;
  console.log(`\nUploading ${toUpload.length} image files…`);
  for (const { local, remote } of toUpload) {
    try {
      ftpUpload(local, remote);
      ok++;
      const folder = remote.split('/').slice(0, 3).join('/');
      byFolder[folder].uploaded = (byFolder[folder].uploaded || 0) + 1;
    } catch (e) {
      fail++;
      console.error(`  ✗ ${remote}: ${e.message}`);
    }
  }

  const siteFiles = ['site/site-data.js', 'site/catalog-data.js', 'site/index.html'];
  for (const f of siteFiles) {
    try {
      ftpUpload(path.join(ROOT, f), f);
      ok++;
      console.log(`✓ deployed ${f}`);
    } catch (e) {
      fail++;
      console.error(`✗ ${f}: ${e.message}`);
    }
  }

  console.log('\n--- Summary ---');
  for (const [folder, c] of Object.entries(byFolder).sort()) {
    console.log(`  ${folder}: uploaded ${c.uploaded || 0} (of ${c.found} local / ${c.needed} referenced)`);
  }
  console.log(`\nTotal: ${ok} OK, ${fail} failed, ${missing.length} missing locally`);
  console.log(`\nVerify: https://tuexhibidor.cl/site/`);
  console.log(`  Hero: https://tuexhibidor.cl/public/images/hero/hero-slide-01-800.jpg`);
  console.log(`  Catalog: https://tuexhibidor.cl/public/images/catalog/tue-ba-001-bandeja-para-cadenas-o-pulseras-con-canales-y-gancho.jpg`);
}

main();
