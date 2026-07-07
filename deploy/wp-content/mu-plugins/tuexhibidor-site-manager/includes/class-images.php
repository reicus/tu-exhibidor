<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Tuexhibidor_Site_Manager_Images {

	public const WIDTHS = array( 400, 800, 1200, 1600 );

	public static function attachment_path( int $attachment_id ): ?string {
		$path = get_attached_file( $attachment_id );
		return ( $path && file_exists( $path ) ) ? $path : null;
	}

	public static function ensure_dir( string $abs_path ): bool {
		$dir = dirname( $abs_path );
		if ( is_dir( $dir ) ) {
			return true;
		}
		return wp_mkdir_p( $dir );
	}

	/** Guarda JPG único (catálogo). */
	public static function save_catalog_jpg( int $attachment_id, string $slug ): bool {
		$source = self::attachment_path( $attachment_id );
		if ( ! $source ) {
			return false;
		}
		$rel  = 'public/images/catalog/' . $slug . '.jpg';
		$dest = Tuexhibidor_Site_Manager_Paths::abs_from_public( $rel );
		if ( ! self::ensure_dir( $dest ) ) {
			return false;
		}
		return self::save_resized_jpg( $source, $dest, 1200 );
	}

	/** Genera tamaños JPG para assets con base responsive. */
	public static function save_responsive_set( int $attachment_id, string $base ): bool {
		$source = self::attachment_path( $attachment_id );
		if ( ! $source || ! $base ) {
			return false;
		}
		$ok = true;
		foreach ( self::WIDTHS as $width ) {
			$dest = Tuexhibidor_Site_Manager_Paths::abs_from_public( $base . '-' . $width . '.jpg' );
			if ( ! self::ensure_dir( $dest ) ) {
				$ok = false;
				continue;
			}
			if ( ! self::save_resized_jpg( $source, $dest, $width ) ) {
				$ok = false;
			}
		}
		return $ok;
	}

	public static function save_brand_file( int $attachment_id, string $relative_path ): bool {
		$source = self::attachment_path( $attachment_id );
		if ( ! $source ) {
			return false;
		}
		$dest = Tuexhibidor_Site_Manager_Paths::abs_from_public( $relative_path );
		if ( ! self::ensure_dir( $dest ) ) {
			return false;
		}
		$ext = strtolower( pathinfo( $dest, PATHINFO_EXTENSION ) );
		if ( 'webp' === $ext && function_exists( 'imagewebp' ) ) {
			return self::save_as_webp( $source, $dest );
		}
		if ( 'png' === $ext ) {
			return self::save_resized_png( $source, $dest, 96 );
		}
		return copy( $source, $dest ) || self::save_resized_jpg( $source, $dest, 512 );
	}

	private static function save_resized_jpg( string $source, string $dest, int $max_width ): bool {
		$editor = wp_get_image_editor( $source );
		if ( is_wp_error( $editor ) ) {
			return @copy( $source, $dest );
		}
		$size = $editor->get_size();
		if ( is_wp_error( $size ) ) {
			return false;
		}
		if ( $size['width'] > $max_width ) {
			$editor->resize( $max_width, null, false );
		}
		$saved = $editor->save( $dest, 'image/jpeg', 88 );
		return ! is_wp_error( $saved );
	}

	private static function save_resized_png( string $source, string $dest, int $max_width ): bool {
		$editor = wp_get_image_editor( $source );
		if ( is_wp_error( $editor ) ) {
			return false;
		}
		$size = $editor->get_size();
		if ( ! is_wp_error( $size ) && $size['width'] > $max_width ) {
			$editor->resize( $max_width, $max_width, true );
		}
		$saved = $editor->save( $dest, 'image/png' );
		return ! is_wp_error( $saved );
	}

	private static function save_as_webp( string $source, string $dest ): bool {
		$editor = wp_get_image_editor( $source );
		if ( is_wp_error( $editor ) ) {
			return false;
		}
		$size = $editor->get_size();
		if ( ! is_wp_error( $size ) && $size['width'] > 96 ) {
			$editor->resize( 96, 96, true );
		}
		$saved = $editor->save( $dest, 'image/webp', 90 );
		return ! is_wp_error( $saved );
	}

	public static function sync_woocommerce_thumbnail( string $sku, int $attachment_id ): void {
		if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
			return;
		}
		$product_id = wc_get_product_id_by_sku( $sku );
		if ( $product_id ) {
			set_post_thumbnail( $product_id, $attachment_id );
		}
	}

	public static function build_sources_map( string $base ): array {
		$sources = array();
		foreach ( self::WIDTHS as $width ) {
			$sources[ (string) $width ] = array(
				'jpg'  => $base . '-' . $width . '.jpg',
				'webp' => $base . '-' . $width . '.webp',
				'avif' => $base . '-' . $width . '.avif',
			);
		}
		return $sources;
	}
}
