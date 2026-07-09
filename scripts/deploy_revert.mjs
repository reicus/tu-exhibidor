/** Deploy revert bundle: site files, gallery, catalog, styles, theme */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { execSync } from 'child_process';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';

function upload(local, remote) {
  execSync(`curl.exe -sS --ftp-create-dirs -T "${local}" "${FTP_BASE}/${remote}" --user "${FTP_USER}"`, {
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

const SITE_FILES = [
  'site/index.html',
  'site/styles.css',
  'site/site-data.js',
  'site/catalog-data.js',
  'site/app.js',
];

const THEME_FILES = [
  'deploy/wp-content/themes/aurum-child/functions.php',
];

let ok = 0;
let fail = 0;

for (const f of [...SITE_FILES, ...THEME_FILES]) {
  const local = path.join(ROOT, f);
  if (!fs.existsSync(local)) {
    console.error(`Missing: ${f}`);
    fail++;
    continue;
  }
  try {
    upload(local, f.replace(/\\/g, '/'));
    ok++;
    console.log(`✓ ${f}`);
  } catch (e) {
    fail++;
    console.error(`✗ ${f}: ${e.message}`);
  }
}

const galleryDir = path.join(ROOT, 'public/images/gallery');
const galleryFiles = fs.readdirSync(galleryDir).filter((f) => f.endsWith('.jpg'));
console.log(`\nSubiendo galería legacy (${galleryFiles.length} archivos)…`);
for (const f of galleryFiles) {
  try {
    upload(path.join(galleryDir, f), `public/images/gallery/${f}`);
    ok++;
  } catch (e) {
    fail++;
    console.error(`  ✗ gallery/${f}`);
  }
}
console.log(`  Galería: ${galleryFiles.length} archivos`);

console.log(`\nDeploy revert: ${ok} OK, ${fail} fallos`);
