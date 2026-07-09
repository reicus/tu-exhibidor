/**
 * Upload one-time PHP cleanup script, dry-run, delete orphans in batches, remove script.
 * Run: node scripts/cleanup_unused_media.mjs
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
const BATCH = 40;

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

if (!fs.existsSync(LOCAL_SCRIPT)) {
  console.error('Missing', LOCAL_SCRIPT);
  process.exit(1);
}

console.log('1) Uploading cleanup script...');
ftpUpload(LOCAL_SCRIPT, REMOTE_SCRIPT);
await new Promise((r) => setTimeout(r, 1500));

console.log('\n2) Dry-run (normal)...');
const dry = await callCleanup({ dry_run: '1' });
console.log(dry.message);
console.log(`   Total media: ${dry.total_media}`);
console.log(`   In use:      ${dry.in_use}`);
console.log(`   Orphans:     ${dry.orphans}`);
console.log(`   Would keep:  ${dry.kept}`);
console.log('   Keep reasons:', dry.keep_reasons);

let orphansToDelete = dry.orphans || 0;
if (!orphansToDelete) {
  console.log('\n2b) Dry-run (strict — sin protección 2026/07 ni recientes)...');
  const strictDry = await callCleanup({ dry_run: '1', strict: '1' });
  console.log(strictDry.message);
  console.log(`   Orphans (strict): ${strictDry.orphans}`);
  if (strictDry.orphans > 0) {
    console.log('   Sample strict orphans:', strictDry.sample_orphans?.slice(0, 10));
    orphansToDelete = strictDry.orphans;
    dry._useStrict = true;
  }
}

console.log('   Sample orphans:', dry.sample_orphans?.slice(0, 10));

if (!orphansToDelete) {
  console.log('\nNothing to delete.');
} else {
  const deleteParams = dry._useStrict ? { delete: '1', strict: '1' } : { delete: '1' };
  console.log(`\n3) Deleting ${orphansToDelete} orphans in batches of ${BATCH}...`);
  let offset = 0;
  let totalDeleted = 0;
  let totalFailed = 0;
  while (true) {
    // Always delete from offset 0: orphan list shrinks after each batch.
    const batch = await callCleanup({ ...deleteParams, offset: '0', limit: String(BATCH) });
    console.log(`   batch: deleted=${batch.deleted} failed=${batch.failed} remaining_orphans=${batch.orphans}`);
    totalDeleted += batch.deleted || 0;
    totalFailed += batch.failed || 0;
    if (batch.done || !batch.deleted) break;
    await new Promise((r) => setTimeout(r, 400));
  }
  console.log(`\n   Total deleted: ${totalDeleted}, failed: ${totalFailed}`);
}

console.log('\n4) Final dry-run verify...');
const verify = await callCleanup({ dry_run: '1' });
console.log(verify.message);
console.log(`   Remaining media: ${verify.total_media}, orphans: ${verify.orphans}`);

console.log('\n5) Removing script from server...');
try {
  ftpDelete(REMOTE_SCRIPT);
  console.log('   Script removed.');
} catch (e) {
  console.warn('   Could not delete remote script:', e.message);
  console.warn('   Please delete manually:', `https://tuexhibidor.cl/${REMOTE_SCRIPT}`);
}

console.log('\n=== CLEANUP COMPLETE ===');
