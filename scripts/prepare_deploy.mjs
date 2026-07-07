/**
 * Prepara paquete para subir al servidor WordPress
 * Ejecutar: node scripts/prepare_deploy.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const DEPLOY = path.join(ROOT, 'deploy');

function cpDir(src, dest) {
  fs.mkdirSync(dest, { recursive: true });
  for (const e of fs.readdirSync(src, { withFileTypes: true })) {
    const s = path.join(src, e.name);
    const d = path.join(dest, e.name);
    if (e.isDirectory()) cpDir(s, d);
    else fs.copyFileSync(s, d);
  }
}

function main() {
  const wpUploads = path.join(DEPLOY, 'wp-content', 'uploads', 'catalog');
  const wpTheme = path.join(DEPLOY, 'wp-content', 'themes', 'aurum-child');
  const wpMu = path.join(DEPLOY, 'wp-content', 'mu-plugins');
  const importDir = path.join(DEPLOY, 'import');

  fs.mkdirSync(wpUploads, { recursive: true });
  fs.mkdirSync(importDir, { recursive: true });

  const catalogSrc = path.join(ROOT, 'public', 'images', 'catalog');
  for (const f of fs.readdirSync(catalogSrc)) {
    if (f.endsWith('.jpg') && !f.includes('originals')) {
      fs.copyFileSync(path.join(catalogSrc, f), path.join(wpUploads, f));
    }
  }

  const themeSrc = path.join(ROOT, 'backup', 'homedir', 'public_html', 'wp-content', 'themes', 'aurum-child');
  if (fs.existsSync(themeSrc)) cpDir(themeSrc, wpTheme);

  const muSrc = path.join(ROOT, 'backup', 'homedir', 'public_html', 'wp-content', 'mu-plugins');
  if (fs.existsSync(muSrc)) cpDir(muSrc, wpMu);

  for (const f of ['catalogo-completo.csv', 'vitrina-products.csv', 'canva-match-summary.json']) {
    const s = path.join(ROOT, 'import', f);
    if (fs.existsSync(s)) fs.copyFileSync(s, path.join(importDir, f));
  }

  fs.copyFileSync(path.join(ROOT, 'docs', 'SEGURIDAD-Y-ACTUALIZACIONES.md'), path.join(DEPLOY, 'LEER-PRIMERO-SEGURIDAD.md'));

  const readme = `# Deploy Tu Exhibidor

## 1. Imágenes (85 productos)
Subir \`wp-content/uploads/catalog/*.jpg\` → servidor:
\`public_html/wp-content/uploads/catalog/\`

## 2. Importar productos WooCommerce
WP Admin → Productos → Importar → \`import/catalogo-completo.csv\`
- Sin precios (modo cotización WhatsApp ya configurado en tema)

## 3. Tema y seguridad
- \`wp-content/themes/aurum-child/\` → reemplazar en servidor
- \`wp-content/mu-plugins/tuexhibidor-security.php\` → subir

## 4. PHP y plugins
Ver LEER-PRIMERO-SEGURIDAD.md — PHP 8.2 + actualizar WooCommerce primero.

Generado: ${new Date().toISOString()}
`;
  fs.writeFileSync(path.join(DEPLOY, 'README-DEPLOY.md'), readme);

  const imgCount = fs.readdirSync(wpUploads).filter((f) => f.endsWith('.jpg')).length;
  console.log(`Deploy listo: ${DEPLOY}`);
  console.log(`Imágenes: ${imgCount}`);
  console.log(`CSV: import/catalogo-completo.csv`);
}

main();
