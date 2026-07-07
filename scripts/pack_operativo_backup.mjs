/**
 * Empaqueta solo el sitio funcional (preview operativo).
 * Run: node scripts/pack_operativo_backup.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'backup-sitio-operativo');

const COPY = [
  { from: 'site', to: 'site' },
  { from: 'public/images/catalog', to: 'public/images/catalog' },
  { from: 'public/images/premium', to: 'public/images/premium' },
  { from: 'public/images/hero', to: 'public/images/hero' },
  { from: 'public/images/brand', to: 'public/images/brand' },
];

function rmDir(dir) {
  if (fs.existsSync(dir)) fs.rmSync(dir, { recursive: true, force: true });
}

function cpDir(src, dest) {
  fs.mkdirSync(dest, { recursive: true });
  for (const e of fs.readdirSync(src, { withFileTypes: true })) {
    const s = path.join(src, e.name);
    const d = path.join(dest, e.name);
    if (e.isDirectory()) cpDir(s, d);
    else fs.copyFileSync(s, d);
  }
}

function countFiles(dir) {
  let n = 0;
  let bytes = 0;
  const walk = (d) => {
    for (const e of fs.readdirSync(d, { withFileTypes: true })) {
      const p = path.join(d, e.name);
      if (e.isDirectory()) walk(p);
      else { n++; bytes += fs.statSync(p).size; }
    }
  };
  if (fs.existsSync(dir)) walk(dir);
  return { n, mb: (bytes / 1024 / 1024).toFixed(1) };
}

const readme = `# Tu Exhibidor — Sitio operativo (backup limpio)

Solo lo necesario para ver y publicar el sitio premium. Sin scripts de build, sin PhotosDrive, sin WordPress backup, sin herramientas extra.

## Contenido

| Carpeta | Qué es |
|---------|--------|
| \`site/\` | HTML, CSS, JS y datos del catálogo (85 productos) |
| \`public/images/catalog/\` | Fotos producto catálogo |
| \`public/images/premium/\` | Galería curada (JPG/WebP/AVIF) |
| \`public/images/hero/\` | 7 slides del hero |
| \`public/images/brand/\` | Logo y favicon |

## Ver en local

\`\`\`powershell
cd "ruta-a-esta-carpeta"
npx serve . -l 3000
\`\`\`

Abrir: **http://localhost:3000/site/?v=29**

## Publicar en servidor

Subir **toda esta carpeta** manteniendo la estructura:
- \`site/\` → accesible como /site/
- \`public/\` → al mismo nivel que site/ (las rutas usan \`../public/images/...\`)

O poner \`site/index.html\` como homepage y ajustar rutas si va en la raíz del dominio.

## Versiones actuales

- styles.css v29
- app.js v24
- catalog-data.js / site-data.js v22

## No incluido (a propósito)

- \`scripts/\`, \`import/\`, \`deploy/\`, \`backup/\`, \`PhotosDrive/\`
- Herramienta ImgSEO, handoffs, fuentes PSD

Generado: ${new Date().toISOString()}
`;

const pkg = {
  name: 'tu-exhibidor-sitio-operativo',
  private: true,
  scripts: {
    preview: 'npx serve . -l 3000',
  },
};

function main() {
  console.log('Empaquetando sitio operativo…');
  rmDir(OUT);
  fs.mkdirSync(OUT, { recursive: true });

  for (const { from, to } of COPY) {
    const src = path.join(ROOT, from);
    const dest = path.join(OUT, to);
    if (!fs.existsSync(src)) {
      console.warn('Skip (no existe):', from);
      continue;
    }
    console.log('  +', to);
    cpDir(src, dest);
  }

  fs.writeFileSync(path.join(OUT, 'README.md'), readme);
  fs.writeFileSync(path.join(OUT, 'package.json'), JSON.stringify(pkg, null, 2));

  const stats = countFiles(OUT);
  console.log(`\nListo: ${OUT}`);
  console.log(`Archivos: ${stats.n} · ~${stats.mb} MB`);

  const zipName = `Tu-Exhibidor-SITIO-OPERATIVO-${new Date().toISOString().slice(0, 10)}.zip`;
  const zipPath = path.join(ROOT, zipName);
  if (fs.existsSync(zipPath)) fs.unlinkSync(zipPath);

  const ps = spawnSync('powershell', [
    '-NoProfile', '-Command',
    `Compress-Archive -Path '${OUT}\\*' -DestinationPath '${zipPath}' -Force`,
  ], { stdio: 'inherit' });

  if (ps.status === 0) {
    const zipMb = (fs.statSync(zipPath).size / 1024 / 1024).toFixed(1);
    console.log(`ZIP: ${zipPath} (~${zipMb} MB)`);
  } else {
    console.warn('No se pudo crear ZIP (carpeta backup-sitio-operativo/ sigue disponible)');
  }
}

main();
