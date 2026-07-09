/**
 * Deploy logo/header fix + PicsArt caption filter in theme; ensure brand logos on server.
 * Run: node scripts/deploy_logo_picsart_fix.mjs
 */
import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';

const FILES = [
  ['deploy/wp-content/themes/aurum-child/functions.php', 'wp-content/themes/aurum-child/functions.php'],
  ['export/imagenes-en-uso/marca/logo-tuexhibidor-ink-96.png', 'public/images/brand/logo-tuexhibidor-ink-96.png'],
  ['export/imagenes-en-uso/marca/logo-tuexhibidor-ink-96.webp', 'public/images/brand/logo-tuexhibidor-ink-96.webp'],
  ['export/imagenes-en-uso/marca/logo-tuexhibidor-gold-96.webp', 'public/images/brand/logo-tuexhibidor-gold-96.webp'],
];

function upload(local, remote) {
  const url = `${FTP_BASE}/${remote.replace(/^\/+/, '')}`;
  execFileSync('curl.exe', ['-sS', '--ftp-create-dirs', '-T', local, '--user', FTP_USER, url], {
    stdio: 'inherit',
  });
}

let ok = 0;
for (const [relLocal, remote] of FILES) {
  const local = path.join(ROOT, relLocal);
  if (!fs.existsSync(local)) {
    console.warn('SKIP missing', relLocal);
    continue;
  }
  console.log('UP', remote);
  upload(local, remote);
  ok++;
}
console.log(`Uploaded ${ok} file(s).`);
