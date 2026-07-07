<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Tuexhibidor_Site_Manager_WooCommerce_Sync {

	private const SYNC_VERSION = '2026-07-07-cats-v2';
	private const BATCH_SIZE   = 15;
	private const SYNC_ENABLED = false;

	/** @var array<string, array{name:string,slug:string}> */
	private static $display_categories = array(
		'collares'     => array( 'name' => 'Collares & Cadenas', 'slug' => 'collares-cadenas' ),
		'pulseras'     => array( 'name' => 'Pulseras & Relojes', 'slug' => 'pulseras-relojes' ),
		'anillos'      => array( 'name' => 'Anillos', 'slug' => 'anillos' ),
		'aros'         => array( 'name' => 'Aros & Zarcillos', 'slug' => 'aros-zarcillos' ),
		'bandejas'     => array( 'name' => 'Bandejas & Bases', 'slug' => 'bandejas-bases' ),
		'dijes'        => array( 'name' => 'Dijes & Charms', 'slug' => 'dijes-charms' ),
		'sets-vitrina' => array( 'name' => 'Sets Vitrina Modular', 'slug' => 'sets-vitrina' ),
	);

	public static function init(): void {
		add_action( 'wp_ajax_tuex_sm_sync_wc', array( __CLASS__, 'ajax_sync' ) );
		add_action( 'init', array( __CLASS__, 'maybe_auto_sync' ), 20 );
	}

	public static function maybe_auto_sync(): void {
		if ( ! self::SYNC_ENABLED ) {
			return;
		}
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) || ! Tuexhibidor_Site_Manager_Paths::is_ready() ) {
			return;
		}
		if ( get_option( 'tuex_sm_wc_sync_version' ) === self::SYNC_VERSION ) {
			return;
		}
		if ( get_transient( 'tuex_sm_wc_sync_lock' ) ) {
			return;
		}
		set_transient( 'tuex_sm_wc_sync_lock', '1', 120 );
		@set_time_limit( 300 );

		while ( get_option( 'tuex_sm_wc_sync_version' ) !== self::SYNC_VERSION ) {
			$result = self::sync_batch( self::BATCH_SIZE );
			if ( $result['done'] || ( 0 === $result['created'] && 0 === $result['updated'] && 0 === $result['skipped'] ) ) {
				break;
			}
		}

		delete_transient( 'tuex_sm_wc_sync_lock' );
	}

	public static function ajax_sync(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
		}
		check_ajax_referer( 'tuex_sm', 'nonce' );
		@set_time_limit( 300 );
		$result = self::sync_batch( self::BATCH_SIZE );
		wp_send_json_success( $result );
	}

	/**
	 * @return array{created:int,updated:int,skipped:int,offset:int,total:int,done:bool}
	 */
	public static function sync_batch( int $limit = 15 ): array {
		$catalog  = Tuexhibidor_Site_Manager_Data::load_catalog();
		$products = $catalog['products'] ?? array();
		$total    = count( $products );
		$offset   = (int) get_option( 'tuex_sm_wc_sync_offset', 0 );

		$created = 0;
		$updated = 0;
		$skipped = 0;
		$slice   = array_slice( $products, $offset, $limit );

		foreach ( $slice as $item ) {
			$result = self::upsert_product( $item );
			if ( 'created' === $result ) {
				++$created;
			} elseif ( 'updated' === $result ) {
				++$updated;
			} else {
				++$skipped;
			}
		}

		$offset += count( $slice );
		update_option( 'tuex_sm_wc_sync_offset', $offset );

		$done = $offset >= $total;
		if ( $done ) {
			update_option( 'tuex_sm_wc_sync_version', self::SYNC_VERSION );
			delete_option( 'tuex_sm_wc_sync_offset' );
		}

		return array(
			'created' => $created,
			'updated' => $updated,
			'skipped' => $skipped,
			'offset'  => $offset,
			'total'   => $total,
			'done'    => $done,
		);
	}

	/**
	 * @param array<string, mixed> $item
	 */
	private static function upsert_product( array $item ): string {
		$slug = sanitize_title( (string) ( $item['slug'] ?? '' ) );
		$code = sanitize_text_field( (string) ( $item['code'] ?? '' ) );
		$name = wp_strip_all_tags( (string) ( $item['name'] ?? '' ) );

		if ( ! $slug || ! $code || ! $name ) {
			return 'skipped';
		}

		$product_id = self::find_product_id( $slug, $code );
		$is_new     = ! $product_id;

		if ( $is_new ) {
			$product_id = wp_insert_post(
				array(
					'post_type'   => 'product',
					'post_status' => 'publish',
					'post_title'  => $name,
					'post_name'   => $slug,
				),
				true
			);
			if ( is_wp_error( $product_id ) || ! $product_id ) {
				return 'skipped';
			}
		} else {
			wp_update_post(
				array(
					'ID'         => $product_id,
					'post_title' => $name,
					'post_name'  => $slug,
				)
			);
		}

		update_post_meta( $product_id, '_sku', $code );
		update_post_meta( $product_id, '_regular_price', '' );
		update_post_meta( $product_id, '_price', '' );
		update_post_meta( $product_id, '_manage_stock', 'no' );
		update_post_meta( $product_id, '_stock_status', 'instock' );
		update_post_meta( $product_id, '_virtual', 'no' );
		update_post_meta( $product_id, '_sold_individually', 'no' );

		wp_set_object_terms( $product_id, 'simple', 'product_type' );
		self::ensure_categories();
		self::assign_category( $product_id, $item );
		self::assign_image( $product_id, (string) ( $item['image'] ?? '' ), $name );

		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients( $product_id );
		}

		return $is_new ? 'created' : 'updated';
	}

	private static function find_product_id( string $slug, string $sku ): int {
		$by_slug = get_page_by_path( $slug, OBJECT, 'product' );
		if ( $by_slug ) {
			return (int) $by_slug->ID;
		}
		if ( function_exists( 'wc_get_product_id_by_sku' ) ) {
			$by_sku = wc_get_product_id_by_sku( $sku );
			if ( $by_sku ) {
				return (int) $by_sku;
			}
		}
		return 0;
	}

	public static function ensure_categories(): void {
		foreach ( self::$display_categories as $config ) {
			$existing = get_term_by( 'slug', $config['slug'], 'product_cat' );
			if ( ! $existing ) {
				wp_insert_term(
					$config['name'],
					'product_cat',
					array( 'slug' => $config['slug'] )
				);
			} elseif ( $existing->name !== $config['name'] ) {
				wp_update_term(
					(int) $existing->term_id,
					'product_cat',
					array( 'name' => $config['name'] )
				);
			}
		}
	}

	/**
	 * @param array<string, mixed> $item
	 */
	private static function assign_category( int $product_id, array $item ): void {
		$display = (string) ( $item['displayCategory'] ?? '' );
		if ( ! $display || ! isset( self::$display_categories[ $display ] ) ) {
			$legacy = array(
				'vitrina'  => 'bandejas',
				'cadenas'  => 'collares',
				'anillos'  => 'anillos',
				'pulseras' => 'pulseras',
			);
			$key     = (string) ( $item['categoryKey'] ?? '' );
			$display = $legacy[ $key ] ?? 'bandejas';
		}
		$slug = self::$display_categories[ $display ]['slug'];
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			wp_set_object_terms( $product_id, array( (int) $term->term_id ), 'product_cat' );
		}
	}

	private static function assign_image( int $product_id, string $relative_image, string $alt ): void {
		if ( has_post_thumbnail( $product_id ) ) {
			return;
		}

		$relative = ltrim( str_replace( '\\', '/', $relative_image ), '/' );
		$filepath = Tuexhibidor_Site_Manager_Paths::abs_from_public( $relative );

		if ( is_readable( $filepath ) ) {
			$attachment_id = self::attachment_from_file( $filepath, $product_id, $alt );
			if ( $attachment_id ) {
				set_post_thumbnail( $product_id, $attachment_id );
			}
			return;
		}

		$url = Tuexhibidor_Site_Manager_Paths::public_url( $relative );
		if ( ! $url ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attachment_id = media_sideload_image( $url, $product_id, $alt, 'id' );
		if ( ! is_wp_error( $attachment_id ) ) {
			set_post_thumbnail( $product_id, (int) $attachment_id );
		}
	}

	private static function attachment_from_file( string $filepath, int $parent_id, string $alt ): int {
		$filename = basename( $filepath );
		$existing = get_posts(
			array(
				'post_type'      => 'attachment',
				'posts_per_page' => 1,
				'meta_key'       => '_wp_attached_file',
				'meta_value'     => $filename,
				'fields'         => 'ids',
			)
		);
		if ( ! empty( $existing[0] ) ) {
			return (int) $existing[0];
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$upload = wp_upload_bits( $filename, null, (string) file_get_contents( $filepath ) );
		if ( ! empty( $upload['error'] ) ) {
			return 0;
		}

		$attachment = array(
			'post_mime_type' => wp_check_filetype( $filename )['type'] ?? 'image/jpeg',
			'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $parent_id );
		if ( is_wp_error( $attachment_id ) ) {
			return 0;
		}
		$meta = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $meta );
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt );
		return (int) $attachment_id;
	}
}
