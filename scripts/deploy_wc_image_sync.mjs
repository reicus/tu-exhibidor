/** Deploy WC thumbnail → catalog/home image sync (MU-plugin). */
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

const FILES = [
  'deploy/wp-content/mu-plugins/tuexhibidor-site-manager/bootstrap.php',
  'deploy/wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-woocommerce-image-sync.php',
];

let ok = 0;
let fail = 0;

for (const f of FILES) {
  const local = path.join(ROOT, f);
  if (!fs.existsSync(local)) {
    console.error(`Missing: ${f}`);
    fail++;
    continue;
  }
  const remote = f.replace(/^deploy\//, '').replace(/\\/g, '/');
  try {
    upload(local, remote);
    ok++;
    console.log(`✓ ${remote}`);
  } catch (e) {
    fail++;
    console.error(`✗ ${remote}: ${e.message}`);
  }
}

// Verify hooks are present in the deployed source (local copy = what we uploaded).
const syncFile = path.join(
  ROOT,
  'deploy/wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-woocommerce-image-sync.php'
);
const src = fs.readFileSync(syncFile, 'utf8');
const hooks = [
  'woocommerce_product_set_image_id',
  'set_post_thumbnail',
  'added_post_meta',
  'updated_post_meta',
  'is_pushing_to_wc',
  'bump_cache_version',
];
const missing = hooks.filter((h) => !src.includes(h));
if (missing.length) {
  console.error('Hook check FAILED — missing:', missing.join(', '));
  fail++;
} else {
  console.log('Hook check OK:', hooks.join(', '));
}

console.log(`\nDeploy WC image sync: ${ok} OK, ${fail} fallos`);
process.exit(fail ? 1 : 0);
