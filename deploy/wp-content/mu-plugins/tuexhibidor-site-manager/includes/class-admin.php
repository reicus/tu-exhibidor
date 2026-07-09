<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Tuexhibidor_Site_Manager_Admin {

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_tuex_sm_replace_image', array( __CLASS__, 'ajax_replace_image' ) );
		add_action( 'wp_ajax_tuex_sm_save_alt', array( __CLASS__, 'ajax_save_alt' ) );
		add_action( 'wp_ajax_tuex_sm_save_featured', array( __CLASS__, 'ajax_save_featured' ) );
	}

	public static function register_menu(): void {
		add_menu_page(
			'Sitio Premium',
			'Sitio Premium',
			'manage_options',
			'tuexhibidor-site-manager',
			array( __CLASS__, 'render_page' ),
			'dashicons-format-gallery',
			3
		);
	}

	public static function enqueue_assets( string $hook ): void {
		if ( 'toplevel_page_tuexhibidor-site-manager' !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style(
			'tuex-sm-admin',
			TUEX_SM_URL . '/assets/admin.css',
			array(),
			TUEX_SM_VERSION
		);
		wp_enqueue_script(
			'tuex-sm-admin',
			TUEX_SM_URL . '/assets/admin.js',
			array( 'jquery' ),
			TUEX_SM_VERSION,
			true
		);
		wp_localize_script(
			'tuex-sm-admin',
			'TuexSiteManager',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'tuex_sm' ),
				'homeUrl' => home_url( '/site/' ),
				'cacheVer' => get_option( 'tuexhibidor_asset_version', '1' ),
			)
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos.', 'tuexhibidor' ) );
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'catalog';
		$tabs = array(
			'catalog'    => 'Catálogo',
			'hero'       => 'Hero',
			'home'       => 'Home estático',
			'featured'   => 'Más pedidos',
			'categories' => 'Categorías',
			'gallery'    => 'Exhibidores en acción',
			'brand'      => 'Marca',
		);
		if ( ! isset( $tabs[ $tab ] ) ) {
			$tab = 'catalog';
		}

		$ready = Tuexhibidor_Site_Manager_Paths::is_ready();
		?>
		<div class="wrap tuex-sm-wrap">
			<h1>Sitio Premium — Imágenes en vivo</h1>
			<div class="tuex-sm-quicklinks notice notice-info inline">
				<p><strong>Acceso privado (requiere iniciar sesión)</strong></p>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( home_url( '/imagenes' ) ); ?>">Sitio Premium — /imagenes</a>
					<a class="button" href="<?php echo esc_url( home_url( '/imagenes?tab=gallery' ) ); ?>">Exhibidores en acción</a>
					<a class="button" href="<?php echo esc_url( home_url( '/medios' ) ); ?>">Medios WP — /medios</a>
					<a class="button" href="<?php echo esc_url( home_url( '/site/#galeria' ) ); ?>" target="_blank" rel="noopener">Ver galería en vivo</a>
				</p>
				<p class="description">Estos enlaces no aparecen en el sitio público. Guárdalos como favoritos o compártelos solo con administradores.</p>
			</div>
			<p class="description">
				Administra las fotos del sitio público en
				<a href="<?php echo esc_url( home_url( '/site/' ) ); ?>" target="_blank" rel="noopener">tuexhibidor.cl/site/</a>.
				Los cambios se publican al instante.
			</p>

			<?php if ( ! $ready ) : ?>
				<div class="notice notice-error"><p>
					No se encontró la carpeta <code>site/</code> o <code>public/images/</code> en el servidor.
					Verifica que el sitio estático esté desplegado en <code>public_html/</code>.
				</p></div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=tuexhibidor-site-manager&tab=' . $key ) ); ?>"
						class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="tuex-sm-panel">
				<?php
				switch ( $tab ) {
					case 'hero':
						self::render_hero_tab();
						break;
					case 'home':
						self::render_home_static_tab();
						break;
					case 'featured':
						self::render_featured_tab();
						break;
					case 'categories':
						self::render_categories_tab();
						break;
					case 'gallery':
						self::render_gallery_tab();
						break;
					case 'brand':
						self::render_brand_tab();
						break;
					default:
						self::render_catalog_tab();
				}
				?>
			</div>
		</div>
		<?php
	}

	private static function render_catalog_tab(): void {
		$catalog  = Tuexhibidor_Site_Manager_Data::load_catalog();
		$products = $catalog['products'];
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$cat      = isset( $_GET['cat'] ) ? sanitize_key( wp_unslash( $_GET['cat'] ) ) : '';

		if ( $search ) {
			$products = array_values(
				array_filter(
					$products,
					static function ( $p ) use ( $search ) {
						$hay = strtolower( ( $p['code'] ?? '' ) . ' ' . ( $p['name'] ?? '' ) );
						return false !== strpos( $hay, strtolower( $search ) );
					}
				)
			);
		}
		if ( $cat ) {
			$products = array_values(
				array_filter(
					$products,
					static function ( $p ) use ( $cat ) {
						return ( $p['displayCategory'] ?? '' ) === $cat;
					}
				)
			);
		}

		$categories = array();
		foreach ( Tuexhibidor_Site_Manager_Data::load_site_data()['displayLabels'] ?? array() as $key => $label ) {
			$categories[ $key ] = $label;
		}
		?>
		<form method="get" class="tuex-sm-filters">
			<input type="hidden" name="page" value="tuexhibidor-site-manager">
			<input type="hidden" name="tab" value="catalog">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Buscar por código o nombre…">
			<select name="cat">
				<option value="">Todas las categorías</option>
				<?php foreach ( $categories as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $cat, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<button type="submit" class="button">Filtrar</button>
			<span class="tuex-sm-count"><?php echo count( $products ); ?> productos</span>
		</form>

		<p class="description">
			<label><input type="checkbox" id="tuex-sm-sync-wc" checked> También actualizar imagen en WooCommerce (por SKU)</label>
		</p>

		<div class="tuex-sm-grid">
			<?php foreach ( $products as $product ) : ?>
				<?php
				$img_url = Tuexhibidor_Site_Manager_Data::asset_preview_url( $product['image'] ?? '' );
				if ( ! $img_url && ! empty( $product['image'] ) ) {
					$img_url = Tuexhibidor_Site_Manager_Paths::public_url( $product['image'] );
				}
				?>
				<article class="tuex-sm-card" data-type="catalog" data-slug="<?php echo esc_attr( $product['slug'] ?? '' ); ?>" data-code="<?php echo esc_attr( $product['code'] ?? '' ); ?>">
					<div class="tuex-sm-thumb">
						<?php if ( $img_url ) : ?>
							<img src="<?php echo esc_url( $img_url . '?v=' . get_option( 'tuexhibidor_asset_version', '1' ) ); ?>" alt="">
						<?php else : ?>
							<span class="tuex-sm-no-img">Sin imagen</span>
						<?php endif; ?>
					</div>
					<div class="tuex-sm-card-body">
						<strong><?php echo esc_html( $product['code'] ?? '' ); ?></strong>
						<p><?php echo esc_html( wp_trim_words( $product['name'] ?? '', 8, '…' ) ); ?></p>
						<button type="button" class="button button-primary tuex-sm-replace">Cambiar imagen</button>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_hero_tab(): void {
		$site = Tuexhibidor_Site_Manager_Data::load_site_data();
		$hero = $site['hero'] ?? array();
		?>
		<p class="description">Carrusel principal de la home (<?php echo count( $hero ); ?> slides). Los cambios se publican al instante en tuexhibidor.cl/site/</p>
		<div class="tuex-sm-grid tuex-sm-grid-wide">
			<?php foreach ( $hero as $index => $asset ) : ?>
				<?php $img_url = Tuexhibidor_Site_Manager_Data::asset_preview_url( $asset ); ?>
				<article class="tuex-sm-card" data-type="hero" data-index="<?php echo (int) $index; ?>">
					<div class="tuex-sm-thumb tuex-sm-thumb-wide">
						<img src="<?php echo esc_url( $img_url . '?v=' . get_option( 'tuexhibidor_asset_version', '1' ) ); ?>" alt="">
					</div>
					<div class="tuex-sm-card-body">
						<label>Texto alternativo (SEO)
							<input type="text" class="tuex-sm-alt widefat" value="<?php echo esc_attr( is_array( $asset ) ? ( $asset['alt'] ?? '' ) : '' ); ?>">
						</label>
						<div class="tuex-sm-actions">
							<button type="button" class="button button-primary tuex-sm-replace">Cambiar imagen</button>
							<button type="button" class="button tuex-sm-save-alt">Guardar texto</button>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_featured_tab(): void {
		$site     = Tuexhibidor_Site_Manager_Data::load_site_data();
		$catalog  = Tuexhibidor_Site_Manager_Data::load_catalog();
		$products = $catalog['products'];
		$by_code  = array();
		foreach ( $products as $product ) {
			if ( ! empty( $product['code'] ) ) {
				$by_code[ $product['code'] ] = $product;
			}
		}

		$featured_skus = $site['featuredSkus'] ?? array();
		if ( ! is_array( $featured_skus ) || empty( $featured_skus ) ) {
			$featured_skus = Tuexhibidor_Site_Manager_Data::default_featured_skus( $products );
		}

		$cache_ver = get_option( 'tuexhibidor_asset_version', '1' );
		$catalog_json = array();
		foreach ( $products as $product ) {
			$code = $product['code'] ?? '';
			if ( ! $code ) {
				continue;
			}
			$img_url = Tuexhibidor_Site_Manager_Data::asset_preview_url( $product['image'] ?? '' );
			if ( ! $img_url && ! empty( $product['image'] ) ) {
				$img_url = Tuexhibidor_Site_Manager_Paths::public_url( $product['image'] );
			}
			$catalog_json[] = array(
				'code'  => $code,
				'name'  => $product['name'] ?? '',
				'slug'  => $product['slug'] ?? '',
				'image' => $img_url,
			);
		}
		?>
		<p class="description">
			Carrusel <strong>Los más pedidos</strong> en la home. Elige productos por código, ordénalos y guarda.
			Las fotos se editan en la pestaña <strong>Catálogo</strong>.
		</p>

		<div class="tuex-sm-featured-editor">
			<div class="tuex-sm-featured-add">
				<label class="screen-reader-text" for="tuex-sm-featured-search">Buscar producto</label>
				<input type="search" id="tuex-sm-featured-search" class="regular-text" placeholder="Buscar por código o nombre para añadir…" autocomplete="off">
				<div id="tuex-sm-featured-suggestions" class="tuex-sm-featured-suggestions" hidden></div>
			</div>

			<ol id="tuex-sm-featured-list" class="tuex-sm-featured-list">
				<?php foreach ( $featured_skus as $i => $code ) : ?>
					<?php
					if ( empty( $by_code[ $code ] ) ) {
						continue;
					}
					$product = $by_code[ $code ];
					$img_url = Tuexhibidor_Site_Manager_Data::asset_preview_url( $product['image'] ?? '' );
					if ( ! $img_url && ! empty( $product['image'] ) ) {
						$img_url = Tuexhibidor_Site_Manager_Paths::public_url( $product['image'] );
					}
					?>
					<li class="tuex-sm-featured-item" data-code="<?php echo esc_attr( $code ); ?>">
						<span class="tuex-sm-featured-order"><?php echo (int) $i + 1; ?></span>
						<div class="tuex-sm-featured-thumb">
							<?php if ( $img_url ) : ?>
								<img src="<?php echo esc_url( $img_url . '?v=' . $cache_ver ); ?>" alt="">
							<?php else : ?>
								<span class="tuex-sm-no-img">Sin imagen</span>
							<?php endif; ?>
						</div>
						<span class="tuex-sm-featured-meta">
							<strong><?php echo esc_html( $code ); ?></strong>
							<?php echo esc_html( wp_trim_words( $product['name'] ?? '', 10, '…' ) ); ?>
						</span>
						<span class="tuex-sm-featured-actions">
							<button type="button" class="button tuex-sm-featured-up" title="Subir">↑</button>
							<button type="button" class="button tuex-sm-featured-down" title="Bajar">↓</button>
							<button type="button" class="button tuex-sm-featured-remove" title="Quitar">✕</button>
						</span>
					</li>
				<?php endforeach; ?>
			</ol>

			<p class="tuex-sm-featured-footer">
				<button type="button" class="button button-primary" id="tuex-sm-featured-save">Guardar más pedidos</button>
				<a class="button" href="<?php echo esc_url( home_url( '/site/#featured' ) ); ?>" target="_blank" rel="noopener">Ver en el sitio</a>
				<span class="tuex-sm-count" id="tuex-sm-featured-count"><?php echo count( $featured_skus ); ?> productos</span>
			</p>
		</div>

		<script type="application/json" id="tuex-sm-catalog-json"><?php echo wp_json_encode( $catalog_json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
		<?php
	}

	private static function render_categories_tab(): void {
		$site   = Tuexhibidor_Site_Manager_Data::load_site_data();
		$images = $site['categoryImages'] ?? array();
		$labels = $site['displayLabels'] ?? array();
		$order  = $site['displayCategories'] ?? array_keys( $images );
		?>
		<p class="description">
			Imágenes de portada de cada categoría (tarjetas bajo el hero).
			La foto de la sección «A medida» se edita en <strong>Home estático</strong>.
			Los carruseles del catálogo usan fotos de producto — edítalas en la pestaña <strong>Catálogo</strong>.
		</p>
		<div class="tuex-sm-grid tuex-sm-grid-wide">
			<?php foreach ( $order as $key ) : ?>
				<?php
				if ( empty( $images[ $key ] ) ) {
					continue;
				}
				$asset   = $images[ $key ];
				$img_url = Tuexhibidor_Site_Manager_Data::asset_preview_url( $asset );
				$label   = $labels[ $key ] ?? $key;
				?>
				<article class="tuex-sm-card" data-type="category" data-category="<?php echo esc_attr( $key ); ?>">
					<div class="tuex-sm-thumb tuex-sm-thumb-wide">
						<img src="<?php echo esc_url( $img_url . '?v=' . get_option( 'tuexhibidor_asset_version', '1' ) ); ?>" alt="">
					</div>
					<div class="tuex-sm-card-body">
						<strong><?php echo esc_html( $label ); ?></strong>
						<label>Texto alternativo (SEO)
							<input type="text" class="tuex-sm-alt widefat" value="<?php echo esc_attr( is_array( $asset ) ? ( $asset['alt'] ?? '' ) : '' ); ?>">
						</label>
						<div class="tuex-sm-actions">
							<button type="button" class="button button-primary tuex-sm-replace">Cambiar imagen</button>
							<button type="button" class="button tuex-sm-save-alt">Guardar texto</button>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_home_static_tab(): void {
		$site  = Tuexhibidor_Site_Manager_Data::ensure_home_static_defaults();
		$slots = Tuexhibidor_Site_Manager_Paths::home_static_slots();
		$cache = get_option( 'tuexhibidor_asset_version', '1' );
		?>
		<p class="description">
			Imágenes fijas del home (no carruseles). Los carruseles del hero, galería, «Quiénes somos» y «Los más pedidos»
			se editan en sus pestañas correspondientes. Las tarjetas de valor y contacto no llevan foto.
		</p>
		<div class="tuex-sm-grid tuex-sm-grid-wide">
			<?php foreach ( $slots as $key => $slot ) : ?>
				<?php
				$asset   = $site['homeStatic'][ $key ] ?? null;
				$img_url = $asset ? Tuexhibidor_Site_Manager_Data::asset_preview_url( $asset ) : '';
				?>
				<article class="tuex-sm-card" data-type="home-static" data-static="<?php echo esc_attr( $key ); ?>">
					<div class="tuex-sm-thumb tuex-sm-thumb-wide">
						<?php if ( $img_url ) : ?>
							<img src="<?php echo esc_url( $img_url . '?v=' . $cache ); ?>" alt="">
						<?php else : ?>
							<span class="tuex-sm-no-img">Sin imagen</span>
						<?php endif; ?>
					</div>
					<div class="tuex-sm-card-body">
						<strong><?php echo esc_html( $slot['label'] ); ?></strong>
						<p class="description">Sección <code><?php echo esc_html( $slot['section'] ); ?></code></p>
						<label>Texto alternativo (SEO)
							<input type="text" class="tuex-sm-alt widefat" value="<?php echo esc_attr( is_array( $asset ) ? ( $asset['alt'] ?? '' ) : '' ); ?>">
						</label>
						<div class="tuex-sm-actions">
							<button type="button" class="button button-primary tuex-sm-replace">Cambiar imagen</button>
							<button type="button" class="button tuex-sm-save-alt">Guardar texto</button>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}

	private static function render_gallery_tab(): void {
		$site    = Tuexhibidor_Site_Manager_Data::load_site_data();
		$gallery = $site['gallery'] ?? array();
		$page    = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
		$per     = 24;
		$total   = count( $gallery );
		$pages   = max( 1, (int) ceil( $total / $per ) );
		$slice   = array_slice( $gallery, ( $page - 1 ) * $per, $per );
		$cache_ver = get_option( 'tuexhibidor_asset_version', '1' );
		?>
		<p class="description">
			<strong>Exhibidores en acción</strong> — carrusel de la sección
			<a href="<?php echo esc_url( home_url( '/site/#galeria' ) ); ?>" target="_blank" rel="noopener">#galeria</a>
			(<?php echo (int) $total; ?> fotos). Haz clic en <em>Cambiar imagen</em> para reemplazar cada slide.
			Las primeras 6 también alimentan el carrusel de <strong>Quiénes somos</strong>.
		</p>
		<div class="tuex-sm-grid tuex-sm-grid-wide">
			<?php foreach ( $slice as $i => $asset ) : ?>
				<?php
				$index    = ( $page - 1 ) * $per + $i;
				$rel      = Tuexhibidor_Site_Manager_Data::gallery_relative_path( $asset );
				$img_url  = Tuexhibidor_Site_Manager_Data::asset_preview_url( $asset );
				$alt      = Tuexhibidor_Site_Manager_Data::gallery_alt( $asset );
				$filename = $rel ? basename( $rel ) : '';
				?>
				<article class="tuex-sm-card" data-type="gallery" data-index="<?php echo (int) $index; ?>">
					<div class="tuex-sm-thumb tuex-sm-thumb-wide">
						<?php if ( $img_url ) : ?>
							<img src="<?php echo esc_url( $img_url . '?v=' . $cache_ver ); ?>" alt="">
						<?php else : ?>
							<span class="tuex-sm-no-img">Sin imagen</span>
						<?php endif; ?>
					</div>
					<div class="tuex-sm-card-body">
						<strong>Slide <?php echo (int) $index + 1; ?></strong>
						<?php if ( $filename ) : ?>
							<p><code><?php echo esc_html( $filename ); ?></code></p>
						<?php endif; ?>
						<label>Texto alternativo (SEO)
							<input type="text" class="tuex-sm-alt widefat" value="<?php echo esc_attr( $alt ); ?>" placeholder="Exhibidor en vitrina — Tu Exhibidor">
						</label>
						<div class="tuex-sm-actions">
							<button type="button" class="button button-primary tuex-sm-replace">Cambiar imagen</button>
							<button type="button" class="button tuex-sm-save-alt">Guardar texto</button>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if ( $pages > 1 ) : ?>
			<div class="tablenav">
				<div class="tablenav-pages">
					<?php for ( $p = 1; $p <= $pages; $p++ ) : ?>
						<a class="button <?php echo $p === $page ? 'button-primary' : ''; ?>"
							href="<?php echo esc_url( admin_url( 'admin.php?page=tuexhibidor-site-manager&tab=gallery&paged=' . $p ) ); ?>">
							<?php echo (int) $p; ?>
						</a>
					<?php endfor; ?>
				</div>
			</div>
		<?php endif; ?>
		<?php
	}

	private static function render_brand_tab(): void {
		$slots = Tuexhibidor_Site_Manager_Paths::brand_slots();
		$labels = array(
			'logo-ink'    => 'Logo header (PNG)',
			'logo-gold'   => 'Logo footer (WebP)',
			'favicon'     => 'Favicon 32×32',
			'apple-touch' => 'Apple touch icon',
		);
		?>
		<p class="description">Logo y favicon del sitio.</p>
		<div class="tuex-sm-grid tuex-sm-grid-wide">
			<?php foreach ( $slots as $key => $path ) : ?>
				<?php $img_url = Tuexhibidor_Site_Manager_Paths::public_url( $path ); ?>
				<article class="tuex-sm-card" data-type="brand" data-brand="<?php echo esc_attr( $key ); ?>">
					<div class="tuex-sm-thumb">
						<img src="<?php echo esc_url( $img_url . '?v=' . get_option( 'tuexhibidor_asset_version', '1' ) ); ?>" alt="">
					</div>
					<div class="tuex-sm-card-body">
						<strong><?php echo esc_html( $labels[ $key ] ?? $key ); ?></strong>
						<p><code><?php echo esc_html( $path ); ?></code></p>
						<button type="button" class="button button-primary tuex-sm-replace">Cambiar</button>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}

	public static function ajax_replace_image(): void {
		self::verify_ajax();
		$type          = sanitize_key( $_POST['item_type'] ?? '' );
		$attachment_id = (int) ( $_POST['attachment_id'] ?? 0 );
		$sync_wc       = ! empty( $_POST['sync_wc'] );

		if ( ! $attachment_id || ! $type ) {
			wp_send_json_error( array( 'message' => 'Datos incompletos.' ) );
		}

		$result = null;
		switch ( $type ) {
			case 'catalog':
				$result = self::replace_catalog( sanitize_text_field( wp_unslash( $_POST['slug'] ?? '' ) ), $attachment_id, $sync_wc );
				break;
			case 'hero':
				$result = self::replace_hero( (int) ( $_POST['index'] ?? -1 ), $attachment_id );
				break;
			case 'gallery':
				$result = self::replace_gallery( (int) ( $_POST['index'] ?? -1 ), $attachment_id );
				break;
			case 'category':
				$result = self::replace_category( sanitize_key( $_POST['category'] ?? '' ), $attachment_id );
				break;
			case 'brand':
				$result = self::replace_brand( sanitize_key( $_POST['brand'] ?? '' ), $attachment_id );
				break;
			case 'home-static':
				$result = self::replace_home_static( sanitize_key( $_POST['static'] ?? '' ), $attachment_id );
				break;
			default:
				wp_send_json_error( array( 'message' => 'Tipo no válido.' ) );
		}

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		Tuexhibidor_Site_Manager_Data::bump_cache_version();
		wp_send_json_success(
			array(
				'message'  => 'Imagen actualizada en el sitio en vivo.',
				'preview'  => $result['preview'] ?? '',
				'cacheVer' => get_option( 'tuexhibidor_asset_version' ),
			)
		);
	}

	public static function ajax_save_alt(): void {
		self::verify_ajax();
		$type  = sanitize_key( $_POST['item_type'] ?? '' );
		$index = (int) ( $_POST['index'] ?? -1 );
		$alt   = sanitize_text_field( wp_unslash( $_POST['alt'] ?? '' ) );

		$site = Tuexhibidor_Site_Manager_Data::load_site_data();

		if ( 'hero' === $type ) {
			if ( $index < 0 || empty( $site['hero'][ $index ] ) || ! is_array( $site['hero'][ $index ] ) ) {
				wp_send_json_error( array( 'message' => 'Slide no encontrado.' ) );
			}
			$site['hero'][ $index ]['alt'] = $alt;
		} elseif ( 'category' === $type ) {
			$key = sanitize_key( $_POST['category'] ?? '' );
			if ( ! $key || empty( $site['categoryImages'][ $key ] ) ) {
				wp_send_json_error( array( 'message' => 'Categoría no encontrada.' ) );
			}
			if ( ! is_array( $site['categoryImages'][ $key ] ) ) {
				$site['categoryImages'][ $key ] = array(
					'base'    => Tuexhibidor_Site_Manager_Data::asset_base( $site['categoryImages'][ $key ] ),
					'alt'     => $alt,
					'sources' => Tuexhibidor_Site_Manager_Images::build_sources_map(
						Tuexhibidor_Site_Manager_Data::asset_base( $site['categoryImages'][ $key ] )
					),
				);
			} else {
				$site['categoryImages'][ $key ]['alt'] = $alt;
			}
		} elseif ( 'home-static' === $type ) {
			$key = sanitize_key( $_POST['static'] ?? '' );
			$site = Tuexhibidor_Site_Manager_Data::ensure_home_static_defaults();
			if ( ! $key || empty( $site['homeStatic'][ $key ] ) ) {
				wp_send_json_error( array( 'message' => 'Imagen estática no encontrada.' ) );
			}
			if ( ! is_array( $site['homeStatic'][ $key ] ) ) {
				$site['homeStatic'][ $key ] = array(
					'base'    => Tuexhibidor_Site_Manager_Data::asset_base( $site['homeStatic'][ $key ] ),
					'alt'     => $alt,
					'sources' => Tuexhibidor_Site_Manager_Images::build_sources_map(
						Tuexhibidor_Site_Manager_Data::asset_base( $site['homeStatic'][ $key ] )
					),
				);
			} else {
				$site['homeStatic'][ $key ]['alt'] = $alt;
			}
		} elseif ( 'gallery' === $type ) {
			if ( $index < 0 || empty( $site['gallery'][ $index ] ) ) {
				wp_send_json_error( array( 'message' => 'Slide de galería no encontrado.' ) );
			}
			$rel = Tuexhibidor_Site_Manager_Data::gallery_relative_path( $site['gallery'][ $index ] );
			if ( ! $rel ) {
				wp_send_json_error( array( 'message' => 'Ruta de imagen no válida.' ) );
			}
			$site['gallery'][ $index ] = array(
				'base' => $rel,
				'alt'  => $alt,
			);
		} else {
			wp_send_json_error( array( 'message' => 'Solicitud no válida.' ) );
		}

		Tuexhibidor_Site_Manager_Data::save_site_data( $site );
		wp_send_json_success( array( 'message' => 'Texto alternativo guardado.' ) );
	}

	public static function ajax_save_featured(): void {
		self::verify_ajax();

		$raw = isset( $_POST['skus'] ) ? wp_unslash( $_POST['skus'] ) : '';
		$skus = json_decode( is_string( $raw ) ? $raw : '[]', true );
		if ( ! is_array( $skus ) ) {
			wp_send_json_error( array( 'message' => 'Lista de productos no válida.' ) );
		}

		$catalog     = Tuexhibidor_Site_Manager_Data::load_catalog();
		$valid_codes = array();
		foreach ( $catalog['products'] as $product ) {
			if ( ! empty( $product['code'] ) ) {
				$valid_codes[ $product['code'] ] = true;
			}
		}

		$clean = array();
		$seen  = array();
		foreach ( $skus as $code ) {
			$code = sanitize_text_field( (string) $code );
			if ( ! $code || empty( $valid_codes[ $code ] ) || isset( $seen[ $code ] ) ) {
				continue;
			}
			$seen[ $code ] = true;
			$clean[]       = $code;
		}

		if ( empty( $clean ) ) {
			wp_send_json_error( array( 'message' => 'Selecciona al menos un producto válido del catálogo.' ) );
		}

		$site                   = Tuexhibidor_Site_Manager_Data::load_site_data();
		$site['featuredSkus']   = $clean;
		Tuexhibidor_Site_Manager_Data::save_site_data( $site );
		Tuexhibidor_Site_Manager_Data::bump_cache_version();

		wp_send_json_success(
			array(
				'message'  => 'Carrusel «Los más pedidos» actualizado en el sitio en vivo.',
				'count'    => count( $clean ),
				'cacheVer' => get_option( 'tuexhibidor_asset_version' ),
			)
		);
	}

	private static function verify_ajax(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Sin permisos.' ), 403 );
		}
		check_ajax_referer( 'tuex_sm', 'nonce' );
	}

	/** @return array|WP_Error */
	private static function replace_catalog( string $slug, int $attachment_id, bool $sync_wc ) {
		if ( ! $slug ) {
			return new WP_Error( 'invalid', 'Slug de producto no válido.' );
		}
		if ( ! Tuexhibidor_Site_Manager_Images::save_catalog_jpg( $attachment_id, $slug ) ) {
			return new WP_Error( 'save', 'No se pudo guardar la imagen en el catálogo.' );
		}

		$catalog  = Tuexhibidor_Site_Manager_Data::load_catalog();
		$products = $catalog['products'];
		$code     = '';
		$rel      = 'public/images/catalog/' . $slug . '.jpg';

		foreach ( $products as &$product ) {
			if ( ( $product['slug'] ?? '' ) === $slug ) {
				$product['image']   = $rel;
				$product['imageOk'] = true;
				$code               = $product['code'] ?? '';
				break;
			}
		}
		unset( $product );

		Tuexhibidor_Site_Manager_Data::save_catalog( $products, $catalog['scores'] );
		if ( $sync_wc && $code ) {
			Tuexhibidor_Site_Manager_Images::sync_woocommerce_thumbnail( $code, $attachment_id );
		}

		return array( 'preview' => Tuexhibidor_Site_Manager_Paths::public_url( $rel ) );
	}

	/** @return array|WP_Error */
	private static function replace_hero( int $index, int $attachment_id ) {
		$site = Tuexhibidor_Site_Manager_Data::load_site_data();
		if ( empty( $site['hero'][ $index ] ) ) {
			return new WP_Error( 'notfound', 'Slide no encontrado.' );
		}
		$current = $site['hero'][ $index ];
		$base    = Tuexhibidor_Site_Manager_Data::asset_base( $current );
		if ( ! $base ) {
			return new WP_Error( 'base', 'No se pudo determinar la ruta base del slide.' );
		}
		if ( ! Tuexhibidor_Site_Manager_Images::save_responsive_set( $attachment_id, $base ) ) {
			return new WP_Error( 'save', 'Error al generar tamaños del hero.' );
		}
		$alt = is_array( $current ) ? ( $current['alt'] ?? '' ) : '';
		$site['hero'][ $index ] = array(
			'base'    => $base,
			'alt'     => $alt,
			'sources' => Tuexhibidor_Site_Manager_Images::build_sources_map( $base ),
		);
		Tuexhibidor_Site_Manager_Data::save_site_data( $site );
		return array( 'preview' => Tuexhibidor_Site_Manager_Data::asset_preview_url( $site['hero'][ $index ] ) );
	}

	/** @return array|WP_Error */
	private static function replace_gallery( int $index, int $attachment_id ) {
		$site = Tuexhibidor_Site_Manager_Data::load_site_data();
		if ( empty( $site['gallery'][ $index ] ) ) {
			return new WP_Error( 'notfound', 'Imagen de galería no encontrada.' );
		}
		$current = $site['gallery'][ $index ];
		$rel     = Tuexhibidor_Site_Manager_Data::gallery_relative_path( $current );
		if ( ! $rel ) {
			return new WP_Error( 'base', 'Ruta no válida.' );
		}
		if ( ! Tuexhibidor_Site_Manager_Images::save_gallery_jpg( $attachment_id, $rel ) ) {
			return new WP_Error( 'save', 'Error al guardar la galería.' );
		}
		$alt = Tuexhibidor_Site_Manager_Data::gallery_alt( $current );
		if ( $alt ) {
			$site['gallery'][ $index ] = array(
				'base' => $rel,
				'alt'  => $alt,
			);
		} else {
			$site['gallery'][ $index ] = $rel;
		}
		Tuexhibidor_Site_Manager_Data::save_site_data( $site );
		return array( 'preview' => Tuexhibidor_Site_Manager_Data::asset_preview_url( $site['gallery'][ $index ] ) );
	}

	/** @return array|WP_Error */
	private static function replace_category( string $key, int $attachment_id ) {
		if ( ! $key ) {
			return new WP_Error( 'invalid', 'Categoría no válida.' );
		}
		$site = Tuexhibidor_Site_Manager_Data::load_site_data();
		if ( empty( $site['categoryImages'][ $key ] ) ) {
			return new WP_Error( 'notfound', 'Imagen de categoría no encontrada.' );
		}
		$current = $site['categoryImages'][ $key ];
		$base    = Tuexhibidor_Site_Manager_Data::asset_base( $current );
		if ( ! $base ) {
			return new WP_Error( 'base', 'No se pudo determinar la ruta base.' );
		}
		if ( ! Tuexhibidor_Site_Manager_Images::save_responsive_set( $attachment_id, $base ) ) {
			return new WP_Error( 'save', 'Error al generar tamaños de la categoría.' );
		}
		$alt = is_array( $current ) ? ( $current['alt'] ?? '' ) : '';
		$site['categoryImages'][ $key ] = array(
			'base'    => $base,
			'alt'     => $alt,
			'sources' => Tuexhibidor_Site_Manager_Images::build_sources_map( $base ),
		);
		Tuexhibidor_Site_Manager_Data::save_site_data( $site );
		return array( 'preview' => Tuexhibidor_Site_Manager_Data::asset_preview_url( $site['categoryImages'][ $key ] ) );
	}

	/** @return array|WP_Error */
	private static function replace_home_static( string $key, int $attachment_id ) {
		$slots = Tuexhibidor_Site_Manager_Paths::home_static_slots();
		if ( empty( $slots[ $key ] ) ) {
			return new WP_Error( 'invalid', 'Slot de home estático no válido.' );
		}
		$site = Tuexhibidor_Site_Manager_Data::ensure_home_static_defaults();
		$base = $slots[ $key ]['base'];
		if ( ! Tuexhibidor_Site_Manager_Images::save_responsive_set( $attachment_id, $base ) ) {
			return new WP_Error( 'save', 'Error al generar tamaños de la imagen estática.' );
		}
		$current = $site['homeStatic'][ $key ] ?? array();
		$alt     = is_array( $current ) ? ( $current['alt'] ?? $slots[ $key ]['default_alt'] ) : $slots[ $key ]['default_alt'];
		$site['homeStatic'][ $key ] = array(
			'base'    => $base,
			'alt'     => $alt,
			'sources' => Tuexhibidor_Site_Manager_Images::build_sources_map( $base ),
		);
		Tuexhibidor_Site_Manager_Data::save_site_data( $site );
		return array( 'preview' => Tuexhibidor_Site_Manager_Data::asset_preview_url( $site['homeStatic'][ $key ] ) );
	}

	/** @return array|WP_Error */
	private static function replace_brand( string $key, int $attachment_id ) {
		$slots = Tuexhibidor_Site_Manager_Paths::brand_slots();
		if ( empty( $slots[ $key ] ) ) {
			return new WP_Error( 'invalid', 'Slot de marca no válido.' );
		}
		$path = $slots[ $key ];
		if ( ! Tuexhibidor_Site_Manager_Images::save_brand_file( $attachment_id, $path ) ) {
			return new WP_Error( 'save', 'No se pudo guardar el archivo de marca.' );
		}
		return array( 'preview' => Tuexhibidor_Site_Manager_Paths::public_url( $path ) );
	}
}
