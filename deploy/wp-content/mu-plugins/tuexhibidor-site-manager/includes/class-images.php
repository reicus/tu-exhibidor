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

	/** Guarda JPG único (galería «Exhibidores en acción»). */
	public static function save_gallery_jpg( int $attachment_id, string $relative ): bool {
		$source = self::attachment_path( $attachment_id );
		if ( ! $source || ! $relative ) {
			return false;
		}
		$dest = Tuexhibidor_Site_Manager_Paths::abs_from_public( $relative );
		if ( ! self::ensure_dir( $dest ) ) {
			return false;
		}
		return self::save_resized_jpg( $source, $dest, 1600 );
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

	/** Copia un set responsive JPG entre bases (seed inicial). */
	public static function copy_responsive_set_files( string $from_base, string $to_base ): bool {
		if ( ! $from_base || ! $to_base || $from_base === $to_base ) {
			return false;
		}
		$ok = true;
		foreach ( self::WIDTHS as $width ) {
			$src  = Tuexhibidor_Site_Manager_Paths::abs_from_public( $from_base . '-' . $width . '.jpg' );
			$dest = Tuexhibidor_Site_Manager_Paths::abs_from_public( $to_base . '-' . $width . '.jpg' );
			if ( ! is_readable( $src ) ) {
				$ok = false;
				continue;
			}
			if ( ! self::ensure_dir( $dest ) ) {
				$ok = false;
				continue;
			}
			if ( ! copy( $src, $dest ) ) {
				$ok = false;
			}
		}
		return $ok;
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

	/** @var bool */
	private static $pushing_to_wc = false;

	public static function is_pushing_to_wc(): bool {
		return self::$pushing_to_wc;
	}

	public static function begin_push_to_wc(): void {
		self::$pushing_to_wc = true;
	}

	public static function end_push_to_wc(): void {
		self::$pushing_to_wc = false;
	}

	public static function sync_woocommerce_thumbnail( string $sku, int $attachment_id ): void {
		if ( ! function_exists( 'wc_get_product_id_by_sku' ) ) {
			return;
		}
		$product_id = wc_get_product_id_by_sku( $sku );
		if ( $product_id ) {
			self::begin_push_to_wc();
			try {
				set_post_thumbnail( $product_id, $attachment_id );
			} finally {
				self::end_push_to_wc();
			}
		}
	}

	/**
	 * Import a local catalog JPG (or public URL) as a WP attachment and set it as product featured image.
	 */
	public static function push_catalog_file_to_product( int $product_id, string $filepath, string $alt = '' ): int {
		if ( $product_id <= 0 || ! is_readable( $filepath ) ) {
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$filename = basename( $filepath );
		$contents = (string) file_get_contents( $filepath );
		if ( '' === $contents ) {
			return 0;
		}

		$upload = wp_upload_bits( $filename, null, $contents );
		if ( ! empty( $upload['error'] ) ) {
			return 0;
		}

		$filetype      = wp_check_filetype( $filename );
		$attachment    = array(
			'post_mime_type' => $filetype['type'] ?? 'image/jpeg',
			'post_title'     => sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);
		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $product_id );
		if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
			return 0;
		}

		$meta = wp_generate_attachment_metadata( (int) $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( (int) $attachment_id, $meta );
		if ( $alt ) {
			update_post_meta( (int) $attachment_id, '_wp_attachment_image_alt', $alt );
		}

		self::begin_push_to_wc();
		try {
			set_post_thumbnail( $product_id, (int) $attachment_id );
			if ( function_exists( 'wc_get_product' ) ) {
				$product = wc_get_product( $product_id );
				if ( $product ) {
					$product->set_image_id( (int) $attachment_id );
					$product->save();
				}
			}
		} finally {
			self::end_push_to_wc();
		}

		return (int) $attachment_id;
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
