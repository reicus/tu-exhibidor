<?php
/**
 * One-time: strip PicsArt JSON metadata from attachment captions and product text.
 * DELETE THIS FILE from public_html after running.
 *
 * Usage:
 *   Scan:    ?token=TOKEN&scan=1
 *   Dry-run: ?token=TOKEN&dry_run=1
 *   Clean:   ?token=TOKEN&clean=1&offset=0&limit=50
 */
declare( strict_types=1 );

const TUEX_PICSART_TOKEN = 'te-clean-picsart-20260709';
const TUEX_BATCH_DEFAULT = 50;

$wp_load = dirname( __FILE__ ) . '/wp-load.php';
if ( ! is_readable( $wp_load ) ) {
	http_response_code( 500 );
	header( 'Content-Type: application/json; charset=utf-8' );
	echo wp_json_encode( array( 'error' => 'wp-load.php not found' ) );
	exit;
}

require_once $wp_load;

header( 'Content-Type: application/json; charset=utf-8' );

$token = isset( $_GET['token'] ) ? (string) $_GET['token'] : ''; // phpcs:ignore
if ( $token !== TUEX_PICSART_TOKEN ) {
	http_response_code( 403 );
	echo wp_json_encode( array( 'error' => 'Forbidden' ) );
	exit;
}

@set_time_limit( 300 );
@ini_set( 'memory_limit', '512M' );

/**
 * Detect PicsArt / editor metadata blobs in text fields.
 */
function tuex_is_picsart_metadata( string $text ): bool {
	if ( $text === '' ) {
		return false;
	}
	if ( preg_match( '/"remix_data"|"fte_image_ids"|"total_effects_time"|"total_editor_time"|"tools_used"|"effects_applied"|"photos_added"|"edited_since_last_sticker_save"/i', $text ) ) {
		return true;
	}
	if ( preg_match( '/picsart/i', $text ) && preg_match( '/"uid"\s*:/', $text ) ) {
		return true;
	}
	return false;
}

/**
 * Remove metadata; keep any real prose if mixed in.
 */
function tuex_clean_picsart_text( string $text ): string {
	if ( ! tuex_is_picsart_metadata( $text ) ) {
		return $text;
	}
	$trim = trim( $text );
	if ( preg_match( '/^[\{\[]/', $trim ) ) {
		return '';
	}
	$clean = preg_replace( '/\{[^{}]*"remix_data"[^{}]*\}/s', '', $text );
	$clean = preg_replace( '/\{[^{}]*"fte_image_ids"[^{}]*\}/s', '', (string) $clean );
	return trim( (string) $clean );
}

/**
 * @return list<array<string, mixed>>
 */
function tuex_find_affected_posts(): array {
	global $wpdb;

	$like_parts = array(
		'%"remix_data"%',
		'%"fte_image_ids"%',
		'%"total_effects_time"%',
		'%"tools_used"%',
		'%PicsArt%',
	);

	$where = array();
	foreach ( $like_parts as $like ) {
		$where[] = $wpdb->prepare( 'post_excerpt LIKE %s', $like );
		$where[] = $wpdb->prepare( 'post_content LIKE %s', $like );
	}

	$sql = "SELECT ID, post_type, post_title, post_excerpt, post_content
		FROM {$wpdb->posts}
		WHERE post_status NOT IN ('trash','auto-draft')
		AND post_type IN ('attachment','product')
		AND (" . implode( ' OR ', $where ) . ')
		ORDER BY ID ASC';

	$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore
	$out  = array();

	foreach ( $rows ?: array() as $row ) {
		$fields = array();
		foreach ( array( 'post_excerpt', 'post_content' ) as $field ) {
			$val = (string) ( $row[ $field ] ?? '' );
			if ( tuex_is_picsart_metadata( $val ) ) {
				$fields[ $field ] = array(
					'before_len' => strlen( $val ),
					'after'      => tuex_clean_picsart_text( $val ),
				);
			}
		}
		if ( $fields ) {
			$out[] = array(
				'id'        => (int) $row['ID'],
				'type'      => (string) $row['post_type'],
				'title'     => (string) $row['post_title'],
				'fields'    => $fields,
			);
		}
	}

	return $out;
}

$scan    = isset( $_GET['scan'] ); // phpcs:ignore
$dry_run = isset( $_GET['dry_run'] ); // phpcs:ignore
$clean   = isset( $_GET['clean'] ); // phpcs:ignore
$offset  = max( 0, (int) ( $_GET['offset'] ?? 0 ) ); // phpcs:ignore
$limit   = max( 1, min( 200, (int) ( $_GET['limit'] ?? TUEX_BATCH_DEFAULT ) ) ); // phpcs:ignore

$affected = tuex_find_affected_posts();
$total    = count( $affected );

if ( $scan ) {
	echo wp_json_encode(
		array(
			'total_affected' => $total,
			'items'          => array_map(
				static function ( array $item ): array {
					return array(
						'id'     => $item['id'],
						'type'   => $item['type'],
						'title'  => $item['title'],
						'fields' => array_keys( $item['fields'] ),
					);
				},
				$affected
			),
		),
		JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
	);
	exit;
}

if ( ! $dry_run && ! $clean ) {
	echo wp_json_encode(
		array(
			'error'          => 'Specify scan=1, dry_run=1, or clean=1',
			'total_affected' => $total,
		)
	);
	exit;
}

$batch   = array_slice( $affected, $offset, $limit );
$updated = 0;
$skipped = 0;
$log     = array();

foreach ( $batch as $item ) {
	$post_id = (int) $item['id'];
	$update  = array( 'ID' => $post_id );
	$changed = false;

	foreach ( $item['fields'] as $field => $info ) {
		$update[ $field ] = (string) $info['after'];
		$changed          = true;
	}

	if ( ! $changed ) {
		++$skipped;
		continue;
	}

	if ( $dry_run ) {
		$log[] = array(
			'id'     => $post_id,
			'type'   => $item['type'],
			'title'  => $item['title'],
			'fields' => array_keys( $item['fields'] ),
			'would'  => 'clean',
		);
		++$updated;
		continue;
	}

	$result = wp_update_post( $update, true );
	if ( is_wp_error( $result ) ) {
		$log[] = array(
			'id'    => $post_id,
			'error' => $result->get_error_message(),
		);
		++$skipped;
		continue;
	}

	$log[] = array(
		'id'     => $post_id,
		'type'   => $item['type'],
		'title'  => $item['title'],
		'fields' => array_keys( $item['fields'] ),
		'status' => 'cleaned',
	);
	++$updated;
}

$next_offset = $offset + $limit;
$done        = $next_offset >= $total;

echo wp_json_encode(
	array(
		'total_affected' => $total,
		'offset'         => $offset,
		'limit'          => $limit,
		'updated'        => $updated,
		'skipped'        => $skipped,
		'done'           => $done,
		'next_offset'    => $done ? null : $next_offset,
		'dry_run'        => (bool) $dry_run,
		'log'            => $log,
	),
	JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
);
