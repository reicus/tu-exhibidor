/** Deploy hero + premium + site-data via FTP curl */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { execSync } from 'child_process';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';

const DIRS = [
  'public/images/hero',
  'public/images/premium',
];

const FILES = [
  'site/site-data.js',
  'site/index.html',
  'site/styles.css',
];

function upload(local, remote) {
  execSync(`curl.exe -sS --ftp-create-dirs -T "${local}" "${FTP_BASE}/${remote}" --user "${FTP_USER}"`, {
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

let ok = 0;
let fail = 0;

for (const f of FILES) {
  const local = path.join(ROOT, f);
  try {
    upload(local, f.replace(/\\/g, '/'));
    ok++;
    console.log(`✓ ${f}`);
  } catch (e) {
    fail++;
    console.error(`✗ ${f}: ${e.message}`);
  }
}

for (const dir of DIRS) {
  const abs = path.join(ROOT, dir);
  if (!fs.existsSync(abs)) continue;
  const files = fs.readdirSync(abs, { recursive: true }).filter((f) => {
    const p = path.join(abs, f);
    return fs.statSync(p).isFile();
  });
  console.log(`\nSubiendo ${dir} (${files.length} archivos)…`);
  for (let i = 0; i < files.length; i++) {
    const rel = path.join(dir, files[i]).replace(/\\/g, '/');
    const local = path.join(ROOT, rel);
    try {
      upload(local, rel);
      ok++;
      if ((i + 1) % 50 === 0 || i === files.length - 1) {
        console.log(`  ${i + 1}/${files.length}`);
      }
    } catch (e) {
      fail++;
      console.error(`  ✗ ${rel}`);
    }
  }
}

console.log(`\nDeploy: ${ok} OK, ${fail} fallos`);
