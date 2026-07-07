<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TUEX_SM_VERSION', '1.1.0' );
define( 'TUEX_SM_DIR', __DIR__ );
define( 'TUEX_SM_URL', content_url( 'mu-plugins/tuexhibidor-site-manager' ) );

require_once TUEX_SM_DIR . '/includes/class-paths.php';
require_once TUEX_SM_DIR . '/includes/class-data.php';
require_once TUEX_SM_DIR . '/includes/class-images.php';
require_once TUEX_SM_DIR . '/includes/class-admin.php';
require_once TUEX_SM_DIR . '/includes/class-router.php';

Tuexhibidor_Site_Manager_Admin::init();
Tuexhibidor_Site_Manager_Router::init();
