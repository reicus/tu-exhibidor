<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce stock status → catalog-data.js inStock (sitio estático /site/).
 */
final class Tuexhibidor_Site_Manager_WooCommerce_Stock_Sync {

	private const BULK_SYNC_VERSION = '2026-07-07-stock-1';

	/** @var array<int, true> */
	private static $synced = array();

	public static function init(): void {
		add_action( 'woocommerce_product_set_stock_status', array( __CLASS__, 'on_stock_status' ), 30, 3 );
		add_action( 'woocommerce_update_product', array( __CLASS__, 'on_product_update' ), 30, 1 );
		add_action( 'updated_post_meta', array( __CLASS__, 'on_stock_meta' ), 30, 4 );
		add_action( 'save_post_product', array( __CLASS__, 'on_save_product' ), 30, 1 );
		add_action( 'wp_ajax_tuex_sm_sync_stock_from_wc', array( __CLASS__, 'ajax_sync_all' ) );
		add_action( 'init', array( __CLASS__, 'maybe_run_bulk_once' ), 98 );
	}

	public static function on_stock_status( $product_id, $status, $product ): void {
		unset( $status, $product );
		self::maybe_sync( (int) $product_id );
	}

	public static function on_product_update( $product_id ): void {
		self::maybe_sync( (int) $product_id );
	}

	public static function on_save_product( int $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		self::maybe_sync( $post_id );
	}

	public static function on_stock_meta( $meta_id, $post_id, $meta_key, $meta_value ): void {
		unset( $meta_id, $meta_value );
		if ( '_stock_status' !== $meta_key || 'product' !== get_post_type( $post_id ) ) {
			return;
		}
		self::maybe_sync( (int) $post_id );
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
		if ( get_option( 'tuex_sm_wc_stock_sync_v' ) === self::BULK_SYNC_VERSION ) {
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
		update_option( 'tuex_sm_wc_stock_sync_v', self::BULK_SYNC_VERSION, false );
	}

	private static function maybe_sync( int $product_id ): void {
		if ( ! $product_id || ! class_exists( 'WooCommerce' ) || ! Tuexhibidor_Site_Manager_Paths::is_ready() ) {
			return;
		}
		if ( isset( self::$synced[ $product_id ] ) ) {
			return;
		}
		self::$synced[ $product_id ] = true;

		$entry = Tuexhibidor_Site_Manager_WooCommerce_Image_Sync::find_catalog_entry_for_wc( $product_id );
		if ( ! $entry ) {
			return;
		}

		$in_stock = self::wc_product_in_stock( $product_id );
		if ( self::update_catalog_in_stock( (string) ( $entry['slug'] ?? '' ), $in_stock ) ) {
			Tuexhibidor_Site_Manager_Data::bump_cache_version();
		}
	}

	private static function wc_product_in_stock( int $product_id ): bool {
		$product = wc_get_product( $product_id );
		return $product && $product->is_in_stock();
	}

	private static function update_catalog_in_stock( string $slug, bool $in_stock ): bool {
		if ( ! $slug ) {
			return false;
		}

		$catalog  = Tuexhibidor_Site_Manager_Data::load_catalog();
		$products = $catalog['products'] ?? array();
		$changed  = false;

		foreach ( $products as &$product ) {
			if ( ! is_array( $product ) ) {
				continue;
			}
			if ( ( $product['slug'] ?? '' ) !== $slug ) {
				continue;
			}
			$current = ! isset( $product['inStock'] ) || false !== $product['inStock'];
			if ( $current === $in_stock ) {
				return false;
			}
			$product['inStock'] = $in_stock;
			$changed            = true;
			break;
		}
		unset( $product );

		if ( ! $changed ) {
			return false;
		}

		return Tuexhibidor_Site_Manager_Data::save_catalog( $products, $catalog['scores'] ?? array() );
	}

	/**
	 * @return array{updated:int,out_of_stock:int,total:int,message:string}
	 */
	public static function sync_all_from_wc(): array {
		if ( ! class_exists( 'WooCommerce' ) || ! Tuexhibidor_Site_Manager_Paths::is_ready() ) {
			return array(
				'updated'      => 0,
				'out_of_stock' => 0,
				'total'        => 0,
				'message'      => 'WooCommerce o catálogo no disponible.',
			);
		}

		$catalog  = Tuexhibidor_Site_Manager_Data::load_catalog();
		$products = $catalog['products'] ?? array();
		$updated  = 0;
		$oos      = 0;

		foreach ( $products as &$item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}
			$pid = Tuexhibidor_Site_Manager_WooCommerce_Image_Sync::find_wc_product_id_for_catalog( $item );
			if ( ! $pid ) {
				continue;
			}
			$in_stock = self::wc_product_in_stock( $pid );
			if ( ! $in_stock ) {
				++$oos;
			}
			$prev = ! isset( $item['inStock'] ) || false !== $item['inStock'];
			if ( $prev !== $in_stock ) {
				++$updated;
			}
			$item['inStock'] = $in_stock;
		}
		unset( $item );

		Tuexhibidor_Site_Manager_Data::save_catalog( $products, $catalog['scores'] ?? array() );
		Tuexhibidor_Site_Manager_Data::bump_cache_version();

		return array(
			'updated'      => $updated,
			'out_of_stock' => $oos,
			'total'        => count( $products ),
			'message'      => sprintf(
				'Stock sincronizado: %1$d cambios, %2$d agotados de %3$d productos.',
				$updated,
				$oos,
				count( $products )
			),
		);
	}
}
