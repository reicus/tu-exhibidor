/**
 * Export in-use WP media attachments to export/wp-media-en-uso/ + manifest.csv
 * Run: node scripts/download_wp_media_en_uso.mjs
 */
import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';
const TOKEN = 'te-cleanup-20260708-media';
const LOCAL_SCRIPT = path.join(ROOT, 'deploy/one-time/tuex-cleanup-unused-media.php');
const REMOTE_SCRIPT = 'tuex-cleanup-unused-media.php';
const OUT_ROOT = path.join(ROOT, 'export/wp-media-en-uso');
const MANIFEST_PATH = path.join(OUT_ROOT, 'manifest.csv');
const SITE_BASE = 'https://tuexhibidor.cl';

function ftpUpload(local, remoteRel) {
  const url = `${FTP_BASE}/${remoteRel.replace(/^\/+/, '')}`;
  execFileSync('curl.exe', ['-sS', '--ftp-create-dirs', '-T', local, '--user', FTP_USER, url], {
    stdio: 'inherit',
  });
}

function ftpDelete(remoteRel) {
  const url = `${FTP_BASE}/${remoteRel.replace(/^\/+/, '')}`;
  execFileSync('curl.exe', ['-sS', '--user', FTP_USER, '-Q', `DELE ${remoteRel}`, url], {
    stdio: 'pipe',
  });
}

async function callScript(params = {}) {
  const qs = new URLSearchParams({ token: TOKEN, ...params });
  const url = `${SITE_BASE}/${REMOTE_SCRIPT}?${qs}`;
  const r = await fetch(url, { headers: { Accept: 'application/json' } });
  const text = await r.text();
  let json;
  try {
    json = JSON.parse(text);
  } catch {
    throw new Error(`Bad response (${r.status}): ${text.slice(0, 500)}`);
  }
  if (json.error) throw new Error(json.error);
  return json;
}

async function downloadHttp(url, dest) {
  const r = await fetch(url, { headers: { 'User-Agent': 'TuExhibidor-Export/1.0' } });
  if (!r.ok) return false;
  const buf = Buffer.from(await r.arrayBuffer());
  if (buf.length < 50) return false;
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  fs.writeFileSync(dest, buf);
  return true;
}

function csvEscape(v) {
  const s = String(v ?? '');
  return /[",\n\r]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
}

function destForItem(item) {
  const rel = (item.relative_path || '').replace(/\\/g, '/');
  if (rel) {
    return path.join(OUT_ROOT, rel);
  }
  const name = item.filename || `attachment-${item.attachment_id}.jpg`;
  return path.join(OUT_ROOT, 'misc', `${item.attachment_id}-${name}`);
}

async function main() {
  if (!fs.existsSync(LOCAL_SCRIPT)) {
    console.error('Missing', LOCAL_SCRIPT);
    process.exit(1);
  }

  console.log('1) Uploading manifest script...');
  ftpUpload(LOCAL_SCRIPT, REMOTE_SCRIPT);
  await new Promise((r) => setTimeout(r, 1200));

  console.log('\n2) Fetching in-use manifest from server...');
  const manifest = await callScript({ manifest: '1' });
  const items = manifest.items || [];
  console.log(`   Total WP media: ${manifest.total_media}`);
  console.log(`   In use:         ${manifest.in_use}`);
  console.log(`   Safety-only:    ${manifest.safety_skipped ?? 0}`);

  fs.mkdirSync(OUT_ROOT, { recursive: true });

  let downloaded = 0;
  let skipped = 0;
  let failed = 0;
  let totalBytes = 0;
  const rows = [['attachment_id', 'filename', 'url', 'used_by', 'local_path', 'bytes', 'status']];

  console.log(`\n3) Downloading ${items.length} files...`);
  for (let i = 0; i < items.length; i++) {
    const item = items[i];
    const dest = destForItem(item);
    const url = item.url || `${SITE_BASE}/wp-content/uploads/${item.relative_path}`;

    if (fs.existsSync(dest) && fs.statSync(dest).size > 100) {
      const bytes = fs.statSync(dest).size;
      totalBytes += bytes;
      downloaded++;
      rows.push([
        item.attachment_id,
        item.filename,
        url,
        item.used_by,
        path.relative(ROOT, dest).replace(/\\/g, '/'),
        bytes,
        'cached',
      ]);
      continue;
    }

    const ok = await downloadHttp(url, dest);
    if (ok) {
      const bytes = fs.statSync(dest).size;
      totalBytes += bytes;
      downloaded++;
      rows.push([
        item.attachment_id,
        item.filename,
        url,
        item.used_by,
        path.relative(ROOT, dest).replace(/\\/g, '/'),
        bytes,
        'ok',
      ]);
      if ((i + 1) % 25 === 0) {
        console.log(`   ... ${i + 1}/${items.length}`);
      }
    } else {
      failed++;
      rows.push([
        item.attachment_id,
        item.filename,
        url,
        item.used_by,
        '',
        0,
        'failed',
      ]);
    }
    await new Promise((r) => setTimeout(r, 80));
  }

  const csv = rows.map((r) => r.map(csvEscape).join(',')).join('\n');
  fs.writeFileSync(MANIFEST_PATH, csv, 'utf8');

  console.log('\n4) Removing script from server...');
  try {
    ftpDelete(REMOTE_SCRIPT);
    console.log('   Script removed.');
  } catch {
    console.warn('   Could not delete remote script; remove manually if needed.');
  }

  const mb = (totalBytes / (1024 * 1024)).toFixed(2);
  console.log('\n=== DOWNLOAD COMPLETE ===');
  console.log(`Downloaded: ${downloaded}`);
  console.log(`Failed:     ${failed}`);
  console.log(`Skipped:    ${skipped} (already present)`);
  console.log(`Total size: ${mb} MB`);
  console.log(`Folder:     ${OUT_ROOT}`);
  console.log(`Manifest:   ${MANIFEST_PATH}`);
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
