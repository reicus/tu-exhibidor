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
			$target = admin_url( 'admin.php?page=tuexhibidor-site-manager' );
			$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
			if ( $tab ) {
				$target = add_query_arg( 'tab', $tab, $target );
			}
			self::redirect_authenticated( $target, 'manage_options' );
		}
		if ( get_query_var( 'tuex_medios' ) ) {
			self::redirect_authenticated(
				admin_url( 'upload.php' ),
				'upload_files'
			);
		}
	}

	private static function redirect_authenticated( string $target, string $cap ): void {
		if ( ! is_user_logged_in() ) {
			$login = home_url( '/login' );
			wp_safe_redirect( $login . '?redirect_to=' . rawurlencode( $target ) );
			exit;
		}
		if ( ! current_user_can( $cap ) ) {
			wp_die(
				esc_html__( 'No tienes permisos para acceder a esta sección.', 'tuexhibidor' ),
				esc_html__( 'Acceso denegado', 'tuexhibidor' ),
				array( 'response' => 403 )
			);
		}
		wp_safe_redirect( $target );
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
	}
}
