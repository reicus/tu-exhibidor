<?php
/**
 * Aurum WordPress Theme
 *
 * Laborator.co
 * www.laborator.co
 */

function aurum_enqueue_child_theme_scripts() {
	wp_enqueue_style( 'aurum-child', get_stylesheet_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'aurum_enqueue_child_theme_scripts', 100 );

// ==== Tu Exhibidor: modo landing sin precios + WhatsApp ====
add_filter('woocommerce_get_price_html', function($price, $product){ return ''; }, 100, 2);
remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
add_action('woocommerce_single_product_summary', 'tuexhibidor_whatsapp_single_buttons', 30);

function tuexhibidor_whatsapp_message($product){
	$texto = 'Hola, me interesa el producto: ' . $product->get_name() . ' ' . get_permalink($product->get_id());
	return rawurlencode($texto);
}

function tuexhibidor_whatsapp_single_buttons(){
	global $product;
	$msg = tuexhibidor_whatsapp_message($product);
	echo '<div class="tuexhibidor-whatsapp-wrap tuexhibidor-whatsapp-single">';
	echo '<a class="button alt tuexhibidor-wa-btn" target="_blank" rel="nofollow" href="https://wa.me/56937490214?text=' . $msg . '">Consultar por WhatsApp - Alfonso</a>';
	echo '<a class="button alt tuexhibidor-wa-btn" target="_blank" rel="nofollow" href="https://wa.me/56991327813?text=' . $msg . '">Consultar por WhatsApp - Leder</a>';
	echo '</div>';
}

// Boton flotante de WhatsApp en todas las paginas
add_action('wp_footer', 'tuexhibidor_floating_whatsapp');
function tuexhibidor_floating_whatsapp(){
	echo '<div class="tuexhibidor-float-wa">
	<button type="button" class="tuexhibidor-float-toggle" aria-label="WhatsApp">
	<svg viewBox="0 0 32 32" width="28" height="28" fill="#fff"><path d="M16 3C9.4 3 4 8.4 4 15c0 2.3.6 4.4 1.7 6.3L4 29l7.9-1.6c1.8 1 3.9 1.6 6.1 1.6 6.6 0 12-5.4 12-12S22.6 3 16 3zm0 21.8c-2 0-3.9-.5-5.5-1.5l-.4-.2-4.7 1 1-4.6-.3-.4C4.9 17.9 4.3 16 4.3 14 4.3 8.6 9.6 4 16 4s12 4.6 12 11-5.4 11-12 11z"/></svg>
	</button>
	<div class="tuexhibidor-float-menu">
	<a href="https://wa.me/56937490214?text=Hola%2C%20quiero%20cotizar%20exhibidores" target="_blank" rel="nofollow">Alfonso Orozco</a>
	<a href="https://wa.me/56991327813?text=Hola%2C%20quiero%20cotizar%20exhibidores" target="_blank" rel="nofollow">Leder Mejia</a>
	</div>
	</div>';
}

// ==== Landing de una sola pagina: secciones y menu con anclas ====
add_action('wp_footer', 'tuexhibidor_landing_sections');
function tuexhibidor_landing_sections(){
	if(!is_front_page()) return;
	echo '<section id="somos" class="tuexhibidor-section tuexhibidor-somos">
	<div class="tuexhibidor-section-inner">
	<h2>Quienes Somos</h2>
	<p>Desde el ano 2000 disenamos y fabricamos exhibidores para joyeria y bisuteria, combinando materiales de calidad, terminaciones cuidadas y un concepto moderno de presentacion. Trabajamos junto a joyerias de toda Latinoamerica, cubriendo cada necesidad con tiempos de entrega reducidos.</p>
	<div class="tuexhibidor-valores">
	<span>Calidad</span><span>Innovacion</span><span>Compromiso</span><span>Experiencia</span>
	</div>
	<a class="tuexhibidor-catalogo-btn" href="https://tuexhibidor.cl/wp-content/uploads/2026/07/catalogo_tuexhibidor.pdf" target="_blank" rel="noopener">Descargar catálogo completo (85+ modelos)</a>
	</div>
	</section>
	<section id="contacto" class="tuexhibidor-section tuexhibidor-contacto">
	<div class="tuexhibidor-section-inner">
	<h2>Contactanos</h2>
	<p>Escribenos por WhatsApp y recibe tu cotización el mismo día, directo con el equipo que fabrica tus exhibidores.</p>
	<div class="tuexhibidor-whatsapp-wrap tuexhibidor-contacto-botones">
	<a class="button alt tuexhibidor-wa-btn" target="_blank" rel="nofollow" href="https://wa.me/56937490214?text=Hola%2C%20quiero%20cotizar%20exhibidores">Alfonso Orozco</a>
	<a class="button alt tuexhibidor-wa-btn" target="_blank" rel="nofollow" href="https://wa.me/56991327813?text=Hola%2C%20quiero%20cotizar%20exhibidores">Leder Mejia</a>
	</div>
	<div class="tuexhibidor-contacto-divider"></div>
	<p class="tuexhibidor-ig-label">Siguenos en Instagram</p>
	<a class="tuexhibidor-ig-btn" href="https://www.instagram.com/tuexhibidor/" target="_blank" rel="noopener">@tuexhibidor</a>
	</div>
	</section>';
	echo '<script>
	document.addEventListener("DOMContentLoaded", function(){
		var rows = document.querySelectorAll(".vc_row");
		if(rows[0]){ rows[0].id = "catalogo"; rows[0].classList.add("tuexhibidor-anchor"); }
		var somos = document.getElementById("somos");
		var instagram = document.getElementById("instagram");
		var contacto = document.getElementById("contacto");
		var footer = document.querySelector(".site-footer");
		if(footer && somos && contacto){ footer.parentNode.insertBefore(somos, footer); if(instagram){ footer.parentNode.insertBefore(instagram, footer); } footer.parentNode.insertBefore(contacto, footer); }
		var map = { "CADENAS Y COLLARES":"#catalogo", "ARETES Y ANILLOS":"#catalogo", "PULSERAS Y RELOJES":"#catalogo", "VITRINA":"#catalogo", "QUIENES SOMOS":"#somos" };
		document.querySelectorAll(".main-menu a").forEach(function(a){
			var t = a.textContent.trim();
			if(map[t]){ a.setAttribute("href", map[t]); }
		});
	});
	</script>';
}

add_action('wp_footer', 'tuexhibidor_menu_anchors_everywhere');
function tuexhibidor_menu_anchors_everywhere(){
	if(is_front_page()) return;
	echo '<script>
	document.addEventListener("DOMContentLoaded", function(){
		var map = { "CADENAS Y COLLARES":"/#catalogo", "ARETES Y ANILLOS":"/#catalogo", "PULSERAS Y RELOJES":"/#catalogo", "VITRINA":"/#catalogo", "QUIENES SOMOS":"/#somos" };
		document.querySelectorAll(".main-menu a").forEach(function(a){
			var t = a.textContent.trim();
			if(map[t]){ a.setAttribute("href", map[t]); }
		});
	});
	</script>';
}

add_action('wp_head', 'tuexhibidor_fonts');
function tuexhibidor_fonts(){
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
	echo '<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">';
}

add_action('wp_head', 'tuexhibidor_landing_css');
function tuexhibidor_landing_css(){
	echo '<style>
	:root{ --gold:#b8935f; --gold-dark:#96723f; --ink:#2b2926; --cream:#faf7f2; }
	html{ scroll-behavior:smooth; }
	body{ font-family:"Poppins",sans-serif; color:var(--ink); background:var(--cream); }
	h1,h2,h3,h4,h5,.site-title,.site-title a{ font-family:"Playfair Display",serif !important; letter-spacing:.3px; }
	.price,.woocommerce-Price-amount,.woocommerce-Price-currencySymbol{ display:none !important; }
	.cart-counter,.cart-icon,.lab-mini-cart,.cart-info,a.cart-counter,.widget_shopping_cart,[class*="mini-cart"]{ display:none !important; }
	a[href*="/cart/"], a[href*="/checkout/"]{ display:none !important; }
	.site-header{ background:#fff !important; box-shadow:0 2px 16px rgba(0,0,0,.06) !important; border:none !important; }
	.main-menu > ul > li > a{ font-weight:500; letter-spacing:1.5px; text-transform:uppercase; font-size:12.5px; color:var(--ink) !important; transition:color .25s ease; cursor:pointer; }
	.main-menu > ul > li > a:hover{ color:var(--gold) !important; }
	.vc_row{ border-radius:18px !important; overflow:hidden; scroll-margin-top:110px; }
	li.shop-item, li.product{ border-radius:16px !important; overflow:hidden; background:#fff; box-shadow:0 4px 18px rgba(43,41,38,.07) !important; border:none !important; transition:transform .35s ease, box-shadow .35s ease !important; }
	li.shop-item:hover, li.product:hover{ transform:translateY(-8px); box-shadow:0 18px 34px rgba(43,41,38,.14) !important; }
	li.shop-item img, li.product img{ border-radius:16px 16px 0 0 !important; }
	li.shop-item h3, li.product h3, .woocommerce-loop-product__title{ font-size:15px !important; padding:0 16px; margin-top:14px !important; margin-bottom:16px !important; }
	li.shop-item .product_cat, li.product .product_cat{ color:var(--gold-dark); text-transform:uppercase; letter-spacing:1px; font-size:11px; }
	.tuexhibidor-whatsapp-wrap{ display:flex; gap:8px; flex-wrap:wrap; margin:14px 16px 18px; }
	.tuexhibidor-wa-btn{ background:linear-gradient(135deg,#25D366,#1DA851) !important; color:#fff !important; border-radius:30px !important; padding:10px 18px !important; text-decoration:none; font-size:12.5px; font-weight:600; letter-spacing:.4px; box-shadow:0 4px 12px rgba(37,211,102,.35); transition:transform .25s ease, box-shadow .25s ease; border:none !important; }
	.tuexhibidor-wa-btn:hover{ transform:translateY(-2px); box-shadow:0 8px 20px rgba(37,211,102,.45); color:#fff !important; }
	a.button, .wpb_button, .vc_btn3{ border-radius:30px !important; letter-spacing:1px !important; text-transform:uppercase; font-weight:500 !important; transition:all .3s ease !important; }
	.site-footer{ background:#211f1d !important; }
	.site-footer a{ color:#d8cfc2 !important; }
	.site-footer a:hover{ color:var(--gold) !important; }
	.tuexhibidor-section{ padding:80px 24px; scroll-margin-top:90px; }
	.tuexhibidor-section-inner{ max-width:820px; margin:0 auto; text-align:center; }
	.tuexhibidor-somos{ background:#fff; }
	.tuexhibidor-somos h2, .tuexhibidor-contacto h2{ font-size:34px; margin-bottom:18px; color:var(--ink); }
	.tuexhibidor-somos p{ font-size:16px; line-height:1.8; color:#5a544c; }
	.tuexhibidor-valores{ margin-top:26px; display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
	.tuexhibidor-valores span{ border:1px solid var(--gold); color:var(--gold-dark); padding:8px 18px; border-radius:30px; font-size:12px; letter-spacing:1px; text-transform:uppercase; }
	.tuexhibidor-contacto{ background:linear-gradient(135deg,#211f1d,#3a352f); color:#fff; }
	.tuexhibidor-contacto h2{ color:#fff; }
	.tuexhibidor-contacto p{ color:#d8cfc2; font-size:16px; margin-bottom:10px; }
	.tuexhibidor-contacto-botones{ justify-content:center; margin-top:18px; }
	.tuexhibidor-float-wa{ position:fixed; right:22px; bottom:22px; z-index:9999; }
	.tuexhibidor-float-toggle{ width:58px; height:58px; border-radius:50%; background:linear-gradient(135deg,#25D366,#1DA851); border:none; box-shadow:0 8px 22px rgba(37,211,102,.45); display:flex; align-items:center; justify-content:center; cursor:pointer; }
	.tuexhibidor-float-menu{ position:absolute; bottom:70px; right:0; background:#fff; border-radius:14px; box-shadow:0 10px 30px rgba(0,0,0,.18); padding:10px; display:none; flex-direction:column; gap:6px; min-width:190px; }
	.tuexhibidor-float-wa:hover .tuexhibidor-float-menu, .tuexhibidor-float-wa:focus-within .tuexhibidor-float-menu{ display:flex; }
	.tuexhibidor-float-menu a{ padding:10px 14px; border-radius:8px; text-decoration:none; color:var(--ink); font-size:13.5px; font-weight:500; }
	.tuexhibidor-float-menu a:hover{ background:#f2efe9; color:var(--gold-dark); }
	</style>';
}


add_action('wp_head', 'tuexhibidor_hide_demo_footer');
function tuexhibidor_hide_demo_footer(){
	echo '<style>.footer-widgets{ display:none !important; }</style>';
}


// ==== Tu Exhibidor: galeria de fotos reales + limpieza de botones + fuentes ====
add_action('wp_footer', 'tuexhibidor_real_gallery');
function tuexhibidor_real_gallery(){
	if(!is_front_page()) return;
	$fotos = array(
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-tbar-triple.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-collar-oro.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-collar-busto.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-collar-tbar.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-collar-turquesa.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-collares-panel.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-bustos-collares.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-bustos-grupo.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-busto-gris.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-set-colgante.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-pulsera-oro.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-pulseras-tbar.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-tbar-burlap.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-relojes.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/te-aretes-tstand.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_01.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_02.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_03.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_04.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_07.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_08.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_09.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_10.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_13.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_14.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_15.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_17.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_18.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_19.jpg',
		'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_20.jpg',
	);
	echo '<section id="galeria" class="tuexhibidor-section tuexhibidor-galeria">';
	echo '<div class="tuexhibidor-section-inner tuexhibidor-galeria-inner">';
	echo '<h2>Exhibidores en acción</h2>';
	echo '<p class="tuexhibidor-galeria-sub">Fotografias reales de exhibidores fabricados por nosotros</p>';
	echo '<div class="tuexhibidor-galeria-grid">';
	foreach($fotos as $foto){
		echo '<div class="tuexhibidor-galeria-item"><img src="'.esc_url($foto).'" loading="lazy" alt="Exhibidor Tu Exhibidor"></div>';
	}
	echo '</div></div></section>';
	echo '<script>
	document.addEventListener("DOMContentLoaded", function(){
		var gal = document.getElementById("galeria");
		var somos = document.getElementById("somos");
		if(gal && somos){ somos.parentNode.insertBefore(gal, somos); }
	});
	</script>';
}

add_action('wp_head', 'tuexhibidor_cleanup_css');
function tuexhibidor_cleanup_css(){
	echo '<style>
	.payment-methods{ display:none !important; }
	body, input, button, textarea, select, .button, a.button{ font-family:"Poppins",sans-serif !important; }
	h1,h2,h3,h4,h5,h6,.site-title,.site-title a,.widget-title,.footer-widget-title{ font-family:"Playfair Display",serif !important; }
	.tuexhibidor-galeria{ background:#fff; padding:70px 24px; }
	.tuexhibidor-galeria-inner{ max-width:1100px; }
	.tuexhibidor-galeria-sub{ color:#8a8378; margin-bottom:34px; font-size:15px; }
	.tuexhibidor-galeria-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:16px; }
	.tuexhibidor-galeria-item{ border-radius:14px; overflow:hidden; box-shadow:0 4px 16px rgba(43,41,38,.08); background:#000; aspect-ratio:1/1; }
	.tuexhibidor-galeria-item img{ width:100%; height:100%; object-fit:cover; transition:transform .4s ease; display:block; }
	.tuexhibidor-galeria-item:hover img{ transform:scale(1.06); }
	</style>';
}


// ==== Tu Exhibidor: ronda 2 de mejoras ====
// Marcar la fila de logos de marcas (falsa publicidad) y la fila de productos para poder ocultarla/estilizarla
add_action('wp_footer', 'tuexhibidor_mark_rows');
function tuexhibidor_mark_rows(){
	if(!is_front_page()) return;
	echo '<script>
	document.addEventListener("DOMContentLoaded", function(){
		var rows = document.querySelectorAll(".vc_row");
		if(rows[1]){ rows[1].id = "grid-productos"; }
		if(rows[2]){ rows[2].id = "brands-row"; }
	});
	</script>';
}

add_action('wp_head', 'tuexhibidor_round2_css');
function tuexhibidor_round2_css(){
	echo '<style>
	/* Ocultar publicidad de marcas de lujo (no afiliadas) */
	#brands-row{ display:none !important; }

	/* Unificar fondos por seccion */
	#catalogo{ background:transparent !important; }
	#grid-productos{ background:var(--cream) !important; padding-bottom:70px !important; }
	.tuexhibidor-somos{ background:var(--cream) !important; }
	.tuexhibidor-galeria{ background:#fff !important; position:relative; }
	.tuexhibidor-galeria::before{ content:""; display:block; height:1px; max-width:120px; margin:0 auto 50px; background:linear-gradient(90deg,transparent,var(--gold),transparent); }

	/* Boton descargar catalogo */
	.tuexhibidor-catalogo-btn{ display:inline-block; margin-top:28px; padding:12px 28px; border:1.5px solid var(--gold); color:var(--gold-dark) !important; text-decoration:none; border-radius:30px; letter-spacing:1px; text-transform:uppercase; font-size:12.5px; font-weight:600; transition:all .3s ease; }
	.tuexhibidor-catalogo-btn:hover{ background:var(--gold); color:#fff !important; }

	/* Seccion Instagram */
	.tuexhibidor-instagram{ background:#fff; text-align:center; padding:60px 24px 70px; }
	.tuexhibidor-instagram h2{ font-size:28px; margin-bottom:8px; }
	.tuexhibidor-instagram p{ color:#8a8378; margin-bottom:20px; }
	.tuexhibidor-ig-btn{ display:inline-block; padding:12px 30px; border-radius:30px; text-decoration:none; font-weight:600; letter-spacing:.5px; color:#fff !important; background:linear-gradient(135deg,#f58529,#dd2a7b,#8134af,#515bd4); box-shadow:0 6px 18px rgba(221,42,123,.3); transition:transform .3s ease; }
	.tuexhibidor-ig-btn:hover{ transform:translateY(-2px); }

	/* Tamano uniforme de imagenes de producto en todo el sitio */
	li.product img, li.shop-item img, .woocommerce ul.products img, .related img{ aspect-ratio:1/1 !important; object-fit:cover !important; width:100% !important; height:auto !important; }

	/* Transiciones suaves al hacer scroll, en todo el sitio */
	.tuexhibidor-reveal{ opacity:0; transform:translateY(24px); transition:opacity .7s ease, transform .7s ease; }
	.tuexhibidor-reveal.tuexhibidor-visible{ opacity:1; transform:translateY(0); }
	</style>';
}

// Animacion de aparicion al hacer scroll (sitio completo)
add_action('wp_footer', 'tuexhibidor_scroll_reveal');
function tuexhibidor_scroll_reveal(){
	echo '<script>
	document.addEventListener("DOMContentLoaded", function(){
		var targets = document.querySelectorAll(".vc_row, li.product, li.shop-item, .tuexhibidor-section");
		targets.forEach(function(el){ el.classList.add("tuexhibidor-reveal"); });
		var obs = new IntersectionObserver(function(entries){
			entries.forEach(function(entry){
				if(entry.isIntersecting){
					entry.target.classList.add("tuexhibidor-visible");
					obs.unobserve(entry.target);
				}
			});
		}, { threshold: 0.1, rootMargin: "0px 0px -40px 0px" });
		targets.forEach(function(el){ obs.observe(el); });
	});
	</script>';
}


// ==== Tu Exhibidor: rebuild final - fondos, fuentes, SEO ====
add_action('wp_head', 'tuexhibidor_final_css');
function tuexhibidor_final_css(){
	echo '<style>
	/* Fuentes consistentes en todo el sitio (sin tocar iconos) */
	body, table, input, textarea, select, button{ font-family:"Poppins",sans-serif !important; }
	body *:not(i):not(svg):not(svg *){ font-family:inherit; }
	h1,h2,h3,h4,h5,h6,.site-title,.site-title a,.widget-title,.footer-widget-title,.page-title,.woocommerce-products-header__title{ font-family:"Playfair Display",serif !important; font-weight:600 !important; }

	/* Ritmo de fondos unificado: crema / blanco, con un solo cierre oscuro */
	body{ background:var(--cream) !important; }
	#catalogo{ background:transparent !important; }
	#grid-productos{ background:var(--cream) !important; }
	.tuexhibidor-galeria{ background:#fff !important; }
	.tuexhibidor-somos{ background:var(--cream) !important; }
	.tuexhibidor-contacto{ background:linear-gradient(180deg,#2b2926,#1c1a18) !important; }
	.site-footer{ background:#1c1a18 !important; }

	/* Divisor y bloque instagram dentro de contacto */
	.tuexhibidor-contacto-divider{ width:60px; height:1px; background:rgba(255,255,255,.2); margin:38px auto 26px; }
	.tuexhibidor-ig-label{ color:#d8cfc2 !important; margin-bottom:14px !important; font-size:14px; }

	/* Limpieza: quitar wishlist (no aplica sin carrito) */
	.yith-wcwl-add-to-wishlist, a.add_to_wishlist{ display:none !important; }

	/* Encabezados de categoria/archivo mas prolijos */
	.woocommerce-products-header{ padding:40px 0 10px; }
	</style>';
}

// SEO: titulo, meta description, Open Graph y datos estructurados
add_filter('pre_get_document_title', function($title){
	if(is_front_page()){
		return 'Tu Exhibidor | Fabrica de Exhibidores para Joyeria y Bisuteria en Chile';
	}
	return $title;
});

add_action('wp_head', 'tuexhibidor_seo_tags', 1);
function tuexhibidor_seo_tags(){
	$desc = 'Fabricamos exhibidores y displays para joyerias y tiendas de bisuteria: bustos para collares, bandejas para anillos y aretes, soportes para pulseras y relojes. Cotiza por WhatsApp.';
	$url = 'https://tuexhibidor.cl/';
	$img = 'https://tuexhibidor.cl/wp-content/uploads/2026/07/prod_14.jpg';
	if(is_front_page()){
		echo '<meta name="description" content="'.esc_attr($desc).'">'."\n";
		echo '<meta name="keywords" content="exhibidores para joyeria, displays para bisuteria, exhibidores de joyas Chile, fabricante de exhibidores, busto para collares, bandeja para anillos, exhibidor para relojes">'."\n";
	}
	echo '<meta property="og:site_name" content="Tu Exhibidor">'."\n";
	echo '<meta property="og:type" content="business.business">'."\n";
	echo '<meta property="og:title" content="Tu Exhibidor | Exhibidores para Joyeria y Bisuteria">'."\n";
	echo '<meta property="og:description" content="'.esc_attr($desc).'">'."\n";
	echo '<meta property="og:url" content="'.esc_url($url).'">'."\n";
	echo '<meta property="og:image" content="'.esc_url($img).'">'."\n";
	echo '<meta name="twitter:card" content="summary_large_image">'."\n";
	if(is_front_page()){
		echo '<script type="application/ld+json">
		{
		"@context": "https://schema.org",
		"@type": "Store",
		"name": "Tu Exhibidor",
		"description": "'.esc_js($desc).'",
		"url": "https://tuexhibidor.cl/",
		"image": "'.esc_js($img).'",
		"address": { "@type": "PostalAddress", "addressCountry": "CL" },
		"areaServed": "CL",
		"sameAs": ["https://www.instagram.com/tuexhibidor/", "https://www.facebook.com/TUEXHIBIDOR.CL"]
		}
		</script>';
	}
}


/* ================= ROUND 3 FIXES ================= */

/* 1. Strip trailing product code like "(XNL)" from product titles */
add_filter('the_title', function($title, $post_id = null){
    if (!is_admin() && $post_id && get_post_type($post_id) === 'product') {
        $title = preg_replace('/\\s*\\([A-Za-z0-9\\/\\-]+\\)\\s*$/u', '', $title);
    }
    return $title;
}, 20, 2);

/* 2. Remove product reviews entirely */
add_filter('woocommerce_product_tabs', function($tabs){
    unset($tabs['reviews']);
    return $tabs;
}, 98);
add_filter('comments_open', function($open, $post_id){
    if (get_post_type($post_id) === 'product') return false;
    return $open;
}, 20, 2);
remove_post_type_support('product', 'comments');

/* 3. Site-wide accent fix + rebuilt Somos/Contacto sections via output buffer */
add_action('template_redirect', function(){
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST) || is_feed()) return;
    ob_start('tuexhibidor_round3_fix_output');
});

function tuexhibidor_round3_fix_output($html){
    $fixes = array(
		'Nuevos Exhibidores' => 'Los más pedidos',
		'Nuestros Productos' => 'Exhibidores en acción',
		'Piezas reales que hoy lucen joyas en vitrinas de todo Chile' => 'Piezas reales que hoy lucen joyas en vitrinas de todo Chile',
		'Escríbenos por WhatsApp y pide tu cotización hoy, directo con quien fabrica tus exhibidores.' => 'Escríbenos por WhatsApp y recibe tu cotización el mismo día, con atención directa del equipo que los fabrica.',
        "Tu Exhibidor | Fabrica de Exhibidores para Joyeria y Bisuteria en Chile" => "Tu Exhibidor | F\u{00e1}brica de Exhibidores para Joyer\u{00ed}a y Bisuter\u{00ed}a en Chile",
        "Fabricamos exhibidores y displays para joyerias y tiendas de bisuteria: bustos para collares, bandejas para anillos y aretes, soportes para pulseras y relojes. Cotiza por WhatsApp." => "Fabricamos exhibidores y displays para joyer\u{00ed}as y tiendas de bisuter\u{00ed}a: bustos para collares, bandejas para anillos y aretes, soportes para pulseras y relojes. Cotiza por WhatsApp.",
        "Fabrica de Exhibidores" => "F\u{00e1}brica de Exhibidores",
        "para Joyeria y Bisuteria" => "para Joyer\u{00ed}a y Bisuter\u{00ed}a",
        "joyerias y tiendas de bisuteria" => "joyer\u{00ed}as y tiendas de bisuter\u{00ed}a",
        "QUIENES SOMOS" => "QUI\u{00c9}NES SOMOS",
        "Fotografias reales" => "Fotograf\u{00ed}as reales",
    );
    foreach ($fixes as $bad => $good) {
        $html = str_replace($bad, $good, $html);
    }

    $somos_html = <<<HTML
<section id="somos" class="tuexhibidor-section tuexhibidor-somos tuexhibidor-reveal">
<div class="tuexhibidor-somos-inner">
<span class="tuexhibidor-eyebrow">Fabricante directo · Precio de fábrica</span>
<h2>Por qué las joyerías nos eligen</h2>
<p>Llevamos más de 20 años fabricando exhibidores para joyería y bisutería que hacen que cada pieza se venda sola. Materiales nobles, terminaciones impecables y un diseño actual que convierte tu vitrina en tu mejor vendedor.</p>
<p>Trabajamos directo con joyerías de todo Chile y Latinoamérica: fabricación a medida, despachos rápidos y atención personalizada de principio a fin. Sin intermediarios y a precio de fábrica.</p>
<div class="tuexhibidor-valores-list">
<div class="tuexhibidor-valor-item"><span class="tuexhibidor-valor-num">01</span><span class="tuexhibidor-valor-txt">Calidad</span></div>
<div class="tuexhibidor-valor-item"><span class="tuexhibidor-valor-num">02</span><span class="tuexhibidor-valor-txt">Innovaci\u{00f3}n</span></div>
<div class="tuexhibidor-valor-item"><span class="tuexhibidor-valor-num">03</span><span class="tuexhibidor-valor-txt">Compromiso</span></div>
<div class="tuexhibidor-valor-item"><span class="tuexhibidor-valor-num">04</span><span class="tuexhibidor-valor-txt">Experiencia</span></div>
</div>
<a href="https://tuexhibidor.cl/wp-content/uploads/2026/07/catalogo_tuexhibidor.pdf" target="_blank" rel="noopener" class="tuexhibidor-catalogo-btn">Descargar cat\u{00e1}logo completo (85+ modelos)</a>
</div>
</section>
HTML;

    $wa1 = 'https://wa.me/56937490214?text=' . rawurlencode("Hola, me interesa cotizar exhibidores para mi joyer\u{00ed}a.");
    $wa2 = 'https://wa.me/56991327813?text=' . rawurlencode("Hola, me interesa cotizar exhibidores para mi joyer\u{00ed}a.");

    $contacto_html = <<<HTML
<section id="contacto" class="tuexhibidor-section tuexhibidor-contacto tuexhibidor-reveal">
<div class="tuexhibidor-contacto-inner">
<span class="tuexhibidor-eyebrow">Cotiza ahora</span>
<h2>Cont\u{00e1}ctanos</h2>
<p>Escr\u{00ed}benos por WhatsApp y pide tu cotización hoy, directo con quien fabrica tus exhibidores.</p>
<div class="tuexhibidor-contacto-buttons">
<a href="{$wa1}" target="_blank" rel="noopener" class="tuexhibidor-wa-btn">Alfonso Orozco</a>
<a href="{$wa2}" target="_blank" rel="noopener" class="tuexhibidor-wa-btn">Leder Mejia</a>
</div>
<div class="tuexhibidor-contacto-divider"></div>
<span class="tuexhibidor-ig-label">S\u{00ed}guenos en Instagram</span>
<a href="https://www.instagram.com/tuexhibidor/" target="_blank" rel="noopener" class="tuexhibidor-ig-btn">@tuexhibidor</a>
</div>
</section>
HTML;

    $html = preg_replace('/<section id="somos"[^>]*>.*?<\\/section>/s', $somos_html, $html, 1);
    $html = preg_replace('/<section id="contacto"[^>]*>.*?<\\/section>/s', $contacto_html, $html, 1);

    return $html;
}

/* 4. CSS: valores no button style, image size safeguards, hide cart icon, hide review leftovers */
add_action('wp_head', function(){
    ?>
    <style>
    .tuexhibidor-valores-list{ display:flex; flex-wrap:wrap; gap:28px; justify-content:center; margin:34px 0; padding:0; }
    .tuexhibidor-valor-item{ display:flex; align-items:center; gap:10px; padding:0 18px; border:none !important; background:none !important; border-radius:0 !important; }
    .tuexhibidor-valor-item:not(:last-child){ border-right:1px solid rgba(184,147,95,0.35) !important; }
    .tuexhibidor-valor-num{ font-family:'Playfair Display',serif; color:var(--gold); font-size:1.1rem; font-weight:600; }
    .tuexhibidor-valor-txt{ font-family:'Poppins',sans-serif; letter-spacing:0.08em; text-transform:uppercase; font-size:0.8rem; color:var(--ink); }

    li.product img, li.shop-item img, .woocommerce ul.products img, .related img,
    .up-sells img, .cross-sells img, .woocommerce-product-gallery img, .flex-control-thumbs img{
        aspect-ratio:1/1 !important; object-fit:cover !important; object-position:center !important;
        width:100% !important; height:auto !important; min-width:0 !important;
    }

    .cart-contents, .site-header .cart, a.cart-icon, .header-cart, .woocommerce-mini-cart, .mini-cart, .cart-count{ display:none !important; }

    .woocommerce-tabs ul.tabs li.reviews_tab, #reviews, .woocommerce-Reviews{ display:none !important; }
    </style>
    <?php
}, 200);


/* == TE-TRADUCCIONES == */
add_filter('gettext', 'te_traducciones_gettext', 99, 3);
function te_traducciones_gettext($translated, $text, $domain){
    $map = array(
        'Home' => 'Inicio',
        'Share this item:' => 'Compartir:', 'Share this item' => 'Compartir', 'Related products' => 'Productos relacionados',
        'Category:' => 'Categoría:',
        'Categories:' => 'Categorías:',
        'Description' => 'Descripción',
        'Additional information' => 'Información adicional',
        'Reviews' => 'Valoraciones',
        'Share item:' => 'Compartir:',
        'Share item' => 'Compartir',
        'Share:' => 'Compartir:',
        'Share' => 'Compartir',
        'Search' => 'Buscar',
        'Read more' => 'Ver más',
        'Select options' => 'Ver opciones',
        'View cart' => 'Ver carrito',
    );
    if (isset($map[$text])) { return $map[$text]; }
    return $translated;
}
/* == fin TE-TRADUCCIONES == */

/* == TE-TRADUCCIONES ngettext == */
add_filter('ngettext', 'te_traducciones_ngettext', 99, 5);
function te_traducciones_ngettext($translated, $single, $plural, $number, $domain){
    if ($single === 'Category:') { return ($number > 1) ? 'Categorías:' : 'Categoría:'; }
    if ($single === 'Tag:') { return ($number > 1) ? 'Etiquetas:' : 'Etiqueta:'; }
    return $translated;
}
/* == fin ngettext == */


/* == TE-LIGHTBOX == */
add_action('wp_footer', 'tuexhibidor_gallery_lightbox', 210);
function tuexhibidor_gallery_lightbox(){
    if(!is_front_page()) return;
    echo '<style>#te-lightbox{position:fixed;inset:0;background:rgba(0,0,0,.9);display:none;align-items:center;justify-content:center;z-index:99999;cursor:zoom-out;opacity:0;transition:opacity .3s;}#te-lightbox.open{display:flex;opacity:1;}#te-lightbox img{max-width:90%;max-height:90%;box-shadow:0 10px 40px rgba(0,0,0,.5);border-radius:4px;}#te-lightbox .te-close{position:absolute;top:20px;right:30px;color:#fff;font-size:44px;cursor:pointer;line-height:1;}#galeria img,.tuexhibidor-galeria img{cursor:zoom-in;}</style>';
    echo '<div id="te-lightbox"><span class="te-close">&times;</span><img src="" alt=""></div>';
    echo '<script>document.addEventListener("click",function(e){var t=e.target;if(t.tagName==="IMG" && t.closest("#galeria, .tuexhibidor-galeria")){var lb=document.getElementById("te-lightbox");lb.querySelector("img").src=t.currentSrc||t.src;lb.classList.add("open");}else if(t.id==="te-lightbox"||t.className==="te-close"){document.getElementById("te-lightbox").classList.remove("open");}});document.addEventListener("keydown",function(e){if(e.key==="Escape"){var lb=document.getElementById("te-lightbox");if(lb)lb.classList.remove("open");}});</script>';
}
/* == fin TE-LIGHTBOX == */


/* == TE-MENU-CATALOGO: CATALOGO como ancla del landing == */
add_filter('wp_nav_menu_objects', 'te_catalogo_anchor', 10, 2);
function te_catalogo_anchor($items, $args){
    foreach($items as $it){
        $t = mb_strtoupper(trim(strip_tags($it->title)));
        if($t==='CATÁLOGO'||$t==='CATALOGO'){ $it->url = home_url('/#catalogo'); }
        elseif($t==='GALERÍA'||$t==='GALERIA'){ $it->url = home_url('/#galeria'); }
        elseif($t==='QUIÉNES SOMOS'||$t==='QUIENES SOMOS'){ $it->url = home_url('/#somos'); }
        elseif($t==='CONTACTO'){ $it->url = home_url('/#contacto'); }
        elseif($t==='INICIO'){ $it->url = home_url('/'); }
    }
    return $items;
}
/* == fin TE-MENU-CATALOGO == */


/* == TE-BREADCRUMB-HOME == */
add_filter('woocommerce_breadcrumb_home_text', function(){ return 'Inicio'; });
/* == fin == */


/* == TE-HOME-CTX == */
add_filter('gettext_with_context','te_home_ctx',20,4);
function te_home_ctx($translated,$text,$context,$domain){ if($text==='Home') return 'Inicio'; return $translated; }
/* == fin == */


/* == TE-CAT-SUBMENU == */
add_filter('wp_nav_menu_objects','te_cat_submenu',30,2);
function te_cat_submenu($items,$args){
    $catId=0;
    foreach($items as $it){ $t=mb_strtoupper(trim(strip_tags($it->title))); if($t==='CATÁLOGO'||$t==='CATALOGO'){ $catId=$it->ID; break; } }
    if(!$catId) return $items;
    $terms=get_terms(array('taxonomy'=>'product_cat','hide_empty'=>false));
    if(is_wp_error($terms)||!$terms) return $items;
    $i=90000;
    foreach($terms as $t){
        if($t->slug==='uncategorized') continue;
        $o=new stdClass();
        $o->ID=$i; $o->db_id=$i; $o->menu_item_parent=$catId; $o->object_id=$i;
        $o->title=$t->name; $lk=get_term_link($t); $o->url=is_wp_error($lk)?'#':$lk;
        $o->type='custom'; $o->object='custom'; $o->target=''; $o->attr_title=''; $o->description=''; $o->xfn='';
        $o->classes=array('menu-item','menu-item-type-custom','te-cat-sub');
        $o->current=false; $o->current_item_ancestor=false; $o->current_item_parent=false;
        $items[]=$o; $i++;
    }
    return $items;
}
/* == fin TE-CAT-SUBMENU == */


/* == TE-HOME-EXTRAS: flechas galeria + link nosotros == */
add_action('wp_footer','te_home_extras',215);
function te_home_extras(){
    if(!is_front_page()) return;
    ?>
    <style>
    .te-gal-holder{position:relative;}
    .te-gal-arrow{position:absolute;top:45%;transform:translateY(-50%);z-index:6;width:46px;height:46px;border-radius:50%;border:0;background:#fff;box-shadow:0 5px 16px rgba(0,0,0,.16);cursor:pointer;font-size:26px;line-height:1;color:#333;display:flex;align-items:center;justify-content:center;transition:all .25s ease;}
    .te-gal-arrow:hover{background:#111;color:#fff;}
    .te-gal-prev{left:-6px;} .te-gal-next{right:-6px;}
    .te-somos-more{display:inline-block;margin-top:22px;padding:12px 30px;border:1px solid #c9a24b;border-radius:999px;color:#111;font-weight:600;letter-spacing:.04em;text-decoration:none;transition:all .3s ease;}
    .te-somos-more:hover{background:#c9a24b;color:#fff;}
    </style>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
      var grid=document.querySelector('#galeria .tuexhibidor-galeria-grid');
      if(grid && !grid.closest('.te-gal-holder')){
        var holder=document.createElement('div'); holder.className='te-gal-holder';
        grid.parentNode.insertBefore(holder,grid); holder.appendChild(grid);
        var prev=document.createElement('button'); prev.type='button'; prev.className='te-gal-arrow te-gal-prev'; prev.innerHTML='‹'; prev.setAttribute('aria-label','Anterior');
        var next=document.createElement('button'); next.type='button'; next.className='te-gal-arrow te-gal-next'; next.innerHTML='›'; next.setAttribute('aria-label','Siguiente');
        holder.appendChild(prev); holder.appendChild(next);
        var step=function(){return Math.max(240, grid.clientWidth*0.85);};
        prev.addEventListener('click',function(){ if(grid.scrollLeft<=5){grid.scrollTo({left:grid.scrollWidth,behavior:'smooth'});}else{grid.scrollBy({left:-step(),behavior:'smooth'});} });
        next.addEventListener('click',function(){ if(grid.scrollLeft+grid.clientWidth>=grid.scrollWidth-5){grid.scrollTo({left:0,behavior:'smooth'});}else{grid.scrollBy({left:step(),behavior:'smooth'});} }); setInterval(function(){ if(grid.matches(':hover'))return; if(grid.scrollLeft+grid.clientWidth>=grid.scrollWidth-5){grid.scrollTo({left:0,behavior:'smooth'});}else{grid.scrollBy({left:step(),behavior:'smooth'});} },4500);
      }
      var somos=document.querySelector('#somos .tuexhibidor-somos-inner')||document.querySelector('#somos');
      if(somos && !somos.querySelector('.te-somos-more')){
        var a=document.createElement('a'); a.href='/nosotros/'; a.className='te-somos-more'; a.textContent='Conócenos más'; somos.appendChild(a);
      }
    });
    </script>
    <?php
}
/* == fin TE-HOME-EXTRAS == */


/* == TE-CASE-PHP: tipo oracion real (productos + menu) == */
function te_sentence($s){ $s=trim($s); if($s==='') return $s; $low=mb_strtolower($s,'UTF-8'); return mb_strtoupper(mb_substr($low,0,1,'UTF-8'),'UTF-8').mb_substr($low,1,null,'UTF-8'); }
add_filter('the_title','te_title_products',20,2);
function te_title_products($title,$id=null){ if(is_admin()) return $title; if($id && get_post_type($id)==='product') return te_sentence($title); return $title; }
add_filter('wp_nav_menu_objects','te_menu_titles',40,2);
function te_menu_titles($items,$args){ $caps=array('CATÁLOGO','CATALOGO','GALERÍA','GALERIA','QUIÉNES SOMOS','QUIENES SOMOS','CONTACTO','INICIO'); foreach($items as $it){ $u=mb_strtoupper(trim(strip_tags($it->title)),'UTF-8'); if(in_array($u,$caps,true)){ $it->title=str_replace('Quienes','Quiénes',te_sentence($it->title)); } } return $items; }
/* == fin TE-CASE-PHP == */


/* == TE-FOOTER-CONTACT == */
add_action('wp_footer','te_footer_contact',216);
function te_footer_contact(){
    ?>
    <style>
    .te-footer-contact{text-align:center;padding:26px 15px 10px;}
    .te-footer-contact .te-fc-title{color:#c9a24b;font-weight:600;letter-spacing:.1em;text-transform:uppercase;font-size:12px;margin-bottom:12px;display:block;}
    .te-fc-mail{display:inline-block;border:1px solid #c9a24b;border-radius:999px;padding:10px 24px;color:#fff !important;text-decoration:none;font-weight:600;transition:all .25s;}
    .te-fc-mail:hover{background:#c9a24b;color:#111 !important;}
    .te-fc-row{margin-top:14px;}
    .te-fc-row a{color:#e9e4da;text-decoration:none;margin:4px 12px;font-size:14px;display:inline-block;transition:color .25s;}
    .te-fc-row a:hover{color:#25D366;}
    .te-fc-social{margin-top:16px;}
    .te-fc-social span{color:#cbb78a;font-size:11px;letter-spacing:.12em;text-transform:uppercase;margin-right:10px;vertical-align:middle;}
    .te-fc-social a{font-size:20px;margin:0 9px;vertical-align:middle;transition:transform .2s;display:inline-block;}
    .te-fc-social a:hover{transform:translateY(-2px);}
    .te-fc-social .fa-facebook{color:#1877F2;}
    .te-fc-social .fa-instagram{background:linear-gradient(45deg,#f09433,#dc2743,#bc1888);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;}
    </style>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
      var f=document.querySelector('.site-footer'); if(!f || document.querySelector('.te-footer-contact')) return;
      var box=document.createElement('div'); box.className='te-footer-contact';
      box.innerHTML='<span class="te-fc-title">Contacto</span>'
        +'<div><a class="te-fc-mail" href="mailto:info@tuexhibidor.cl">Escríbenos: info@tuexhibidor.cl</a></div>'
        +'<div class="te-fc-row"><a href="https://wa.me/56937490214" target="_blank" rel="nofollow">WhatsApp Alfonso: +56 9 3749 0214</a>'
        +'<a href="https://wa.me/56991327813" target="_blank" rel="nofollow">WhatsApp Leder: +56 9 9132 7813</a></div>'
        +'<div class="te-fc-social"><span>Síguenos</span><a href="https://www.facebook.com/TUEXHIBIDOR.CL" target="_blank" rel="nofollow"><i class="fa fa-facebook"></i></a><a href="https://www.instagram.com/tuexhibidor/" target="_blank" rel="nofollow"><i class="fa fa-instagram"></i></a></div>';
      f.insertBefore(box, f.firstChild);
    });
    </script>
    <?php
}
/* == fin TE-FOOTER-CONTACT == */


/* == TE-CONTACT-FORM == */
add_action('wp_footer','te_contact_form',217);
function te_contact_form(){
    if(!is_front_page()) return;
    $form=do_shortcode('[contact-form-7 id="b57a72c" title="Formulario de contacto 1"]');
    ?>
    <style>
    .te-contact-form{max-width:520px;margin:26px auto 0;text-align:left;}
    .te-cf-title{text-align:center;color:#fff;margin:0 0 16px;font-size:18px;}
    .te-contact-form label{display:block;color:#e9e4da;font-size:13px;margin-bottom:14px;}
    .te-contact-form input[type=text],.te-contact-form input[type=email],.te-contact-form textarea{width:100%;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.25);color:#fff;padding:12px 14px;border-radius:8px;font-size:14px;margin-top:5px;}
    .te-contact-form textarea{min-height:110px;}
    .te-contact-form .wpcf7-submit{background:#c9a24b;color:#111;border:0;padding:13px 34px;border-radius:999px;font-weight:600;cursor:pointer;display:block;margin:8px auto 0;transition:all .25s;}
    .te-contact-form .wpcf7-submit:hover{background:#d9b45f;transform:translateY(-2px);}
    .te-contact-form .wpcf7-response-output{display:none !important;}
    #te-popup{position:fixed;inset:0;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center;z-index:100000;}
    #te-popup.open{display:flex;}
    #te-popup .te-pop-box{background:#fff;border-radius:14px;padding:34px 30px;max-width:380px;text-align:center;box-shadow:0 20px 50px rgba(0,0,0,.3);}
    #te-popup .te-pop-ico{font-size:42px;line-height:1;margin-bottom:10px;font-weight:700;}
    #te-popup h4{margin:0 0 8px;font-size:20px;}
    #te-popup p{margin:0 0 18px;color:#555;font-size:15px;}
    #te-popup button{background:#c9a24b;color:#111;border:0;padding:10px 26px;border-radius:999px;font-weight:600;cursor:pointer;}
    </style>
    <div id='te-cf7-holder' style='display:none;'><div class='te-contact-form'><h3 class='te-cf-title'>O escríbenos aquí</h3><?php echo $form; ?></div></div>
    <div id='te-popup'><div class='te-pop-box'><div class='te-pop-ico'></div><h4></h4><p></p><button type='button'>Cerrar</button></div></div>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
      var holder=document.getElementById('te-cf7-holder');
      var c=document.querySelector('#contacto .tuexhibidor-contacto-inner')||document.querySelector('#contacto');
      if(holder && c && holder.firstElementChild){ c.appendChild(holder.firstElementChild); holder.remove(); }
      var pop=function(ico,title,msg,color){ var p=document.getElementById('te-popup'); p.querySelector('.te-pop-ico').textContent=ico; p.querySelector('.te-pop-ico').style.color=color; var hh=p.querySelector('h4'); hh.textContent=title; hh.style.color=color; p.querySelector('p').textContent=msg; p.classList.add('open'); };
      document.getElementById('te-popup').addEventListener('click',function(e){ if(e.target.id==='te-popup'||e.target.tagName==='BUTTON'){ this.classList.remove('open'); } });
      document.addEventListener('wpcf7mailsent',function(){ pop('✓','¡Mensaje enviado!','Gracias por escribirnos. Te responderemos muy pronto.','#1e9e57'); });
      document.addEventListener('wpcf7mailfailed',function(){ pop('!','No se pudo enviar','Hubo un problema. Por favor escríbenos por WhatsApp.','#c0392b'); });
      document.addEventListener('wpcf7invalid',function(){ pop('!','Revisa los campos','Completa tu nombre, correo y mensaje.','#c0392b'); });
    });
    </script>
    <?php
}
/* == fin TE-CONTACT-FORM == */


/* TE-QUITA-IVA */
add_filter('woocommerce_short_description','te_quita_iva',20);
add_filter('the_content','te_quita_iva',20);
function te_quita_iva($c){ return str_ireplace(array('Precio incluye IVA.','Precio incluye IVA'), '', $c); }


/* == TE-FOOTER-DEMO: eliminar widgets demo Aurum (Suiza/Europa/Américas) == */
add_action('widgets_init', function(){
	unregister_sidebar('footer_sidebar_left');
	unregister_sidebar('footer_sidebar_right');
}, 20);
add_action('wp_footer', function(){
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function(){
		document.querySelectorAll('.site-footer .widget_text').forEach(function(w){
			var t = (w.textContent || '').trim();
			if (/Switzerland|Europe|Americas|Zurich|Moscow|New York/i.test(t)) { w.remove(); }
		});
		document.querySelectorAll('.site-footer h3').forEach(function(h){
			if (/Switzerland|Europe|Americas/i.test(h.textContent)) {
				var p = h.closest('.widget, .col, .sidebar');
				if (p) p.remove();
			}
		});
	});
	</script>
	<?php
}, 5);
/* == fin TE-FOOTER-DEMO == */


/* == TE-SECURITY-HEADERS (complementa mu-plugin) == */
add_action('send_headers', function(){
	if (headers_sent()) return;
	header('X-Content-Type-Options: nosniff');
	header('X-Frame-Options: SAMEORIGIN');
}, 11);
/* == fin TE-SECURITY-HEADERS == */
