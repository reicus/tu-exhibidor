<?php
/**
 * Quick scan: attachments kept only by post_parent vs truly referenced.
 * DELETE after use.
 */
declare( strict_types=1 );
const TUEX_SCAN_TOKEN = 'te-cleanup-20260708-media';
$wp_load = dirname( __FILE__ ) . '/wp-load.php';
require_once $wp_load;
header( 'Content-Type: application/json; charset=utf-8' );
if ( ( $_GET['token'] ?? '' ) !== TUEX_SCAN_TOKEN ) { http_response_code( 403 ); die('{}'); }

global $wpdb;
$all = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type='attachment' ORDER BY ID" );
$thumb_ids = array_flip( array_map( 'intval', $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key='_thumbnail_id' AND meta_value>0" ) ?: array() ) );

$only_parent = array();
$no_thumb = array();
foreach ( $all as $id ) {
    $id = (int) $id;
    $parent = (int) get_post_field( 'post_parent', $id );
    $file = basename( (string) get_attached_file( $id ) );
    $date = get_post_field( 'post_date', $id );
    if ( $parent > 0 && ! isset( $thumb_ids[ $id ] ) ) {
        $only_parent[] = array( 'id' => $id, 'parent' => $parent, 'ptype' => get_post_type( $parent ), 'file' => $file, 'date' => $date );
    }
    if ( ! isset( $thumb_ids[ $id ] ) ) {
        $no_thumb[] = array( 'id' => $id, 'file' => $file, 'date' => $date );
    }
}

echo wp_json_encode( array(
    'total' => count( $all ),
    'with_thumbnail_meta' => count( $thumb_ids ),
    'only_post_parent_not_thumb' => count( $only_parent ),
    'sample_only_parent' => array_slice( $only_parent, 0, 25 ),
    'not_featured_image' => count( $no_thumb ),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
