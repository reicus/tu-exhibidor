<?php
/**
 * Cabecera de tienda — categorías en lugar de ordenar por precio/popularidad.
 */

$shop_title_show   = get_data( 'shop_title_show' );
$shop_sorting_show = get_data( 'shop_sorting_show' );

if ( $shop_title_show || $shop_sorting_show ) :
	?>
	<div class="woocommerce-shop-header woocommerce-shop-header--columned te-shop-header">
		<div class="woocommerce-shop-header--title">
			<h1 class="page-title">
				<?php
				if ( $shop_title_show ) {
					woocommerce_page_title();
				}
				?>
				<?php if ( $shop_sorting_show ) : ?>
				<small><?php woocommerce_result_count(); ?></small>
				<?php endif; ?>
			</h1>
		</div>
		<?php if ( $shop_sorting_show && function_exists( 'te_shop_category_filter' ) ) : ?>
		<div class="woocommerce-shop-header--sorting te-shop-header__categories">
			<?php te_shop_category_filter(); ?>
		</div>
		<?php endif; ?>
	</div>
	<?php
else :
	?>
	<div class="shop-spacer"></div>
	<?php
endif;
