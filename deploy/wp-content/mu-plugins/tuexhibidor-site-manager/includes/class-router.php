<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rutas amigables para administración de imágenes.
 * /imagenes  → Sitio Premium (o login)
 * /medios    → Biblioteca de medios WordPress (o login)
 */
final class Tuexhibidor_Site_Manager_Router {

	private const REWRITE_OPTION = 'tuex_sm_rewrite_version';
	private const REWRITE_VERSION = '2';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_rewrites' ), 5 );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_vars' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_redirects' ), 1 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'admin_bar_links' ), 95 );
	}

	public static function register_rewrites(): void {
		add_rewrite_rule( '^imagenes/?$', 'index.php?tuex_imagenes=1', 'top' );
		add_rewrite_rule( '^medios/?$', 'index.php?tuex_medios=1', 'top' );

		if ( get_option( self::REWRITE_OPTION ) !== self::REWRITE_VERSION ) {
			flush_rewrite_rules( false );
			update_option( self::REWRITE_OPTION, self::REWRITE_VERSION );
		}
	}

	public static function register_query_vars( array $vars ): array {
		$vars[] = 'tuex_imagenes';
		$vars[] = 'tuex_medios';
		return $vars;
	}

	public static function handle_redirects(): void {
		if ( get_query_var( 'tuex_imagenes' ) ) {
			self::redirect_admin(
				admin_url( 'admin.php?page=tuexhibidor-site-manager' ),
				'imagenes'
			);
		}
		if ( get_query_var( 'tuex_medios' ) ) {
			self::redirect_admin(
				admin_url( 'upload.php' ),
				'medios'
			);
		}
	}

	private static function redirect_admin( string $target, string $slug ): void {
		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( $target );
			exit;
		}
		$login = home_url( '/login' );
		$redirect_to = rawurlencode( $target );
		wp_safe_redirect( $login . '?redirect_to=' . $redirect_to . '&tuex=' . $slug );
		exit;
	}

	public static function admin_bar_links( WP_Admin_Bar $bar ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$bar->add_node(
			array(
				'id'    => 'tuex-imagenes',
				'title' => 'Imágenes sitio',
				'href'  => home_url( '/imagenes' ),
				'meta'  => array( 'class' => 'tuex-admin-bar-link' ),
			)
		);
		$bar->add_node(
			array(
				'id'     => 'tuex-sitio-premium',
				'parent' => 'tuex-imagenes',
				'title'  => 'Sitio Premium',
				'href'   => admin_url( 'admin.php?page=tuexhibidor-site-manager' ),
			)
		);
		$bar->add_node(
			array(
				'id'     => 'tuex-medios-wp',
				'parent' => 'tuex-imagenes',
				'title'  => 'Medios WordPress',
				'href'   => home_url( '/medios' ),
			)
		);
		$bar->add_node(
			array(
				'id'     => 'tuex-ver-sitio',
				'parent' => 'tuex-imagenes',
				'title'  => 'Ver sitio público',
				'href'   => home_url( '/site/' ),
				'meta'   => array( 'target' => '_blank' ),
			)
		);
	}
}
