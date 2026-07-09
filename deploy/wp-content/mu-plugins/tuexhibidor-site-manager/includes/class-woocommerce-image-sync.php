<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce product thumbnail → public/images/catalog/{slug}.jpg
 */
final class Tuexhibidor_Site_Manager_WooCommerce_Image_Sync {

	private const BULK_SYNC_VERSION = '2026-07-07-bulk-3';

	/** @var array<string, true> */
	private static $synced = array();

	/** One-shot: push site catalog JPGs → WooCommerce featured images. */
	private const PUSH_TO_WC_VERSION = '2026-07-08-site-covers-v1';

	/** Products per request when running front-end batch push. */
	private const PUSH_BATCH_SIZE = 8;

	public static function init(): void {
		add_action( 'woocommerce_product_set_image_id', array( __CLASS__, 'on_product_image' ), 30, 2 );
		add_action( 'set_post_thumbnail', array( __CLASS__, 'on_set_thumbnail' ), 30, 3 );
		add_action( 'updated_post_meta', array( __CLASS__, 'on_thumbnail_meta' ), 30, 4 );
		add_action( 'wp_ajax_tuex_sm_sync_images_from_wc', array( __CLASS__, 'ajax_sync_all' ) );
		add_action( 'wp_ajax_tuex_sm_push_covers_to_wc', array( __CLASS__, 'ajax_push_covers_to_wc' ) );
		add_action( 'init', array( __CLASS__, 'maybe_run_bulk_once' ), 99 );
		add_action( 'init', array( __CLASS__, 'maybe_push_covers_to_wc_once' ), 100 );
	}

	public static function on_product_image( $product_id, $image_id ): void {
		$product_id = (int) $product_id;
		$image_id   = (int) $image_id;
		if ( ! $product_id || ! $image_id ) {
			return;
		}
		self::maybe_sync( $product_id, $image_id );
	}

	public static function on_set_thumbnail( $post_id, $thumbnail_id, $prev_thumbnail_id ): void {
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}
		$post_id       = (int) $post_id;
		$thumbnail_id  = (int) $thumbnail_id;
		$prev_thumb_id = (int) $prev_thumbnail_id;
		if ( ! $post_id || ! $thumbnail_id || $thumbnail_id === $prev_thumb_id ) {
			return;
		}
		self::maybe_sync( $post_id, $thumbnail_id );
	}

	public static function on_thumbnail_meta( $meta_id, $post_id, $meta_key, $meta_value ): void {
		if ( '_thumbnail_id' !== $meta_key || 'product' !== get_post_type( $post_id ) ) {
			return;
		}
		$image_id = (int) $meta_value;
		if ( $image_id > 0 ) {
			self::maybe_sync( (int) $post_id, $image_id );
		}
	}

	public static function ajax_sync_all(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
		}
		check_ajax_referer( 'tuex_sm', 'nonce' );
		@set_time_limit( 300 );
		$result = self::sync_all_from_wc();
		wp_send_json_success( $result );
	}

	public static function maybe_run_bulk_once(): void {
		if ( get_option( 'tuex_sm_wc_img_sync_v' ) === self::BULK_SYNC_VERSION ) {
			return;
		}
		if ( ! is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) || ! Tuexhibidor_Site_Manager_Paths::is_ready() ) {
			return;
		}
		@set_time_limit( 300 );
		self::sync_all_from_wc();
		update_option( 'tuex_sm_wc_img_sync_v', self::BULK_SYNC_VERSION, false );
	}

	/**
	 * Admin one-shot: site catalog JPG → WC featured (all catalog products).
	 * Prefer the query/AJAX batch runner for large catalogs to avoid timeouts.
	 */
	public static function maybe_push_covers_to_wc_once(): void {
		if ( get_option( 'tuex_sm_push_covers_v' ) === self::PUSH_TO_WC_VERSION ) {
			return;
		}
		if ( ! is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) || ! Tuexhibidor_Site_Manager_Paths::is_ready() ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		@set_time_limit( 300 );
		$result = self::push_covers_to_wc_batch( 0, 200 );
		if ( empty( $result['done'] ) ) {
			return;
		}
		update_option( 'tuex_sm_push_covers_v', self::PUSH_TO_WC_VERSION, false );
	}

	public static function ajax_push_covers_to_wc(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
		}
		check_ajax_referer( 'tuex_sm', 'nonce' );
		$offset = isset( $_REQUEST['offset'] ) ? max( 0, (int) $_REQUEST['offset'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$limit  = isset( $_REQUEST['limit'] ) ? max( 1, min( 20, (int) $_REQUEST['limit'] ) ) : self::PUSH_BATCH_SIZE; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		@set_time_limit( 120 );
		$result = self::push_covers_to_wc_batch( $offset, $limit );
		if ( ! empty( $result['done'] ) ) {
			update_option( 'tuex_sm_push_covers_v', self::PUSH_TO_WC_VERSION, false );
		}
		wp_send_json_success( $result );
	}

	/**
	 * @deprecated One-shot public query runner removed after migration; kept no-op.
	 */
	public static function maybe_handle_push_covers_query(): void {
		// Intentionally disabled after the 2026-07-08 site→WC cover migration.
	}

	private static function push_covers_token(): string {
		return '';
	}

	/**
	 * Push public/images/catalog/{slug}.jpg → WooCommerce featured image for catalog products.
	 *
	 * @return array<string, mixed>
	 */
	public static function push_covers_to_wc_batch( int $offset, int $limit ): array {
		$catalog  = Tuexhibidor_Site_Manager_Data::load_catalog();
		$products = array_values(
			array_filter(
				$catalog['products'] ?? array(),
				static function ( $item ) {
					return is_array( $item ) && ! empty( $item['slug'] );
				}
			)
		);
		$total  = count( $products );
		$slice  = array_slice( $products, $offset, $limit );
		$ok     = 0;
		$skip   = 0;
		$fail   = 0;
		$codes  = array();
		$errors = array();

		foreach ( $slice as $item ) {
			$code = (string) ( $item['code'] ?? '' );
			$slug = (string) ( $item['slug'] ?? '' );
			$pid  = self::find_wc_product_id_for_catalog( $item );
			if ( ! $pid ) {
				++$skip;
				$errors[] = $code . ':no-wc-product';
				continue;
			}

			$rel  = (string) ( $item['image'] ?? ( 'public/images/catalog/' . $slug . '.jpg' ) );
			$path = Tuexhibidor_Site_Manager_Paths::abs_from_public( $rel );
			if ( ! is_readable( $path ) ) {
				++$fail;
				$errors[] = $code . ':missing-file';
				continue;
			}

			$alt = (string) ( $item['name'] ?? $code );
			$aid = Tuexhibidor_Site_Manager_Images::push_catalog_file_to_product( $pid, $path, $alt );
			if ( $aid > 0 ) {
				++$ok;
				$codes[] = $code;
			} else {
				++$fail;
				$errors[] = $code . ':upload-failed';
			}
		}

		$next = $offset + count( $slice );
		$done = $next >= $total;

		return array(
			'ok'       => $ok,
			'skip'     => $skip,
			'fail'     => $fail,
			'offset'   => $offset,
			'next'     => $next,
			'total'    => $total,
			'done'     => $done,
			'updated'  => $codes,
			'errors'   => $errors,
			'message'  => sprintf(
				'Lote offset %1$d: ok=%2$d skip=%3$d fail=%4$d (total %5$d).',
				$offset,
				$ok,
				$skip,
				$fail,
				$total
			),
		);
	}

	private static function maybe_sync( int $product_id, int $image_id ): void {
		if ( Tuexhibidor_Site_Manager_Images::is_pushing_to_wc() ) {
			return;
		}
		if ( ! Tuexhibidor_Site_Manager_Paths::is_ready() ) {
			return;
		}

		$key = $product_id . ':' . $image_id;
		if ( isset( self::$synced[ $key ] ) ) {
			return;
		}
		self::$synced[ $key ] = true;

		$entry = self::find_catalog_entry_for_wc( $product_id );
		if ( ! $entry ) {
			return;
		}
		self::sync_catalog_item( $entry, $image_id );
	}

	/**
	 * @return array{ok:int,skip:int,fail:int,missing:array<int,string>}
	 */
	public static function sync_all_from_wc(): array {
		$catalog  = Tuexhibidor_Site_Manager_Data::load_catalog();
		$products = $catalog['products'] ?? array();
		$ok       = 0;
		$skip     = 0;
		$fail     = 0;
		$missing  = array();

		foreach ( $products as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$code = (string) ( $item['code'] ?? '' );
			$pid  = self::find_wc_product_id_for_catalog( $item );
			if ( ! $pid ) {
				++$skip;
				if ( $code ) {
					$missing[] = $code;
				}
				continue;
			}
			$image_id = (int) get_post_thumbnail_id( $pid );
			if ( ! $image_id ) {
				++$skip;
				continue;
			}
			if ( self::sync_catalog_item( $item, $image_id ) ) {
				++$ok;
			} else {
				++$fail;
			}
		}

		Tuexhibidor_Site_Manager_Data::bump_cache_version();

		return array(
			'ok'      => $ok,
			'skip'    => $skip,
			'fail'    => $fail,
			'missing' => $missing,
			'message' => sprintf(
				'Sincronizadas %1$d imágenes. Omitidas: %2$d. Fallidas: %3$d.',
				$ok,
				$skip,
				$fail
			),
		);
	}

	/**
	 * @param array<string, mixed> $item
	 */
	private static function sync_catalog_item( array $item, int $image_id ): bool {
		$site_slug = (string) ( $item['slug'] ?? '' );
		if ( ! $site_slug || ! $image_id ) {
			return false;
		}
		if ( ! Tuexhibidor_Site_Manager_Images::save_catalog_jpg( $image_id, $site_slug ) ) {
			return false;
		}

		$catalog  = Tuexhibidor_Site_Manager_Data::load_catalog();
		$products = $catalog['products'] ?? array();
		$rel      = 'public/images/catalog/' . $site_slug . '.jpg';
		$changed  = false;

		foreach ( $products as &$product ) {
			if ( ! is_array( $product ) ) {
				continue;
			}
			if ( ( $product['slug'] ?? '' ) === $site_slug ) {
				$product['image']   = $rel;
				$product['imageOk'] = true;
				$changed            = true;
				break;
			}
		}
		unset( $product );

		if ( $changed ) {
			Tuexhibidor_Site_Manager_Data::save_catalog( $products, $catalog['scores'] ?? array() );
		}

		return true;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function find_catalog_entry_for_wc( int $product_id ): ?array {
		$sku  = (string) get_post_meta( $product_id, '_sku', true );
		$slug = (string) get_post_field( 'post_name', $product_id );
		$name = (string) get_post_field( 'post_title', $product_id );

		$catalog = Tuexhibidor_Site_Manager_Data::load_catalog();
		foreach ( $catalog['products'] ?? array() as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			if ( self::catalog_item_matches_wc( $item, $sku, $slug, $name ) ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * @param array<string, mixed> $item
	 */
	public static function find_wc_product_id_for_catalog( array $item ): int {
		$code = (string) ( $item['code'] ?? '' );
		$slug = (string) ( $item['slug'] ?? '' );

		if ( $code && function_exists( 'wc_get_product_id_by_sku' ) ) {
			foreach ( self::sku_variants( $code ) as $sku_try ) {
				$by_sku = (int) wc_get_product_id_by_sku( $sku_try );
				if ( $by_sku > 0 ) {
					return $by_sku;
				}
			}
		}

		if ( $slug ) {
			$by_slug = get_page_by_path( $slug, OBJECT, 'product' );
			if ( $by_slug ) {
				return (int) $by_slug->ID;
			}
		}

		if ( ! function_exists( 'wc_get_products' ) ) {
			return 0;
		}

		$ids = wc_get_products(
			array(
				'limit'  => -1,
				'return' => 'ids',
				'status' => array( 'publish', 'draft', 'private' ),
			)
		);

		foreach ( $ids as $pid ) {
			$pid = (int) $pid;
			if ( $pid <= 0 ) {
				continue;
			}
			$wc_sku  = (string) get_post_meta( $pid, '_sku', true );
			$wc_slug = (string) get_post_field( 'post_name', $pid );
			$wc_name = (string) get_post_field( 'post_title', $pid );
			if ( self::catalog_item_matches_wc( $item, $wc_sku, $wc_slug, $wc_name ) ) {
				return $pid;
			}
		}

		return 0;
	}

	/**
	 * @param array<string, mixed> $item
	 */
	private static function catalog_item_matches_wc( array $item, string $wc_sku, string $wc_slug, string $wc_name ): bool {
		$code     = (string) ( $item['code'] ?? '' );
		$cat_slug = (string) ( $item['slug'] ?? '' );

		if ( $code && $wc_sku ) {
			foreach ( self::sku_variants( $code ) as $code_try ) {
				foreach ( self::sku_variants( $wc_sku ) as $sku_try ) {
					if ( 0 === strcasecmp( $code_try, $sku_try ) ) {
						return true;
					}
				}
			}
		}

		if ( $cat_slug && $wc_slug && $cat_slug === $wc_slug ) {
			return true;
		}

		if ( $code && self::slug_matches_code( $wc_slug, $code ) ) {
			return true;
		}

		if ( $code && self::name_contains_code( $wc_name, $code ) ) {
			return true;
		}

		// Alias: catálogo E-35 ↔ WooCommerce E-XNL (mismo producto en tienda).
		if ( $code && $wc_sku && self::codes_are_aliases( $code, $wc_sku ) ) {
			return true;
		}

		return false;
	}

	/** @return array<int, string> */
	private static function sku_variants( string $code ): array {
		$code = strtoupper( trim( $code ) );
		if ( ! $code ) {
			return array();
		}
		$variants = array( $code );
		if ( str_starts_with( $code, 'TE-' ) ) {
			$variants[] = substr( $code, 3 );
		} else {
			$variants[] = 'TE-' . $code;
		}
		return array_values( array_unique( $variants ) );
	}

	private static function normalize_key( string $value ): string {
		return preg_replace( '/[^A-Z0-9]/', '', strtoupper( $value ) );
	}

	private static function slug_matches_code( string $slug, string $code ): bool {
		$slug = strtolower( $slug );
		$code = strtolower( $code );
		if ( ! $slug || ! $code ) {
			return false;
		}
		if ( str_contains( $slug, $code ) ) {
			return true;
		}
		$nk = self::normalize_key( $code );
		$ns = self::normalize_key( $slug );
		return $nk && ( $ns === $nk || str_contains( $ns, $nk ) );
	}

	private static function name_contains_code( string $name, string $code ): bool {
		$name = strtolower( $name );
		$code = strtolower( $code );
		if ( ! $name || ! $code ) {
			return false;
		}
		return str_contains( $name, '(' . $code . ')' ) || str_contains( $name, '(' . strtolower( $code ) . ')' );
	}

	private static function codes_are_aliases( string $a, string $b ): bool {
		$aliases = array(
			'E-35'  => array( 'E-XNL', 'EXNL', '7-XNL' ),
			'E-XNL' => array( 'E-35', 'EXNL', '7-XNL' ),
		);
		$a = strtoupper( trim( $a ) );
		$b = strtoupper( trim( $b ) );
		if ( isset( $aliases[ $a ] ) && in_array( $b, $aliases[ $a ], true ) ) {
			return true;
		}
		if ( isset( $aliases[ $b ] ) && in_array( $a, $aliases[ $b ], true ) ) {
			return true;
		}
		return false;
	}
}
