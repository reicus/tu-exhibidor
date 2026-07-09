<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Tuexhibidor_Site_Manager_Paths {

	public static function site_dir(): string {
		return trailingslashit( ABSPATH ) . 'site/';
	}

	public static function public_dir(): string {
		return trailingslashit( ABSPATH ) . 'public/';
	}

	public static function images_dir(): string {
		return self::public_dir() . 'images/';
	}

	public static function abs_from_public( string $relative ): string {
		$relative = ltrim( str_replace( '\\', '/', $relative ), '/' );
		if ( 0 === strpos( $relative, 'public/' ) ) {
			return trailingslashit( ABSPATH ) . $relative;
		}
		return self::images_dir() . ltrim( str_replace( 'public/images/', '', $relative ), '/' );
	}

	public static function public_url( string $relative ): string {
		$relative = ltrim( str_replace( '\\', '/', $relative ), '/' );
		return home_url( '/' . $relative );
	}

	public static function site_data_file(): string {
		return self::site_dir() . 'site-data.js';
	}

	public static function catalog_data_file(): string {
		return self::site_dir() . 'catalog-data.js';
	}

	public static function index_file(): string {
		return self::site_dir() . 'index.html';
	}

	public static function is_ready(): bool {
		return is_dir( self::site_dir() )
			&& is_dir( self::images_dir() )
			&& is_readable( self::site_data_file() )
			&& is_readable( self::catalog_data_file() );
	}

	/** @return array<int, string> */
	public static function brand_slots(): array {
		return array(
			'logo-ink'       => 'public/images/brand/logo-tuexhibidor-ink-96.png',
			'logo-gold'      => 'public/images/brand/logo-tuexhibidor-gold-96.webp',
			'favicon'        => 'public/images/brand/favicon-32.png',
			'apple-touch'    => 'public/images/brand/apple-touch-icon.png',
		);
	}

	/** Imágenes fijas del home (no carrusel). @return array<string, array{base:string,label:string,section:string,default_alt:string}> */
	public static function home_static_slots(): array {
		return array(
			'medida' => array(
				'base'        => 'public/images/home/medida',
				'label'       => 'Exhibición a la medida',
				'section'     => '#medida',
				'default_alt' => 'Set vitrina modular Tu Exhibidor',
			),
		);
	}
}
