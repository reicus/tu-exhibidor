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

	public static function save_site_data( array $data, ?string $asset_version = null ): bool {
		if ( null !== $asset_version ) {
			$data['assetVersion'] = $asset_version;
		} elseif ( empty( $data['assetVersion'] ) ) {
			$data['assetVersion'] = (string) get_option( 'tuexhibidor_asset_version', time() );
		}
		$js = 'window.SITE_DATA=' . wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ";\n";
		return false !== file_put_contents( Tuexhibidor_Site_Manager_Paths::site_data_file(), $js );
	}

	public static function save_catalog( array $products, array $scores ): bool {
		$catalog = 'window.CATALOG_DATA=' . wp_json_encode( array( 'products' => $products ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ";\n";
		$scoreJs = 'window.CATALOG_SCORES=' . wp_json_encode( $scores, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ";\n";
		$file    = Tuexhibidor_Site_Manager_Paths::catalog_data_file();
		return false !== file_put_contents( $file, $catalog . $scoreJs );
	}

	public static function bump_cache_version(): string {
		$ver = (string) time();
		update_option( 'tuexhibidor_asset_version', $ver );

		$index = Tuexhibidor_Site_Manager_Paths::index_file();
		if ( ! is_readable( $index ) || ! is_writable( $index ) ) {
			return $ver;
		}
		$html = file_get_contents( $index );
		if ( ! is_string( $html ) ) {
			return $ver;
		}
		$html = preg_replace( '/(\?v=)[^"\'>\s&]+/', '${1}' . $ver, $html );
		$html = preg_replace(
			'#(<script\s+src="/site/(?:site-data|catalog-data|app)\.js)(?:\?v=[^"]*)?(")#',
			'$1?v=' . $ver . '$2',
			$html
		);
		$html = preg_replace(
			'#(<link rel="preload" as="image" href="/public/images/hero/hero-slide-01-800\.jpg)(?:\?v=[^"]*)?(")#',
			'$1?v=' . $ver . '$2',
			$html
		);
		file_put_contents( $index, $html );

		$site = self::load_site_data();
		if ( ! empty( $site ) ) {
			$site['assetVersion'] = $ver;
			self::save_site_data( $site, $ver );
		}

		return $ver;
	}

	/** SKUs por defecto para «Los más pedidos» (score ≥ 0.78, top 12). */
	public static function default_featured_skus( array $products ): array {
		$featured = array_values(
			array_filter(
				$products,
				static function ( $p ) {
					return ( $p['score'] ?? 1 ) >= 0.78 && ( $p['imageOk'] ?? true ) !== false;
				}
			)
		);
		usort(
			$featured,
			static function ( $a, $b ) {
				return ( $b['score'] ?? 0 ) <=> ( $a['score'] ?? 0 );
			}
		);
		$featured = array_slice( $featured, 0, 12 );
		return array_values(
			array_filter(
				array_map(
					static function ( $p ) {
						return $p['code'] ?? '';
					},
					$featured
				)
			)
		);
	}

	public static function asset_preview_url( $asset ): string {
		if ( is_string( $asset ) ) {
			return Tuexhibidor_Site_Manager_Paths::public_url( $asset );
		}
		if ( is_array( $asset ) && ! empty( $asset['base'] ) ) {
			$base = (string) $asset['base'];
			if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $base ) ) {
				return Tuexhibidor_Site_Manager_Paths::public_url( $base );
			}
			return Tuexhibidor_Site_Manager_Paths::public_url( $base . '-800.jpg' );
		}
		return '';
	}

	/** Ruta relativa de un asset de galería (string o objeto). */
	public static function gallery_relative_path( $asset ): string {
		if ( is_string( $asset ) ) {
			return $asset;
		}
		if ( is_array( $asset ) && ! empty( $asset['base'] ) ) {
			$base = (string) $asset['base'];
			if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $base ) ) {
				return $base;
			}
			if ( ! empty( $asset['sources']['800']['jpg'] ) ) {
				return (string) $asset['sources']['800']['jpg'];
			}
			return $base . '-800.jpg';
		}
		return '';
	}

	public static function gallery_alt( $asset ): string {
		return is_array( $asset ) && ! empty( $asset['alt'] ) ? (string) $asset['alt'] : '';
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

	/** Garantiza slots homeStatic en site-data.js (seed desde categorías si faltan). */
	public static function ensure_home_static_defaults(): array {
		$site  = self::load_site_data();
		$slots = Tuexhibidor_Site_Manager_Paths::home_static_slots();
		$dirty = false;

		if ( ! isset( $site['homeStatic'] ) || ! is_array( $site['homeStatic'] ) ) {
			$site['homeStatic'] = array();
			$dirty              = true;
		}

		foreach ( $slots as $key => $slot ) {
			if ( ! empty( $site['homeStatic'][ $key ] ) ) {
				continue;
			}
			$fallback = null;
			if ( 'medida' === $key && ! empty( $site['categoryImages']['sets-vitrina'] ) ) {
				$fallback = $site['categoryImages']['sets-vitrina'];
			}
			$base     = $slot['base'];
			$dest_800 = Tuexhibidor_Site_Manager_Paths::abs_from_public( $base . '-800.jpg' );
			if ( ! is_readable( $dest_800 ) && $fallback ) {
				$fb_base = self::asset_base( $fallback );
				if ( $fb_base ) {
					Tuexhibidor_Site_Manager_Images::copy_responsive_set_files( $fb_base, $base );
				}
			}
			$alt = is_array( $fallback ) ? ( $fallback['alt'] ?? $slot['default_alt'] ) : $slot['default_alt'];
			$site['homeStatic'][ $key ] = array(
				'base'    => $base,
				'alt'     => $alt,
				'sources' => Tuexhibidor_Site_Manager_Images::build_sources_map( $base ),
			);
			$dirty = true;
		}

		if ( $dirty ) {
			self::save_site_data( $site );
		}

		return $site;
	}
}
