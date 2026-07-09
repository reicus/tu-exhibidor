/**
 * Delete server public/images orphans via one-time PHP on server.
 * Keeps files referenced in live site-data.js + catalog-data.js. Skips brand/.
 * Run: node scripts/cleanup_server_image_orphans.mjs [--dry-run]
 */
import fs from 'fs';
import path from 'path';
import { execFileSync } from 'child_process';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';
const TOKEN = 'te-cleanup-20260708-public';
const LOCAL_SCRIPT = path.join(ROOT, 'deploy/one-time/tuex-cleanup-public-images.php');
const REMOTE_SCRIPT = 'tuex-cleanup-public-images.php';
const BATCH = 80;
const dryRun = process.argv.includes('--dry-run');

function ftpUpload(local, remoteRel) {
  const url = `${FTP_BASE}/${remoteRel.replace(/^\/+/, '')}`;
  execFileSync('curl.exe', ['-sS', '--ftp-create-dirs', '-T', local, '--user', FTP_USER, url], {
    stdio: 'inherit',
  });
}

function ftpDelete(remoteRel) {
  const remote = `/public_html/${remoteRel.replace(/^\/+/, '')}`;
  execFileSync('curl.exe', ['-sS', '--user', FTP_USER, '-Q', `DELE ${remote}`, 'ftp://rooster.hostingplus.cl/'], {
    stdio: 'pipe',
  });
}

async function callCleanup(params = {}) {
  const qs = new URLSearchParams({ token: TOKEN, ...params });
  const url = `https://tuexhibidor.cl/${REMOTE_SCRIPT}?${qs}`;
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

function printInventory(json) {
  console.log(json.message);
  console.log(`   Referenced (expanded): ${json.referenced}`);
  console.log(`   Total on server:       ${json.total_remote}`);
  console.log(`   Kept:                  ${json.kept}`);
  console.log(`   Orphans:               ${json.orphans}`);
  if (json.by_folder) {
    console.log('   By folder:');
    for (const [folder, c] of Object.entries(json.by_folder)) {
      console.log(`     ${folder}: ${c.remote} remote, ${c.keep} kept, ${c.orphans} orphans`);
    }
  }
  if (json.sample_orphans?.length) {
    console.log('   Sample orphans:', json.sample_orphans.slice(0, 10).join('\n     '));
  }
}

if (!fs.existsSync(LOCAL_SCRIPT)) {
  console.error('Missing', LOCAL_SCRIPT);
  process.exit(1);
}

console.log(dryRun ? 'DRY RUN — no deletions\n' : 'Cleaning server image orphans…\n');

console.log('1) Uploading cleanup script…');
ftpUpload(LOCAL_SCRIPT, REMOTE_SCRIPT);
await new Promise((r) => setTimeout(r, 1500));

console.log('\n2) Dry-run scan…');
const dry = await callCleanup({ dry_run: '1' });
printInventory(dry);

if (dryRun || !dry.orphans) {
  if (dryRun && dry.orphans) console.log(`\nWould delete ${dry.orphans} orphan files`);
  console.log('\n3) Removing script from server…');
  try {
    ftpDelete(REMOTE_SCRIPT);
    console.log('   Script removed.');
  } catch (e) {
    console.warn('   Could not delete remote script:', e.message);
  }
  console.log('\n=== DONE ===');
  process.exit(0);
}

console.log(`\n3) Deleting ${dry.orphans} orphans in batches of ${BATCH}…`);
let totalDeleted = 0;
let totalFailed = 0;
while (true) {
  const batch = await callCleanup({ delete: '1', offset: '0', limit: String(BATCH) });
  console.log(`   batch: deleted=${batch.deleted} failed=${batch.failed} remaining_orphans=${batch.orphans}`);
  totalDeleted += batch.deleted || 0;
  totalFailed += batch.failed || 0;
  if (batch.done || !batch.deleted) break;
  await new Promise((r) => setTimeout(r, 300));
}

console.log(`\n   Total deleted: ${totalDeleted}, failed: ${totalFailed}`);

console.log('\n4) Final verify…');
const verify = await callCleanup({ dry_run: '1' });
printInventory(verify);

console.log('\n5) Removing script from server…');
try {
  ftpDelete(REMOTE_SCRIPT);
  console.log('   Script removed.');
} catch (e) {
  console.warn('   Could not delete remote script:', e.message);
}

console.log('\n=== CLEANUP COMPLETE ===');
console.log(`Kept: ${verify.kept}, Deleted: ${totalDeleted}, Remaining orphans: ${verify.orphans}`);
