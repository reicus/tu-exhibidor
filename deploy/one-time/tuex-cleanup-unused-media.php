<?php
/**
 * One-time: remove unused WordPress media attachments.
 * DELETE THIS FILE from public_html after running.
 *
 * Usage:
 *   Dry-run:  ?token=TOKEN&dry_run=1
 *   Delete:   ?token=TOKEN&delete=1&offset=0&limit=50
 */
declare( strict_types=1 );

const TUEX_CLEANUP_TOKEN = 'te-cleanup-20260708-media';
const TUEX_BATCH_DEFAULT = 40;

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
if ( $token !== TUEX_CLEANUP_TOKEN ) {
	http_response_code( 403 );
	echo wp_json_encode( array( 'error' => 'Forbidden' ) );
	exit;
}

@set_time_limit( 300 );
@ini_set( 'memory_limit', '512M' );

require_once ABSPATH . 'wp-admin/includes/image.php';

final class Tuex_Cleanup_Unused_Media {

	/** @var array<int, true> */
	private static $in_use = array();

	/** @var array<int, string> */
	private static $keep_reasons = array();

	/** @var array<int, list<string>> */
	private static $keep_reasons_all = array();

	/** @var array<string, true> */
	private static $site_basenames = array();

	/** @var array<string, true> */
	private static $catalog_slugs = array();

	public static function run( bool $dry_run, int $offset, int $limit, bool $strict = false ): array {
		self::build_site_basenames();
		self::collect_in_use_ids( $strict );

		global $wpdb;
		$all_ids = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts}
			WHERE post_type = 'attachment'
			AND post_status IN ('inherit','private','publish')
			ORDER BY ID ASC"
		);
		$all_ids = array_map( 'intval', $all_ids ?: array() );
		$total   = count( $all_ids );

		$orphans = array();
		foreach ( $all_ids as $id ) {
			if ( ! isset( self::$in_use[ $id ] ) ) {
				$orphans[] = $id;
			}
		}

		$batch     = array_slice( $orphans, $offset, $limit );
		$deleted   = 0;
		$failed    = 0;
		$deleted_list = array();
		$failed_list  = array();

		if ( ! $dry_run ) {
			foreach ( $batch as $aid ) {
				$file = get_attached_file( $aid );
				$title = get_the_title( $aid );
				if ( wp_delete_attachment( $aid, true ) ) {
					++$deleted;
					$deleted_list[] = array(
						'id'    => $aid,
						'title' => $title,
						'file'  => $file ? basename( (string) $file ) : '',
					);
				} else {
					++$failed;
					$failed_list[] = $aid;
				}
			}
		}

		$sample_orphans = array();
		foreach ( array_slice( $orphans, 0, 30 ) as $aid ) {
			$file = get_attached_file( $aid );
			$sample_orphans[] = array(
				'id'    => $aid,
				'title' => get_the_title( $aid ),
				'file'  => $file ? basename( (string) $file ) : '',
				'date'  => get_post_field( 'post_date', $aid ),
			);
		}

		$keep_samples = array();
		$kept_ids     = array_values( array_filter( $all_ids, static fn( $id ) => isset( self::$in_use[ $id ] ) ) );
		foreach ( array_slice( $kept_ids, 0, 20 ) as $aid ) {
			$keep_samples[] = array(
				'id'     => $aid,
				'reason' => self::$keep_reasons[ $aid ] ?? 'unknown',
				'file'   => basename( (string) get_attached_file( $aid ) ),
			);
		}

		$next    = $offset + count( $batch );
		$done    = $next >= count( $orphans );
		$reasons = array_count_values( self::$keep_reasons );

		return array(
			'dry_run'        => $dry_run,
			'total_media'    => $total,
			'in_use'         => count( self::$in_use ),
			'orphans'        => count( $orphans ),
			'kept'           => count( $kept_ids ),
			'offset'         => $offset,
			'limit'          => $limit,
			'batch_size'     => count( $batch ),
			'deleted'        => $deleted,
			'failed'         => $failed,
			'next_offset'    => $done ? null : $next,
			'done'           => $done,
			'keep_reasons'   => $reasons,
			'sample_orphans' => $sample_orphans,
			'sample_kept'    => $keep_samples,
			'deleted_batch'  => $deleted_list,
			'failed_ids'     => $failed_list,
			'message'        => $dry_run
				? sprintf( 'DRY-RUN: %1$d total, %2$d en uso, %3$d huérfanos (se borrarían).', $total, count( self::$in_use ), count( $orphans ) )
				: sprintf( 'DELETE lote offset %1$d: borrados %2$d, fallos %3$d.', $offset, $deleted, $failed ),
		);
	}

	private static function mark_keep( int $id, string $reason ): void {
		if ( $id <= 0 ) {
			return;
		}
		self::$in_use[ $id ] = true;
		if ( ! isset( self::$keep_reasons[ $id ] ) ) {
			self::$keep_reasons[ $id ] = $reason;
		}
		if ( ! isset( self::$keep_reasons_all[ $id ] ) ) {
			self::$keep_reasons_all[ $id ] = array();
		}
		if ( ! in_array( $reason, self::$keep_reasons_all[ $id ], true ) ) {
			self::$keep_reasons_all[ $id ][] = $reason;
		}
	}

	public static function build_manifest(): array {
		self::build_site_basenames();
		self::collect_in_use_ids( false );

		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT p.ID, p.post_title, p.post_date, pm.meta_value AS attached_file
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_wp_attached_file'
			WHERE p.post_type = 'attachment'
			AND p.post_status IN ('inherit','private','publish')
			ORDER BY p.ID ASC",
			ARRAY_A
		);

		$items       = array();
		$safety_only = array();
		$safety_keys = array( 'upload_2026_07', 'recent_upload' );

		foreach ( $rows ?: array() as $row ) {
			$aid      = (int) $row['ID'];
			$reasons  = self::$keep_reasons_all[ $aid ] ?? array();
			$in_use   = isset( self::$in_use[ $aid ] );
			$rel      = (string) ( $row['attached_file'] ?? '' );
			$basename = $rel ? basename( $rel ) : '';
			$url      = $rel ? wp_get_upload_dir()['baseurl'] . '/' . $rel : wp_get_attachment_url( $aid );
			$used_by  = self::describe_usage( $aid, $reasons );

			$only_safety = $in_use && $reasons && ! array_diff( $reasons, $safety_keys );
			if ( $only_safety ) {
				$safety_only[] = $aid;
			}

			if ( ! $in_use ) {
				continue;
			}

			$items[] = array(
				'attachment_id' => $aid,
				'filename'      => $basename,
				'relative_path' => $rel,
				'url'           => $url ? (string) $url : '',
				'used_by'       => $used_by,
				'reasons'       => $reasons,
				'post_date'     => (string) ( $row['post_date'] ?? '' ),
				'safety_only'   => $only_safety,
			);
		}

		return array(
			'total_media'    => count( $rows ?: array() ),
			'in_use'         => count( $items ),
			'safety_skipped' => count( $safety_only ),
			'items'          => $items,
		);
	}

	/** @param list<string> $reasons */
	private static function describe_usage( int $aid, array $reasons ): string {
		global $wpdb;
		$parts = array();

		if ( in_array( 'thumbnail_id', $reasons, true ) || in_array( 'post_parent_thumbnail', $reasons, true ) ) {
			$sku = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT pm2.meta_value FROM {$wpdb->postmeta} pm
					INNER JOIN {$wpdb->postmeta} pm2 ON pm2.post_id = pm.post_id AND pm2.meta_key = '_sku'
					WHERE pm.meta_key = '_thumbnail_id' AND pm.meta_value = %d LIMIT 1",
					(string) $aid
				)
			);
			$parts[] = $sku ? 'product_thumbnail:' . $sku : 'product_thumbnail';
		}

		if ( in_array( 'product_gallery', $reasons, true ) || in_array( 'post_parent_gallery', $reasons, true ) ) {
			$parts[] = 'product_gallery';
		}
		if ( in_array( 'custom_logo', $reasons, true ) ) {
			$parts[] = 'custom_logo';
		}
		if ( in_array( 'site_icon', $reasons, true ) ) {
			$parts[] = 'site_icon';
		}
		if ( in_array( 'site_basename', $reasons, true ) || in_array( 'catalog_slug', $reasons, true ) ) {
			$parts[] = 'site_catalog';
		}

		foreach ( array( 'post_content_class', 'post_content_url', 'post_content_query', 'option_class', 'option_serialized' ) as $r ) {
			if ( in_array( $r, $reasons, true ) ) {
				$parts[] = $r;
			}
		}

		foreach ( array( 'upload_2026_07', 'recent_upload' ) as $r ) {
			if ( in_array( $r, $reasons, true ) ) {
				$parts[] = $r;
			}
		}

		return $parts ? implode( '; ', array_unique( $parts ) ) : implode( '; ', $reasons );
	}

	private static function build_site_basenames(): void {
		$catalog_file = ABSPATH . 'site/catalog-data.js';
		$site_file    = ABSPATH . 'site/site-data.js';

		if ( is_readable( $catalog_file ) ) {
			$content = file_get_contents( $catalog_file );
			if ( is_string( $content ) && preg_match( '/window\.CATALOG_DATA\s*=\s*(\{.*?\})\s*;/s', $content, $m ) ) {
				$data = json_decode( $m[1], true );
				foreach ( ( $data['products'] ?? array() ) as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}
					$slug = (string) ( $item['slug'] ?? '' );
					if ( $slug ) {
						self::$catalog_slugs[ $slug ] = true;
						self::$site_basenames[ $slug . '.jpg' ] = true;
					}
					$image = (string) ( $item['image'] ?? '' );
					if ( $image ) {
						self::$site_basenames[ basename( $image ) ] = true;
					}
				}
			}
		}

		if ( is_readable( $site_file ) ) {
			$content = file_get_contents( $site_file );
			if ( is_string( $content ) ) {
				$json = preg_replace( '/^window\.SITE_DATA=/', '', $content );
				$json = preg_replace( '/;\s*$/', '', (string) $json );
				$site = json_decode( (string) $json, true );
				if ( is_array( $site ) ) {
					foreach ( ( $site['hero'] ?? array() ) as $slide ) {
						self::add_asset_paths( $slide );
					}
					foreach ( ( $site['gallery'] ?? array() ) as $g ) {
						if ( is_string( $g ) ) {
							self::$site_basenames[ basename( $g ) ] = true;
						}
					}
					foreach ( ( $site['categoryImages'] ?? array() ) as $cat ) {
						self::add_asset_paths( $cat );
					}
					foreach ( ( $site['homeStatic'] ?? array() ) as $block ) {
						self::add_asset_paths( $block );
					}
				}
			}
		}

		$brand_dir = ABSPATH . 'public/images/brand/';
		if ( is_dir( $brand_dir ) ) {
			foreach ( scandir( $brand_dir ) ?: array() as $f ) {
				if ( $f && '.' !== $f[0] ) {
					self::$site_basenames[ $f ] = true;
				}
			}
		}
	}

	/** @param array<string, mixed>|mixed $asset */
	private static function add_asset_paths( $asset ): void {
		if ( ! is_array( $asset ) ) {
			return;
		}
		if ( ! empty( $asset['base'] ) ) {
			$base = (string) $asset['base'];
			foreach ( array( 400, 800, 1200, 1600 ) as $w ) {
				self::$site_basenames[ basename( $base . '-' . $w . '.jpg' ) ] = true;
				self::$site_basenames[ basename( $base . '-' . $w . '.webp' ) ] = true;
				self::$site_basenames[ basename( $base . '-' . $w . '.avif' ) ] = true;
			}
		}
		foreach ( ( $asset['sources'] ?? array() ) as $size ) {
			if ( ! is_array( $size ) ) {
				continue;
			}
			foreach ( $size as $p ) {
				if ( is_string( $p ) && $p ) {
					self::$site_basenames[ basename( $p ) ] = true;
				}
			}
		}
	}

	private static function collect_in_use_ids( bool $strict = false ): void {
		global $wpdb;

		// WooCommerce + post thumbnails.
		$thumb_rows = $wpdb->get_results(
			"SELECT post_id, meta_value FROM {$wpdb->postmeta}
			WHERE meta_key = '_thumbnail_id' AND meta_value REGEXP '^[0-9]+$'",
			ARRAY_A
		);
		foreach ( $thumb_rows ?: array() as $row ) {
			self::mark_keep( (int) $row['meta_value'], 'thumbnail_id' );
		}

		// WooCommerce galleries.
		$gallery_rows = $wpdb->get_col(
			"SELECT meta_value FROM {$wpdb->postmeta}
			WHERE meta_key = '_product_image_gallery' AND meta_value != ''"
		);
		foreach ( $gallery_rows ?: array() as $csv ) {
			foreach ( array_filter( array_map( 'intval', explode( ',', (string) $csv ) ) ) as $gid ) {
				self::mark_keep( $gid, 'product_gallery' );
			}
		}

		// Embedded in post_content.
		$content_rows = $wpdb->get_col(
			"SELECT post_content FROM {$wpdb->posts}
			WHERE post_content LIKE '%wp-image-%'
			OR post_content LIKE '%wp-content/uploads%'
			OR post_content LIKE '%attachment_id%'"
		);
		foreach ( $content_rows ?: array() as $html ) {
			if ( preg_match_all( '/wp-image-(\d+)/', (string) $html, $m ) ) {
				foreach ( $m[1] as $id ) {
					self::mark_keep( (int) $id, 'post_content_class' );
				}
			}
			if ( preg_match_all( '/\?attachment_id=(\d+)/', (string) $html, $m ) ) {
				foreach ( $m[1] as $id ) {
					self::mark_keep( (int) $id, 'post_content_query' );
				}
			}
			if ( preg_match_all( '/wp-content\/uploads\/[^"\'\s)]+/', (string) $html, $m ) ) {
				foreach ( $m[0] as $url_path ) {
					self::mark_keep_by_upload_path( (string) $url_path, 'post_content_url' );
				}
			}
		}

		// Post meta referencing attachment IDs (serialized or plain).
		$meta_rows = $wpdb->get_results(
			"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta}
			WHERE (
				meta_value REGEXP 'wp-image-[0-9]+'
				OR meta_value REGEXP '\"id\";i:[0-9]+'
				OR meta_value REGEXP ';i:[0-9]+;'
				OR (meta_key LIKE '%image%' AND meta_value REGEXP '^[0-9]+$')
				OR meta_key IN ('_thumbnail_id','_product_image_gallery','_wp_attachment_metadata')
			)
			AND meta_key NOT LIKE '\\_wp%'
			LIMIT 50000",
			ARRAY_A
		);
		foreach ( $meta_rows ?: array() as $row ) {
			$val = (string) ( $row['meta_value'] ?? '' );
			if ( preg_match( '/^[0-9]+$/', $val ) ) {
				self::mark_keep( (int) $val, 'post_meta_numeric:' . $row['meta_key'] );
			}
			if ( preg_match_all( '/wp-image-(\d+)/', $val, $m ) ) {
				foreach ( $m[1] as $id ) {
					self::mark_keep( (int) $id, 'post_meta_class' );
				}
			}
			if ( preg_match_all( '/;i:(\d+);/', $val, $m ) ) {
				foreach ( $m[1] as $id ) {
					$aid = (int) $id;
					if ( 'attachment' === get_post_type( $aid ) ) {
						self::mark_keep( $aid, 'post_meta_serialized' );
					}
				}
			}
		}

		// Theme mods: logo, site icon.
		$custom_logo = (int) get_theme_mod( 'custom_logo' );
		self::mark_keep( $custom_logo, 'custom_logo' );
		$site_icon = (int) get_option( 'site_icon' );
		self::mark_keep( $site_icon, 'site_icon' );

		// Options (widgets, customizer, etc.).
		$option_rows = $wpdb->get_results(
			"SELECT option_name, option_value FROM {$wpdb->options}
			WHERE option_value LIKE '%wp-image-%'
			OR option_value LIKE '%attachment_id%'
			OR option_name IN ('site_icon','custom_logo')
			LIMIT 5000",
			ARRAY_A
		);
		foreach ( $option_rows ?: array() as $row ) {
			$val = (string) ( $row['option_value'] ?? '' );
			if ( preg_match_all( '/wp-image-(\d+)/', $val, $m ) ) {
				foreach ( $m[1] as $id ) {
					self::mark_keep( (int) $id, 'option_class' );
				}
			}
			if ( preg_match_all( '/;i:(\d+);/', $val, $m ) ) {
				foreach ( $m[1] as $id ) {
					$aid = (int) $id;
					if ( 'attachment' === get_post_type( $aid ) ) {
						self::mark_keep( $aid, 'option_serialized' );
					}
				}
			}
		}

		// Only keep post_parent attachments if they are the parent's thumbnail or in its gallery.
		$parent_rows = $wpdb->get_results(
			"SELECT p.ID, p.post_parent FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->posts} parent ON parent.ID = p.post_parent
			WHERE p.post_type = 'attachment'
			AND p.post_parent > 0
			AND parent.post_status NOT IN ('trash','auto-draft')",
			ARRAY_A
		);
		foreach ( $parent_rows ?: array() as $row ) {
			$aid     = (int) $row['ID'];
			$parent  = (int) $row['post_parent'];
			$thumb   = (int) get_post_thumbnail_id( $parent );
			$gallery = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $parent, '_product_image_gallery', true ) ) ) );
			if ( $aid === $thumb ) {
				self::mark_keep( $aid, 'post_parent_thumbnail' );
			} elseif ( in_array( $aid, $gallery, true ) ) {
				self::mark_keep( $aid, 'post_parent_gallery' );
			}
		}

		// Map attachments by filename to catalog/site assets.
		$attachments = $wpdb->get_results(
			"SELECT p.ID, p.post_date, pm.meta_value AS attached_file
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_wp_attached_file'
			WHERE p.post_type = 'attachment'",
			ARRAY_A
		);
		foreach ( $attachments ?: array() as $row ) {
			$aid  = (int) $row['ID'];
			$file = (string) ( $row['attached_file'] ?? '' );
			$base = $file ? basename( $file ) : '';
			$slug = $base ? pathinfo( $base, PATHINFO_FILENAME ) : '';

			if ( ! $strict ) {
				// Protect recent 2026/07 catalog cover uploads.
				if ( str_contains( $file, '2026/07/' ) ) {
					self::mark_keep( $aid, 'upload_2026_07' );
				}

				// Protect uploads from last 7 days (conservative).
				$post_date = strtotime( (string) ( $row['post_date'] ?? '' ) );
				if ( $post_date && $post_date >= strtotime( '-7 days' ) ) {
					self::mark_keep( $aid, 'recent_upload' );
				}
			}

			if ( $base && isset( self::$site_basenames[ $base ] ) ) {
				self::mark_keep( $aid, 'site_basename' );
			}
			if ( $slug && isset( self::$catalog_slugs[ $slug ] ) ) {
				self::mark_keep( $aid, 'catalog_slug' );
			}
		}
	}

	private static function mark_keep_by_upload_path( string $url_path, string $reason ): void {
		global $wpdb;
		$rel = preg_replace( '#^.*?wp-content/uploads/#', '', $url_path );
		$rel = strtok( (string) $rel, '?#' );
		if ( ! $rel ) {
			return;
		}
		$aid = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
				$rel
			)
		);
		if ( $aid ) {
			self::mark_keep( $aid, $reason );
		}
	}
}

if ( isset( $_GET['manifest'] ) && '1' === (string) $_GET['manifest'] ) { // phpcs:ignore
	echo wp_json_encode( Tuex_Cleanup_Unused_Media::build_manifest(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
	exit;
}

$dry_run = ! isset( $_GET['delete'] ) || '1' !== (string) $_GET['delete']; // phpcs:ignore
$offset  = isset( $_GET['offset'] ) ? max( 0, (int) $_GET['offset'] ) : 0; // phpcs:ignore
$limit   = isset( $_GET['limit'] ) ? max( 1, min( 100, (int) $_GET['limit'] ) ) : TUEX_BATCH_DEFAULT; // phpcs:ignore
$strict  = isset( $_GET['strict'] ) && '1' === (string) $_GET['strict']; // phpcs:ignore

if ( isset( $_GET['dry_run'] ) && '1' === (string) $_GET['dry_run'] ) { // phpcs:ignore
	$dry_run = true;
}

$result = Tuex_Cleanup_Unused_Media::run( $dry_run, $offset, $limit, $strict );
echo wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
