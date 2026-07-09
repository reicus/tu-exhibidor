<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TUEX_SM_VERSION', '1.3.1' );
define( 'TUEX_SM_DIR', __DIR__ );
define( 'TUEX_SM_URL', content_url( 'mu-plugins/tuexhibidor-site-manager' ) );

require_once TUEX_SM_DIR . '/includes/class-paths.php';
require_once TUEX_SM_DIR . '/includes/class-data.php';
require_once TUEX_SM_DIR . '/includes/class-images.php';
require_once TUEX_SM_DIR . '/includes/class-woocommerce-sync.php';
require_once TUEX_SM_DIR . '/includes/class-woocommerce-image-sync.php';
require_once TUEX_SM_DIR . '/includes/class-woocommerce-stock-sync.php';
require_once TUEX_SM_DIR . '/includes/class-category-merge.php';
require_once TUEX_SM_DIR . '/includes/class-admin.php';
require_once TUEX_SM_DIR . '/includes/class-router.php';

Tuexhibidor_Site_Manager_Admin::init();
Tuexhibidor_Site_Manager_Router::init();
Tuexhibidor_Site_Manager_WooCommerce_Sync::init();
Tuexhibidor_Site_Manager_WooCommerce_Image_Sync::init();
Tuexhibidor_Site_Manager_WooCommerce_Stock_Sync::init();
Tuexhibidor_Site_Manager_Category_Merge::init();
