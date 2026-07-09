/**
 * Upload one-time PHP script, clean PicsArt metadata from WP DB, delete script.
 * Run: node scripts/clean_picsart_metadata.mjs
 */
import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';
const TOKEN = 'te-clean-picsart-20260709';
const LOCAL_SCRIPT = path.join(ROOT, 'deploy/one-time/tuex-clean-picsart-metadata.php');
const REMOTE_SCRIPT = 'tuex-clean-picsart-metadata.php';
const SITE_BASE = 'https://tuexhibidor.cl';
const BATCH = 50;

function ftpUpload(local, remoteRel) {
  const url = `${FTP_BASE}/${remoteRel.replace(/^\/+/, '')}`;
  execFileSync('curl.exe', ['-sS', '--ftp-create-dirs', '-T', local, '--user', FTP_USER, url], {
    stdio: 'inherit',
  });
}

function ftpDelete(remoteRel) {
  const remote = remoteRel.replace(/^\/+/, '').replace(/^public_html\//, '');
  execFileSync('curl.exe', ['-sS', '--user', FTP_USER, '-Q', `DELE public_html/${remote}`, 'ftp://rooster.hostingplus.cl/'], {
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

async function main() {
  if (!fs.existsSync(LOCAL_SCRIPT)) {
    console.error('Missing', LOCAL_SCRIPT);
    process.exit(1);
  }

  console.log('1) Upload cleanup script...');
  ftpUpload(LOCAL_SCRIPT, REMOTE_SCRIPT);
  await new Promise((r) => setTimeout(r, 1500));

  console.log('\n2) Scan affected posts...');
  const scan = await callScript({ scan: '1' });
  console.log(`   Affected: ${scan.total_affected}`);
  if (scan.total_affected === 0) {
    console.log('   Nothing to clean.');
    ftpDelete(REMOTE_SCRIPT);
    return;
  }

  let offset = 0;
  let totalCleaned = 0;
  console.log('\n3) Cleaning in batches...');
  for (;;) {
    const batch = await callScript({ clean: '1', offset: String(offset), limit: String(BATCH) });
    totalCleaned += batch.updated || 0;
    console.log(`   offset=${offset} updated=${batch.updated} done=${batch.done}`);
    if (batch.done) break;
    offset = batch.next_offset ?? offset + BATCH;
    await new Promise((r) => setTimeout(r, 400));
  }

  console.log('\n4) Verify scan...');
  const verify = await callScript({ scan: '1' });
  console.log(`   Remaining affected: ${verify.total_affected}`);
  console.log(`   Total cleaned: ${totalCleaned}`);

  console.log('\n5) Delete remote script...');
  ftpDelete(REMOTE_SCRIPT);
  console.log('Done.');
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
