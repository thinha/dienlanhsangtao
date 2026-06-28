<?php
/**
 * Product detail — main content.
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

$discount   = dmc_pl_discount_label( $product );
$gifts      = dmc_pl_get_gift_products( $product->get_id() );
$categories = get_the_terms( $product->get_id(), 'product_cat' );
$cat_name   = ( ! empty( $categories ) && ! is_wp_error( $categories ) ) ? $categories[0]->name : '';
?>
<main class="pl-main pl-main--detail">
	<?php get_template_part( 'template-parts/commons/product-breadcrumb' ); ?>

	<div class="container">
		<article class="pl-detail card">
			<div class="pl-detail__gallery">
				<?php get_template_part( 'template-parts/product-detail/gallery-swiper' ); ?>
			</div>

			<div class="pl-detail__info">
				<div class="pl-detail__head">
					<?php if ( $cat_name ) : ?>
						<span class="pl-detail__category"><?php echo esc_html( $cat_name ); ?></span>
					<?php endif; ?>

					<h1 class="pl-detail__title"><?php the_title(); ?></h1>

					<div class="pl-detail__badges">
						<?php if ( $discount ) : ?>
							<span class="pl-detail__badge pl-detail__badge--sale"><?php echo esc_html( sprintf( __( 'Giảm %s', 'flatsome-child' ), $discount ) ); ?></span>
						<?php endif; ?>
						<?php if ( ! empty( $gifts ) ) : ?>
							<span class="pl-detail__badge pl-detail__badge--gift"><?php esc_html_e( 'Quà tặng kèm', 'flatsome-child' ); ?></span>
						<?php endif; ?>
						<?php if ( $product->is_in_stock() ) : ?>
							<span class="pl-detail__badge pl-detail__badge--stock"><?php esc_html_e( 'Còn hàng', 'flatsome-child' ); ?></span>
						<?php endif; ?>
					</div>

					<?php if ( $product->get_average_rating() ) : ?>
						<div class="pl-detail__rating">
							<?php echo wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="pl-detail__review-count">
								<?php echo esc_html( $product->get_rating_count() ); ?> <?php esc_html_e( 'đánh giá', 'flatsome-child' ); ?>
							</span>
						</div>
					<?php endif; ?>
				</div>

				<div class="pl-detail__price-box">
					<?php woocommerce_template_single_price(); ?>
				</div>

				<?php do_action( 'dmc_product_detail_after_price', $product ); ?>

				<div class="pl-detail__buy-box" id="pl-detail-buy">
					<div class="pl-detail__actions">
						<?php woocommerce_template_single_add_to_cart(); ?>
					</div>
				</div>

				<div class="pl-detail__meta">
					<?php if ( wc_product_sku_enabled() && $product->get_sku() ) : ?>
						<div class="pl-detail__meta-row">
							<span class="pl-detail__meta-label"><?php esc_html_e( 'SKU', 'flatsome-child' ); ?></span>
							<span class="pl-detail__meta-value"><?php echo esc_html( $product->get_sku() ); ?></span>
						</div>
					<?php endif; ?>
					<div class="pl-detail__meta-row">
						<span class="pl-detail__meta-label"><?php esc_html_e( 'Danh mục', 'flatsome-child' ); ?></span>
						<span class="pl-detail__meta-value">
							<?php echo wc_get_product_category_list( $product->get_id(), ', ' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</span>
					</div>
				</div>

				<?php
				if ( function_exists( 'dmc_tmp_render_product_trust_badges' ) ) {
					dmc_tmp_render_product_trust_badges();
				}
				?>

				<a class="pl-detail__scroll-more" href="#pl-detail-more">
					<?php esc_html_e( 'Xem thông tin chi tiết', 'flatsome-child' ); ?>
					<span aria-hidden="true">↓</span>
				</a>
			</div>
		</article>

		<?php get_template_part( 'template-parts/product-detail/sections' ); ?>

		<?php get_template_part( 'template-parts/product-detail/reviews' ); ?>
	</div>

	<?php get_template_part( 'template-parts/product-detail/related' ); ?>

	<div class="pl-mobile-buy" id="pl-mobile-buy" hidden>
		<div class="pl-mobile-buy__price">
			<?php echo wp_kses_post( $product->get_price_html() ); ?>
		</div>
		<button type="button" class="pl-mobile-buy__btn" data-scroll-to="#pl-detail-buy">
			<?php esc_html_e( 'Mua ngay', 'flatsome-child' ); ?>
		</button>
	</div>
</main>
