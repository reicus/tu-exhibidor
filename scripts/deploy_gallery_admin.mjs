/** Deploy «Exhibidores en acción» gallery admin fixes */
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
  'deploy/wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-data.php',
  'deploy/wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-images.php',
  'deploy/wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-admin.php',
  'deploy/wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-router.php',
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

console.log(`\nDeploy gallery admin: ${ok} OK, ${fail} fallos`);
