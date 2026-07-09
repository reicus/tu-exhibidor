/** Static check: WC image sync hooks and loop guard in MU-plugin source. */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const syncFile = path.join(
  ROOT,
  'deploy/wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-woocommerce-image-sync.php'
);
const imagesFile = path.join(
  ROOT,
  'deploy/wp-content/mu-plugins/tuexhibidor-site-manager/includes/class-images.php'
);

const sync = fs.readFileSync(syncFile, 'utf8');
const images = fs.readFileSync(imagesFile, 'utf8');

const checks = [
  { name: 'woocommerce_product_set_image_id hook', ok: /add_action\(\s*'woocommerce_product_set_image_id'/.test(sync) },
  { name: 'set_post_thumbnail hook', ok: /add_action\(\s*'set_post_thumbnail'/.test(sync) },
  { name: 'added_post_meta hook', ok: /add_action\(\s*'added_post_meta'/.test(sync) },
  { name: 'updated_post_meta hook', ok: /add_action\(\s*'updated_post_meta'/.test(sync) },
  { name: 'thumbnail meta key filter', ok: sync.includes("'_thumbnail_id'") },
  { name: 'loop guard is_pushing_to_wc', ok: sync.includes('is_pushing_to_wc()') },
  { name: 'begin_push_to_wc in images', ok: images.includes('begin_push_to_wc') },
  { name: 'bump_cache_version on single sync', ok: /maybe_sync[\s\S]*bump_cache_version/.test(sync) },
  { name: 'save_catalog_jpg target', ok: sync.includes('save_catalog_jpg') },
  { name: 'save_catalog data update', ok: sync.includes('save_catalog') },
];

let fail = 0;
for (const c of checks) {
  const mark = c.ok ? '✓' : '✗';
  console.log(`${mark} ${c.name}`);
  if (!c.ok) fail++;
}

process.exit(fail ? 1 : 0);
