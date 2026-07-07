/** Mapeo catálogo Canva → categorías estilo exhibidoresdejoyas.com / tuexhibidor.cl */
export const DISPLAY_CATEGORIES = [
  'collares',
  'pulseras',
  'anillos',
  'aros',
  'bandejas',
  'dijes',
  'sets-vitrina',
];

export const DISPLAY_LABELS = {
  collares: 'Collares & Cadenas',
  pulseras: 'Pulseras & Relojes',
  anillos: 'Anillos',
  aros: 'Aros & Zarcillos',
  bandejas: 'Bandejas & Bases',
  dijes: 'Dijes & Charms',
  'sets-vitrina': 'Sets Vitrina Modular',
};

export const DISPLAY_INTROS = {
  collares: 'Pecheras, cuellos y bustos para collares y cadenas — la presentación que usan las joyerías más exigentes.',
  pulseras: 'Soportes T-bar, media luna y cilindros para pulseras y relojes de gama alta.',
  anillos: 'Cilindros, bandejas y sets diseñados para destacar anillos y piezas de valor.',
  aros: 'Exhibidores para aros, aretes y zarcillos con acabado impecable.',
  bandejas: 'Bandejas planas, con ranuras y ganchos para vitrinas ordenadas y elegantes.',
  dijes: 'Bandejas para charms y dijes con divisiones precisas a tu medida.',
  'sets-vitrina': 'Sistema modular completo para vitrinas — sets coordinados en ecocuero premium.',
};

/** Imagen representativa por categoría (fallback si no hay site-media) */
export const CATEGORY_PRODUCT_CODES = {
  collares: 'E-35',
  pulseras: 'TUE-PU-029',
  anillos: 'TUE-AN-010',
  aros: 'TUE-AR-044',
  bandejas: 'TUE-BA-001',
  dijes: 'TUE-DI-015',
  'sets-vitrina': 'TUE-STAND-001',
};

export function resolveDisplayCategory(product) {
  const code = (product.code || '').toUpperCase();
  const n = (product.name || product.description || '').toLowerCase();

  if (code.startsWith('TUE-STAND')) return 'sets-vitrina';
  if (code.startsWith('TUE-DI') || code.startsWith('TUE-BC') || /dije|charm|encanto/.test(n)) {
    return 'dijes';
  }
  if (product.categoryKey === 'cadenas') return 'collares';
  if (product.categoryKey === 'pulseras') return 'pulseras';

  if (product.categoryKey === 'anillos') {
    if (/aro|arete|colgante|zarcillo/.test(n)) return 'aros';
    return 'anillos';
  }

  if (product.categoryKey === 'vitrina') {
    if (/set vitrina|stand-/.test(n) || code.startsWith('TUE-STAND')) return 'sets-vitrina';
    if (/aro|arete|colgante|zarcillo|par.*aro/.test(n)) return 'aros';
    if (/dije|charm|encanto/.test(n)) return 'dijes';
    return 'bandejas';
  }

  return 'bandejas';
}
