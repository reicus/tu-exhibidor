<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Unifica categorías WooCommerce legacy → premium y asegura productos publicados.
 */
final class Tuexhibidor_Site_Manager_Category_Merge {

	private const VERSION = '2026-07-07-v3';

	/** @var array<string, string> slug premium => nombre */
	private static $premium = array(
		'collares-cadenas' => 'Collares & Cadenas',
		'pulseras-relojes' => 'Pulseras & Relojes',
		'anillos'          => 'Anillos',
		'aros-zarcillos'   => 'Aros & Zarcillos',
		'bandejas-bases'   => 'Bandejas & Bases',
		'dijes-charms'     => 'Dijes & Charms',
		'sets-vitrina'     => 'Sets Vitrina Modular',
	);

	/** @var array<string, string> slug legacy => slug premium */
	private static $legacy_map = array(
		'aretes-y-anillos'   => 'aros-zarcillos',
		'cadenas-y-collares' => 'collares-cadenas',
		'pulseras-y-relojes' => 'pulseras-relojes',
		'vitrina'            => 'bandejas-bases',
	);

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'maybe_run' ), 24 );
		add_action( 'wp_ajax_tuex_sm_merge_categories', array( __CLASS__, 'ajax_run' ) );
	}

	public static function premium_categories(): array {
		return self::$premium;
	}

	public static function legacy_map(): array {
		return self::$legacy_map;
	}

	public static function category_base_url(): string {
		return trailingslashit( home_url( '/product-category' ) );
	}

	public static function maybe_run(): void {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return;
		}
		if ( self::VERSION === get_option( 'te_cat_merge_version' ) ) {
			return;
		}
		if ( get_transient( 'te_cat_merge_lock' ) ) {
			return;
		}
		set_transient( 'te_cat_merge_lock', '1', 120 );
		@set_time_limit( 300 );
		self::run();
		delete_transient( 'te_cat_merge_lock' );
	}

	public static function ajax_run(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
		}
		check_ajax_referer( 'tuex_sm', 'nonce' );
		@set_time_limit( 300 );
		delete_option( 'te_cat_merge_version' );
		$result = self::run();
		wp_send_json_success( $result );
	}

	/**
	 * @return array<string, int|string>
	 */
	public static function run(): array {
		self::ensure_premium_terms();

		$moved        = 0;
		$inferred     = 0;
		$published    = 0;
		$merged_terms = 0;

		$product_ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$premium_slugs = array_keys( self::$premium );

		foreach ( $product_ids as $product_id ) {
			$product_id = (int) $product_id;

			if ( 'publish' !== get_post_status( $product_id ) ) {
				wp_update_post(
					array(
						'ID'          => $product_id,
						'post_status' => 'publish',
					)
				);
				++$published;
			}

			$terms = wp_get_object_terms( $product_id, 'product_cat' );
			if ( is_wp_error( $terms ) ) {
				continue;
			}

			$term_ids   = array();
			$has_premium = false;

			foreach ( $terms as $term ) {
				$slug = $term->slug;
				if ( 'uncategorized' === $slug ) {
					continue;
				}
				if ( isset( self::$legacy_map[ $slug ] ) ) {
					$target = get_term_by( 'slug', self::$legacy_map[ $slug ], 'product_cat' );
					if ( $target && ! is_wp_error( $target ) ) {
						$term_ids[] = (int) $target->term_id;
						$has_premium = true;
						++$moved;
					}
					continue;
				}
				if ( in_array( $slug, $premium_slugs, true ) ) {
					$term_ids[]  = (int) $term->term_id;
					$has_premium = true;
				}
			}

			if ( ! $has_premium ) {
				$inferred_slug = self::infer_slug( $product_id );
				$inferred_term = get_term_by( 'slug', $inferred_slug, 'product_cat' );
				if ( $inferred_term && ! is_wp_error( $inferred_term ) ) {
					$term_ids[] = (int) $inferred_term->term_id;
					++$inferred;
				}
			}

			$term_ids = array_values( array_unique( array_filter( $term_ids ) ) );
			if ( $term_ids ) {
				wp_set_object_terms( $product_id, $term_ids, 'product_cat' );
			}
		}

		foreach ( self::$legacy_map as $old_slug => $new_slug ) {
			$old_term = get_term_by( 'slug', $old_slug, 'product_cat' );
			$new_term = get_term_by( 'slug', $new_slug, 'product_cat' );
			if ( ! $old_term || ! $new_term || is_wp_error( $old_term ) || is_wp_error( $new_term ) ) {
				continue;
			}
			$deleted = wp_delete_term( (int) $old_term->term_id, 'product_cat', (int) $new_term->term_id );
			if ( ! is_wp_error( $deleted ) && $deleted ) {
				++$merged_terms;
			}
		}

		clean_term_cache( array(), 'product_cat' );
		update_option( 'te_cat_merge_version', self::VERSION );
		update_option( 'te_legacy_cat_migrated', self::VERSION );

		return array(
			'version'       => self::VERSION,
			'products'      => count( $product_ids ),
			'moved'         => $moved,
			'inferred'      => $inferred,
			'published'     => $published,
			'merged_terms'  => $merged_terms,
			'premium_cats'  => count( self::$premium ),
		);
	}

	private static function ensure_premium_terms(): void {
		foreach ( self::$premium as $slug => $name ) {
			$existing = get_term_by( 'slug', $slug, 'product_cat' );
			if ( ! $existing ) {
				wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
			} elseif ( $existing->name !== $name ) {
				wp_update_term( (int) $existing->term_id, 'product_cat', array( 'name' => $name ) );
			}
		}
	}

	private static function infer_slug( int $product_id ): string {
		$name = mb_strtolower( wp_strip_all_tags( get_the_title( $product_id ) ), 'UTF-8' );
		$sku  = '';
		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$sku = strtoupper( (string) $product->get_sku() );
			}
		}

		if ( str_starts_with( $sku, 'TUE-STAND' ) || preg_match( '/set vitrina|stand-/', $name ) ) {
			return 'sets-vitrina';
		}
		if ( str_starts_with( $sku, 'TUE-DI' ) || str_starts_with( $sku, 'TUE-BC' ) || preg_match( '/dije|charm|encanto/', $name ) ) {
			return 'dijes-charms';
		}
		if ( preg_match( '/collar|cadena|busto|pechera|cuello/', $name ) ) {
			return 'collares-cadenas';
		}
		if ( preg_match( '/pulsera|reloj|t-bar|tbar/', $name ) ) {
			return 'pulseras-relojes';
		}
		if ( preg_match( '/aro|arete|zarcillo|colgante/', $name ) ) {
			return 'aros-zarcillos';
		}
		if ( preg_match( '/anillo/', $name ) ) {
			return 'anillos';
		}

		return 'bandejas-bases';
	}
}
