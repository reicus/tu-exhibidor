/** Delete server image folders via FTP (correct /public_html/ DELE paths) */
import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FTP_USER = 'tuexhibi:Tecno2025..';
const FTP_HOST = 'ftp://rooster.hostingplus.cl/';
const DIRS = [
  'public/images/hero',
  'public/images/gallery',
  'public/images/catalog',
  'public/images/premium',
  'public/images/home',
];

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

function ftpDelete(rel) {
  const remote = `/public_html/${rel.replace(/^\/+/, '')}`;
  execFileSync('curl.exe', ['-sS', '--user', FTP_USER, '-Q', `DELE ${remote}`, FTP_HOST], {
    stdio: ['ignore', 'pipe', 'pipe'],
  });
}

function ftpRmd(rel) {
  const remote = `/public_html/${rel.replace(/^\/+/, '').replace(/\/$/, '')}`;
  try {
    execFileSync('curl.exe', ['-sS', '--user', FTP_USER, '-Q', `RMD ${remote}`, FTP_HOST], {
      stdio: ['ignore', 'pipe', 'pipe'],
    });
  } catch { /* ignore */ }
}

const files = [...new Set(DIRS.flatMap(localFilesUnder))];
console.log(`Deleting ${files.length} files…`);
let ok = 0;
let fail = 0;
for (let i = 0; i < files.length; i++) {
  try {
    ftpDelete(files[i]);
    ok++;
    if ((i + 1) % 100 === 0 || i === files.length - 1) console.log(`  ${i + 1}/${files.length} (${ok} ok, ${fail} fail)`);
  } catch {
    fail++;
  }
}
console.log(`Files: ${ok} deleted, ${fail} failed`);

const subdirs = [];
for (const dir of DIRS) {
  const abs = path.join(ROOT, dir);
  if (!fs.existsSync(abs)) continue;
  const walk = (d, prefix) => {
    for (const name of fs.readdirSync(d)) {
      const full = path.join(d, name);
      if (fs.statSync(full).isDirectory()) {
        const rel = `${prefix}/${name}`.replace(/\\/g, '/');
        subdirs.push(rel);
        walk(full, rel);
      }
    }
  };
  walk(abs, dir.replace(/\\/g, '/'));
}
const allDirs = [...new Set([...subdirs, ...DIRS])].sort((a, b) => b.length - a.length);
for (const d of allDirs) ftpRmd(d);
console.log(`RMD attempted on ${allDirs.length} directories`);

async function verify() {
  const sample = 'https://tuexhibidor.cl/public/images/hero/hero-slide-01-800.jpg';
  const logo = 'https://tuexhibidor.cl/public/images/brand/logo-tuexhibidor-ink-96.png';
  const hero = await fetch(sample, { method: 'HEAD' });
  const brand = await fetch(logo, { method: 'HEAD' });
  console.log(`Verify hero sample: ${hero.status} (expect 404)`);
  console.log(`Verify logo: ${brand.status} (expect 200)`);
  const data = await fetch(`https://tuexhibidor.cl/site/site-data.js?v=${Date.now()}`);
  const text = await data.text();
  console.log(`site-data has logo ref: ${text.includes('logo-tuexhibidor-ink-96.png')}`);
}
await verify();
