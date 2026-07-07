<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Tuexhibidor_Site_Manager_Data {

	public static function parse_js_var( string $file, string $var ): ?array {
		if ( ! is_readable( $file ) ) {
			return null;
		}
		$content = file_get_contents( $file );
		if ( ! is_string( $content ) ) {
			return null;
		}
		$pattern = '/window\.' . preg_quote( $var, '/' ) . '\s*=\s*(\{.*\})\s*;?\s*$/s';
		if ( ! preg_match( $pattern, $content, $matches ) ) {
			return null;
		}
		$data = json_decode( $matches[1], true );
		return is_array( $data ) ? $data : null;
	}

	public static function load_site_data(): array {
		$data = self::parse_js_var( Tuexhibidor_Site_Manager_Paths::site_data_file(), 'SITE_DATA' );
		return $data ?: array();
	}

	public static function load_catalog(): array {
		$file = Tuexhibidor_Site_Manager_Paths::catalog_data_file();
		if ( ! is_readable( $file ) ) {
			return array( 'products' => array(), 'scores' => array() );
		}
		$content = file_get_contents( $file );
		$products = array();
		$scores   = array();
		if ( is_string( $content ) ) {
			if ( preg_match( '/window\.CATALOG_DATA\s*=\s*(\{.*?\})\s*;/s', $content, $m ) ) {
				$decoded = json_decode( $m[1], true );
				$products = $decoded['products'] ?? array();
			}
			if ( preg_match( '/window\.CATALOG_SCORES\s*=\s*(\{.*?\})\s*;/s', $content, $m ) ) {
				$decoded = json_decode( $m[1], true );
				$scores  = is_array( $decoded ) ? $decoded : array();
			}
		}
		return array(
			'products' => $products,
			'scores'   => $scores,
		);
	}

	public static function save_site_data( array $data ): bool {
		$js = 'window.SITE_DATA=' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ";\n";
		return false !== file_put_contents( Tuexhibidor_Site_Manager_Paths::site_data_file(), $js );
	}

	public static function save_catalog( array $products, array $scores ): bool {
		$catalog = 'window.CATALOG_DATA=' . wp_json_encode( array( 'products' => $products ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ";\n";
		$scoreJs = 'window.CATALOG_SCORES=' . wp_json_encode( $scores, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ";\n";
		$file    = Tuexhibidor_Site_Manager_Paths::catalog_data_file();
		return false !== file_put_contents( $file, $catalog . $scoreJs );
	}

	public static function bump_cache_version(): void {
		$index = Tuexhibidor_Site_Manager_Paths::index_file();
		if ( ! is_readable( $index ) || ! is_writable( $index ) ) {
			update_option( 'tuexhibidor_asset_version', (string) time() );
			return;
		}
		$html = file_get_contents( $index );
		if ( ! is_string( $html ) ) {
			return;
		}
		$ver  = (string) time();
		$html = preg_replace( '/([?&]v=)\d+/', '${1}' . $ver, $html );
		file_put_contents( $index, $html );
		update_option( 'tuexhibidor_asset_version', $ver );
	}

	public static function asset_preview_url( $asset ): string {
		if ( is_string( $asset ) ) {
			return Tuexhibidor_Site_Manager_Paths::public_url( $asset );
		}
		if ( is_array( $asset ) && ! empty( $asset['base'] ) ) {
			return Tuexhibidor_Site_Manager_Paths::public_url( $asset['base'] . '-800.jpg' );
		}
		return '';
	}

	public static function asset_base( $asset ): string {
		if ( is_array( $asset ) && ! empty( $asset['base'] ) ) {
			return (string) $asset['base'];
		}
		if ( is_string( $asset ) ) {
			return preg_replace( '/\.(jpg|jpeg|png|webp)$/i', '', $asset );
		}
		return '';
	}
}
