<?php
/**
 * Plugin Name: Tu Exhibidor Security Hardening
 * Description: Cabeceras de seguridad, hardening básico y ocultación de versión WP.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Cabeceras HTTP de seguridad */
add_action( 'send_headers', function () {
	if ( headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );
	}
}, 10 );

/** Ocultar versión de WordPress */
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

/** Desactivar XML-RPC si no se usa */
add_filter( 'xmlrpc_enabled', '__return_false' );

/** Bloquear enumeración de autores en REST (usuarios no autenticados) */
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! is_user_logged_in() && isset( $endpoints['/wp/v2/users'] ) ) {
		unset( $endpoints['/wp/v2/users'] );
	}
	if ( ! is_user_logged_in() && isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
		unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
} );

/** Forzar cookies seguras en HTTPS */
add_action( 'init', function () {
	if ( is_ssl() ) {
		@ini_set( 'session.cookie_httponly', '1' );
		@ini_set( 'session.cookie_secure', '1' );
	}
}, 1 );
