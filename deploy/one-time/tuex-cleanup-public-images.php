<?php
/**
 * One-time: delete orphan files in public/images/ not referenced by site-data.js + catalog-data.js.
 * Skips brand/ and wp-content. DELETE THIS FILE after running.
 *
 * Usage:
 *   Dry-run:  ?token=TOKEN&dry_run=1
 *   Delete:   ?token=TOKEN&delete=1&offset=0&limit=80
 */
header( 'Content-Type: application/json; charset=utf-8' );

$token = isset( $_GET['token'] ) ? (string) $_GET['token'] : '';
if ( $token !== 'te-cleanup-20260708-public' ) {
	http_response_code( 403 );
	echo json_encode( array( 'error' => 'Forbidden' ) );
	exit;
}

@set_time_limit( 300 );
@ini_set( 'memory_limit', '512M' );

function tuex_json_out( $data, $code = 200 ) {
	http_response_code( $code );
	echo json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	exit;
}

try {
	$root      = dirname( __FILE__ );
	$scan_dirs = array(
		'public/images/hero',
		'public/images/gallery',
		'public/images/catalog',
		'public/images/premium',
		'public/images/home',
	);

	$site_path    = $root . '/site/site-data.js';
	$catalog_path = $root . '/site/catalog-data.js';

	if ( ! is_readable( $site_path ) || ! is_readable( $catalog_path ) ) {
		tuex_json_out(
			array(
				'error'         => 'Missing site-data.js or catalog-data.js',
				'site_exists'   => is_readable( $site_path ),
				'catalog_exists'=> is_readable( $catalog_path ),
				'root'          => $root,
			),
			500
		);
	}

	$site_content = (string) file_get_contents( $site_path );
	$cat_content  = (string) file_get_contents( $catalog_path );

	if ( ! preg_match( '/window\.SITE_DATA\s*=\s*(\{.*\})\s*;?\s*$/s', $site_content, $site_m ) ) {
		tuex_json_out( array( 'error' => 'Could not parse site-data.js' ), 500 );
	}
	if ( ! preg_match( '/window\.CATALOG_DATA\s*=\s*(\{.*?\})\s*;/s', $cat_content, $cat_m ) ) {
		tuex_json_out( array( 'error' => 'Could not parse catalog-data.js' ), 500 );
	}

	$site    = json_decode( $site_m[1], true );
	$catalog = json_decode( $cat_m[1], true );
	if ( ! is_array( $site ) || ! is_array( $catalog ) ) {
		tuex_json_out( array( 'error' => 'JSON decode failed' ), 500 );
	}

	$raw = array();
	$collect = function ( $val ) use ( &$raw, &$collect ) {
		if ( ! $val ) {
			return;
		}
		if ( is_string( $val ) && 0 === strpos( $val, 'public/images/' ) && false === strpos( $val, 'brand/' ) ) {
			$raw[ $val ] = true;
			return;
		}
		if ( ! is_array( $val ) ) {
			return;
		}
		if ( isset( $val['base'] ) && is_string( $val['base'] ) && 0 === strpos( $val['base'], 'public/images/' ) ) {
			$raw[ $val['base'] ] = true;
		}
		if ( isset( $val['sources'] ) && is_array( $val['sources'] ) ) {
			foreach ( $val['sources'] as $size ) {
				if ( ! is_array( $size ) ) {
					continue;
				}
				foreach ( $size as $p ) {
					if ( is_string( $p ) && 0 === strpos( $p, 'public/images/' ) ) {
						$raw[ $p ] = true;
					}
				}
			}
		}
		foreach ( $val as $v ) {
			$collect( $v );
		}
	};

	$collect( $site );
	foreach ( ( isset( $catalog['products'] ) ? $catalog['products'] : array() ) as $product ) {
		$collect( isset( $product['image'] ) ? $product['image'] : null );
	}

	$keep = array();
	foreach ( array_keys( $raw ) as $p ) {
		if ( preg_match( '/\.(jpg|jpeg|webp|avif|png)$/i', $p ) ) {
			$keep[ $p ] = true;
			continue;
		}
		foreach ( array( 400, 800, 1200, 1600 ) as $w ) {
			foreach ( array( 'jpg', 'webp', 'avif' ) as $ext ) {
				$keep[ "{$p}-{$w}.{$ext}" ] = true;
			}
		}
	}

	$all_files = array();
	foreach ( $scan_dirs as $rel ) {
		$abs = $root . '/' . $rel;
		if ( ! is_dir( $abs ) ) {
			continue;
		}
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $abs, FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $it as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}
			$full     = $file->getPathname();
			$rel_path = str_replace( '\\', '/', substr( $full, strlen( $root ) + 1 ) );
			if ( false !== strpos( $rel_path, '/brand/' ) ) {
				continue;
			}
			$all_files[] = $rel_path;
		}
	}
	sort( $all_files );

	$orphans = array();
	$kept    = array();
	foreach ( $all_files as $f ) {
		if ( isset( $keep[ $f ] ) ) {
			$kept[] = $f;
		} else {
			$orphans[] = $f;
		}
	}

	$dry_run = isset( $_GET['dry_run'] ) && (string) $_GET['dry_run'] === '1';
	$delete  = isset( $_GET['delete'] ) && (string) $_GET['delete'] === '1';
	$offset  = max( 0, (int) ( isset( $_GET['offset'] ) ? $_GET['offset'] : 0 ) );
	$limit   = max( 1, min( 200, (int) ( isset( $_GET['limit'] ) ? $_GET['limit'] : 80 ) ) );

	$by_folder = array();
	foreach ( $scan_dirs as $d ) {
		$by_folder[ $d ] = array( 'remote' => 0, 'keep' => 0, 'orphans' => 0 );
	}
	foreach ( $all_files as $f ) {
		$parts  = explode( '/', $f );
		$folder = implode( '/', array_slice( $parts, 0, 3 ) );
		if ( ! isset( $by_folder[ $folder ] ) ) {
			continue;
		}
		++$by_folder[ $folder ]['remote'];
		if ( isset( $keep[ $f ] ) ) {
			++$by_folder[ $folder ]['keep'];
		} else {
			++$by_folder[ $folder ]['orphans'];
		}
	}

	$batch        = array_slice( $orphans, $offset, $limit );
	$deleted      = 0;
	$failed       = 0;
	$deleted_list = array();
	$failed_list  = array();

	if ( $delete && ! $dry_run ) {
		foreach ( $batch as $rel ) {
			$abs = $root . '/' . $rel;
			if ( is_file( $abs ) && @unlink( $abs ) ) {
				++$deleted;
				$deleted_list[] = $rel;
			} else {
				++$failed;
				$failed_list[] = $rel;
			}
		}
	}

	$next = $offset + count( $batch );
	$done = $next >= count( $orphans );

	tuex_json_out(
		array(
			'dry_run'          => $dry_run,
			'delete'           => $delete,
			'referenced'       => count( $keep ),
			'total_remote'     => count( $all_files ),
			'kept'             => count( $kept ),
			'orphans'          => count( $orphans ),
			'by_folder'        => $by_folder,
			'offset'           => $offset,
			'limit'            => $limit,
			'batch_size'       => count( $batch ),
			'deleted'          => $deleted,
			'failed'           => $failed,
			'next_offset'      => $done ? null : $next,
			'done'             => $done,
			'sample_orphans'   => array_slice( $orphans, 0, 25 ),
			'deleted_batch'    => $deleted_list,
			'failed_batch'     => $failed_list,
			'catalog_products' => count( isset( $catalog['products'] ) ? $catalog['products'] : array() ),
			'message'          => $dry_run
				? sprintf(
					'DRY-RUN: %1$d archivos en servidor, %2$d referenciados, %3$d huérfanos (se borrarían).',
					count( $all_files ),
					count( $kept ),
					count( $orphans )
				)
				: sprintf( 'DELETE lote offset %1$d: borrados %2$d, fallos %3$d.', $offset, $deleted, $failed ),
		)
	);
} catch ( Exception $e ) {
	tuex_json_out( array( 'error' => $e->getMessage() ), 500 );
}
