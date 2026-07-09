import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const v = String(Math.floor(Date.now() / 1000));

const siteData = path.join(ROOT, 'site/site-data.js');
let sd = fs.readFileSync(siteData, 'utf8');
sd = sd.replace(/"assetVersion":"\d+"/, `"assetVersion":"${v}"`);
fs.writeFileSync(siteData, sd);

const index = path.join(ROOT, 'site/index.html');
let html = fs.readFileSync(index, 'utf8');
html = html.replace(/\?v=\d+/g, `?v=${v}`);
fs.writeFileSync(index, html);

console.log(v);
