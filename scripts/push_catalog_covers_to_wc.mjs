/**
 * Deploy MU-plugin image sync files and run site→WC featured cover batches.
 * Run: node scripts/push_catalog_covers_to_wc.mjs
 */
import { execFileSync } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const FTP_BASE = 'ftp://rooster.hostingplus.cl/public_html';
const FTP_USER = 'tuexhibi:Tecno2025..';
const TOKEN = 'te-push-20260708-covers';
const LIMIT = 5;

const files = [
  {
    local: path.join(
      ROOT,
      'deploy/wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-images.php'
    ),
    remote: 'wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-images.php',
  },
  {
    local: path.join(
      ROOT,
      'deploy/wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-woocommerce-image-sync.php'
    ),
    remote:
      'wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-woocommerce-image-sync.php',
  },
];

function ftpUpload(local, remoteRel) {
  const url = `${FTP_BASE}/${remoteRel.replace(/^\/+/, '')}`;
  console.log('FTP', remoteRel);
  execFileSync('curl.exe', ['-sS', '--ftp-create-dirs', '-T', local, '--user', FTP_USER, url], {
    stdio: 'inherit',
  });
}

async function runBatch(offset) {
  const url = `https://tuexhibidor.cl/?tuex_push_covers=${encodeURIComponent(TOKEN)}&offset=${offset}&limit=${LIMIT}`;
  const r = await fetch(url, { headers: { Accept: 'application/json' } });
  const text = await r.text();
  let json;
  try {
    json = JSON.parse(text);
  } catch {
    throw new Error(`Bad response offset=${offset}: ${text.slice(0, 400)}`);
  }
  if (!json.success) {
    throw new Error(`Batch failed offset=${offset}: ${JSON.stringify(json)}`);
  }
  return json.data;
}

for (const f of files) ftpUpload(f.local, f.remote);

console.log('Waiting for PHP to pick up files...');
await new Promise((r) => setTimeout(r, 2000));

let offset = 0;
let totalOk = 0;
let totalSkip = 0;
let totalFail = 0;
const allUpdated = [];
const allErrors = [];

while (true) {
  console.log(`Batch offset=${offset}...`);
  const data = await runBatch(offset);
  console.log(data.message, data.updated, data.errors);
  totalOk += data.ok || 0;
  totalSkip += data.skip || 0;
  totalFail += data.fail || 0;
  allUpdated.push(...(data.updated || []));
  allErrors.push(...(data.errors || []));
  offset = data.next;
  if (data.done) break;
  await new Promise((r) => setTimeout(r, 500));
}

console.log(
  JSON.stringify(
    {
      totalOk,
      totalSkip,
      totalFail,
      updatedCount: allUpdated.length,
      updated: allUpdated,
      errors: allErrors,
    },
    null,
    2
  )
);
