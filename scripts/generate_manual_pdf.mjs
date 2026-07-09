/**
 * Generate PDF from docs/MANUAL-COMPLETO-TU-EXHIBIDOR.md using system Chrome.
 * Run: node scripts/generate_manual_pdf.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { marked } from 'marked';
import puppeteer from 'puppeteer-core';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const MD = path.join(ROOT, 'docs/MANUAL-COMPLETO-TU-EXHIBIDOR.md');
const PDF = path.join(ROOT, 'docs/MANUAL-COMPLETO-TU-EXHIBIDOR.pdf');

const CHROME_PATHS = [
  'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
  'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
];

function findBrowser() {
  for (const p of CHROME_PATHS) {
    if (fs.existsSync(p)) return p;
  }
  throw new Error('Chrome/Edge not found');
}

const md = fs.readFileSync(MD, 'utf8');
const body = marked.parse(md);

const html = `<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Manual Tu Exhibidor</title>
<style>
  @page { margin: 18mm 15mm; }
  body {
    font-family: "Segoe UI", system-ui, sans-serif;
    font-size: 10.5pt;
    line-height: 1.45;
    color: #1a1a1a;
    max-width: 100%;
  }
  h1 { font-size: 20pt; border-bottom: 2px solid #333; padding-bottom: 6px; margin-top: 24px; page-break-before: always; }
  h1:first-of-type { page-break-before: avoid; }
  h2 { font-size: 14pt; margin-top: 20px; color: #222; }
  h3 { font-size: 12pt; margin-top: 14px; }
  table { border-collapse: collapse; width: 100%; margin: 12px 0; font-size: 9.5pt; }
  th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
  th { background: #f0f0f0; }
  code { background: #f4f4f4; padding: 1px 4px; font-size: 9pt; }
  pre { background: #f4f4f4; padding: 10px; overflow-x: auto; font-size: 8.5pt; border-radius: 4px; }
  pre code { background: none; padding: 0; }
  blockquote { border-left: 3px solid #999; margin: 12px 0; padding-left: 12px; color: #444; }
  hr { border: none; border-top: 1px solid #ddd; margin: 20px 0; }
  a { color: #0645ad; }
</style>
</head>
<body>${body}</body>
</html>`;

const browser = await puppeteer.launch({
  executablePath: findBrowser(),
  headless: true,
  args: ['--no-sandbox', '--disable-setuid-sandbox'],
});

const page = await browser.newPage();
await page.setContent(html, { waitUntil: 'networkidle0' });
await page.pdf({
  path: PDF,
  format: 'A4',
  printBackground: true,
  margin: { top: '18mm', right: '15mm', bottom: '18mm', left: '15mm' },
});
await browser.close();

const size = fs.statSync(PDF).size;
console.log(`PDF generated: ${PDF} (${(size / 1024).toFixed(1)} KB)`);
