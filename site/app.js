const IMG = (p) => `/${p}`;

const RESPONSIVE_WIDTHS = [400, 800, 1200, 1600];

function isResponsiveAsset(asset) {
  return typeof asset === 'object' && asset?.base && asset?.sources;
}

function assetBase(asset) {
  return typeof asset === 'object' ? asset.base : asset;
}

function assetAlt(asset, fallback = 'Tu Exhibidor') {
  return typeof asset === 'object' && asset.alt ? asset.alt : fallback;
}

function mediaPath(base, width, format) {
  return `/${base}-${width}.${format}`;
}

function pictureHtml(asset, { sizes = '100vw', className = '', loading = 'lazy' } = {}) {
  const base = assetBase(asset);
  const alt = assetAlt(asset);
  const srcset = (fmt) => RESPONSIVE_WIDTHS
    .map((w) => `${mediaPath(base, w, fmt)} ${w}w`)
    .join(', ');
  return `<picture>
    <source type="image/avif" srcset="${srcset('avif')}" sizes="${sizes}">
    <source type="image/webp" srcset="${srcset('webp')}" sizes="${sizes}">
    <img src="${mediaPath(base, 800, 'jpg')}" alt="${alt}" loading="${loading}" class="${className}">
  </picture>`;
}

function resolveImgSrc(asset, width = 800) {
  if (isResponsiveAsset(asset)) return mediaPath(assetBase(asset), width, 'jpg');
  return IMG(assetBase(asset));
}

function waUrl(code, name) {
  const clean = name.replace(/\s*\([^)]*\)\s*$/, '');
  return `https://wa.me/56937490214?text=${encodeURIComponent(`Hola, me interesa ${code}: ${clean}`)}`;
}

function cleanName(name) {
  return name.replace(/\s*\([^)]*\)\s*$/, '');
}

function productCard(p, compact = false) {
  const img = IMG(p.image);
  return `
    <article class="card${compact ? ' card-compact' : ''}${p.imageOk === false ? ' card-no-img' : ''}">
      <div class="card-img-wrap">
        <img src="${img}" alt="${cleanName(p.name)}" loading="lazy">
      </div>
      <div class="info">
        <div class="code">${p.code}</div>
        <h4>${cleanName(p.name)}</h4>
      </div>
    </article>`;
}

/** Imagen de slide: siempre JPG, eager, object-fit via CSS */
function carouselSlideHtml(asset, { priority = false, alt = 'Tu Exhibidor' } = {}) {
  const altText = assetAlt(asset, alt);
  const pri = priority ? ' fetchpriority="high"' : '';

  if (isResponsiveAsset(asset)) {
    const base = assetBase(asset);
    const src = mediaPath(base, 800, 'jpg');
    return `<img class="carousel-slide-media" src="${src}" alt="${altText}" loading="eager" decoding="async"${pri} data-base="${base}">`;
  }

  return `<img class="carousel-slide-media" src="${IMG(assetBase(asset))}" alt="${altText}" loading="eager" decoding="async"${pri}>`;
}

function fillSlides(trackId, images, alt = 'Tu Exhibidor') {
  const track = document.getElementById(trackId);
  if (!track) return;
  track.innerHTML = images.map((asset, i) =>
    `<div class="carousel-slide">${carouselSlideHtml(asset, { priority: i === 0, alt })}</div>`,
  ).join('');
}

function bindImageFallbacks(root = document) {
  root.querySelectorAll('img[data-fallback]').forEach((img) => {
    img.addEventListener('error', () => {
      if (img.dataset.fallback && img.src !== img.dataset.fallback) {
        img.src = img.dataset.fallback;
      }
    }, { once: true });
  });
}

/**
 * Carrusel simple y estable.
 * - Full-width: loop con salto instantáneo (sin clones).
 * - Peek: 1 clon al final para loop suave.
 */
function setupCarousel(root) {
  if (!root || root.dataset.carouselReady) return root._carousel;

  const track = root.querySelector('.carousel-track');
  if (!track) return null;

  const peek = root.classList.contains('peek-carousel');
  const autoplay = Number(root.dataset.autoplay) || 0;
  const dotsEl = root.querySelector('.carousel-dots');
  const originals = track.children.length;

  if (originals <= 1) {
    root.dataset.carouselReady = '1';
    root._carousel = { go: () => {}, getIndex: () => 0, count: originals };
    return root._carousel;
  }

  let total = originals;
  let index = 0;
  let timer;

  if (peek) {
    track.appendChild(track.children[0].cloneNode(true));
    total = originals + 1;
  }

  const step = () => {
    if (peek) return track.children[0]?.getBoundingClientRect().width || root.clientWidth;
    return root.clientWidth || 1;
  };

  const realIndex = () => (peek && index >= originals ? 0 : index % originals);

  const paint = (instant = false) => {
    if (instant) track.style.transition = 'none';
    track.style.transform = `translateX(-${index * step()}px)`;
    if (instant) {
      void track.offsetWidth;
      track.style.transition = '';
    }
    const active = realIndex();
    root.querySelectorAll('.carousel-dot').forEach((d, i) => d.classList.toggle('active', i === active));
    root.querySelectorAll('.gallery-thumb').forEach((t, i) => t.classList.toggle('active', i === active));
  };

  const snapPeek = () => {
    if (peek && index >= originals) {
      index = 0;
      paint(true);
    }
  };

  const go = (target) => {
    if (peek) {
      if (target < 0) {
        index = originals - 1;
        paint(false);
        return;
      }
      index = target;
      paint(false);
      if (index >= originals) setTimeout(snapPeek, 460);
      return;
    }

    const n = originals;
    const next = ((target % n) + n) % n;
    const wrapping = (index === n - 1 && next === 0) || (index === 0 && next === n - 1);
    index = next;
    if (wrapping) {
      track.style.transition = 'none';
      paint(true);
      requestAnimationFrame(() => { track.style.transition = ''; });
    } else {
      paint(false);
    }
  };

  const next = () => go(index + 1);
  const prev = () => {
    if (peek && index === 0) {
      track.style.transition = 'none';
      index = originals;
      paint(true);
      requestAnimationFrame(() => {
        index = originals - 1;
        paint(false);
      });
      return;
    }
    go(index - 1);
  };

  if (dotsEl) {
    dotsEl.innerHTML = Array.from({ length: originals }, (_, i) =>
      `<button type="button" class="carousel-dot${i ? '' : ' active'}" aria-label="Slide ${i + 1}"></button>`,
    ).join('');
    dotsEl.querySelectorAll('.carousel-dot').forEach((d, i) => {
      d.addEventListener('click', () => { go(i); resetTimer(); });
    });
  }

  root.querySelector('.prev')?.addEventListener('click', () => { prev(); resetTimer(); });
  root.querySelector('.next')?.addEventListener('click', () => { next(); resetTimer(); });

  track.addEventListener('transitionend', (e) => {
    if (e.target === track && e.propertyName === 'transform') snapPeek();
  });

  let tx = 0;
  track.addEventListener('touchstart', (e) => { tx = e.touches[0].clientX; }, { passive: true });
  track.addEventListener('touchend', (e) => {
    const dx = e.changedTouches[0].clientX - tx;
    if (Math.abs(dx) > 40) (dx < 0 ? next : prev)();
    resetTimer();
  });

  const resetTimer = () => {
    if (!autoplay) return;
    clearInterval(timer);
    timer = setInterval(next, autoplay);
  };

  window.addEventListener('resize', () => paint(true));

  paint(true);
  resetTimer();

  const api = {
    go: (i) => { go(i); resetTimer(); },
    getIndex: realIndex,
    count: originals,
  };

  root.dataset.carouselReady = '1';
  root._carousel = api;
  return api;
}

function renderCategoryCards(site) {
  const wrap = document.getElementById('category-cards');
  if (!wrap || !site.displayCategories) return;

  wrap.innerHTML = site.displayCategories.map((key) => {
    const label = site.displayLabels[key] || key;
    const img = site.categoryImages[key];
    const src = resolveImgSrc(img, 800);
    const alt = assetAlt(img, label);
    return `
      <div class="cat-card" data-cat="${key}" tabindex="0">
        <img class="cat-bg" src="${src}" alt="${alt}" loading="lazy">
        <span>${label}</span>
      </div>`;
  }).join('');
  bindImageFallbacks(wrap);
}

function renderFeatured(products) {
  const track = document.getElementById('featured-track');
  if (!track) return;
  track.innerHTML = products.map((p) =>
    `<div class="carousel-slide featured-slide">${productCard(p, true)}</div>`,
  ).join('');
  bindImageFallbacks(track);
  setupCarousel(track.closest('.carousel'));
}

function renderCatalog(products, site) {
  const labels = site.displayLabels || {};
  const intros = site.displayIntros || {};
  const order = site.displayCategories || [...new Set(products.map((p) => p.displayCategory))];

  const byCat = {};
  products.forEach((p) => {
    const k = p.displayCategory || 'bandejas';
    (byCat[k] ||= []).push(p);
  });

  const tabs = document.getElementById('cat-tabs');
  const panels = document.getElementById('catalog-panels');
  const search = document.getElementById('catalog-search');
  let active = order[0] || 'collares';

  order.forEach((key, i) => {
    if (!byCat[key]?.length) return;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `cat-tab${i === 0 ? ' active' : ''}`;
    btn.dataset.cat = key;
    btn.textContent = labels[key] || key;
    btn.addEventListener('click', () => setActive(key));
    tabs.appendChild(btn);

    const panel = document.createElement('div');
    panel.className = `catalog-panel${i === 0 ? ' active' : ''}`;
    panel.id = `panel-${key}`;
    panel.innerHTML = `
      <p class="panel-intro">${intros[key] || ''}</p>
      <p class="panel-count">${byCat[key].length} modelos · consulta con el código al taller</p>
      <div class="carousel catalog-carousel peek-carousel" data-autoplay="5000">
        <div class="carousel-track catalog-track"></div>
        <button type="button" class="carousel-btn prev" aria-label="Anterior">‹</button>
        <button type="button" class="carousel-btn next" aria-label="Siguiente">›</button>
      </div>
      <div class="catalog-grid hidden"></div>
      <button type="button" class="btn btn-outline toggle-grid">Ver todos (${byCat[key].length})</button>`;
    panels.appendChild(panel);

    const track = panel.querySelector('.catalog-track');
    byCat[key].forEach((p) => {
      track.insertAdjacentHTML('beforeend', `<div class="carousel-slide catalog-slide">${productCard(p)}</div>`);
    });
    panel.querySelector('.catalog-grid').innerHTML = byCat[key].map((p) => productCard(p)).join('');
    setupCarousel(panel.querySelector('.catalog-carousel'));

    panel.querySelector('.toggle-grid').addEventListener('click', () => {
      const expanded = panel.querySelector('.catalog-grid').classList.toggle('hidden') === false;
      panel.querySelector('.catalog-carousel').classList.toggle('hidden', expanded);
      panel.querySelector('.toggle-grid').textContent = expanded ? 'Ver carrusel' : `Ver todos (${byCat[key].length})`;
    });
  });

  bindImageFallbacks(panels);

  function setActive(key) {
    active = key;
    document.getElementById('search-results')?.remove();
    panels.classList.remove('hidden');
    tabs.querySelectorAll('.cat-tab').forEach((t) => t.classList.toggle('active', t.dataset.cat === key));
    panels.querySelectorAll('.catalog-panel').forEach((p) => p.classList.toggle('active', p.id === `panel-${key}`));
    if (search) search.value = '';
  }

  document.querySelectorAll('.cat-card').forEach((card) => {
    const go = () => {
      setActive(card.dataset.cat);
      document.getElementById('catalogo').scrollIntoView({ behavior: 'smooth' });
    };
    card.addEventListener('click', go);
    card.addEventListener('keydown', (e) => { if (e.key === 'Enter') go(); });
  });

  search?.addEventListener('input', () => {
    const q = search.value.trim().toLowerCase();
    if (!q) {
      document.getElementById('search-results')?.remove();
      panels.classList.remove('hidden');
      setActive(active);
      return;
    }

    const hits = products.filter((p) =>
      p.code.toLowerCase().includes(q) || cleanName(p.name).toLowerCase().includes(q),
    );
    tabs.querySelectorAll('.cat-tab').forEach((t) => t.classList.remove('active'));
    panels.querySelectorAll('.catalog-panel').forEach((p) => p.classList.remove('active'));

    let resultEl = document.getElementById('search-results');
    if (!resultEl) {
      resultEl = document.createElement('div');
      resultEl.id = 'search-results';
      resultEl.className = 'search-results';
      panels.after(resultEl);
    }
    resultEl.innerHTML = hits.length
      ? `<h3>${hits.length} resultado${hits.length > 1 ? 's' : ''}</h3><div class="catalog-grid">${hits.map((p) => productCard(p)).join('')}</div>`
      : '<p class="section-lead">Sin resultados. Prueba con el código del producto.</p>';
    bindImageFallbacks(resultEl);
    panels.classList.add('hidden');
  });
}

function renderGallery(images) {
  fillSlides('gallery-track', images);
  const carousel = document.querySelector('.gallery-carousel');
  const api = setupCarousel(carousel);

  const thumbs = document.getElementById('gallery-thumbs');
  thumbs.innerHTML = images.map((asset, i) => `
    <button type="button" class="gallery-thumb${i ? '' : ' active'}" data-idx="${i}">
      <img src="${resolveImgSrc(asset, 400)}" alt="" loading="eager">
    </button>`).join('');

  thumbs.querySelectorAll('.gallery-thumb').forEach((btn) => {
    btn.addEventListener('click', () => api?.go(Number(btn.dataset.idx)));
  });

  const lb = document.getElementById('lightbox');
  const lbImg = document.getElementById('lightbox-img');
  let lbIdx = 0;

  carousel?.addEventListener('click', (e) => {
    const img = e.target.closest('.carousel-slide-media');
    if (!img) return;
    lbIdx = api?.getIndex() ?? 0;
    lbImg.src = resolveImgSrc(images[lbIdx], 1600);
    lb.hidden = false;
    document.body.style.overflow = 'hidden';
  });

  const closeLb = () => { lb.hidden = true; document.body.style.overflow = ''; };
  lb.querySelector('.lightbox-close').addEventListener('click', closeLb);
  lb.addEventListener('click', (e) => { if (e.target === lb) closeLb(); });
  lb.querySelector('.prev').addEventListener('click', () => {
    lbIdx = (lbIdx - 1 + images.length) % images.length;
    lbImg.src = resolveImgSrc(images[lbIdx], 1600);
  });
  lb.querySelector('.next').addEventListener('click', () => {
    lbIdx = (lbIdx + 1) % images.length;
    lbImg.src = resolveImgSrc(images[lbIdx], 1600);
  });
  document.addEventListener('keydown', (e) => {
    if (lb.hidden) return;
    if (e.key === 'Escape') closeLb();
    if (e.key === 'ArrowLeft') lb.querySelector('.prev').click();
    if (e.key === 'ArrowRight') lb.querySelector('.next').click();
  });
}

function initWaWidget() {
  const btn = document.getElementById('wa-float');
  const menu = document.getElementById('wa-menu');
  if (!btn || !menu) return;

  btn.addEventListener('click', (e) => {
    e.stopPropagation();
    const open = menu.hidden;
    menu.hidden = !open;
    btn.setAttribute('aria-expanded', String(open));
  });

  document.addEventListener('click', (e) => {
    const widget = document.getElementById('wa-widget');
    if (widget?.contains(e.target)) return;
    menu.hidden = true;
    btn.setAttribute('aria-expanded', 'false');
  });
}

function initSite() {
  const products = window.CATALOG_DATA?.products || [];
  const site = window.SITE_DATA || {};
  const featured = products
    .filter((p) => (p.score ?? 1) >= 0.78 && p.imageOk !== false)
    .sort((a, b) => (b.score ?? 0) - (a.score ?? 0))
    .slice(0, 12);

  document.getElementById('stat-products').textContent = `${products.length}+`;

  const heroImgs = site.hero?.length ? site.hero : featured.slice(0, 5).map((p) => p.image);
  const aboutImgs = site.gallery?.slice(0, 6) || heroImgs;
  const galleryImgs = site.gallery?.length ? site.gallery : products.slice(0, 16).map((p) => p.image);

  fillSlides('hero-track', heroImgs);
  setupCarousel(document.querySelector('.hero-carousel'));

  fillSlides('about-track', aboutImgs);
  setupCarousel(document.querySelector('.about-carousel'));

  renderCategoryCards(site);
  renderFeatured(featured.length ? featured : products.slice(0, 8));
  renderCatalog(products, site);
  renderGallery(galleryImgs);

  const medidaImg = document.getElementById('medida-img');
  if (medidaImg && site.categoryImages?.['sets-vitrina']) {
    medidaImg.src = resolveImgSrc(site.categoryImages['sets-vitrina'], 1200);
  }

  initWaWidget();
}

document.querySelector('.nav-toggle')?.addEventListener('click', () => {
  document.querySelector('.main-nav')?.classList.toggle('open');
});

document.querySelectorAll('.main-nav a').forEach((a) => {
  a.addEventListener('click', () => document.querySelector('.main-nav')?.classList.remove('open'));
});

if (window.CATALOG_DATA) initSite();
