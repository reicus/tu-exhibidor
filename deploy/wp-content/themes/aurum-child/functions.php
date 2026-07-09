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

/* == TE-SOCIAL: iconos y enlaces unificados == */
function te_social_urls(): array {
	return array(
		'facebook'  => 'https://facebook.com/tuexhibidor.cl',
		'instagram' => 'https://www.instagram.com/tuexhibidor/',
	);
}

function te_social_icon_svg( string $network, int $size = 20 ): string {
	$icons = array(
		'facebook' => 'M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z',
		'instagram' => 'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z',
		'whatsapp' => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z',
		'link' => 'M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7c-2.76 0-5 2.24-5 5s2.24 5 5 5h4v-1.9H7c-1.71 0-3.1-1.39-3.1-3.1zM8 13h8v-2H8v2zm9-6h-4v1.9h4c1.71 0 3.1 1.39 3.1 3.1s-1.39 3.1-3.1 3.1h-4V17h4c2.76 0 5-2.24 5-5s-2.24-5-5-5z',
	);
	if ( ! isset( $icons[ $network ] ) ) {
		return '';
	}
	return '<svg viewBox="0 0 24 24" width="' . (int) $size . '" height="' . (int) $size . '" aria-hidden="true"><path fill="currentColor" d="' . esc_attr( $icons[ $network ] ) . '"/></svg>';
}

function te_social_links_html( string $wrap_class = 'social-links', string $link_class = 'social-link', int $size = 20 ): string {
	$urls = te_social_urls();
	$html = '<div class="' . esc_attr( $wrap_class ) . '" aria-label="Síguenos en redes sociales">';
	foreach ( array( 'facebook', 'instagram' ) as $network ) {
		$label = ucfirst( $network );
		$html .= '<a class="' . esc_attr( $link_class ) . '" href="' . esc_url( $urls[ $network ] ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $label ) . ' Tu Exhibidor">';
		$html .= te_social_icon_svg( $network, $size );
		$html .= '</a>';
	}
	$html .= '</div>';
	return $html;
}

function te_header_social_networks_html(): string {
	$urls = te_social_urls();
	$wa   = 'https://wa.me/56937490214?text=' . rawurlencode( 'Hola, quiero cotizar exhibidores' );
	$html = '<ul class="social-networks">';
	$html .= '<li><a class="te-header-social" href="' . esc_url( $urls['facebook'] ) . '" target="_blank" rel="noopener noreferrer" title="Facebook" aria-label="Facebook">' . te_social_icon_svg( 'facebook', 16 ) . '</a></li>';
	$html .= '<li><a class="te-header-social" href="' . esc_url( $urls['instagram'] ) . '" target="_blank" rel="noopener noreferrer" title="Instagram" aria-label="Instagram">' . te_social_icon_svg( 'instagram', 16 ) . '</a></li>';
	$html .= '<li><a class="te-header-social te-header-social--wa" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener noreferrer" title="Contactar por WhatsApp" aria-label="Contactar por WhatsApp">' . te_social_icon_svg( 'whatsapp', 16 ) . '</a></li>';
	$html .= '</ul>';
	return $html;
}

add_filter( 'widget_display_callback', 'te_override_header_social_widget', 10, 3 );
function te_override_header_social_widget( $instance, $widget, $args ) {
	$widget_classes = $args['before_widget'] ?? '';
	if ( strpos( $widget_classes, 'social-networks' ) === false && strpos( $widget_classes, 'widget-social' ) === false ) {
		return $instance;
	}
	echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo te_header_social_networks_html();
	echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return false;
}

add_action( 'template_redirect', 'te_buffer_clean_header_socials', 0 );
function te_buffer_clean_header_socials() {
	if ( is_admin() || wp_doing_ajax() || wp_is_json_request() ) {
		return;
	}
	ob_start( 'te_filter_header_socials_html' );
}

function te_filter_header_socials_html( string $html ): string {
	$replacement = te_header_social_networks_html();
	$html        = preg_replace(
		'#<div class="top-menu--widget top-menu--widget-social-networks">\s*<ul class="social-networks">.*?</ul>#s',
		'<div class="top-menu--widget top-menu--widget-social-networks">' . $replacement,
		$html
	);
	return is_string( $html ) ? $html : '';
}

add_action( 'woocommerce_init', 'te_replace_product_share', 100 );
function te_replace_product_share() {
	remove_action( 'woocommerce_share', 'aurum_woocommerce_share' );
	add_action( 'woocommerce_share', 'te_product_share_links', 5 );
}

function te_product_share_links() {
	global $product;
	if ( ! $product instanceof WC_Product ) {
		return;
	}
	$url   = get_permalink( $product->get_id() );
	$title = $product->get_name();
	$wa    = rawurlencode( 'Mira este exhibidor: ' . $title . ' ' . $url );
	$fb    = rawurlencode( $url );
	?>
	<div class="te-product-share">
		<p class="te-product-share__label">Compartir</p>
		<div class="te-product-share__links social-links">
			<a class="social-link te-share-wa" href="https://wa.me/?text=<?php echo esc_attr( $wa ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Compartir por WhatsApp"><?php echo te_social_icon_svg( 'whatsapp' ); ?></a>
			<a class="social-link te-share-fb" href="https://www.facebook.com/sharer.php?u=<?php echo esc_attr( $fb ); ?>" target="_blank" rel="noopener noreferrer" aria-label="Compartir en Facebook"><?php echo te_social_icon_svg( 'facebook' ); ?></a>
			<button type="button" class="social-link te-share-copy" data-url="<?php echo esc_url( $url ); ?>" aria-label="Copiar enlace"><?php echo te_social_icon_svg( 'link' ); ?></button>
		</div>
	</div>
	<?php
}

add_action( 'wp_footer', 'te_upgrade_header_socials', 4 );
function te_upgrade_header_socials() {
	$urls     = te_social_urls();
	$fb       = esc_js( $urls['facebook'] );
	$ig       = esc_js( $urls['instagram'] );
	$ink_logo = esc_js( home_url( '/public/images/brand/logo-tuexhibidor-ink-96.webp' ) );
	$icon_fb  = str_replace( "'", "\\'", te_social_icon_svg( 'facebook', 16 ) );
	$icon_ig  = str_replace( "'", "\\'", te_social_icon_svg( 'instagram', 16 ) );
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function(){
		var inkLogo = '<?php echo $ink_logo; ?>';
		document.querySelectorAll('.site-header img.normal-logo, .site-header #site-logo').forEach(function(img, idx){
			img.src = inkLogo;
			img.removeAttribute('srcset');
			img.width = 52;
			img.height = 52;
			img.style.height = '52px';
			img.style.width = 'auto';
			if (idx > 0) {
				var wrap = img.closest('.logo');
				if (wrap) wrap.style.display = 'none';
			}
		});
		document.querySelectorAll('.logo.text-logo a').forEach(function(a){
			if (a.querySelector('img')) return;
			a.innerHTML = '<img src="'+inkLogo+'" class="logo-dimensions normal-logo" id="site-logo" alt="Tu Exhibidor" width="52" height="52" style="height:52px;width:auto">';
			var wrap = a.closest('.logo');
			if (wrap) { wrap.classList.remove('text-logo'); wrap.classList.add('image-logo','te-brand-logo'); }
		});
		document.querySelectorAll('.site-header style').forEach(function(node){
			if (node.textContent && node.textContent.indexOf('logo-dimensions') !== -1) {
				node.textContent = '.logo-dimensions{min-width:0!important;width:auto!important;height:52px!important;}';
			}
		});

		var socialWidgets = document.querySelectorAll('.top-menu--widget-social-networks');
		for (var i = socialWidgets.length - 1; i > 0; i--) {
			var col = socialWidgets[i].closest('.col');
			if (col) col.remove();
			else socialWidgets[i].remove();
		}

		document.querySelectorAll('.top-menu .social-networks, .header-top-socials ul').forEach(function(ul){
			if (!ul || ul.dataset.teSocialReady) return;
			ul.dataset.teSocialReady = '1';
			ul.innerHTML = <?php echo wp_json_encode( preg_replace( '#^<ul class="social-networks">|</ul>$#', '', te_header_social_networks_html() ) ); ?>;
		});

		var searchSvg = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.2-3.2"></path></svg><span class="sr-only">Buscar</span>';
		document.querySelectorAll('.search-btn').forEach(function(btn){
			btn.innerHTML = searchSvg;
			btn.classList.add('te-search-btn');
		});

		document.querySelectorAll('.te-share-copy').forEach(function(btn){
			btn.addEventListener('click', function(){
				var url = btn.getAttribute('data-url');
				if (!url) return;
				var done = function(){ btn.classList.add('is-copied'); setTimeout(function(){ btn.classList.remove('is-copied'); }, 1800); };
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(url).then(done);
				} else {
					var t = document.createElement('textarea');
					t.value = url; document.body.appendChild(t); t.select();
					try { document.execCommand('copy'); done(); } catch(e) {}
					document.body.removeChild(t);
				}
			});
		});
	});
	</script>
	<?php
}
/* == fin TE-SOCIAL == */

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
	$msg = tuexhibidor_whatsapp_message( $product );
	echo '<div class="te-product-quote">';
	echo '<p class="te-product-quote__title">Cotizar este producto</p>';
	echo '<p class="te-product-quote__sub">Atención directa del taller — respuesta el mismo día</p>';
	echo '<div class="te-product-quote__buttons">';
	echo '<a class="te-product-wa-btn" target="_blank" rel="nofollow" href="https://wa.me/56937490214?text=' . $msg . '">Alfonso Orozco</a>';
	echo '<a class="te-product-wa-btn" target="_blank" rel="nofollow" href="https://wa.me/56991327813?text=' . $msg . '">Leder Mejia</a>';
	echo '</div></div>';
}

// Boton flotante de WhatsApp (mismo diseño que /site/)
add_action('wp_footer', 'tuexhibidor_floating_whatsapp');
function tuexhibidor_floating_whatsapp(){
	$msg = 'Hola%2C%20quiero%20cotizar%20exhibidores';
	echo '<div class="wa-widget" id="wa-widget">
	<div class="wa-menu" id="wa-menu" hidden>
		<p class="wa-menu-title">Cotizar por WhatsApp</p>
		<p class="wa-menu-sub">¿Con quién quieres hablar?</p>
		<a class="wa-menu-item" href="https://wa.me/56937490214?text=' . $msg . '" target="_blank" rel="nofollow">
			<span class="wa-menu-name">Alfonso Orozco</span>
			<span class="wa-menu-num">+56 9 3749 0214</span>
		</a>
		<a class="wa-menu-item" href="https://wa.me/56991327813?text=' . $msg . '" target="_blank" rel="nofollow">
			<span class="wa-menu-name">Leder Mejia</span>
			<span class="wa-menu-num">+56 9 9132 7813</span>
		</a>
	</div>
	<button type="button" class="wa-float" id="wa-float" aria-label="Abrir WhatsApp" aria-expanded="false">
		<svg viewBox="0 0 32 32" width="28" height="28" aria-hidden="true"><path fill="currentColor" d="M16 3C9.4 3 4 8.4 4 15c0 2.1.5 4.1 1.5 5.9L4 29l8.3-1.5c1.7.9 3.6 1.4 5.7 1.4 6.6 0 12-5.4 12-12S22.6 3 16 3zm0 22c-1.8 0-3.5-.5-5-1.3l-.4-.2-4.9 1 1-4.8-.2-.4A8.9 8.9 0 0 1 7 15c0-5 4-9 9-9s9 4 9 9-4 9-9 9zm4.9-6.7c-.3-.1-1.6-.8-1.9-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-1 .9-.2 0-.4 0-.7-.1-.3-.1-1.2-.4-2.3-1.4-.9-.8-1.5-1.7-1.7-2-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.1.1-.3 0-.4 0-.1-.7-1.7-1-2.3-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.4.1-.6.3-.2.2-.8.8-.8 2s.8 2.3.9 2.5c.1.2 1.6 2.5 3.9 3.4.5.2.9.3 1.2.4.5.2 1 .1 1.4-.1.4-.2 1.2-.5 1.4-1 .2-.5.2-.9.1-1-.1-.1-.3-.2-.6-.3z"/></svg>
	</button>
	</div>';
}

add_action('wp_footer', 'te_wa_widget_script', 99);
function te_wa_widget_script() {
	echo '<script>
	document.addEventListener("DOMContentLoaded", function(){
		var btn = document.getElementById("wa-float");
		var menu = document.getElementById("wa-menu");
		if (!btn || !menu) return;
		btn.addEventListener("click", function(e){
			e.stopPropagation();
			var open = menu.hidden;
			menu.hidden = !open;
			btn.setAttribute("aria-expanded", String(open));
		});
		document.addEventListener("click", function(e){
			var widget = document.getElementById("wa-widget");
			if (widget && widget.contains(e.target)) return;
			menu.hidden = true;
			btn.setAttribute("aria-expanded", "false");
		});
	});
	</script>';
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
	<a class="tuexhibidor-catalogo-btn" href="https://tuexhibidor.cl/wp-content/uploads/2026/07/catalogo_tuexhibidor.pdf" target="_blank" rel="noopener">Descargar Catalogo</a>
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
		var map = { "CADENAS Y COLLARES":"#catalogo", "ARETES Y ANILLOS":"#catalogo", "PULSERAS Y RELOJES":"#catalogo", "VITRINA":"#catalogo", "QUIENES SOMOS":"#nosotros", "QUIÉNES SOMOS":"#nosotros", "NOSOTROS":"#nosotros", "INICIO":"#inicio", "CATÁLOGO":"#catalogo", "CATALOGO":"#catalogo", "GALERÍA":"#galeria", "GALERIA":"#galeria", "CONTACTO":"#contacto", "A MEDIDA":"#medida" };
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
	$cat_links = array();
	foreach ( te_premium_shop_categories() as $slug => $name ) {
		$cat_links[] = array(
			'name' => $name,
			'url'  => home_url( '/product-category/' . $slug . '/' ),
		);
	}
	echo '<script>
	document.addEventListener("DOMContentLoaded", function(){
		var base = "/site/";
		var topMap = { "INICIO": base + "#inicio", "GALERÍA": base + "#galeria", "GALERIA": base + "#galeria", "QUIÉNES SOMOS": base + "#nosotros", "QUIENES SOMOS": base + "#nosotros", "NOSOTROS": base + "#nosotros", "CONTACTO": base + "#contacto", "A MEDIDA": base + "#medida", "TIENDA": "/shop/", "SHOP": "/shop/", "CATÁLOGO": "/shop/", "CATALOGO": "/shop/" };
		var cats = ' . wp_json_encode( $cat_links ) . ';
		var menu = document.querySelector(".main-menu > ul.nav") || document.querySelector(".main-menu ul");
		if (!menu) return;

		var catalogLi = null;
		menu.querySelectorAll(":scope > li").forEach(function(li){
			var a = li.querySelector(":scope > a");
			if (!a) return;
			var t = a.textContent.trim().toUpperCase();
			if (t === "CATÁLOGO" || t === "CATALOGO") catalogLi = li;
			if (topMap[t] && !li.classList.contains("te-cat-sub") && !li.querySelector(".sub-menu")) a.setAttribute("href", topMap[t]);
		});

		if (!catalogLi) {
			catalogLi = document.createElement("li");
			catalogLi.className = "menu-item menu-item-type-custom menu-item-has-children te-catalog-parent";
			var parentA = document.createElement("a");
			parentA.href = "/shop/";
			parentA.textContent = "Catálogo";
			catalogLi.appendChild(parentA);
			var after = menu.querySelector("li");
			if (after && after.nextSibling) menu.insertBefore(catalogLi, after.nextSibling);
			else menu.appendChild(catalogLi);
		} else {
			catalogLi.classList.add("menu-item-has-children", "te-catalog-parent");
			var pa = catalogLi.querySelector(":scope > a");
			if (pa) pa.setAttribute("href", "/shop/");
		}

		var sub = catalogLi.querySelector("ul.sub-menu");
		if (!sub) { sub = document.createElement("ul"); sub.className = "sub-menu"; catalogLi.appendChild(sub); }
		sub.innerHTML = "";
		cats.forEach(function(c){
			var li = document.createElement("li");
			li.className = "menu-item menu-item-type-custom te-cat-sub";
			var a = document.createElement("a");
			a.href = c.url;
			a.textContent = c.name;
			li.appendChild(a);
			sub.appendChild(li);
		});

		var mobileMenu = document.querySelector(".mobile-menu--content ul, .mobile-menu ul.nav");
		if (mobileMenu && !mobileMenu.querySelector(".te-cat-sub") && !mobileMenu.querySelector(".te-mobile-cats")) {
			var block = document.createElement("li");
			block.className = "te-mobile-cats";
			block.innerHTML = "<span class=\\"te-mobile-cats-label\\">Categorías</span>";
			cats.forEach(function(c){
				var li = document.createElement("li");
				li.className = "menu-item te-cat-sub";
				var a = document.createElement("a");
				a.href = c.url;
				a.textContent = c.name;
				li.appendChild(a);
				block.appendChild(li);
			});
			mobileMenu.appendChild(block);
		}
	});
	</script>';
}

add_action('wp_head', 'tuexhibidor_fonts');
function tuexhibidor_fonts(){
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
	echo '<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">';
}

add_action('wp_head', 'tuexhibidor_landing_css');
function tuexhibidor_landing_css(){
	echo '<style>
	:root{ --gold:#b8935f; --gold-dark:#96723f; --ink:#2b2926; --cream:#ebe3d8; --cream-warm:#e3d9cc; --surface:#ddd3c8; --muted:#8a8378; }
	html{ scroll-behavior:smooth; }
	body{ font-family:"Poppins",sans-serif; color:var(--ink); background:var(--cream); }
	h1,h2,h3,h4,h5,h6,.site-title,.site-title a{ font-family:"Poppins",sans-serif !important; letter-spacing:0; }
	.price,.woocommerce-Price-amount,.woocommerce-Price-currencySymbol{ display:none !important; }
	.cart-counter,.cart-icon,.lab-mini-cart,.cart-info,a.cart-counter,.widget_shopping_cart,[class*="mini-cart"]{ display:none !important; }
	a[href*="/cart/"], a[href*="/checkout/"]{ display:none !important; }
	.site-header{ background:rgba(235,227,216,.94) !important; backdrop-filter:blur(12px); box-shadow:none !important; border-bottom:1px solid rgba(184,147,95,.2) !important; }
	.top-menu.top-menu--dark,
	.top-menu--dark{
		background:rgba(235,227,216,.94) !important;
		background-color:rgba(235,227,216,.94) !important;
		border-bottom:none !important;
		box-shadow:none !important;
	}
	.top-menu--dark .social-networks a,
	.top-menu--dark .social-networks i{ color:var(--gold-dark) !important; }
	.top-menu .row{ justify-content:flex-end !important; }
	.top-menu .social-networks,
	.header-top-socials ul{ justify-content:flex-end !important; }
	.te-header-social,
	.top-menu .social-networks a.te-header-social{
		width:36px !important; height:36px !important;
		border-radius:50% !important;
		background:rgba(255,255,255,.55) !important;
		border:1px solid rgba(150,114,63,.28) !important;
		color:var(--ink) !important;
	}
	.te-header-social:hover,
	.top-menu .social-networks a.te-header-social:hover{
		background:var(--gold) !important;
		border-color:var(--gold) !important;
		color:#fff !important;
	}
	.site-header .logo img,
	.site-header #site-logo{
		height:52px !important;
		width:auto !important;
		max-width:none !important;
	}
	.site-header .logo-dimensions{ min-width:0 !important; width:auto !important; }
	.full-menu .menu-container > .logo{ display:none !important; }
	.te-search-btn,
	.search-btn.te-search-btn{
		display:inline-flex !important;
		align-items:center !important;
		justify-content:center !important;
		width:38px !important;
		height:38px !important;
		border-radius:50% !important;
		border:1px solid rgba(150,114,63,.25) !important;
		background:rgba(255,255,255,.45) !important;
		color:var(--ink) !important;
		padding:0 !important;
		transition:background .2s ease, color .2s ease, border-color .2s ease !important;
	}
	.te-search-btn:hover,
	.search-btn.te-search-btn:hover{
		background:var(--gold) !important;
		border-color:var(--gold) !important;
		color:#fff !important;
	}
	.te-search-btn svg,
	.search-btn.te-search-btn svg{ display:block !important; }
	.main-menu > ul > li > a{ font-weight:500; letter-spacing:0; text-transform:none; font-size:14px; color:var(--ink) !important; transition:color .25s ease, background .25s ease; cursor:pointer; padding:8px 14px; border-radius:999px; }
	.main-menu > ul > li > a:hover{ color:var(--gold) !important; }
	.vc_row{ border-radius:18px !important; overflow:hidden; scroll-margin-top:110px; }
	li.shop-item, li.product{ border-radius:16px !important; overflow:hidden; background:var(--surface,#ddd3c8); box-shadow:0 4px 18px rgba(43,41,38,.07) !important; border:1px solid rgba(184,147,95,.15) !important; transition:transform .35s ease, box-shadow .35s ease !important; }
	li.shop-item:hover, li.product:hover{ transform:translateY(-8px); box-shadow:0 18px 34px rgba(43,41,38,.14) !important; }
	li.shop-item img, li.product img{ border-radius:16px 16px 0 0 !important; }
	li.shop-item h3, li.product h3, .woocommerce-loop-product__title{ font-size:15px !important; padding:0 16px; margin-top:14px !important; margin-bottom:16px !important; }
	li.shop-item .product_cat, li.product .product_cat{ color:var(--gold-dark); text-transform:uppercase; letter-spacing:1px; font-size:11px; }
	.tuexhibidor-whatsapp-wrap{ display:flex; gap:12px; flex-wrap:wrap; margin:14px 0 18px; justify-content:center; }
	.tuexhibidor-wa-btn{
		display:inline-flex; align-items:center; justify-content:center;
		background:#25D366 !important; color:#fff !important;
		border-radius:999px !important; padding:12px 24px !important;
		text-decoration:none; font-size:14px; font-weight:500;
		border:2px solid transparent !important; box-shadow:none !important;
		transition:background .25s ease, transform .25s ease;
	}
	.tuexhibidor-wa-btn:hover{ background:#1ebe57 !important; transform:translateY(-2px); color:#fff !important; }
	.tuexhibidor-whatsapp-single{ justify-content:flex-start; margin-top:8px; }
	.wa-widget{ position:fixed; bottom:24px; right:24px; z-index:9999; }
	.wa-float{
		background:#25D366; color:#fff; width:58px; height:58px;
		border:none; border-radius:50%;
		display:flex; align-items:center; justify-content:center;
		box-shadow:0 6px 22px rgba(37,211,102,.45);
		cursor:pointer; transition:transform .2s, box-shadow .2s;
	}
	.wa-float:hover{ transform:scale(1.06); box-shadow:0 8px 28px rgba(37,211,102,.55); }
	.wa-menu{
		position:absolute; bottom:70px; right:0;
		width:min(280px, calc(100vw - 48px));
		background:var(--surface,#ddd3c8); border-radius:16px;
		box-shadow:0 12px 40px rgba(43,41,38,.18);
		border:1px solid rgba(184,147,95,.2);
		padding:16px;
	}
	.wa-menu[hidden]{ display:none; }
	.wa-menu-title{
		font-family:"Poppins",sans-serif;
		font-size:1rem; color:var(--ink); margin:0 0 2px;
	}
	.wa-menu-sub{ font-size:12px; color:var(--muted); margin:0 0 12px; }
	.wa-menu-item{
		display:flex; flex-direction:column; gap:2px;
		padding:12px 14px; border-radius:10px;
		text-decoration:none; color:var(--ink);
		border:1px solid rgba(184,147,95,.15);
		margin-bottom:8px; transition:.2s;
	}
	.wa-menu-item:last-child{ margin-bottom:0; }
	.wa-menu-item:hover{
		background:rgba(37,211,102,.08);
		border-color:#25D366;
	}
	.wa-menu-name{ font-weight:600; font-size:14px; }
	.wa-menu-num{ font-size:12px; color:var(--muted); }
	a.button, .wpb_button, .vc_btn3{ border-radius:30px !important; letter-spacing:1px !important; text-transform:uppercase; font-weight:500 !important; transition:all .3s ease !important; }
	.site-footer{ background:var(--cream-warm) !important; color:var(--muted) !important; border-top:1px solid rgba(184,147,95,.25); }
	.site-footer a{ color:var(--gold-dark) !important; }
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
	</style>';
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
	h1,h2,h3,h4,h5,h6,.site-title,.site-title a,.widget-title,.footer-widget-title{ font-family:"Poppins",sans-serif !important; }
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

	/* Tamano uniforme de imagenes en landing (WooCommerce lo maneja te_unified_premium_skin) */
	body:not(.woocommerce):not(.woocommerce-page) li.product img,
	body:not(.woocommerce):not(.woocommerce-page) li.shop-item img{
		aspect-ratio:1/1 !important; object-fit:contain !important; width:100% !important; height:auto !important;
		background:var(--surface,#ddd3c8) !important; padding:10px !important; box-sizing:border-box !important;
	}

	/* Transiciones suaves al hacer scroll, en todo el sitio */
	.tuexhibidor-reveal{ opacity:0; transform:translateY(24px); transition:opacity .7s ease, transform .7s ease; }
	.tuexhibidor-reveal.tuexhibidor-visible{ opacity:1; transform:translateY(0); }
	</style>';
}

// Animacion de aparicion al hacer scroll (landing — no tienda WooCommerce)
add_action('wp_footer', 'tuexhibidor_scroll_reveal');
function tuexhibidor_scroll_reveal(){
	if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_shop() || is_product_taxonomy() || is_product() ) ) {
		return;
	}
	echo '<script>
	document.addEventListener("DOMContentLoaded", function(){
		var targets = document.querySelectorAll(".vc_row, .tuexhibidor-section");
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
	h1,h2,h4,h5,h6,.site-title,.site-title a,.widget-title,.footer-widget-title,.page-title,.woocommerce-products-header__title{ font-family:"Poppins",sans-serif !important; font-weight:600 !important; }

	/* Ritmo de fondos unificado: crema / blanco, con un solo cierre oscuro */
	body{ background:var(--cream) !important; }
	#catalogo{ background:transparent !important; }
	#grid-productos{ background:var(--cream) !important; }
	.tuexhibidor-galeria{ background:#fff !important; }
	.tuexhibidor-somos{ background:var(--cream) !important; }
	.tuexhibidor-contacto{ background:linear-gradient(180deg,#2b2926,#1c1a18) !important; }
	.site-footer{ background:var(--cream-warm) !important; }

	/* Divisor y bloque instagram dentro de contacto */
	.tuexhibidor-contacto-divider{ width:60px; height:1px; background:rgba(255,255,255,.2); margin:38px auto 26px; }
	.tuexhibidor-ig-label{ color:#d8cfc2 !important; margin-bottom:14px !important; font-size:14px; }

	/* Limpieza: quitar wishlist (no aplica sin carrito) */
	.yith-wcwl-add-to-wishlist, a.add_to_wishlist{ display:none !important; }

	/* Encabezados de categoria/archivo mas prolijos */
	.woocommerce-products-header{ padding:40px 0 10px; }
	</style>';
}

// SEO: títulos, meta description, Open Graph, schema y sitemap
add_filter( 'pre_get_document_title', 'te_seo_document_title', 5 );
function te_seo_document_title( $title ) {
	if ( function_exists( 'is_product' ) && is_product() ) {
		global $post;
		$name = $post ? te_sentence( get_the_title( $post ) ) : $title;
		return $name . ' | Exhibidores joyería | Tu Exhibidor';
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return 'Tienda de exhibidores para joyería en Chile | Tu Exhibidor';
	}
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		$term = get_queried_object();
		if ( $term && isset( $term->slug ) ) {
			$map = te_seo_category_meta();
			if ( isset( $map[ $term->slug ]['title'] ) ) {
				return $map[ $term->slug ]['title'] . ' | Tu Exhibidor';
			}
			return te_sentence( $term->name ) . ' | Exhibidores para joyería | Tu Exhibidor';
		}
	}
	return $title;
}

add_filter( 'woocommerce_page_title', function ( $title ) {
	if ( is_shop() ) {
		return 'Exhibidores para joyería';
	}
	return $title;
} );

function te_seo_default_description(): string {
	return 'Exhibidores en ecocuero premium para joyerías en Chile. Bustos, bandejas, sets vitrina y fabricación a medida. +20 años. Cotiza directo con el taller.';
}

function te_seo_category_meta(): array {
	return array(
		'anillos'            => array(
			'title' => 'Exhibidores para anillos',
			'desc'  => 'Bandejas, cilindros y sets exhibidores para anillos en ecocuero. Diseños para vitrinas de joyería en Chile.',
		),
		'collares-cadenas'   => array(
			'title' => 'Exhibidores para collares y cadenas',
			'desc'  => 'Bustos, pecheras y soportes para collares y cadenas. Fabricación chilena para joyerías y boutiques.',
		),
		'aros-zarcillos'     => array(
			'title' => 'Exhibidores para aros y zarcillos',
			'desc'  => 'Soportes y bandejas para aros colgantes y zarcillos. Exhibidores en ecocuero hechos en Chile.',
		),
		'pulseras-relojes'   => array(
			'title' => 'Exhibidores para pulseras y relojes',
			'desc'  => 'Soportes, medias lunas y exhibidores para pulseras y relojes. Soluciones para vitrinas de joyería.',
		),
		'bandejas-bases'     => array(
			'title' => 'Bandejas y bases para joyería',
			'desc'  => 'Bandejas planas, con ranuras y ganchos para joyas varias. Exhibidores en ecocuero premium.',
		),
		'dijes-charms'       => array(
			'title' => 'Exhibidores para dijes y charms',
			'desc'  => 'Bandejas y soportes para dijes, charms y piezas pequeñas. Fabricación chilena Tu Exhibidor.',
		),
		'sets-vitrina'       => array(
			'title' => 'Sets vitrina para joyería',
			'desc'  => 'Sets modulares en ecocuero para equipar vitrinas completas. Exhibidores coordinados hechos en Chile.',
		),
	);
}

function te_seo_current_description(): string {
	if ( function_exists( 'is_product' ) && is_product() ) {
		global $post;
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post ) : null;
		if ( $product ) {
			$short = trim( wp_strip_all_tags( $product->get_short_description() ) );
			if ( $short ) {
				return wp_trim_words( $short, 28, '…' );
			}
			$name = te_sentence( $product->get_name() );
			return $name . '. Exhibidor para joyería en ecocuero. Fabricación chilena — cotiza en Tu Exhibidor.';
		}
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return 'Catálogo de exhibidores para joyería en Chile: anillos, collares, aros, pulseras, bandejas y sets vitrina en ecocuero. Cotiza por WhatsApp.';
	}
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		$term = get_queried_object();
		if ( $term && isset( $term->slug ) ) {
			$map = te_seo_category_meta();
			if ( isset( $map[ $term->slug ]['desc'] ) ) {
				return $map[ $term->slug ]['desc'];
			}
		}
	}
	return te_seo_default_description();
}

function te_seo_canonical_url() {
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return wc_get_page_permalink( 'shop' );
	}
	if ( is_tax() ) {
		$term = get_queried_object();
		return $term ? get_term_link( $term ) : home_url( '/' );
	}
	return home_url( '/' );
}

function te_seo_og_image(): string {
	if ( function_exists( 'is_product' ) && is_product() ) {
		global $post;
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post ) : null;
		if ( $product ) {
			$id = $product->get_image_id();
			if ( $id ) {
				$url = wp_get_attachment_image_url( $id, 'large' );
				if ( $url ) {
					return $url;
				}
			}
		}
	}
	return 'https://tuexhibidor.cl/public/images/hero/hero-slide-01-1200.jpg';
}

add_action( 'wp_head', 'te_seo_head_tags', 1 );
function te_seo_head_tags() {
	if ( is_admin() ) {
		return;
	}
	$desc      = te_seo_current_description();
	$canonical = te_seo_canonical_url();
	$og_image  = te_seo_og_image();
	$title     = wp_get_document_title();
	$url       = ( is_string( $canonical ) && ! is_wp_error( $canonical ) ) ? $canonical : home_url( '/' );

	echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta property="og:locale" content="es_CL">' . "\n";
	echo '<meta property="og:site_name" content="Tu Exhibidor">' . "\n";
	echo '<meta property="og:type" content="' . ( function_exists( 'is_product' ) && is_product() ? 'product' : 'website' ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:image" content="' . esc_url( $og_image ) . '">' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	echo '<meta name="twitter:image" content="' . esc_url( $og_image ) . '">' . "\n";
	te_seo_json_ld( $desc, $url, $og_image );
}

function te_seo_json_ld( string $desc, string $url, string $image ): void {
	$graph = array(
		array(
			'@type'       => 'Organization',
			'@id'         => 'https://tuexhibidor.cl/#organization',
			'name'        => 'Tu Exhibidor',
			'url'         => 'https://tuexhibidor.cl/',
			'logo'        => 'https://tuexhibidor.cl/public/images/brand/logo-tuexhibidor-ink-96.png',
			'description' => te_seo_default_description(),
			'email'       => 'info@tuexhibidor.cl',
			'areaServed'  => array( '@type' => 'Country', 'name' => 'Chile' ),
			'sameAs'      => array(
				'https://www.instagram.com/tuexhibidor/',
				'https://www.facebook.com/tuexhibidor.cl/',
			),
		),
		array(
			'@type'              => 'Store',
			'@id'                => 'https://tuexhibidor.cl/#store',
			'name'               => 'Tu Exhibidor',
			'url'                => 'https://tuexhibidor.cl/',
			'image'              => $image,
			'description'        => $desc,
			'priceRange'         => '$$',
			'address'            => array(
				'@type'          => 'PostalAddress',
				'addressCountry' => 'CL',
			),
			'parentOrganization' => array( '@id' => 'https://tuexhibidor.cl/#organization' ),
		),
	);
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		$graph[] = array(
			'@type'           => 'WebSite',
			'@id'             => 'https://tuexhibidor.cl/#website',
			'url'             => 'https://tuexhibidor.cl/',
			'name'            => 'Tu Exhibidor',
			'inLanguage'      => 'es-CL',
			'publisher'       => array( '@id' => 'https://tuexhibidor.cl/#organization' ),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => 'https://tuexhibidor.cl/shop/?s={search_term_string}',
				),
				'query-input' => 'required name=search_term_string',
			),
		);
	}
	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

add_filter( 'get_the_archive_description', 'te_seo_term_archive_description' );
function te_seo_term_archive_description( $desc ) {
	if ( ! function_exists( 'is_product_taxonomy' ) || ! is_product_taxonomy() ) {
		return $desc;
	}
	if ( $desc ) {
		return $desc;
	}
	$term = get_queried_object();
	if ( ! $term || ! isset( $term->slug ) ) {
		return $desc;
	}
	$map = te_seo_category_meta();
	if ( isset( $map[ $term->slug ]['desc'] ) ) {
		return '<p>' . esc_html( $map[ $term->slug ]['desc'] ) . '</p>';
	}
	return $desc;
}

add_filter( 'robots_txt', 'te_seo_robots_txt', 20 );
function te_seo_robots_txt( $output ) {
	if ( strpos( $output, 'sitemap-site.xml' ) === false ) {
		$output .= "\nSitemap: https://tuexhibidor.cl/sitemap-site.xml\n";
	}
	return $output;
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
<a href="https://tuexhibidor.cl/wp-content/uploads/2026/07/catalogo_tuexhibidor.pdf" target="_blank" rel="noopener" class="tuexhibidor-catalogo-btn">Descargar Cat\u{00e1}logo</a>
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
    .tuexhibidor-valor-num{ font-family:'Poppins',sans-serif; color:var(--gold); font-size:1.1rem; font-weight:600; }
    .tuexhibidor-valor-txt{ font-family:'Poppins',sans-serif; letter-spacing:0.08em; text-transform:uppercase; font-size:0.8rem; color:var(--ink); }

    /* Imagenes de producto WooCommerce: ver te_unified_premium_skin + te_single_product_layout_css */

    .cart-contents, .site-header .cart, a.cart-icon, .header-cart, .woocommerce-mini-cart, .mini-cart, .cart-count{ display:none !important; }

    .woocommerce-tabs ul.tabs li.reviews_tab, #reviews, .woocommerce-Reviews{ display:none !important; }
    </style>
    <?php
}, 200);


/* == TE-TRADUCCIONES == */
add_filter( 'locale', 'te_force_locale_es_cl', 1 );
function te_force_locale_es_cl( $locale ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $locale;
	}
	return 'es_CL';
}

add_filter( 'gettext', 'te_traducciones_gettext', 99, 3 );
function te_traducciones_gettext( $translated, $text, $domain ) {
	$map = array(
		'Home'                         => 'Inicio',
		'Shop'                         => 'Catálogo',
		'Search'                       => 'Buscar',
		'Cart'                         => 'Carrito',
		'Checkout'                     => 'Finalizar compra',
		'Wishlist'                     => 'Lista de deseos',
		'My account'                   => 'Mi cuenta',
		'Account'                      => 'Cuenta',
		'Next'                         => 'Siguiente',
		'Previous'                     => 'Anterior',
		'Next &raquo;'                 => 'Siguiente &raquo;',
		'&laquo; Previous'             => '&laquo; Anterior',
		'&raquo;'                      => '&raquo;',
		'Default sorting'              => 'Orden predeterminado',
		'Sort by popularity'           => 'Ordenar por popularidad',
		'Sort by average rating'       => 'Ordenar por calificación media',
		'Sort by latest'               => 'Ordenar por los últimos',
		'Sort by price: low to high'   => 'Ordenar por precio: bajo a alto',
		'Sort by price: high to low'   => 'Ordenar por precio: alto a bajo',
		'Showing the single result'    => 'Mostrando el único resultado',
		'Showing all %d results'     => 'Mostrando los %d resultados',
		'Loading cart contents...'     => 'Cargando carrito...',
		'No products found'            => 'No se encontraron productos',
		'No products were found matching your selection.' => 'No hay productos que coincidan con tu búsqueda.',
		'Return to shop'               => 'Volver al catálogo',
		'Read more'                    => 'Ver más',
		'Select options'               => 'Ver opciones',
		'View cart'                    => 'Ver carrito',
		'Add to cart'                  => 'Consultar',
		'Description'                  => 'Descripción',
		'Additional information'       => 'Información adicional',
		'Reviews'                      => 'Valoraciones',
		'Related products'             => 'Productos relacionados',
		'Share this item:'             => 'Compartir:',
		'Share this item'              => 'Compartir',
		'Share item:'                  => 'Compartir:',
		'Share item'                   => 'Compartir',
		'Share:'                       => 'Compartir:',
		'Share'                        => 'Compartir',
		'Category:'                    => 'Categoría:',
		'Categories:'                  => 'Categorías:',
		'Tag:'                         => 'Etiqueta:',
		'Tags:'                        => 'Etiquetas:',
		'SKU:'                         => 'Código:',
		'Page not found'               => 'Página no encontrada',
		'Page not found!'              => '¡Página no encontrada!',
		'Error 404'                    => 'Error 404',
		'Oops! That page can&rsquo;t be found.' => 'Esa página no existe.',
		'It looks like nothing was found at this location. Maybe try a search?' => 'No encontramos nada aquí. Prueba con el buscador.',
		'All'                          => 'Todos',
		'View'                         => 'Ver',
		'Close'                        => 'Cerrar',
		'Menu'                         => 'Menú',
		'Follow us'                    => 'Síguenos',
		'Follow Us'                    => 'Síguenos',
		'Sale!'                        => '¡Oferta!',
		'New'                          => 'Nuevo',
		'Out of stock'                 => 'Agotado',
		'In stock'                     => 'Disponible',
	);
	if ( isset( $map[ $text ] ) ) {
		return $map[ $text ];
	}
	return $translated;
}

add_filter( 'ngettext', 'te_traducciones_ngettext', 99, 5 );
function te_traducciones_ngettext( $translated, $single, $plural, $number, $domain ) {
	if ( $single === 'Category:' ) {
		return ( $number > 1 ) ? 'Categorías:' : 'Categoría:';
	}
	if ( $single === 'Tag:' ) {
		return ( $number > 1 ) ? 'Etiquetas:' : 'Etiqueta:';
	}
	if ( $single === 'Showing all %d result' && $plural === 'Showing all %d results' ) {
		return ( $number > 1 ) ? 'Mostrando los %d resultados' : 'Mostrando el único resultado';
	}
	if ( $single === 'Showing the single result' ) {
		return 'Mostrando el único resultado';
	}
	if ( strpos( $single, 'Showing %1$d&ndash;%2$d of %3$d result' ) === 0 ) {
		return ( $number > 1 )
			? 'Mostrando %1$d&ndash;%2$d de %3$d resultados'
			: 'Mostrando %1$d&ndash;%2$d de %3$d resultado';
	}
	return $translated;
}

add_filter( 'woocommerce_pagination_args', function( $args ) {
	$args['prev_text'] = '&laquo; Anterior';
	$args['next_text'] = 'Siguiente &raquo;';
	return $args;
} );

add_filter( 'woocommerce_get_breadcrumb', function( $crumbs ) {
	foreach ( $crumbs as $i => $crumb ) {
		if ( isset( $crumb[0] ) && in_array( $crumb[0], array( 'Shop', 'Catálogo' ), true ) && $i > 0 ) {
			continue;
		}
		if ( isset( $crumb[0] ) && $crumb[0] === 'Shop' ) {
			$crumbs[ $i ][0] = 'Catálogo';
		}
	}
	return $crumbs;
}, 20 );

add_action( 'wp_head', 'te_hide_english_chrome', 5 );
function te_hide_english_chrome() {
	echo '<style>
	.site-footer > .container,
	.site-footer .footer-widgets,
	.site-footer .copyright,
	.site-footer .footer-bottom,
	.site-footer .widget,
	.mini-cart, .cart-contents, .header-cart, a.cart-icon,
	a[href*="/cart"], a[href*="/checkout"], .wishlist-link,
	.yith-wcwl-add-to-wishlist, .add_to_wishlist,
	.woocommerce-mini-cart, .widget_shopping_cart{ display:none !important; }
	</style>';
}

add_filter( 'woocommerce_structured_data_breadcrumb', function( $markup ) {
	if ( ! is_array( $markup ) ) {
		return $markup;
	}
	foreach ( $markup as $key => $item ) {
		if ( isset( $item['item']['name'] ) && $item['item']['name'] === 'Shop' ) {
			$markup[ $key ]['item']['name'] = 'Catálogo';
		}
	}
	return $markup;
}, 20 );
/* == fin TE-TRADUCCIONES == */


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
	$on_shop = function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_shop() || is_product_taxonomy() || is_product() );
	$base    = trailingslashit( home_url( '/site' ) );
	foreach ( $items as $it ) {
		if ( ! empty( $it->menu_item_parent ) || in_array( 'te-cat-sub', (array) $it->classes, true ) ) {
			continue;
		}
		$t = mb_strtoupper( trim( strip_tags( $it->title ) ) );
		if ( 'CATÁLOGO' === $t || 'CATALOGO' === $t ) {
			$it->url = $on_shop ? home_url( '/shop/' ) : $base . '#catalogo';
		} elseif ( 'GALERÍA' === $t || 'GALERIA' === $t ) {
			$it->url = $base . '#galeria';
		} elseif ( in_array( $t, array( 'QUIÉNES SOMOS', 'QUIENES SOMOS', 'NOSOTROS' ), true ) ) {
			$it->url = $base . '#nosotros';
		} elseif ( 'CONTACTO' === $t ) {
			$it->url = $base . '#contacto';
		} elseif ( 'INICIO' === $t || 'HOME' === $t ) {
			$it->url = $base . '#inicio';
		} elseif ( 'A MEDIDA' === $t ) {
			$it->url = $base . '#medida';
		} elseif ( 'TIENDA' === $t || 'SHOP' === $t ) {
			$it->url = home_url( '/shop/' );
		}
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


/* == TE-CAT-SUBMENU: mismas categorías que el sitio premium == */
function te_premium_shop_categories(): array {
	return array(
		'collares-cadenas' => 'Collares & Cadenas',
		'pulseras-relojes' => 'Pulseras & Relojes',
		'anillos'          => 'Anillos',
		'aros-zarcillos'   => 'Aros & Zarcillos',
		'bandejas-bases'   => 'Bandejas & Bases',
		'dijes-charms'     => 'Dijes & Charms',
		'sets-vitrina'     => 'Sets Vitrina Modular',
	);
}

/** Mapeo categorías WooCommerce antiguas → nombres premium (sin tocar productos). */
function te_legacy_category_map(): array {
	return array(
		'aretes-y-anillos'   => 'aros-zarcillos',
		'cadenas-y-collares' => 'collares-cadenas',
		'pulseras-y-relojes' => 'pulseras-relojes',
		'vitrina'            => 'bandejas-bases',
	);
}

add_action( 'init', 'te_ensure_premium_categories', 22 );
function te_ensure_premium_categories(): void {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}
	foreach ( te_premium_shop_categories() as $slug => $name ) {
		$existing = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $existing ) {
			wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
		} elseif ( $existing->name !== $name ) {
			wp_update_term( (int) $existing->term_id, 'product_cat', array( 'name' => $name ) );
		}
	}
}

add_action( 'init', 'te_migrate_legacy_categories', 23 );
function te_migrate_legacy_categories(): void {
	if ( '2026-07-07-v2' === get_option( 'te_legacy_cat_migrated' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	te_ensure_premium_categories();

	foreach ( te_legacy_category_map() as $old_slug => $new_slug ) {
		$old_term = get_term_by( 'slug', $old_slug, 'product_cat' );
		$new_term = get_term_by( 'slug', $new_slug, 'product_cat' );
		if ( ! $old_term || ! $new_term ) {
			continue;
		}

		$product_ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'tax_query'      => array(
					array(
						'taxonomy' => 'product_cat',
						'field'    => 'term_id',
						'terms'    => (int) $old_term->term_id,
					),
				),
			)
		);

		foreach ( $product_ids as $product_id ) {
			$term_ids = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
			if ( is_wp_error( $term_ids ) ) {
				continue;
			}
			$term_ids   = array_map( 'intval', (array) $term_ids );
			$term_ids   = array_values( array_diff( $term_ids, array( (int) $old_term->term_id ) ) );
			$term_ids[] = (int) $new_term->term_id;
			wp_set_object_terms( $product_id, array_values( array_unique( $term_ids ) ), 'product_cat' );
		}
	}

	update_option( 'te_legacy_cat_migrated', '2026-07-07-v2' );
}

add_action( 'template_redirect', 'te_legacy_category_redirect', 1 );
function te_legacy_category_redirect(): void {
	$map = te_legacy_category_map();
	if ( ! $map ) {
		return;
	}

	if ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term && ! empty( $term->slug ) && isset( $map[ $term->slug ] ) ) {
			wp_safe_redirect( home_url( '/product-category/' . $map[ $term->slug ] . '/' ), 301 );
			exit;
		}
		return;
	}

	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = trim( (string) parse_url( $path, PHP_URL_PATH ), '/' );
	if ( preg_match( '#^(?:shop/)?product-category/([^/]+)/?$#', $path, $matches ) ) {
		$slug = sanitize_title( $matches[1] );
		if ( isset( $map[ $slug ] ) ) {
			wp_safe_redirect( home_url( '/product-category/' . $map[ $slug ] . '/' ), 301 );
			exit;
		}
	}
}

add_filter( 'get_the_terms', 'te_premium_category_labels', 20, 3 );
function te_premium_category_labels( $terms, $post_id, $taxonomy ) {
	if ( 'product_cat' !== $taxonomy || ! is_array( $terms ) ) {
		return $terms;
	}
	$labels = te_premium_shop_categories();
	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term && isset( $labels[ $term->slug ] ) ) {
			$term->name = $labels[ $term->slug ];
		}
	}
	return $terms;
}

add_filter( 'woocommerce_product_categories', 'te_premium_wc_category_labels', 20 );
function te_premium_wc_category_labels( $categories ) {
	if ( ! is_array( $categories ) ) {
		return $categories;
	}
	$labels = te_premium_shop_categories();
	foreach ( $categories as $cat ) {
		if ( isset( $cat->slug, $labels[ $cat->slug ] ) ) {
			$cat->name = $labels[ $cat->slug ];
		}
	}
	return $categories;
}

add_filter( 'wp_nav_menu_objects', 'te_cat_submenu', 30, 2 );
function te_cat_submenu( $items, $args ) {
	$on_shop   = function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_shop() || is_product_taxonomy() || is_product() );
	$cat_id    = 0;
	$cat_index = null;

	foreach ( $items as $idx => $it ) {
		$t = mb_strtoupper( trim( strip_tags( $it->title ) ) );
		if ( 'CATÁLOGO' === $t || 'CATALOGO' === $t ) {
			$cat_id    = (int) $it->ID;
			$cat_index = $idx;
			break;
		}
	}

	if ( ! $cat_id ) {
		$cat_id = 80000;
		$parent = new stdClass();
		$parent->ID               = $cat_id;
		$parent->db_id            = $cat_id;
		$parent->menu_item_parent = 0;
		$parent->object_id        = $cat_id;
		$parent->title            = 'Catálogo';
		$parent->url              = $on_shop ? home_url( '/shop/' ) : te_site_base_url() . '#catalogo';
		$parent->type             = 'custom';
		$parent->object           = 'custom';
		$parent->target           = '';
		$parent->attr_title       = '';
		$parent->description      = '';
		$parent->xfn              = '';
		$parent->classes          = array( 'menu-item', 'menu-item-type-custom', 'menu-item-has-children', 'te-catalog-parent' );
		$parent->current          = $on_shop && ( is_shop() || is_product_taxonomy() );
		$parent->current_item_ancestor = is_product();
		$parent->current_item_parent   = false;

		$insert_at = 1;
		foreach ( $items as $i => $it ) {
			$t = mb_strtoupper( trim( strip_tags( $it->title ) ) );
			if ( 'INICIO' === $t || 'HOME' === $t ) {
				$insert_at = $i + 1;
				break;
			}
		}
		array_splice( $items, $insert_at, 0, array( $parent ) );
	}

	$items = array_values(
		array_filter(
			$items,
			function ( $it ) use ( $cat_id ) {
				return (int) $it->menu_item_parent !== $cat_id || ! in_array( 'te-cat-sub', (array) $it->classes, true );
			}
		)
	);

	foreach ( $items as $it ) {
		if ( (int) $it->ID === $cat_id ) {
			if ( ! in_array( 'menu-item-has-children', (array) $it->classes, true ) ) {
				$it->classes[] = 'menu-item-has-children';
			}
			$it->classes[] = 'te-catalog-parent';
			if ( $on_shop ) {
				$it->url = home_url( '/shop/' );
			}
		}
	}

	$shop_base = trailingslashit( home_url( '/product-category' ) );
	$i         = 90000;
	foreach ( te_premium_shop_categories() as $slug => $name ) {
		$o                          = new stdClass();
		$o->ID                      = $i;
		$o->db_id                   = $i;
		$o->menu_item_parent        = $cat_id;
		$o->object_id               = $i;
		$o->title                   = $name;
		$o->url                     = $shop_base . $slug . '/';
		$o->type                    = 'custom';
		$o->object                  = 'custom';
		$o->target                  = '';
		$o->attr_title              = '';
		$o->description             = '';
		$o->xfn                     = '';
		$o->classes                 = array( 'menu-item', 'menu-item-type-custom', 'te-cat-sub' );
		$o->current                 = is_product_taxonomy() && get_queried_object() && get_queried_object()->slug === $slug;
		$o->current_item_ancestor   = false;
		$o->current_item_parent     = false;
		$items[]                    = $o;
		++$i;
	}
	return $items;
}
/* == fin TE-CAT-SUBMENU == */


/* == TE-SHOP-CAT-FILTER: categorías en vez de ordenar por precio/popularidad == */
add_action( 'wp_loaded', 'te_replace_shop_ordering' );
function te_replace_shop_ordering() {
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
}

function te_shop_category_filter() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return;
	}
	if ( ! is_shop() && ! is_product_taxonomy() ) {
		return;
	}

	$categories   = te_premium_shop_categories();
	$shop_base    = trailingslashit( home_url( '/product-category' ) );
	$current_slug = '';
	if ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$current_slug = $term->slug;
		}
	}

	echo '<nav class="te-shop-cat-filter" aria-label="Filtrar por categoría">';
	$all_active = is_shop() && ! is_product_category() ? ' is-active' : '';
	printf(
		'<a class="te-shop-cat-chip%s" href="%s">%s</a>',
		esc_attr( $all_active ),
		esc_url( home_url( '/shop/' ) ),
		esc_html__( 'Todos', 'aurum' )
	);
	foreach ( $categories as $slug => $name ) {
		$active = ( $current_slug === $slug ) ? ' is-active' : '';
		$term   = get_term_by( 'slug', $slug, 'product_cat' );
		$count  = $term instanceof WP_Term ? (int) $term->count : 0;
		printf(
			'<a class="te-shop-cat-chip%s" href="%s"><span class="te-shop-cat-chip__label">%s</span>',
			esc_attr( $active ),
			esc_url( $shop_base . $slug . '/' ),
			esc_html( $name )
		);
		if ( $count > 0 ) {
			printf( '<span class="te-shop-cat-count">%d</span>', $count );
		}
		echo '</a>';
	}
	echo '</nav>';
}
/* == fin TE-SHOP-CAT-FILTER == */


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


add_action('wp_head', 'te_premium_footer_styles', 216);
function te_premium_footer_styles(){
    ?>
    <style id="te-premium-footer-styles">
    .site-footer{ padding:0 !important; text-align:center !important; }
    .te-premium-footer{
        padding:28px 24px 32px;
        max-width:1180px;
        margin:0 auto;
        font-size:13px;
        line-height:1.6;
        color:var(--muted,#8a8378);
    }
    .te-premium-footer .footer-logo{ margin:0 auto 14px; display:block; opacity:.92; }
    .te-premium-footer .social-links{
        display:flex; align-items:center; justify-content:center; gap:14px; margin:0 0 14px;
    }
    .te-premium-footer .social-link{
        display:inline-flex; align-items:center; justify-content:center;
        width:44px; height:44px; border-radius:50%;
        color:var(--gold-dark,#96723f); background:rgba(184,147,95,.12);
        border:1px solid rgba(184,147,95,.28);
        transition:background .25s, color .25s, transform .25s;
    }
    .te-premium-footer .social-link:hover{
        background:var(--gold,#b8935f); color:#fff; transform:translateY(-2px);
    }
    .te-premium-footer p{ margin:0 0 6px; }
    .te-premium-footer a{ color:var(--gold-dark,#96723f); text-decoration:none; }
    .te-premium-footer a:hover{ color:var(--gold,#b8935f); }
    .te-premium-footer .footer-credit{ margin-top:12px; font-size:12px; opacity:.85; }
    .te-premium-footer .footer-catalog{ margin:0 0 14px; }
    .te-premium-footer .footer-catalog-btn{
        display:inline-block; padding:7px 16px; border-radius:999px;
        border:1px solid rgba(184,147,95,.45); color:var(--gold-dark,#96723f);
        background:rgba(255,255,255,.45); font-size:12px; font-weight:500;
        letter-spacing:.03em; text-decoration:none;
        transition:background .25s, color .25s, border-color .25s, transform .25s;
    }
    .te-premium-footer .footer-catalog-btn:hover{
        background:var(--gold,#b8935f); border-color:var(--gold,#b8935f);
        color:#fff; transform:translateY(-1px);
    }
    </style>
    <?php
}

function te_render_premium_footer_markup(): void {
	$logo = esc_url( home_url( '/public/images/brand/logo-tuexhibidor-gold-96.webp' ) );
	$pdf  = esc_url( home_url( '/wp-content/uploads/2026/07/catalogo_tuexhibidor.pdf' ) );
	echo '<div class="te-premium-footer">';
	echo '<img class="footer-logo" src="' . $logo . '" alt="Tu Exhibidor" width="40" height="40" loading="lazy">';
	echo te_social_links_html( 'social-links', 'social-link', 20 );
	echo '<p class="footer-catalog"><a class="footer-catalog-btn" href="' . $pdf . '" target="_blank" rel="noopener">Catálogo completo (PDF)</a></p>';
	echo '<p>Comercializadora Tu Exhibidor SPA · RUT 77.036.189-3</p>';
	echo '<p><a href="mailto:info@tuexhibidor.cl">info@tuexhibidor.cl</a> · WhatsApp +56 9 3749 0214 / +56 9 9132 7813</p>';
	echo '<p class="footer-credit">Desarrollado por <a href="https://tecnotix.cl" target="_blank" rel="noopener noreferrer">Tecnotix Solutions</a></p>';
	echo '</div>';
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


/* == TE-FOOTER-DEMO: footer limpio sin widgets demo Aurum == */
add_action( 'widgets_init', function () {
	unregister_sidebar( 'footer_sidebar_left' );
	unregister_sidebar( 'footer_sidebar_right' );
}, 20 );

add_filter( 'sidebars_widgets', 'te_strip_footer_demo_widgets', 20 );
function te_strip_footer_demo_widgets( $sidebars ) {
	if ( ! is_array( $sidebars ) ) {
		return $sidebars;
	}
	foreach ( $sidebars as $id => $widgets ) {
		if ( ! is_array( $widgets ) ) {
			continue;
		}
		if ( preg_match( '/footer/i', (string) $id ) ) {
			$sidebars[ $id ] = array();
		}
	}
	return $sidebars;
}

add_action( 'wp_head', 'te_strip_demo_footer_assets', 10002 );
function te_strip_demo_footer_assets() {
	echo '<style id="te-footer-clean">footer.site-footer{background-image:none!important;background-position:initial!important;}</style>';
}

add_action( 'wp_footer', 'te_pagination_spanish_labels', 5 );
function te_pagination_spanish_labels() {
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function(){
		document.querySelectorAll('.woocommerce-pagination a, .page-numbers a').forEach(function(a){
			if (a.textContent.trim() === 'Next »' || a.textContent.trim() === 'Next') a.textContent = 'Siguiente »';
			if (a.textContent.trim() === '« Previous' || a.textContent.trim() === 'Previous') a.textContent = '« Anterior';
		});
	});
	</script>
	<?php
}
/* == fin TE-FOOTER-DEMO == */


/* == TE-SECURITY-HEADERS (complementa mu-plugin) == */
add_action('send_headers', function(){
	if (headers_sent()) return;
	header('X-Content-Type-Options: nosniff');
	header('X-Frame-Options: SAMEORIGIN');
}, 11);
/* == fin TE-SECURITY-HEADERS == */


/* == TE-UNIFICADO: navegación + paleta sitio premium en WordPress == */
function te_site_base_url() {
	return trailingslashit( home_url( '/site' ) );
}

add_action( 'template_redirect', 'te_redirect_wp_home_to_static', 1 );
function te_redirect_wp_home_to_static() {
	if ( is_admin() || wp_doing_ajax() || is_customize_preview() ) {
		return;
	}
	if ( is_front_page() && ! is_paged() ) {
		wp_safe_redirect( te_site_base_url(), 302 );
		exit;
	}
}

add_filter( 'woocommerce_breadcrumb_defaults', 'te_unified_breadcrumbs' );
function te_unified_breadcrumbs( $defaults ) {
	$defaults['home'] = 'Inicio';
	$defaults['wrap_before'] = '<nav class="woocommerce-breadcrumb te-breadcrumb" aria-label="Breadcrumb">';
	return $defaults;
}

add_filter( 'woocommerce_get_breadcrumb', 'te_breadcrumb_home_link', 10, 2 );
function te_breadcrumb_home_link( $crumbs, $breadcrumb ) {
	if ( ! empty( $crumbs[0][0] ) ) {
		$crumbs[0][1] = te_site_base_url() . '#inicio';
	}
	return $crumbs;
}

/** Quita efectos hover Aurum que ocultan la imagen principal en la tienda. */
add_filter( 'woocommerce_post_class', 'te_disable_product_hover_fx', 20 );
function te_disable_product_hover_fx( $classes ) {
	return array_values(
		array_diff(
			$classes,
			array( 'hover-effect-1', 'hover-effect-2', 'hover-effect-zoom-over', 'image-slide' )
		)
	);
}

add_action( 'wp_head', 'te_unified_premium_skin', 3 );
function te_unified_premium_skin() {
	echo '<style>
	:root{
		--gold:#b8935f;
		--gold-dark:#96723f;
		--cream:#ebe3d8;
		--cream-warm:#e3d9cc;
		--surface:#ddd3c8;
		--img-well:#ddd3c8;
		--ink:#2b2926;
		--muted:#8a8378;
	}
	body{ background:var(--cream) !important; color:var(--ink); }
	.site-header, header.site-header, .header-wrapper{
		background:rgba(235,227,216,.94) !important;
		backdrop-filter:blur(12px);
		border-bottom:1px solid rgba(184,147,95,.2) !important;
		box-shadow:none !important;
	}
	.main-menu > ul > li > a,
	.top-menu a,
	.mobile-menu a{
		color:var(--ink) !important;
		font-family:"Poppins",sans-serif !important;
		font-weight:500;
		letter-spacing:0;
		text-transform:none !important;
		font-size:14px;
	}
	.main-menu .nav a,
	header.site-header .nav a,
	header.site-header div.nav>ul>li>a,
	.main-navigation a{
		text-transform:none !important;
		font-family:"Poppins",sans-serif !important;
	}
	.main-menu li.menu-item-has-children{ position:relative; }
	.main-menu li.menu-item-has-children > ul.sub-menu{
		display:none;
		position:absolute;
		top:calc(100% + 4px);
		left:50%;
		transform:translateX(-50%);
		min-width:220px;
		margin:0;
		padding:8px 0;
		list-style:none;
		background:#fff;
		border:1px solid rgba(184,147,95,.22);
		border-radius:12px;
		box-shadow:0 12px 28px rgba(43,41,38,.12);
		z-index:120;
	}
	.main-menu li.menu-item-has-children:hover > ul.sub-menu,
	.main-menu li.menu-item-has-children:focus-within > ul.sub-menu{ display:block; }
	.main-menu .sub-menu li{ margin:0; padding:0; }
	.main-menu .sub-menu a{
		display:block;
		padding:9px 16px;
		color:var(--ink) !important;
		font-size:13px !important;
		font-weight:500 !important;
		text-decoration:none !important;
		white-space:nowrap;
		background:transparent !important;
		border-radius:0 !important;
	}
	.te-mobile-cats-label{
		display:block;
		padding:12px 16px 6px;
		font-size:11px;
		letter-spacing:.12em;
		text-transform:uppercase;
		color:var(--muted,#8a8378);
		font-weight:600;
	}
	.main-menu > ul > li > a:hover,
	.top-menu a:hover{ background:var(--gold,#b8935f) !important; color:#fff !important; }
	.site-footer{
		background:var(--cream-warm) !important;
		color:var(--muted) !important;
		border-top:1px solid rgba(184,147,95,.25);
	}
	.site-footer a{ color:var(--gold-dark) !important; }
	.site-footer a:hover{ color:var(--gold) !important; }
	.woocommerce .content-area,
	.woocommerce-page .content-area,
	.woocommerce-products-header,
	.woocommerce-page .page-title,
	.archive .page-title{
		font-family:"Poppins",sans-serif !important;
		color:var(--ink);
	}
	.woocommerce ul.products,
	.woocommerce-page .woocommerce{ background:transparent !important; }
	.woocommerce-products-header{ text-align:center; padding:28px 20px 8px !important; }
	.woocommerce-products-header__title,
	.woocommerce-page .page-title{ font-size:clamp(1.6rem,3vw,2.2rem) !important; margin:0 !important; }
	.woocommerce-result-count,
	.woocommerce-ordering{ color:var(--muted) !important; }
	.woocommerce ul.products,
	.woocommerce-page ul.products{
		display:grid !important;
		grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)) !important;
		gap:20px !important;
		margin:0 !important;
		padding:20px 0 36px !important;
		clear:both !important;
		list-style:none !important;
	}
	.woocommerce ul.products::before,
	.woocommerce ul.products::after,
	.woocommerce-page ul.products::before,
	.woocommerce-page ul.products::after{ display:none !important; content:none !important; }
	.woocommerce ul.products li.product,
	.woocommerce ul.products li.shop-item,
	.woocommerce-page ul.products li.product,
	.woocommerce-page ul.products li.shop-item{
		width:100% !important;
		max-width:none !important;
		float:none !important;
		margin:0 !important;
		padding:0 !important;
		clear:none !important;
		display:flex !important;
		flex-direction:column !important;
	}
	li.product, li.shop-item{
		background:var(--img-well) !important;
		border-radius:16px !important;
		box-shadow:0 4px 18px rgba(43,41,38,.07) !important;
		border:1px solid rgba(184,147,95,.15) !important;
		overflow:hidden !important;
		transition:transform .3s ease, box-shadow .3s ease !important;
		isolation:isolate;
	}
	li.product:hover, li.shop-item:hover{
		transform:translateY(-4px);
		box-shadow:0 14px 32px rgba(43,41,38,.12) !important;
		z-index:2;
	}
	li.shop-item .item-image,
	li.product .product-item-image,
	li.product .thumb{
		overflow:hidden !important;
		border-radius:16px 16px 0 0 !important;
	}
	li.shop-item .item-info,
	li.product .product-item-details{
		background:var(--surface) !important;
		padding:14px 16px 18px !important;
		margin-top:auto !important;
		flex:1 1 auto !important;
		display:flex !important;
		flex-direction:column !important;
		justify-content:flex-start !important;
		min-height:92px !important;
	}
	li.shop-item .item-info h3,
	li.shop-item .item-info h3 a,
	li.product h3,
	li.product h3 a,
	.woocommerce-loop-product__title{
		background:transparent !important;
		padding:0 !important;
		margin:0 0 8px !important;
		font-size:14px !important;
		font-weight:500 !important;
		line-height:1.35 !important;
		min-height:2.7em !important;
		color:var(--ink) !important;
	}
	li.shop-item .product-terms,
	li.shop-item .product_cat,
	li.product .product_cat{
		display:block;
		color:var(--gold-dark) !important;
		text-transform:uppercase;
		letter-spacing:.08em;
		font-size:11px !important;
	}
	/* Aurum hover-effect-1: desactivar swap — imagen siempre visible */
	li.shop-item .item-image .image-placeholder.shop-image,
	li.product .item-image .shop-image,
	li.shop-item.hover-effect-1 .shop-image,
	li.shop-item.hover-effect-1:hover .shop-image{
		display:none !important;
		opacity:0 !important;
		visibility:hidden !important;
	}
	/* Pozos de imagen en loops: ver te_product_loop_image_css (prioridad 10006) */
	li.product .price, li.shop-item .price{ display:none !important; }
	.woocommerce-pagination,
	.woocommerce nav.woocommerce-pagination{
		margin:12px 0 48px !important;
		text-align:center !important;
	}
	.woocommerce-pagination ul,
	.woocommerce-pagination .pagination,
	nav.woocommerce-pagination ul{
		display:inline-flex !important;
		flex-wrap:wrap;
		align-items:center;
		justify-content:center;
		gap:10px !important;
		list-style:none !important;
		margin:0 !important;
		padding:0 !important;
		border:none !important;
		background:transparent !important;
	}
	.woocommerce-pagination ul li,
	.woocommerce-pagination .pagination li{
		margin:0 !important;
		padding:0 !important;
		border:none !important;
		background:transparent !important;
		float:none !important;
	}
	.woocommerce-pagination .page-numbers,
	.woocommerce-pagination ul li span,
	.woocommerce-pagination ul li a{
		display:inline-flex !important;
		align-items:center;
		justify-content:center;
		min-width:42px;
		height:42px;
		padding:0 16px !important;
		border-radius:999px !important;
		background:rgba(255,255,255,.35) !important;
		border:1px solid rgba(184,147,95,.35) !important;
		color:var(--gold-dark) !important;
		font-family:"Poppins",sans-serif !important;
		font-size:13px !important;
		font-weight:500 !important;
		line-height:1 !important;
		text-decoration:none !important;
		box-shadow:none !important;
		transition:background .2s ease, color .2s ease, border-color .2s ease;
	}
	.woocommerce-pagination .page-numbers.current,
	.woocommerce-pagination ul li span.current{
		background:var(--gold) !important;
		border-color:var(--gold) !important;
		color:#fff !important;
	}
	.woocommerce-pagination a.page-numbers:hover,
	.woocommerce-pagination ul li a:hover{
		background:var(--gold-dark) !important;
		border-color:var(--gold-dark) !important;
		color:#fff !important;
	}
	.woocommerce-ordering select,
	.woocommerce .orderby{
		border-radius:999px !important;
		border:1px solid rgba(184,147,95,.35) !important;
		background:rgba(255,255,255,.35) !important;
		color:var(--ink) !important;
		padding:10px 40px 10px 16px !important;
		font-family:"Poppins",sans-serif !important;
		font-size:13px !important;
		box-shadow:none !important;
	}
	.woocommerce div.product div.images img,
	.woocommerce div.product div.images .woocommerce-product-gallery__image{
		background:var(--img-well) !important;
		object-fit:contain !important;
		padding:16px !important;
		box-sizing:border-box !important;
		border-radius:16px !important;
		border:1px solid rgba(184,147,95,.15) !important;
	}
	.woocommerce div.product .product_title{ color:var(--ink) !important; }
	.woocommerce div.product .summary{ background:var(--surface); border-radius:16px; padding:24px; border:1px solid rgba(184,147,95,.15); }
	.woocommerce-breadcrumb{ color:var(--muted) !important; }
	.woocommerce-breadcrumb a{ color:var(--gold-dark) !important; }
	.te-breadcrumb{
		max-width:1180px;
		margin:0 auto;
		padding:12px 20px 0;
		font-size:13px;
		color:var(--muted);
	}
	.te-breadcrumb a{ color:var(--gold-dark); text-decoration:none; }
	.te-breadcrumb a:hover{ color:var(--gold); }
	.te-wp-back{
		display:inline-flex;
		align-items:center;
		gap:8px;
		margin:14px 20px 0;
		padding:8px 16px;
		border-radius:999px;
		background:rgba(255,255,255,.75);
		border:1px solid rgba(184,147,95,.35);
		color:var(--gold-dark);
		text-decoration:none;
		font-size:13px;
		font-weight:500;
	}
	.te-wp-back:hover{ background:var(--gold); color:#fff; border-color:var(--gold); }
	.top-bar, .header-top,
	.top-menu.top-menu--dark,
	.top-menu--dark{
		background:rgba(235,227,216,.94) !important;
		background-color:rgba(235,227,216,.94) !important;
		border-bottom:none !important;
		box-shadow:none !important;
		color:var(--muted) !important;
	}
	.top-menu--dark .social-networks a,
	.top-menu--dark .social-networks i,
	.top-menu--light .social-networks a,
	.top-menu--light .social-networks i{
		color:var(--gold-dark) !important;
		transition:color .2s ease, transform .2s ease;
	}
	.top-menu--dark .social-networks a:hover,
	.top-menu--dark .social-networks a:hover i,
	.top-menu--light .social-networks a:hover,
	.top-menu--light .social-networks a:hover i{
		color:var(--gold) !important;
		transform:translateY(-1px);
	}
	.top-menu--dark .container,
	.top-menu--light .container{ padding-top:6px; padding-bottom:6px; }
	.page-container, .container.main-container{ max-width:1180px; }
	li.product .product-item-image,
	li.product .thumb,
	li.product a.woocommerce-LoopProduct-link,
	li.shop-item .product-item-image,
	.woocommerce ul.products li.product > a{
		background:var(--img-well) !important;
		display:block;
	}
	li.product .product-item-details,
	li.shop-item .product-item-details{
		background:var(--surface) !important;
	}
	.header-top-socials a,
	.top-bar a{ color:var(--gold-dark) !important; }
	</style>';
}

add_action( 'wp_body_open', 'te_shop_back_to_premium', 5 );
add_action( 'woocommerce_before_main_content', 'te_shop_back_to_premium', 5 );
function te_shop_back_to_premium() {
	// Barra "Volver al catálogo premium" desactivada — navegación por menú y breadcrumbs.
	return;
}

add_action( 'wp_enqueue_scripts', 'te_dequeue_aurum_fonts', 999 );
function te_dequeue_aurum_fonts() {
	wp_dequeue_style( 'primary-font' );
	wp_dequeue_style( 'heading-font' );
	wp_deregister_style( 'primary-font' );
	wp_deregister_style( 'heading-font' );
}

add_action( 'wp_head', 'te_premium_typography', 9999 );
function te_premium_typography() {
	echo '<style id="te-premium-typography">
	html, body{
		font-family:"Poppins",sans-serif !important;
		font-size:14px;
		line-height:1.5;
		color:var(--ink,#2b2926);
		-webkit-font-smoothing:antialiased;
		-moz-osx-font-smoothing:grayscale;
	}
	body, p, label, input, textarea, select, button,
	.button, a.button, .btn, .primary-font, .heading-font,
	.woocommerce-result-count, .woocommerce-ordering, .orderby,
	.woocommerce-breadcrumb, .te-breadcrumb, .te-wp-back,
	.woocommerce-pagination, .page-numbers,
	.site-footer, .site-footer p, .te-premium-footer,
	.mobile-menu a, .top-menu a, .sub-menu a,
	.select2-container, .dropdown-menu{
		font-family:"Poppins",sans-serif !important;
		font-weight:400;
	}
	h1, h2, h4, h5, h6,
	.site-title, .site-title a,
	.woocommerce-products-header__title,
	.page-title, .entry-title,
	.product_title, .woocommerce div.product .product_title,
	.tuexhibidor-section h2, .widget-title{
		font-family:"Poppins",sans-serif !important;
		font-weight:600 !important;
		letter-spacing:0;
	}
	.woocommerce-products-header__title,
	.page-title{
		font-size:clamp(1.75rem,3vw,2rem) !important;
		line-height:1.2 !important;
	}
	.main-menu > ul > li > a{
		font-family:"Poppins",sans-serif !important;
		font-size:14px !important;
		font-weight:500 !important;
		letter-spacing:0 !important;
		text-transform:none !important;
		padding:8px 14px !important;
		border-radius:999px !important;
		transition:background .25s ease, color .25s ease !important;
	}
	.main-menu > ul > li > a:hover,
	.main-menu > ul > li.current-menu-item > a{
		background:var(--gold,#b8935f) !important;
		color:#fff !important;
	}
	li.product h3, li.shop-item h3,
	li.product h3 a, li.shop-item h3 a,
	li.shop-item .item-info h3, li.shop-item .item-info h3 a,
	.woocommerce-loop-product__title,
	.woocommerce ul.products li.product .woocommerce-loop-product__title{
		font-family:"Poppins",sans-serif !important;
		font-weight:500 !important;
		font-size:14px !important;
		letter-spacing:0 !important;
		line-height:1.35 !important;
	}
	li.shop-item .product-terms,
	li.shop-item .product-terms a,
	li.product .product_cat, li.product .product_cat a,
	.product-terms a{
		font-family:"Poppins",sans-serif !important;
		font-size:11px !important;
		font-weight:400 !important;
		letter-spacing:.08em !important;
		text-transform:uppercase !important;
	}
	.woocommerce-result-count,
	.woocommerce-ordering label,
	.woocommerce-ordering .orderby{
		font-size:13px !important;
		color:var(--muted,#8a8378) !important;
	}
	.woocommerce-breadcrumb, .te-breadcrumb{
		font-size:13px !important;
	}
	.woocommerce div.product .woocommerce-product-details__short-description,
	.woocommerce-Tabs-panel, .woocommerce-product-details__short-description p{
		font-family:"Poppins",sans-serif !important;
		font-size:15px !important;
		line-height:1.65 !important;
	}
	.share-post.share-post-icons,
	.share-post .share-post-links{ display:none !important; }
	.te-product-share{
		margin:22px 0 8px;
		padding-top:18px;
		border-top:1px solid rgba(184,147,95,.18);
	}
	.te-product-share__label,
	.te-product-quote__title{
		font-family:"Poppins",sans-serif !important;
		font-size:1.05rem !important;
		font-weight:600 !important;
		color:var(--ink,#2b2926) !important;
		margin:0 0 6px !important;
	}
	.te-product-share__links{ margin-top:10px; }
	.te-product-quote{
		margin:20px 0 10px;
		padding:18px 18px 16px;
		border-radius:16px;
		background:var(--surface,#ddd3c8);
		border:1px solid rgba(184,147,95,.22);
	}
	.te-product-quote__sub{
		font-size:13px !important;
		color:var(--muted,#8a8378) !important;
		margin:0 0 14px !important;
	}
	.te-product-quote__buttons{
		display:flex; flex-wrap:wrap; gap:10px;
	}
	.te-product-wa-btn,
	.summary .te-product-wa-btn,
	.woocommerce .te-product-wa-btn{
		display:inline-flex !important;
		align-items:center !important;
		justify-content:center !important;
		padding:10px 18px !important;
		border-radius:999px !important;
		background:#25D366 !important;
		color:#fff !important;
		font-family:"Poppins",sans-serif !important;
		font-size:13px !important;
		font-weight:500 !important;
		line-height:1.2 !important;
		text-decoration:none !important;
		border:2px solid transparent !important;
		box-shadow:none !important;
		min-height:0 !important;
		width:auto !important;
		max-width:none !important;
		transition:background .25s ease, transform .25s ease !important;
	}
	.te-product-wa-btn:hover{
		background:#1ebe57 !important;
		color:#fff !important;
		transform:translateY(-2px);
	}
	.social-links{
		display:flex; align-items:center; justify-content:flex-start;
		gap:12px; flex-wrap:wrap;
	}
	.social-link,
	.header-top-socials a{
		display:inline-flex !important;
		align-items:center !important;
		justify-content:center !important;
		width:44px !important;
		height:44px !important;
		border-radius:50% !important;
		color:var(--gold-dark,#96723f) !important;
		background:rgba(184,147,95,.12) !important;
		border:1px solid rgba(184,147,95,.28) !important;
		text-decoration:none !important;
		padding:0 !important;
		box-shadow:none !important;
		transition:background .25s ease, color .25s ease, transform .25s ease !important;
	}
	.top-menu .social-networks a,
	.top-menu .social-networks a.te-header-social{
		width:36px !important;
		height:36px !important;
		background:rgba(255,255,255,.55) !important;
		border:1px solid rgba(150,114,63,.28) !important;
		color:var(--ink,#2b2926) !important;
	}
	.social-link:hover,
	.header-top-socials a:hover{
		background:var(--gold,#b8935f) !important;
		color:#fff !important;
		transform:translateY(-2px);
	}
	.top-menu .social-networks a:hover,
	.top-menu .social-networks a.te-header-social:hover{
		background:var(--gold,#b8935f) !important;
		border-color:var(--gold,#b8935f) !important;
		color:#fff !important;
		transform:translateY(-1px);
	}
	.te-share-copy{
		cursor:pointer;
		font:inherit;
	}
	.te-share-copy.is-copied{
		background:var(--gold,#b8935f) !important;
		color:#fff !important;
	}
	.top-menu .social-networks,
	.header-top-socials ul{
		display:flex !important;
		align-items:center !important;
		gap:10px !important;
		list-style:none !important;
		margin:0 !important;
		padding:0 !important;
	}
	.top-menu .social-networks i,
	.top-menu .social-networks .fa{ display:none !important; }
	.te-premium-footer .social-links{ justify-content:center; }
	</style>';
}
/* == fin TE-UNIFICADO == */


/* == TE-SINGLE-PRODUCT: ficha equilibrada + relacionados == */
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
} );

/**
 * Ocultar productos agotados en carruseles/listas de productos (relacionados, upsells, cross-sells).
 */
function te_filter_in_stock_product_ids( array $ids ): array {
	return array_values( array_filter( $ids, static function ( $id ) {
		$product = wc_get_product( $id );
		return $product && $product->is_in_stock();
	} ) );
}

add_filter( 'woocommerce_related_products', 'te_filter_in_stock_product_ids', 20 );
add_filter( 'woocommerce_upsell_ids', 'te_filter_in_stock_product_ids', 20 );
add_filter( 'woocommerce_cross_sell_ids', 'te_filter_in_stock_product_ids', 20 );

add_filter( 'woocommerce_shortcode_products_query', function ( $query_args ) {
	$query_args['meta_query']   = $query_args['meta_query'] ?? array();
	$query_args['meta_query'][] = te_in_stock_meta_clause();
	return $query_args;
}, 20 );

/**
 * Ocultar agotados en tienda, categorías, etiquetas y búsqueda de productos.
 */
function te_in_stock_meta_clause(): array {
	return array(
		'key'     => '_stock_status',
		'value'   => 'instock',
		'compare' => '=',
	);
}

add_filter( 'woocommerce_product_query_meta_query', function ( $meta_query ) {
	if ( ! is_array( $meta_query ) ) {
		$meta_query = array();
	}
	$meta_query[] = te_in_stock_meta_clause();
	return $meta_query;
}, 20 );

add_filter( 'pre_option_woocommerce_hide_out_of_stock_items', static function () {
	return 'yes';
} );

/**
 * Ocultar agotados en cualquier consulta de productos (búsqueda, widgets, etc.).
 */
add_action( 'pre_get_posts', 'te_hide_out_of_stock_products_query', 25 );
function te_hide_out_of_stock_products_query( $query ) {
	if ( is_admin() || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
		return;
	}

	$is_product_context = $query->is_search()
		|| $query->is_post_type_archive( 'product' )
		|| $query->is_tax( get_object_taxonomies( 'product' ) );

	if ( ! $is_product_context ) {
		return;
	}

	if ( $query->is_search() ) {
		$post_type = $query->get( 'post_type' );
		if ( $post_type && 'product' !== $post_type ) {
			return;
		}
		if ( ! $post_type ) {
			$query->set( 'post_type', 'product' );
		}
	}

	$meta_query = $query->get( 'meta_query' );
	if ( ! is_array( $meta_query ) ) {
		$meta_query = array();
	}
	$meta_query[] = te_in_stock_meta_clause();
	$query->set( 'meta_query', $meta_query );
}

add_filter( 'woocommerce_product_is_visible', 'te_hide_out_of_stock_product_visibility', 20, 2 );
function te_hide_out_of_stock_product_visibility( $visible, $product_id ) {
	if ( ! $visible ) {
		return false;
	}
	$product = wc_get_product( $product_id );
	return $product && $product->is_in_stock();
}

add_filter( 'woocommerce_products_widget_query_args', 'te_hide_out_of_stock_widget_args', 20 );
add_filter( 'woocommerce_recent_products_widget_query_args', 'te_hide_out_of_stock_widget_args', 20 );
add_filter( 'woocommerce_top_rated_products_widget_query_args', 'te_hide_out_of_stock_widget_args', 20 );
function te_hide_out_of_stock_widget_args( $args ) {
	$args['meta_query']   = $args['meta_query'] ?? array();
	$args['meta_query'][] = te_in_stock_meta_clause();
	return $args;
}

add_filter( 'woocommerce_rest_product_object_query', 'te_hide_out_of_stock_rest_query', 20 );
function te_hide_out_of_stock_rest_query( $args ) {
	$args['meta_query']   = $args['meta_query'] ?? array();
	$args['meta_query'][] = te_in_stock_meta_clause();
	return $args;
}

add_action( 'template_redirect', 'te_redirect_out_of_stock_product', 15 );
function te_redirect_out_of_stock_product() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$product = wc_get_product( get_queried_object_id() );
	if ( $product && ! $product->is_in_stock() ) {
		$shop = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
		wp_safe_redirect( $shop ? $shop : home_url( '/shop/' ), 302 );
		exit;
	}
}

add_action( 'wp_head', 'te_single_product_layout_css', 10050 );
function te_single_product_layout_css() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	echo '<style id="te-single-product-layout">
	/* Aurum float 50/50 layout overrides — must beat .woocommerce div.single-product--product-details>.product .summary */
	.woocommerce div.single-product{
		display:block !important;
		margin-left:0 !important;
		margin-right:0 !important;
		min-width:0 !important;
	}
	.woocommerce div.single-product--product-details{
		width:100% !important;
		max-width:100% !important;
		padding-left:0 !important;
		padding-right:0 !important;
	}
	.woocommerce div.single-product--product-details > .product{
		margin-left:0 !important;
		margin-right:0 !important;
	}
	.woocommerce div.single-product--product-details > .product > div,
	.woocommerce div.single-product--product-details > .product > section,
	.woocommerce div.single-product--product-details > .product .product-images-container,
	.woocommerce div.single-product--product-details > .product .summary{
		float:none !important;
		width:100% !important;
		max-width:100% !important;
		padding-left:0 !important;
		padding-right:0 !important;
		margin-left:0 !important;
		margin-right:0 !important;
	}
	.single-product .woocommerce-breadcrumb.te-breadcrumb{
		max-width:1140px;
		margin:0 auto 8px;
		padding:0 20px;
		font-size:13px !important;
	}
	.single-product div.product{
		display:grid !important;
		grid-template-columns:minmax(0, 1fr) minmax(0, 1fr) !important;
		grid-template-areas:"gallery side" "related related" !important;
		grid-template-rows:auto auto !important;
		gap:20px 24px !important;
		max-width:1180px !important;
		margin:0 auto 24px !important;
		padding:0 16px !important;
		align-items:start !important;
		align-content:start !important;
		float:none !important;
		width:100% !important;
		box-sizing:border-box !important;
	}
	.single-product div.product::after{ display:none !important; }
	.single-product div.product > div.images,
	.single-product div.product > .product-images-container{
		grid-area:gallery !important;
		width:100% !important;
		float:none !important;
		margin:0 !important;
		min-width:0 !important;
		max-width:100% !important;
		overflow:hidden !important;
		align-self:start !important;
		height:auto !important;
		max-height:none !important;
	}
	.single-product div.product > .te-product-side{
		grid-area:side !important;
		display:flex !important;
		flex-direction:column !important;
		gap:20px !important;
		align-self:start !important;
		justify-self:stretch !important;
		width:100% !important;
		min-width:0 !important;
		max-width:100% !important;
		height:auto !important;
		min-height:0 !important;
	}
	.single-product div.product > .te-product-side > .summary.entry-summary,
	.single-product div.product > .te-product-side > div.summary{
		grid-area:unset !important;
		width:100% !important;
		flex:0 0 auto !important;
	}
	.single-product div.product > .te-product-side > .tabs,
	.single-product div.product > .te-product-side > .woocommerce-tabs{
		grid-area:unset !important;
		grid-column:unset !important;
		grid-row:unset !important;
		width:100% !important;
		flex:0 0 auto !important;
		margin:0 !important;
	}
	.single-product div.product > .summary.entry-summary,
	.single-product div.product > div.summary{
		grid-area:summary !important;
		align-self:start !important;
		justify-self:stretch !important;
		height:auto !important;
		min-height:0 !important;
		max-height:none !important;
		flex:none !important;
		width:100% !important;
		float:none !important;
		margin:0 !important;
		min-width:0 !important;
		max-width:100% !important;
		overflow:visible !important;
	}
	.single-product div.product > .related.products{
		grid-area:related !important;
		grid-column:1 / -1 !important;
		width:100% !important;
		max-width:100% !important;
		margin:8px 0 0 !important;
		padding:0 0 24px !important;
	}
	.single-product div.product div.images,
	.single-product div.product div.summary{
		width:100% !important;
		float:none !important;
		margin:0 !important;
		min-width:0 !important;
		max-width:100% !important;
		overflow:hidden !important;
	}
	.woocommerce div.single-product--product-details > .product > .summary.entry-summary,
	.single-product div.product div.summary{
		align-self:start !important;
		height:auto !important;
		min-height:0 !important;
		max-height:none !important;
		flex:none !important;
		flex-grow:0 !important;
	}
	/* Force stacked gallery (main + horizontal thumbs). Beat Aurum vertical side-rail. */
	.woocommerce .product-images-container.thumbnails-vertical .product-images,
	.single-product .product-images-container,
	.single-product .product-images-container.thumbnails-vertical,
	.single-product .product-images-container .product-images,
	.single-product .product-images-container.thumbnails-vertical .product-images{
		display:flex !important;
		flex-direction:column !important;
		align-items:stretch !important;
		gap:12px !important;
		width:100% !important;
		max-width:100% !important;
		min-width:0 !important;
	}
	.single-product .product-images-container,
	.single-product .product-images-container.thumbnails-vertical{
		background:var(--img-well,#ddd3c8) !important;
		border-radius:16px !important;
		padding:16px !important;
		border:1px solid rgba(184,147,95,.15) !important;
		box-sizing:border-box !important;
	}
	.woocommerce .product-images-container.thumbnails-vertical .product-images .product-images--main,
	.single-product .product-images-container .product-images--main,
	.single-product .product-images-container.thumbnails-vertical .product-images--main,
	.single-product .product-images--main{
		order:1 !important;
		flex:0 0 auto !important;
		width:100% !important;
		max-width:100% !important;
		min-width:0 !important;
		align-self:stretch !important;
		aspect-ratio:auto !important;
		min-height:0 !important;
		max-height:none !important;
		height:auto !important;
		display:block !important;
		align-items:unset !important;
		justify-content:unset !important;
		overflow:visible !important;
		position:relative !important;
		background:var(--img-well,#ddd3c8) !important;
		border-radius:12px !important;
		padding:8px !important;
		box-sizing:border-box !important;
	}
	.single-product .product-images--main .woocommerce-product-gallery,
	.single-product .product-images--main .slick-slider,
	.single-product .product-images--main .slick-list,
	.single-product .product-images--main .slick-track,
	.single-product .product-images--main .woocommerce-product-gallery__wrapper{
		width:100% !important;
		max-width:100% !important;
		height:auto !important;
		min-height:0 !important;
		max-height:none !important;
	}
	.single-product .product-images--main .slick-track{
		transform:none !important;
		display:block !important;
		align-items:unset !important;
		justify-content:unset !important;
	}
	.single-product .product-images--main .slick-slide{
		width:100% !important;
		height:auto !important;
		float:none !important;
	}
	.single-product .product-images--main .woocommerce-product-gallery__image{
		display:none !important;
		width:100% !important;
		height:auto !important;
		margin:0 !important;
	}
	.single-product .product-images--main .woocommerce-product-gallery__image:first-child,
	.single-product .product-images--main .woocommerce-product-gallery__image.slick-active,
	.single-product .product-images--main .woocommerce-product-gallery__image.slick-current{
		display:block !important;
	}
	/* Kill Aurum/Customizer padding-bottom crop wells + object-fit:cover. */
	.single-product .product-images--main .woocommerce-product-gallery__image .image-placeholder,
	.single-product .product-images--main .image-placeholder,
	.single-product .product-images--main .image-placeholder[style],
	.product-images--main .woocommerce-product-gallery__image .image-placeholder,
	.product-images--main .image-placeholder{
		padding:0 !important;
		padding-bottom:0 !important;
		height:auto !important;
		max-height:none !important;
		width:100% !important;
		min-height:0 !important;
		display:block !important;
		align-items:unset !important;
		justify-content:unset !important;
		position:relative !important;
		overflow:visible !important;
		background:transparent !important;
	}
	.single-product .product-images--main .image-placeholder > a,
	.single-product .product-images--main .woocommerce-product-gallery__image > a{
		display:block !important;
		width:100% !important;
		height:auto !important;
		position:relative !important;
		text-align:center !important;
	}
	.woocommerce .product-images-container.thumbnails-vertical .product-images .product-images--thumbnails,
	.single-product .product-images--thumbnails,
	.single-product .product-images--thumbnails.thumbnails-vertical,
	.single-product .product-images--thumbnails.slick-vertical,
	.product-images--thumbnails.slick-vertical{
		order:2 !important;
		flex:0 0 auto !important;
		width:100% !important;
		max-width:100% !important;
		min-width:0 !important;
		height:auto !important;
		max-height:110px !important;
		min-height:0 !important;
		margin:0 !important;
		padding:0 !important;
		display:flex !important;
		flex-direction:row !important;
		flex-wrap:wrap !important;
		align-items:flex-start !important;
		align-content:flex-start !important;
		justify-content:flex-start !important;
		gap:8px !important;
		position:static !important;
		float:none !important;
		overflow:hidden !important;
	}
	.single-product .product-images-container,
	.single-product .product-images,
	.single-product .product-images-container.thumbnails-vertical{
		height:auto !important;
		min-height:0 !important;
		align-self:start !important;
	}
	.single-product .product-images--thumbnails .slick-list,
	.single-product .product-images--thumbnails .slick-track,
	.product-images--thumbnails.slick-vertical .slick-list,
	.product-images--thumbnails.slick-vertical .slick-track{
		transform:none !important;
		width:auto !important;
		max-width:100% !important;
		height:auto !important;
		min-height:0 !important;
		max-height:none !important;
		display:flex !important;
		flex-direction:row !important;
		flex-wrap:wrap !important;
		align-items:flex-start !important;
		gap:8px !important;
	}
	.single-product .product-images--thumbnails .slick-slide,
	.product-images--thumbnails.slick-vertical .slick-slide,
	.single-product .product-images--thumbnails .woocommerce-product-gallery__image{
		width:88px !important;
		min-width:88px !important;
		max-width:88px !important;
		height:88px !important;
		float:none !important;
		padding:0 !important;
		margin:0 !important;
		box-sizing:border-box !important;
		overflow:hidden !important;
		border-radius:8px !important;
		background:rgba(255,255,255,.35) !important;
	}
	.single-product .product-images--thumbnails .image-placeholder,
	.single-product .product-images--thumbnails .image-placeholder[style]{
		padding:0 !important;
		padding-bottom:0 !important;
		width:100% !important;
		height:100% !important;
		position:relative !important;
		display:block !important;
		overflow:hidden !important;
	}
	.single-product .product-images-container .slick-arrow{ display:none !important; }
	.single-product .image-placeholder{
		padding-bottom:0 !important;
		height:auto !important;
		position:relative !important;
		display:block !important;
	}
	/* Beat theme-custom-css cover rules: absolute fill + object-fit:cover. */
	.single-product .product-images--main .image-placeholder img,
	.product-images--main .image-placeholder img,
	.single-product .woocommerce-product-gallery__image img,
	.single-product div.product div.images img,
	.single-product .product-images--main img{
		position:static !important;
		top:auto !important;
		left:auto !important;
		right:auto !important;
		bottom:auto !important;
		max-height:min(560px, 70vh) !important;
		max-width:100% !important;
		width:auto !important;
		height:auto !important;
		margin:0 auto !important;
		padding:8px !important;
		box-sizing:border-box !important;
		display:block !important;
		aspect-ratio:unset !important;
		object-fit:contain !important;
		object-position:center center !important;
		opacity:1 !important;
		visibility:visible !important;
	}
	.single-product .product-images--thumbnails img{
		position:static !important;
		width:100% !important;
		height:100% !important;
		max-width:100% !important;
		max-height:100% !important;
		padding:4px !important;
		box-sizing:border-box !important;
		object-fit:contain !important;
		object-position:center center !important;
	}
	.single-product div.summary{
		background:var(--surface,#ddd3c8) !important;
		border-radius:16px !important;
		padding:24px 26px !important;
		border:1px solid rgba(184,147,95,.18) !important;
		box-shadow:0 4px 18px rgba(43,41,38,.06) !important;
	}
	.single-product .product_title{
		font-family:"Poppins",sans-serif !important;
		font-weight:600 !important;
		font-size:clamp(1.2rem, 2.2vw, 1.55rem) !important;
		line-height:1.3 !important;
		margin:0 0 10px !important;
		color:var(--ink,#2b2926) !important;
	}
	.single-product .product_title .product-terms{
		display:block;
		margin-top:8px;
		font-family:"Poppins",sans-serif !important;
		font-size:11px !important;
		font-weight:500 !important;
		letter-spacing:.1em;
		text-transform:uppercase;
	}
	.single-product .product_title .product-terms a{
		color:var(--gold-dark,#96723f) !important;
		text-decoration:none;
	}
	.single-product .product_meta .posted_in,
	.single-product .product_meta .sku_wrapper{ display:none !important; }
	.single-product .woocommerce-product-details__short-description{
		font-family:"Poppins",sans-serif !important;
		font-size:14px !important;
		line-height:1.65 !important;
		color:var(--muted,#8a8378) !important;
		margin:0 0 16px !important;
	}
	.single-product .woocommerce-product-details__short-description p{
		margin:0 0 8px !important;
	}
	.related.products{
		clear:both;
		width:100% !important;
		max-width:100% !important;
		margin:0 !important;
		padding:0 !important;
	}
	.related.products > h2{
		text-align:center;
		font-family:"Poppins",sans-serif !important;
		font-size:1.35rem !important;
		font-weight:600 !important;
		margin:0 0 20px;
		color:var(--ink,#2b2926);
	}
	.related.products ul.products{
		display:grid !important;
		grid-template-columns:repeat(4, minmax(0, 1fr)) !important;
		gap:16px !important;
		margin:0 !important;
		padding:0 !important;
		list-style:none !important;
		width:100% !important;
		align-items:stretch !important;
	}
	.related.products ul.products[class*="columns-"] li,
	.related.products ul.products.columns-4 li{
		width:100% !important;
	}
	.related.products ul.products::before,
	.related.products ul.products::after{ display:none !important; content:none !important; }
	.related.products ul.products li.product,
	.related.products ul.products li.shop-item{
		width:100% !important;
		max-width:none !important;
		margin:0 !important;
		float:none !important;
		clear:none !important;
		display:flex !important;
		flex-direction:column !important;
		height:100% !important;
		align-self:stretch !important;
		background:var(--img-well,#ddd3c8) !important;
		border-radius:16px !important;
		border:1px solid rgba(184,147,95,.15) !important;
		box-shadow:0 4px 18px rgba(43,41,38,.06) !important;
		overflow:hidden !important;
	}
	.related.products .shop-item .item-info,
	.related.products li.product .product-item-details,
	.related.products li.product .item-info{
		background:var(--surface,#ddd3c8) !important;
		padding:12px 14px 16px !important;
		text-align:center !important;
		flex:1 1 auto !important;
		display:flex !important;
		flex-direction:column !important;
		justify-content:flex-start !important;
		min-height:0 !important;
	}
	.related.products .shop-item .item-info h3,
	.related.products .shop-item .item-info h3 a,
	.related.products li.product h3,
	.related.products li.product h3 a{
		font-size:13px !important;
		line-height:1.35 !important;
		margin:0 0 6px !important;
		padding:0 !important;
		min-height:0 !important;
		font-weight:500 !important;
		color:var(--ink,#2b2926) !important;
	}
	.related.products .shop-item .product-terms,
	.related.products .shop-item .product_cat,
	.related.products li.product .product_cat{
		font-size:10px !important;
		letter-spacing:.08em !important;
		text-transform:uppercase !important;
		color:var(--gold-dark,#96723f) !important;
		margin:0 !important;
	}
	.related.products .shop-item.hover-effect-1 .shop-image,
	.related.products .shop-item.hover-effect-1:hover .shop-image{
		display:none !important;
		opacity:0 !important;
		visibility:hidden !important;
	}
	@media (max-width: 960px){
		.single-product div.product{
			grid-template-columns:1fr !important;
			grid-template-areas:"gallery" "side" "related" !important;
			grid-template-rows:auto auto auto !important;
			gap:20px !important;
			padding:0 16px !important;
		}
		.single-product div.product div.images,
		.single-product div.product div.summary{
			width:100% !important;
			float:none !important;
		}
		.related.products ul.products{
			grid-template-columns:repeat(2, minmax(0, 1fr)) !important;
		}
	}
	@media (max-width: 520px){
		.related.products ul.products{
			grid-template-columns:1fr !important;
		}
	}
	</style>';
}

/**
 * Una sola fuente de verdad: pozo cuadrado 1:1 + object-fit contain en TODOS los loops de producto.
 * Shop, categorías, búsqueda, relacionados, up-sells, cross-sells — incluye sets-vitrina y dijes-charms.
 */
add_action( 'wp_head', 'te_product_loop_image_css', 10006 );
function te_product_loop_image_css() {
	if ( ! function_exists( 'is_woocommerce' ) ) {
		return;
	}
	echo '<style id="te-product-loop-images">
	.woocommerce ul.products li.shop-item .item-image,
	.woocommerce ul.products li.product .item-image,
	.woocommerce ul.products li.product .product-item-image,
	.woocommerce ul.products li.product .thumb,
	.related.products li.shop-item .item-image,
	.related.products li.product .item-image,
	.related.products li.product .product-item-image,
	.up-sells li.shop-item .item-image,
	.up-sells li.product .item-image,
	.cross-sells li.shop-item .item-image,
	.cross-sells li.product .item-image{
		position:relative !important;
		width:100% !important;
		aspect-ratio:1/1 !important;
		background:var(--img-well,#ddd3c8) !important;
		overflow:hidden !important;
		flex-shrink:0 !important;
	}
	.woocommerce ul.products .image-placeholder:not(.shop-image),
	.related.products ul.products .image-placeholder:not(.shop-image),
	.related.products ul.products li.shop-item .item-image .image-placeholder:not(.shop-image),
	.related.products ul.products li.product .item-image .image-placeholder:not(.shop-image),
	.up-sells .image-placeholder:not(.shop-image),
	.cross-sells .image-placeholder:not(.shop-image){
		padding:0 !important;
		padding-bottom:0 !important;
		margin:0 !important;
		height:100% !important;
		width:100% !important;
		min-height:0 !important;
		position:absolute !important;
		inset:0 !important;
		display:block !important;
		opacity:1 !important;
		visibility:visible !important;
	}
	.woocommerce ul.products .image-placeholder[style],
	.related.products ul.products .image-placeholder[style],
	.related.products ul.products li.shop-item .item-image .image-placeholder[style],
	.related.products ul.products li.product .item-image .image-placeholder[style],
	.up-sells .image-placeholder[style],
	.cross-sells .image-placeholder[style]{
		padding-bottom:0 !important;
	}
	.woocommerce ul.products .item-image > a,
	.woocommerce ul.products .image-placeholder > a,
	.related.products .item-image > a,
	.related.products .image-placeholder > a,
	.up-sells .item-image > a,
	.cross-sells .item-image > a{
		display:block !important;
		position:absolute !important;
		inset:0 !important;
		width:100% !important;
		height:100% !important;
	}
	.woocommerce ul.products li.shop-item .item-image img,
	.woocommerce ul.products li.product .item-image img,
	.woocommerce ul.products li.product a img,
	.related.products ul.products li.shop-item .item-image img,
	.related.products ul.products li.product .item-image img,
	.related.products ul.products li.shop-item a img,
	.related.products ul.products li.product a img,
	.related.products li.shop-item .item-image img,
	.related.products li.product .item-image img,
	.related.products li.product a img,
	.up-sells li.product img,
	.cross-sells li.product img{
		position:absolute !important;
		top:0 !important;
		left:0 !important;
		right:0 !important;
		bottom:0 !important;
		width:100% !important;
		height:100% !important;
		max-width:100% !important;
		max-height:100% !important;
		margin:0 !important;
		padding:10px !important;
		box-sizing:border-box !important;
		object-fit:contain !important;
		object-position:center !important;
		background:var(--img-well,#ddd3c8) !important;
		border-radius:0 !important;
		aspect-ratio:unset !important;
		opacity:1 !important;
		visibility:visible !important;
		transform:none !important;
	}
	.woocommerce ul.products .shop-image,
	.related.products .shop-image,
	.up-sells .shop-image,
	.cross-sells .shop-image{
		display:none !important;
		opacity:0 !important;
		visibility:hidden !important;
	}
	.woocommerce ul.products .bounce-loader,
	.related.products .bounce-loader{
		display:none !important;
	}
	</style>';
}

add_action( 'wp_head', 'te_shop_ui_polish', 10003 );
function te_shop_ui_polish() {
	if ( ! function_exists( 'is_woocommerce' ) || ! ( is_woocommerce() || is_shop() || is_product_taxonomy() || is_product() ) ) {
		return;
	}
	echo '<style id="te-shop-ui-polish">
	body.woocommerce,
	body.woocommerce-page,
	body.woocommerce *:not(i):not(.fa):not([class*="entypo"]):not(svg):not(svg *){
		font-family:"Poppins",sans-serif !important;
	}
	.woocommerce .content-area,
	.woocommerce-page .content-area,
	.page-container,
	.container.main-container{
		width:100% !important;
		max-width:1180px !important;
		margin:0 auto !important;
		padding-left:16px !important;
		padding-right:16px !important;
		box-sizing:border-box !important;
	}
	.woocommerce-breadcrumb.te-breadcrumb,
	.woocommerce-products-header,
	.woocommerce-result-count,
	.woocommerce-before-shop-loop{
		text-align:center !important;
	}
	.woocommerce-ordering{
		display:none !important;
	}
	.te-shop-header.woocommerce-shop-header--columned{
		display:flex !important;
		flex-direction:column !important;
		align-items:center !important;
		text-align:center !important;
		gap:12px !important;
		margin-bottom:8px !important;
	}
	.te-shop-header .page-title{
		text-align:center !important;
	}
	.te-shop-header__categories,
	.woocommerce-shop-header--sorting.te-shop-header__categories{
		width:100% !important;
		float:none !important;
	}
	.te-shop-cat-filter{
		display:flex !important;
		flex-wrap:wrap !important;
		justify-content:center !important;
		align-items:center !important;
		gap:8px !important;
		width:100% !important;
		margin:4px 0 18px !important;
		padding:0 !important;
	}
	.te-shop-cat-chip{
		display:inline-flex !important;
		align-items:center !important;
		gap:6px !important;
		padding:10px 18px !important;
		border-radius:999px !important;
		border:1px solid rgba(184,147,95,.35) !important;
		background:rgba(255,255,255,.35) !important;
		color:var(--ink,#2b2926) !important;
		font-family:"Poppins",sans-serif !important;
		font-size:13px !important;
		font-weight:500 !important;
		line-height:1.2 !important;
		text-decoration:none !important;
		transition:background .2s ease,color .2s ease,border-color .2s ease !important;
	}
	.te-shop-cat-chip:hover,
	.te-shop-cat-chip.is-active{
		background:var(--gold,#b8935f) !important;
		border-color:var(--gold,#b8935f) !important;
		color:#fff !important;
	}
	.te-shop-cat-count{
		display:inline-flex !important;
		align-items:center !important;
		justify-content:center !important;
		min-width:1.4em !important;
		padding:2px 7px !important;
		border-radius:999px !important;
		background:rgba(255,255,255,.22) !important;
		font-size:11px !important;
		font-weight:600 !important;
		line-height:1 !important;
	}
	.te-shop-cat-chip:not(.is-active):not(:hover) .te-shop-cat-count{
		background:rgba(184,147,95,.14) !important;
		color:var(--gold-dark,#96723f) !important;
	}
	.woocommerce-before-shop-loop{
		display:flex !important;
		flex-direction:column !important;
		align-items:center !important;
		justify-content:center !important;
		gap:10px !important;
		margin-bottom:8px !important;
	}
	.te-wp-back{
		display:none !important;
	}
	li.shop-item .item-info,
	li.product .product-item-details,
	.single-product div.summary,
	.single-product .te-product-quote,
	.single-product .te-product-share{
		text-align:center !important;
	}
	li.shop-item .item-info h3,
	li.shop-item .item-info h3 a,
	li.product h3,
	li.product h3 a,
	.woocommerce-loop-product__title{
		text-align:center !important;
	}
	li.shop-item .product-terms,
	li.shop-item .product_cat,
	li.product .product_cat,
	.product-terms{
		text-align:center !important;
	}
	.te-product-quote__buttons,
	.te-product-share__links,
	.social-links{
		justify-content:center !important;
	}
	.related.products > h2,
	.woocommerce-products-header__title,
	.page-title{
		text-align:center !important;
		font-family:"Poppins",sans-serif !important;
		font-weight:600 !important;
	}
	.single-product .product_title{
		text-align:center !important;
		font-family:"Poppins",sans-serif !important;
		font-weight:600 !important;
	}
	.single-product .product_title .product-terms{
		text-align:center !important;
	}
	.single-product .woocommerce-product-details__short-description,
	.single-product .woocommerce-product-details__short-description p{
		text-align:center !important;
	}
	.te-product-share__label,
	.te-product-quote__title{
		font-family:"Poppins",sans-serif !important;
		text-align:center !important;
	}
	@media (max-width: 768px){
		html, body{
			overflow-x:hidden !important;
			max-width:100% !important;
		}
		.site-header, .page-container, .woocommerce, .woocommerce-page{
			overflow-x:hidden !important;
			max-width:100vw !important;
		}
		.single-product div.product{
			display:flex !important;
			flex-direction:column !important;
			grid-template-columns:unset !important;
			width:100% !important;
			max-width:100% !important;
			padding:0 12px !important;
			margin:0 auto 28px !important;
		}
		.single-product div.product div.images,
		.single-product div.product div.summary{
			width:100% !important;
			max-width:100% !important;
			min-width:0 !important;
			flex:0 0 auto !important;
		}
		.single-product .product-images-container,
		.single-product .product-images-container.thumbnails-vertical{
			flex-direction:column !important;
			width:100% !important;
			max-width:100% !important;
			min-width:0 !important;
		}
		.single-product .product-images--main,
		.single-product .product-images--main .woocommerce-product-gallery,
		.single-product .product-images--main .slick-slider,
		.single-product .product-images--main .slick-list,
		.single-product .product-images--main .woocommerce-product-gallery__wrapper{
			width:100% !important;
			max-width:100% !important;
			min-width:0 !important;
			min-height:0 !important;
			overflow:hidden !important;
		}
		.single-product .product-images--main .slick-track{
			width:100% !important;
			max-width:100% !important;
			height:auto !important;
			min-height:0 !important;
			transform:none !important;
			display:block !important;
		}
		.single-product .product-images--main .slick-slide{
			width:100% !important;
			max-width:100% !important;
			float:none !important;
			height:0 !important;
			min-height:0 !important;
			overflow:hidden !important;
			display:none !important;
		}
		.single-product .product-images--main .slick-slide.slick-current,
		.single-product .product-images--main .slick-slide.slick-active,
		.single-product .product-images--main .slick-slide:first-child{
			display:flex !important;
			align-items:center !important;
			justify-content:center !important;
			height:100% !important;
		}
		.single-product .product-images--main .woocommerce-product-gallery__image{
			display:none !important;
			height:0 !important;
			min-height:0 !important;
			overflow:hidden !important;
			margin:0 !important;
			padding:0 !important;
			opacity:0 !important;
			visibility:hidden !important;
			pointer-events:none !important;
		}
		.single-product .product-images--main .woocommerce-product-gallery__image:first-child,
		.single-product .product-images--main .woocommerce-product-gallery__image.slick-current,
		.single-product .product-images--main .woocommerce-product-gallery__image.slick-active{
			display:flex !important;
			align-items:center !important;
			justify-content:center !important;
			height:100% !important;
			opacity:1 !important;
			visibility:visible !important;
			pointer-events:auto !important;
		}
		.single-product .product-images--main .image-placeholder,
		.single-product .product-images--main .image-placeholder[style]{
			padding-bottom:0 !important;
			height:100% !important;
			width:100% !important;
		}
		.single-product .product-images--main img{
			object-fit:contain !important;
			position:static !important;
			width:auto !important;
			height:auto !important;
			max-width:100% !important;
			max-height:100% !important;
		}
		.single-product .product-images--thumbnails,
		.single-product .product-images--thumbnails.thumbnails-vertical,
		.single-product .product-images--thumbnails.slick-vertical{
			display:flex !important;
			flex-direction:row !important;
			flex-wrap:nowrap !important;
			overflow-x:auto !important;
			gap:8px !important;
			max-height:88px !important;
			height:auto !important;
			width:100% !important;
			position:static !important;
		}
		.single-product .product-images--thumbnails .woocommerce-product-gallery__image,
		.single-product .product-images--thumbnails .slick-slide{
			flex:0 0 64px !important;
			width:64px !important;
			min-width:64px !important;
			max-width:64px !important;
			height:64px !important;
			display:block !important;
			opacity:1 !important;
			visibility:visible !important;
		}
		.single-product .product-images--thumbnails .slick-list,
		.single-product .product-images--thumbnails .slick-track{
			height:auto !important;
			min-height:0 !important;
			max-height:88px !important;
			width:auto !important;
			transform:none !important;
		}
		.single-product .slick-cloned{
			display:none !important;
			height:0 !important;
			overflow:hidden !important;
		}
		.woocommerce ul.products{
			grid-template-columns:repeat(2, minmax(0, 1fr)) !important;
			padding-left:0 !important;
			padding-right:0 !important;
		}
	}
	@media (max-width: 480px){
		.woocommerce ul.products{
			grid-template-columns:1fr !important;
		}
	}
	</style>';
}

add_action( 'wp_footer', 'te_wrap_product_side_column', 44 );
function te_wrap_product_side_column() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	echo '<script>
	(function(){
		function wrapSide(){
			var product = document.querySelector(".single-product div.product");
			if(!product || product.querySelector(".te-product-side")) return;
			var summary = product.querySelector(":scope > .summary.entry-summary, :scope > div.summary");
			var tabs = product.querySelector(":scope > .tabs, :scope > .woocommerce-tabs");
			if(!summary) return;
			var side = document.createElement("div");
			side.className = "te-product-side";
			summary.parentNode.insertBefore(side, summary);
			side.appendChild(summary);
			if(tabs) side.appendChild(tabs);
		}
		document.addEventListener("DOMContentLoaded", wrapSide);
	})();
	</script>';
}

add_action( 'wp_footer', 'te_fix_mobile_product_gallery', 45 );
function te_fix_mobile_product_gallery() {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	echo '<script>
	(function(){
		function upgradeThumbSrc(img){
			if(!img) return;
			var href = "";
			var link = img.closest("a");
			if(link) href = link.getAttribute("href") || "";
			var candidates = [];
			if(href) candidates.push(href);
			var srcset = img.getAttribute("srcset") || "";
			srcset.split(",").forEach(function(part){
				var u = part.trim().split(" ")[0];
				if(u) candidates.push(u);
			});
			var ds = img.getAttribute("data-src");
			if(ds) candidates.push(ds);
			var current = img.getAttribute("src") || "";
			if(current) candidates.push(current);
			var best = "";
			candidates.forEach(function(u){
				if(!u) return;
				// Prefer full/scaled over hard-cropped 100x100 thumbs.
				if(/-\d+x\d+\.(jpe?g|png|webp|gif)$/i.test(u) && /-\d{2,3}x\d{2,3}\./.test(u) && !/-\d{3,}x\d{3,}\./.test(u)){
					return;
				}
				if(!best) best = u;
				if(/-scaled\./i.test(u) || !/-\d+x\d+\./.test(u)) best = u;
			});
			if(!best && href) best = href;
			if(best && best !== current){
				img.setAttribute("src", best);
				img.removeAttribute("srcset");
				img.removeAttribute("sizes");
			}
		}
		function normalizePlaceholders(root){
			if(!root) return;
			root.querySelectorAll(".image-placeholder").forEach(function(ph){
				ph.style.paddingBottom = "0";
				ph.style.height = "100%";
				ph.style.position = "relative";
				ph.style.overflow = "hidden";
				ph.style.display = "flex";
				ph.style.alignItems = "center";
				ph.style.justifyContent = "center";
			});
			root.querySelectorAll("img").forEach(function(img){
				if(img.closest(".product-images--thumbnails")){
					upgradeThumbSrc(img);
				}
				img.style.position = "static";
				img.style.objectFit = "contain";
				img.style.objectPosition = "center center";
				img.style.maxWidth = "100%";
				if(img.closest(".product-images--thumbnails")){
					img.style.maxHeight = "100%";
					img.style.width = "100%";
					img.style.height = "100%";
				} else {
					img.style.maxHeight = "min(560px, 70vh)";
					img.style.width = "auto";
					img.style.height = "auto";
				}
				img.style.opacity = "1";
				img.style.visibility = "visible";
				var ds = img.getAttribute("data-src");
				if(ds && (!img.getAttribute("src") || String(img.getAttribute("src")).indexOf("data:") === 0)){
					img.setAttribute("src", ds);
				}
				img.classList.remove("lazyload");
			});
		}
		function fixThumbStrip(){
			var thumbs = document.querySelector(".single-product .product-images--thumbnails");
			if(!thumbs) return;
			try{
				if(window.jQuery && jQuery.fn && jQuery.fn.slick && jQuery(thumbs).hasClass("slick-initialized")){
					jQuery(thumbs).slick("unslick");
				}
			}catch(err){}
			thumbs.classList.remove("slick-initialized","slick-slider","slick-vertical","slick-dotted","slick-swipe-sample");
			thumbs.querySelectorAll(".slick-cloned").forEach(function(el){ el.remove(); });
			var track = thumbs.querySelector(".slick-track");
			if(track){
				while(track.firstChild){ thumbs.appendChild(track.firstChild); }
			}
			thumbs.querySelectorAll(".slick-list, .slick-track, .slick-arrow").forEach(function(el){ el.remove(); });
			thumbs.style.setProperty("display", "flex", "important");
			thumbs.style.setProperty("flex-direction", "row", "important");
			thumbs.style.setProperty("flex-wrap", "wrap", "important");
			thumbs.style.setProperty("align-items", "flex-start", "important");
			thumbs.style.setProperty("justify-content", "flex-start", "important");
			thumbs.style.setProperty("gap", "8px", "important");
			thumbs.style.setProperty("width", "100%", "important");
			thumbs.style.setProperty("max-width", "100%", "important");
			thumbs.style.setProperty("min-width", "0", "important");
			thumbs.style.setProperty("height", "auto", "important");
			thumbs.style.setProperty("max-height", "110px", "important");
			thumbs.style.setProperty("min-height", "0", "important");
			thumbs.style.setProperty("margin", "0", "important");
			thumbs.style.setProperty("padding", "0", "important");
			thumbs.style.setProperty("float", "none", "important");
			thumbs.style.setProperty("position", "static", "important");
			thumbs.style.setProperty("overflow", "hidden", "important");
			thumbs.querySelectorAll(".woocommerce-product-gallery__image").forEach(function(slide){
				slide.classList.remove("slick-slide","slick-current","slick-active","slick-cloned");
				slide.removeAttribute("data-slick-index");
				slide.removeAttribute("aria-hidden");
				slide.removeAttribute("tabindex");
				slide.style.setProperty("flex", "0 0 88px", "important");
				slide.style.setProperty("width", "88px", "important");
				slide.style.setProperty("min-width", "88px", "important");
				slide.style.setProperty("max-width", "88px", "important");
				slide.style.setProperty("height", "88px", "important");
				slide.style.setProperty("float", "none", "important");
				slide.style.setProperty("padding", "0", "important");
				slide.style.setProperty("margin", "0", "important");
				slide.style.setProperty("display", "block", "important");
				slide.style.setProperty("overflow", "hidden", "important");
				slide.style.setProperty("border-radius", "8px", "important");
			});
			normalizePlaceholders(thumbs);
			if(!thumbs.getAttribute("data-te-thumbs-bound")){
				thumbs.setAttribute("data-te-thumbs-bound", "1");
				thumbs.querySelectorAll("a").forEach(function(a, idx){
					a.addEventListener("click", function(ev){
						ev.preventDefault();
						var main = document.querySelector(".single-product .product-images--main");
						if(!main) return;
						try{
							if(window.jQuery && jQuery(main).hasClass("slick-initialized")){
								jQuery(main).slick("slickGoTo", idx);
								return;
							}
						}catch(err2){}
						main.querySelectorAll(".woocommerce-product-gallery__image").forEach(function(s, i){
							var show = i === idx;
							s.style.display = show ? "flex" : "none";
						});
					});
				});
			}
		}
		function fixMainGallery(){
			var main = document.querySelector(".single-product .product-images--main");
			if(!main) return;
			main.style.width = "100%";
			main.style.maxWidth = "100%";
			main.style.minWidth = "0";
			main.style.overflow = "hidden";
			[".slick-slider",".slick-list",".slick-track",".woocommerce-product-gallery__wrapper"].forEach(function(sel){
				var el = main.querySelector(sel);
				if(el){
					el.style.width = "100%";
					el.style.maxWidth = "100%";
					el.style.minWidth = "0";
					el.style.height = "auto";
					el.style.maxHeight = "min(560px, 70vh)";
				}
			});
			var track = main.querySelector(".slick-track");
			if(track){
				track.style.transform = "translate3d(0,0,0)";
				track.style.display = "block";
				track.style.height = "auto";
				track.style.width = "100%";
			}
			var list = main.querySelector(".slick-list");
			if(list){
				list.style.height = "auto";
				list.style.maxHeight = "min(560px, 70vh)";
			}
			main.style.removeProperty("height");
			main.style.setProperty("height", "auto", "important");
			main.style.setProperty("aspect-ratio", "auto", "important");
			main.querySelectorAll(".slick-slide, .woocommerce-product-gallery__image").forEach(function(slide, i){
				if(slide.classList.contains("slick-cloned")){
					slide.style.display = "none";
					slide.style.height = "0";
					return;
				}
				var show = slide.classList.contains("slick-current") || slide.classList.contains("slick-active") || i === 0;
				slide.style.display = show ? "block" : "none";
				slide.style.height = "auto";
				slide.style.width = "100%";
				slide.style.overflow = "visible";
			});
			normalizePlaceholders(main);
		}
		function fixProductGallery(){
			var product = document.querySelector(".single-product div.product");
			if(product && window.innerWidth <= 768){
				product.style.display = "flex";
				product.style.flexDirection = "column";
				product.style.width = "100%";
				product.style.maxWidth = "100%";
			}
			var images = document.querySelector(".single-product div.product div.images, .single-product .product-images-container");
			if(images){
				images.style.width = "100%";
				images.style.maxWidth = "100%";
				images.style.minWidth = "0";
				images.style.overflow = "hidden";
				images.style.display = "flex";
				images.style.flexDirection = "column";
			}
			var wrap = document.querySelector(".single-product .product-images");
			if(wrap){
				wrap.style.display = "flex";
				wrap.style.flexDirection = "column";
				wrap.style.width = "100%";
			}
			fixMainGallery();
			fixThumbStrip();
		}
		function boot(){
			fixProductGallery();
			setTimeout(fixProductGallery, 200);
			setTimeout(fixProductGallery, 600);
			setTimeout(fixProductGallery, 1400);
			setTimeout(fixProductGallery, 2500);
		}
		if(document.readyState === "loading"){
			document.addEventListener("DOMContentLoaded", boot);
		} else {
			boot();
		}
		window.addEventListener("load", function(){ setTimeout(fixProductGallery, 100); });
		window.addEventListener("resize", fixProductGallery);
		if(window.jQuery){
			jQuery(document).on("init reInit setPosition afterChange", ".product-images--main, .product-images--thumbnails", function(){
				setTimeout(fixProductGallery, 30);
			});
		}
	})();
	</script>';
}

add_action( 'wp_footer', 'te_fix_lazy_product_images', 40 );
function te_fix_lazy_product_images() {
	if ( ! function_exists( 'is_woocommerce' ) || ! ( is_woocommerce() || is_shop() || is_product_taxonomy() || is_product() ) ) {
		return;
	}
	echo '<script>
	document.addEventListener("DOMContentLoaded", function(){
		document.querySelectorAll("img.lazyload, img[data-src]").forEach(function(img){
			var ds = img.getAttribute("data-src");
			if (ds && (!img.getAttribute("src") || img.getAttribute("src").indexOf("data:") === 0)) {
				img.setAttribute("src", ds);
			}
			img.classList.remove("lazyload");
			img.style.opacity = "1";
			img.style.visibility = "visible";
		});
	});
	</script>';
}

add_filter( 'wp_get_attachment_image_attributes', 'te_wc_product_image_alt', 15, 3 );
function te_wc_product_image_alt( $attr, $attachment, $size ) {
	if ( ! function_exists( 'is_woocommerce' ) || ! ( is_woocommerce() || is_shop() || is_product_taxonomy() || is_product() ) ) {
		return $attr;
	}
	if ( ! empty( $attr['alt'] ) ) {
		return $attr;
	}
	$attachment_id = is_object( $attachment ) ? (int) $attachment->ID : (int) $attachment;
	$alt           = trim( (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) );
	if ( $alt !== '' ) {
		$attr['alt'] = $alt;
		return $attr;
	}
	$product = null;
	if ( isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof WC_Product ) {
		$product = $GLOBALS['product'];
	} elseif ( is_product() ) {
		$product = wc_get_product( get_the_ID() );
	} else {
		$parent_id = (int) wp_get_post_parent_id( $attachment_id );
		if ( $parent_id && get_post_type( $parent_id ) === 'product' ) {
			$product = wc_get_product( $parent_id );
		}
	}
	if ( $product ) {
		$attr['alt'] = te_product_image_alt_text( $product );
	}
	return $attr;
}

function te_product_image_alt_text( WC_Product $product ): string {
	$image_id = (int) $product->get_image_id();
	if ( $image_id ) {
		$alt = trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
		if ( $alt !== '' ) {
			return $alt;
		}
	}
	return wp_strip_all_tags( $product->get_name() );
}

add_filter( 'woocommerce_gallery_image_html_attachment_image_params', 'te_wc_gallery_image_alt', 10, 4 );
function te_wc_gallery_image_alt( $params, $attachment_id, $image_size, $main_image ) {
	if ( ! empty( $params['alt'] ) ) {
		return $params;
	}
	$alt = trim( (string) get_post_meta( (int) $attachment_id, '_wp_attachment_image_alt', true ) );
	if ( $alt === '' && isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof WC_Product ) {
		$alt = te_product_image_alt_text( $GLOBALS['product'] );
	}
	if ( $alt !== '' ) {
		$params['alt'] = $alt;
	}
	return $params;
}

add_filter( 'woocommerce_product_get_image', 'te_wc_product_get_image_alt', 20, 6 );
function te_wc_product_get_image_alt( $image, $product, $size, $attr, $placeholder, $image_attr ) {
	if ( ! $image || ! $product instanceof WC_Product ) {
		return $image;
	}
	$alt = te_product_image_alt_text( $product );
	if ( $alt === '' ) {
		return $image;
	}
	if ( preg_match( '/alt=(["\'])\s*\1/', $image ) ) {
		return preg_replace( '/alt=(["\'])\s*\1/', 'alt="' . esc_attr( $alt ) . '"', $image, 1 );
	}
	if ( ! preg_match( '/alt=/', $image ) ) {
		return preg_replace( '/<img\s/', '<img alt="' . esc_attr( $alt ) . '" ', $image, 1 );
	}
	return $image;
}

add_filter( 'wp_get_attachment_image_attributes', 'te_wc_image_no_lazy', 99 );
function te_wc_image_no_lazy( $attr ) {
	if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_shop() || is_product_taxonomy() || is_product() ) ) {
		if ( ! empty( $attr['data-src'] ) && empty( $attr['src'] ) ) {
			$attr['src'] = $attr['data-src'];
		}
		if ( isset( $attr['class'] ) ) {
			$attr['class'] = trim( str_replace( 'lazyload', '', $attr['class'] ) );
		}
	}
	return $attr;
}

/* == fin TE-SINGLE-PRODUCT == */


/* == TE-404: página amigable estilo premium == */
add_filter( 'pre_get_document_title', 'te_friendly_404_title', 12 );
function te_friendly_404_title( $title ) {
	if ( is_404() ) {
		return 'Página no encontrada – Tu Exhibidor';
	}
	return $title;
}

add_action( 'wp_head', 'te_friendly_404_css', 10000 );
function te_friendly_404_css() {
	if ( ! is_404() ) {
		return;
	}
	echo '<style>
	.error404 .not-found,
	.error404 .page-title,
	.error404 .entry-header{ display:none !important; }
	.te-404{
		position:relative;
		padding:48px 24px 80px;
		min-height:calc(100vh - 220px);
		display:flex;
		align-items:center;
		justify-content:center;
		background:
			radial-gradient(ellipse 80% 60% at 50% 0%, rgba(184,147,95,.12), transparent 70%),
			var(--cream,#ebe3d8);
	}
	.te-404__inner{
		position:relative;
		width:min(640px, 100%);
		text-align:center;
	}
	.te-404__watermark{
		position:absolute;
		top:-28px;
		left:50%;
		transform:translateX(-50%);
		font-family:"Poppins",sans-serif;
		font-size:clamp(7rem, 22vw, 11rem);
		font-weight:700;
		line-height:1;
		color:rgba(184,147,95,.14);
		pointer-events:none;
		user-select:none;
		margin:0;
	}
	.te-404__logo{
		display:block;
		margin:0 auto 18px;
		opacity:.95;
	}
	.te-404__eyebrow{
		margin:0 0 8px;
		font-size:12px;
		letter-spacing:.14em;
		text-transform:uppercase;
		color:var(--gold-dark,#96723f);
		font-weight:600;
	}
	.te-404__title{
		font-family:"Poppins",sans-serif !important;
		font-size:clamp(1.75rem, 4vw, 2.35rem);
		font-weight:600;
		color:var(--ink,#2b2926);
		margin:0 0 14px;
		line-height:1.2;
	}
	.te-404__lead{
		margin:0 auto 18px;
		max-width:520px;
		font-size:15px;
		line-height:1.75;
		color:var(--muted,#8a8378);
	}
	.te-404__path{
		margin:0 auto 28px;
		font-size:12px;
		color:var(--muted,#8a8378);
	}
	.te-404__path code{
		display:inline-block;
		margin-top:4px;
		padding:4px 10px;
		border-radius:8px;
		background:rgba(255,255,255,.5);
		border:1px solid rgba(184,147,95,.2);
		font-size:11px;
		color:var(--ink,#2b2926);
		word-break:break-all;
	}
	.te-404__card{
		margin:0 auto 28px;
		padding:28px 24px 22px;
		border-radius:18px;
		background:var(--surface,#ddd3c8);
		border:1px solid rgba(184,147,95,.22);
		box-shadow:0 8px 28px rgba(43,41,38,.08);
		text-align:center;
	}
	.te-404__card-title{
		font-family:"Poppins",sans-serif !important;
		font-size:1.35rem;
		margin:0 0 6px;
		color:var(--ink,#2b2926);
	}
	.te-404__card-sub{
		margin:0 0 18px;
		font-size:13px;
		color:var(--muted,#8a8378);
	}
	.te-404__contacts{
		display:grid;
		grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
		gap:12px;
		margin-bottom:16px;
	}
	.te-404__contact{
		display:flex;
		flex-direction:column;
		align-items:flex-start;
		gap:4px;
		padding:16px 18px;
		border-radius:14px;
		text-decoration:none;
		color:var(--ink,#2b2926);
		background:rgba(255,255,255,.45);
		border:1px solid rgba(184,147,95,.18);
		transition:background .25s ease, border-color .25s ease, transform .25s ease;
	}
	.te-404__contact:hover{
		background:rgba(37,211,102,.08);
		border-color:#25D366;
		transform:translateY(-2px);
		color:var(--ink,#2b2926);
	}
	.te-404__contact-icon{
		display:inline-flex;
		align-items:center;
		justify-content:center;
		width:36px;
		height:36px;
		border-radius:50%;
		background:#25D366;
		color:#fff;
		margin-bottom:4px;
	}
	.te-404__contact-name{
		font-weight:600;
		font-size:15px;
	}
	.te-404__contact-num{
		font-size:12px;
		color:var(--muted,#8a8378);
	}
	.te-404__email{
		margin:0 0 14px;
		font-size:13px;
		color:var(--muted,#8a8378);
	}
	.te-404__email a{
		color:var(--gold-dark,#96723f);
		font-weight:500;
		text-decoration:none;
	}
	.te-404__email a:hover{ color:var(--gold,#b8935f); }
	.te-404__social{ justify-content:center !important; margin-top:4px; }
	.te-404__nav{
		display:flex;
		flex-wrap:wrap;
		gap:12px;
		justify-content:center;
	}
	.te-404__btn{
		display:inline-flex;
		align-items:center;
		justify-content:center;
		padding:12px 22px;
		border-radius:999px;
		font-size:13px;
		font-weight:500;
		text-decoration:none;
		transition:background .25s ease, color .25s ease, transform .25s ease, border-color .25s ease;
	}
	.te-404__btn--gold{
		background:var(--gold,#b8935f);
		color:#fff !important;
		border:2px solid var(--gold,#b8935f);
	}
	.te-404__btn--gold:hover{
		background:var(--gold-dark,#96723f);
		border-color:var(--gold-dark,#96723f);
		transform:translateY(-2px);
		color:#fff !important;
	}
	.te-404__btn--outline{
		background:transparent;
		color:var(--gold-dark,#96723f) !important;
		border:1.5px solid rgba(184,147,95,.55);
	}
	.te-404__btn--outline:hover{
		background:var(--gold,#b8935f);
		border-color:var(--gold,#b8935f);
		color:#fff !important;
		transform:translateY(-2px);
	}
	@media (max-width: 520px){
		.te-404{ padding:32px 16px 64px; }
		.te-404__card{ padding:22px 16px 18px; }
		.te-404__contacts{ grid-template-columns:1fr; }
		.te-404__nav{ flex-direction:column; align-items:stretch; }
		.te-404__btn{ width:100%; }
	}
	</style>';
}
/* == fin TE-404 == */

/* == TE-PICSART-CLEAN: quitar metadata de captions y descripciones == */
function te_is_picsart_metadata( string $text ): bool {
	if ( $text === '' ) {
		return false;
	}
	if ( preg_match( '/"remix_data"|"fte_image_ids"|"total_effects_time"|"total_editor_time"|"tools_used"|"effects_applied"|"photos_added"|"edited_since_last_sticker_save"/i', $text ) ) {
		return true;
	}
	return (bool) ( preg_match( '/picsart/i', $text ) && preg_match( '/"uid"\s*:/', $text ) );
}

function te_clean_picsart_text( string $text ): string {
	if ( ! te_is_picsart_metadata( $text ) ) {
		return $text;
	}
	$trim = trim( $text );
	if ( preg_match( '/^[\{\[]/', $trim ) ) {
		return '';
	}
	$clean = preg_replace( '/\{[^{}]*"remix_data"[^{}]*\}/s', '', $text );
	$clean = preg_replace( '/\{[^{}]*"fte_image_ids"[^{}]*\}/s', '', (string) $clean );
	return trim( (string) $clean );
}

add_filter( 'wp_get_attachment_caption', 'te_strip_picsart_attachment_caption', 20, 2 );
function te_strip_picsart_attachment_caption( $caption, $post_id ) {
	$caption = (string) $caption;
	return te_clean_picsart_text( $caption );
}

add_filter( 'the_content', 'te_strip_picsart_product_content', 5 );
function te_strip_picsart_product_content( $content ) {
	if ( ! is_singular( 'product' ) ) {
		return $content;
	}
	return te_clean_picsart_text( (string) $content );
}
/* == fin TE-PICSART-CLEAN == */

/* == TE-BRAND-LOGO: imagen original en header (reemplaza texto TU EXHIBIDOR) == */
function te_brand_logo_img_markup(): string {
	$home = esc_url( home_url( '/' ) );
	$webp = esc_url( home_url( '/public/images/brand/logo-tuexhibidor-ink-96.webp' ) );
	$png  = esc_url( home_url( '/public/images/brand/logo-tuexhibidor-ink-96.png' ) );
	return '<a href="' . $home . '" class="te-brand-logo-link" rel="home">'
		. '<picture><source srcset="' . $webp . '" type="image/webp">'
		. '<img src="' . $png . '" class="logo-dimensions normal-logo" id="site-logo" alt="Tu Exhibidor — exhibidores de alta joyería" width="52" height="52" decoding="async">'
		. '</picture></a>';
}

add_action( 'template_redirect', 'te_buffer_fix_text_logo', 1 );
function te_buffer_fix_text_logo() {
	if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'te_replace_text_logo_html' );
}

function te_replace_text_logo_html( string $html ): string {
	if ( strpos( $html, 'text-logo' ) === false ) {
		return $html;
	}
	$logo = te_brand_logo_img_markup();
	return (string) preg_replace(
		'#<div class="logo text-logo">\s*<a[^>]*>.*?</a>\s*</div>#s',
		'<div class="logo image-logo te-brand-logo">' . $logo . '</div>',
		$html
	);
}

add_action( 'wp_head', 'te_brand_logo_styles', 4 );
function te_brand_logo_styles() {
	echo '<style>
	.logo.te-brand-logo,
	.logo.image-logo.te-brand-logo{ display:flex; align-items:center; justify-content:center; }
	.logo.te-brand-logo a,
	.logo.te-brand-logo .te-brand-logo-link{ display:inline-flex; align-items:center; padding:4px 0; line-height:0; text-decoration:none !important; }
	.logo.te-brand-logo img,
	.site-header .logo.te-brand-logo #site-logo{
		height:52px !important;
		width:auto !important;
		max-height:52px !important;
		display:block;
	}
	.logo.text-logo{ font-size:0 !important; letter-spacing:0 !important; }
	</style>';
}
/* == fin TE-BRAND-LOGO == */
