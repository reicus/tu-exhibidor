<?php
/**
 * Plugin Name: Tu Exhibidor — Búsqueda catálogo
 * Description: Envía la búsqueda al catálogo WooCommerce (productos y categorías), evitando redirección al sitio estático.
 * Version: 1.0.0
 * Author: Tecnotix Solutions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function te_shop_search_url() {
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$shop = wc_get_page_permalink( 'shop' );
		if ( $shop ) {
			return $shop;
		}
	}
	return home_url( '/shop/' );
}

add_action( 'template_redirect', 'te_redirect_root_search_to_shop', 0 );
function te_redirect_root_search_to_shop() {
	if ( is_admin() || wp_doing_ajax() || is_customize_preview() ) {
		return;
	}
	if ( empty( $_GET['s'] ) ) {
		return;
	}
	if ( isset( $_GET['post_type'] ) && 'product' !== $_GET['post_type'] ) {
		return;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return;
	}

	$term = sanitize_text_field( wp_unslash( $_GET['s'] ) );
	if ( '' === $term ) {
		return;
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				's'         => $term,
				'post_type' => 'product',
			),
			te_shop_search_url()
		),
		302
	);
	exit;
}

add_action( 'template_redirect', 'te_patch_home_redirect_for_search', 2 );
function te_patch_home_redirect_for_search() {
	if ( is_admin() || wp_doing_ajax() || is_customize_preview() ) {
		return;
	}
	if ( empty( $_GET['s'] ) && ! is_search() ) {
		return;
	}
	remove_action( 'template_redirect', 'te_redirect_wp_home_to_static', 1 );
}

add_action( 'pre_get_posts', 'te_limit_shop_search_to_products', 20 );
function te_limit_shop_search_to_products( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$term = $query->get( 's' );
	if ( ! $term && ! empty( $_GET['s'] ) ) {
		$term = sanitize_text_field( wp_unslash( $_GET['s'] ) );
	}
	if ( '' === $term ) {
		return;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		$query->set( 'post_type', 'product' );
	}
}

add_action( 'pre_get_posts', 'te_shop_search_hide_out_of_stock', 30 );
function te_shop_search_hide_out_of_stock( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$term = $query->get( 's' );
	if ( ! $term && ! empty( $_GET['s'] ) ) {
		$term = sanitize_text_field( wp_unslash( $_GET['s'] ) );
	}
	if ( '' === $term ) {
		return;
	}

	$post_type = $query->get( 'post_type' );
	if ( $post_type && 'product' !== $post_type ) {
		return;
	}

	$meta_query = $query->get( 'meta_query' );
	if ( ! is_array( $meta_query ) ) {
		$meta_query = array();
	}
	$meta_query[] = array(
		'key'     => '_stock_status',
		'value'   => 'instock',
		'compare' => '=',
	);
	$query->set( 'meta_query', $meta_query );
}

add_filter( 'posts_clauses', 'te_product_search_include_categories', 20, 2 );
function te_product_search_include_categories( $clauses, $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->get( 's' ) ) {
		return $clauses;
	}
	if ( 'product' !== $query->get( 'post_type' ) ) {
		return $clauses;
	}

	global $wpdb;
	$like = '%' . $wpdb->esc_like( $query->get( 's' ) ) . '%';

	$clauses['join']    .= " LEFT JOIN {$wpdb->term_relationships} te_tr ON {$wpdb->posts}.ID = te_tr.object_id ";
	$clauses['join']    .= " LEFT JOIN {$wpdb->term_taxonomy} te_tt ON te_tr.term_taxonomy_id = te_tt.term_taxonomy_id AND te_tt.taxonomy = 'product_cat' ";
	$clauses['join']    .= " LEFT JOIN {$wpdb->terms} te_t ON te_tt.term_id = te_t.term_id ";
	$clauses['where']   .= $wpdb->prepare( ' OR (te_t.name LIKE %s)', $like );
	$clauses['distinct'] = 'DISTINCT';

	return $clauses;
}

add_action( 'template_redirect', 'te_shop_search_output_buffer', 5 );
function te_shop_search_output_buffer() {
	if ( is_admin() || wp_doing_ajax() || is_feed() ) {
		return;
	}
	ob_start( 'te_shop_search_fix_html' );
}

function te_shop_search_fix_html( $html ) {
	$shop = esc_url( te_shop_search_url() );
	$html = preg_replace(
		'/(<form\\b[^>]*\\bclass="[^"]*\\bsearch-form\\b[^"]*"[^>]*)action="[^"]*"/i',
		'$1action="' . $shop . '"',
		$html
	);
	$html = preg_replace(
		'/(<form\\b[^>]*\\bclass="[^"]*\\bsearch-form\\b[^"]*"[^>]*>)(\\s*<div class="search-input-env">)/i',
		'$1<input type="hidden" name="post_type" value="product" />$2',
		$html
	);
	$html = str_replace( 'placeholder="Search..."', 'placeholder="Buscar productos o categorías…"', $html );
	return $html;
}

add_action( 'wp_footer', 'te_fix_shop_search_forms', 5 );
function te_fix_shop_search_forms() {
	$shop = esc_js( te_shop_search_url() );
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function(){
		var shopUrl = '<?php echo $shop; ?>';
		document.querySelectorAll('form.search-form, header form[method="get"]').forEach(function(form){
			if (!form.querySelector('input[name="s"]')) return;
			if (form.dataset.teSearchReady) return;
			form.dataset.teSearchReady = '1';
			form.setAttribute('action', shopUrl);
			if (!form.querySelector('input[name="post_type"]')) {
				var hid = document.createElement('input');
				hid.type = 'hidden';
				hid.name = 'post_type';
				hid.value = 'product';
				form.appendChild(hid);
			}
			var inp = form.querySelector('input[name="s"]');
			if (inp) inp.placeholder = 'Buscar productos o categorías…';
			var btn = form.querySelector('.search-btn');
			var env = form.querySelector('.search-input-env');
			if (!btn || !inp) return;
			btn.addEventListener('click', function(e){
				if (inp.value.trim() === '') {
					e.preventDefault();
					form.classList.add('input-visible');
					if (env) env.classList.add('visible');
					setTimeout(function(){ inp.focus(); }, 80);
					return;
				}
				e.preventDefault();
				form.submit();
			});
		});
	});
	</script>
	<?php
}
