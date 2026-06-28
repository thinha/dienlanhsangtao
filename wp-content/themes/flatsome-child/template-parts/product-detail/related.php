<?php
/**
 * Product detail — full-width related products.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$related = dmc_pl_get_related_products( $product );
if ( empty( $related ) ) {
	return;
}
?>
<section class="pl-related-full">
	<div class="pl-related-full__inner">
		<div class="pl-related-full__head">
			<h2><?php esc_html_e( 'CÓ THỂ BẠN CŨNG THÍCH', 'flatsome-child' ); ?></h2>
		</div>
		<div class="pl-related-full__grid pl-grid">
			<?php foreach ( $related as $related_product ) : ?>
				<?php dmc_pl_render_product_card( $related_product ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
