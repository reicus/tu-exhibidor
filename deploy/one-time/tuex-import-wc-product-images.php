<?php
/**
 * One-time: import staged product images → WooCommerce featured image ONLY (no static site sync).
 * DELETE THIS FILE after running.
 *
 * Usage:
 *   Dry-run:  ?token=TOKEN&dry_run=1
 *   Import:   ?token=TOKEN&run=1&offset=0&limit=10
 */
declare( strict_types=1 );

const TUEX_IMPORT_TOKEN = 'te-import-wc-images-20260708';

header( 'Content-Type: application/json; charset=utf-8' );

$token = isset( $_GET['token'] ) ? (string) $_GET['token'] : '';
if ( $token !== TUEX_IMPORT_TOKEN ) {
	http_response_code( 403 );
	echo wp_json_encode( array( 'error' => 'Forbidden' ) );
	exit;
}

$wp_load = dirname( __FILE__, 2 ) . '/../wp-load.php';
if ( ! is_readable( $wp_load ) ) {
	http_response_code( 500 );
	echo wp_json_encode( array( 'error' => 'wp-load.php not found', 'path' => $wp_load ) );
	exit;
}

require_once $wp_load;

@set_time_limit( 120 );
@ini_set( 'memory_limit', '512M' );

function tuex_import_out( array $data, int $code = 200 ): void {
	http_response_code( $code );
	echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}

$dir      = dirname( __FILE__ ) . '/wc-image-import';
$manifest = $dir . '/manifest.json';
$dry_run  = ! empty( $_GET['dry_run'] );
$run      = ! empty( $_GET['run'] );
$offset   = max( 0, (int) ( $_GET['offset'] ?? 0 ) );
$limit    = max( 1, min( 15, (int) ( $_GET['limit'] ?? 10 ) ) );

if ( ! is_readable( $manifest ) ) {
	tuex_import_out(
		array(
			'error'    => 'Missing manifest.json',
			'dir'      => $dir,
			'expected' => $manifest,
		),
		500
	);
}

$payload = json_decode( (string) file_get_contents( $manifest ), true );
if ( ! is_array( $payload ) || ! isset( $payload['matched'] ) || ! is_array( $payload['matched'] ) ) {
	tuex_import_out( array( 'error' => 'Invalid manifest.json' ), 500 );
}

$items = array_values( $payload['matched'] );
$total = count( $items );
$slice = array_slice( $items, $offset, $limit );

if ( ! $dry_run && ! $run ) {
	tuex_import_out(
		array(
			'message' => 'Pass dry_run=1 or run=1',
			'total'   => $total,
			'dir'     => $dir,
		)
	);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

/**
 * Set featured image without triggering WC→site catalog sync.
 */
function tuex_set_wc_featured_only( int $product_id, int $attachment_id ): bool {
	if ( ! $product_id || ! $attachment_id ) {
		return false;
	}

	if ( class_exists( 'Tuexhibidor_Site_Manager_Images' ) ) {
		Tuexhibidor_Site_Manager_Images::begin_push_to_wc();
	}
	try {
		$prev = (int) get_post_thumbnail_id( $product_id );
		set_post_thumbnail( $product_id, $attachment_id );
		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$product->set_image_id( $attachment_id );
				$product->save();
			}
		}
		return (int) get_post_thumbnail_id( $product_id ) === $attachment_id;
	} finally {
		if ( class_exists( 'Tuexhibidor_Site_Manager_Images' ) ) {
			Tuexhibidor_Site_Manager_Images::end_push_to_wc();
		}
	}
}

function tuex_import_attachment_for_product( int $product_id, string $filepath, string $alt ): int {
	if ( $product_id <= 0 || ! is_readable( $filepath ) ) {
		return 0;
	}

	if ( class_exists( 'Tuexhibidor_Site_Manager_Images' ) ) {
		return Tuexhibidor_Site_Manager_Images::push_catalog_file_to_product( $product_id, $filepath, $alt );
	}

	$filename = basename( $filepath );
	$contents = (string) file_get_contents( $filepath );
	if ( '' === $contents ) {
		return 0;
	}

	$upload = wp_upload_bits( $filename, null, $contents );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$filetype   = wp_check_filetype( $filename );
	$attachment = array(
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

	tuex_set_wc_featured_only( $product_id, (int) $attachment_id );
	return (int) $attachment_id;
}

$ok     = 0;
$fail   = 0;
$skip   = 0;
$rows   = array();
$errors = array();

foreach ( $slice as $item ) {
	$pid   = (int) ( $item['product_id'] ?? 0 );
	$file  = (string) ( $item['import_file'] ?? '' );
	$sku   = (string) ( $item['sku'] ?? '' );
	$name  = (string) ( $item['name'] ?? '' );
	$path  = $dir . '/' . $file;

	if ( ! $pid || ! $file || ! is_readable( $path ) ) {
		++$fail;
		$errors[] = ( $sku ?: $file ) . ':missing-file-or-id';
		continue;
	}

	if ( 'product' !== get_post_type( $pid ) ) {
		++$skip;
		$errors[] = $sku . ':not-a-product-' . $pid;
		continue;
	}

	if ( $dry_run ) {
		++$ok;
		$rows[] = array(
			'sku'        => $sku,
			'product_id' => $pid,
			'file'       => $file,
			'thumb_id'   => (int) get_post_thumbnail_id( $pid ),
			'permalink'  => get_permalink( $pid ),
		);
		continue;
	}

	$aid = tuex_import_attachment_for_product( $pid, $path, $name );
	if ( $aid > 0 ) {
		++$ok;
		$rows[] = array(
			'sku'             => $sku,
			'product_id'      => $pid,
			'attachment_id'   => $aid,
			'thumb_id'        => (int) get_post_thumbnail_id( $pid ),
			'permalink'       => get_permalink( $pid ),
			'attachment_url'  => wp_get_attachment_url( $aid ),
		);
	} else {
		++$fail;
		$errors[] = $sku . ':upload-failed';
	}
}

$next = $offset + count( $slice );
$done = $next >= $total;

tuex_import_out(
	array(
		'dry_run'  => $dry_run,
		'ok'       => $ok,
		'fail'     => $fail,
		'skip'     => $skip,
		'offset'   => $offset,
		'next'     => $next,
		'total'    => $total,
		'done'     => $done,
		'items'    => $rows,
		'errors'   => $errors,
		'message'  => sprintf(
			'%s lote offset %d: ok=%d fail=%d skip=%d (total %d).',
			$dry_run ? 'Dry-run' : 'Import',
			$offset,
			$ok,
			$fail,
			$skip,
			$total
		),
	)
);
