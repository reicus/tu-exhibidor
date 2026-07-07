<?php
/**
 * Plugin Name: Tu Exhibidor — Gestor del sitio premium
 * Description: Administra imágenes del sitio estático (/site/) desde WordPress.
 * Version: 1.0.0
 * Author: Tecnotix Solutions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bootstrap = __DIR__ . '/tuexhibidor-site-manager/bootstrap.php';
if ( ! is_readable( $bootstrap ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p><strong>Sitio Premium:</strong> faltan archivos del plugin. Sube la carpeta <code>mu-plugins/tuexhibidor-site-manager/</code> completa al servidor.</p></div>';
		}
	);
	return;
}

require_once $bootstrap;
