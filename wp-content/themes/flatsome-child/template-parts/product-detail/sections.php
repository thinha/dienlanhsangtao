<?php
/**
 * Product detail — stacked Shopee-style sections (frontend display).
 *
 * Data nhập tại admin:
 * - Chi tiết / quà tặng / thông số kỹ thuật: ACF "Add more detail product"
 * - Thuộc tính WooCommerce (visible) hiển thị dạng hàng label/value phía trên bảng specs
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

$product_id      = $product->get_id();
$gifts           = dmc_pl_get_gift_products( $product_id );
$detail_rows     = dmc_pl_product_detail_rows( $product );
$technical_rows  = dmc_pl_product_technical_rows( $product );
$technical_specs = dmc_pl_get_technical_specs_content( $product_id );

if ( ! $technical_specs ) {
	$short_desc = $product->get_short_description();
	if ( $short_desc ) {
		$technical_specs = dmc_pl_normalize_technical_specs_html(
			apply_filters( 'woocommerce_short_description', $short_desc )
		);
	}
}

$has_details   = ! empty( $detail_rows ) || ! empty( $gifts );
$has_technical = ! empty( $technical_rows ) || ! empty( $technical_specs );
$has_desc       = (bool) $product->get_description();

if ( ! $has_details && ! $has_technical && ! $has_desc ) {
	return;
}
?>
<section class="pl-detail-sections card" id="pl-detail-more">
	<?php if ( $has_details ) : ?>
		<div class="pl-shopee-block">
			<div class="pl-shopee-block__head"><?php esc_html_e( 'CHI TIẾT SẢN PHẨM', 'flatsome-child' ); ?></div>
			<div class="pl-shopee-block__body">
				<?php foreach ( $detail_rows as $row ) : ?>
					<div class="pl-shopee-row">
						<div class="pl-shopee-row__label"><?php echo esc_html( $row['label'] ); ?></div>
						<div class="pl-shopee-row__value">
							<?php
							if ( ! empty( $row['html'] ) ) {
								echo wp_kses_post( $row['value'] );
							} else {
								echo esc_html( $row['value'] );
							}
							?>
						</div>
					</div>
				<?php endforeach; ?>

				<?php if ( ! empty( $gifts ) ) : ?>
					<?php foreach ( $gifts as $gift ) : ?>
						<div class="pl-shopee-row pl-shopee-row--gift">
							<div class="pl-shopee-row__label"><?php esc_html_e( 'Quà tặng kèm', 'flatsome-child' ); ?></div>
							<div class="pl-shopee-row__value pl-shopee-gift-inline">
								<img
									src="<?php echo esc_url( $gift['image']['url'] ); ?>"
									alt="<?php echo esc_attr( $gift['title'] ?: __( 'Quà tặng kèm', 'flatsome-child' ) ); ?>"
									width="56"
									height="56"
									loading="lazy"
								>
								<div>
									<?php if ( $gift['title'] ) : ?>
										<strong><?php echo esc_html( $gift['title'] ); ?></strong>
									<?php endif; ?>
									<?php if ( $gift['description'] ) : ?>
										<div><?php echo wp_kses_post( $gift['description'] ); ?></div>
									<?php endif; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $has_technical ) : ?>
		<div class="pl-shopee-block" id="pl-detail-specs">
			<div class="pl-shopee-block__head"><?php esc_html_e( 'THÔNG SỐ KỸ THUẬT', 'flatsome-child' ); ?></div>
			<div class="pl-shopee-block__body">
				<?php if ( ! empty( $technical_rows ) ) : ?>
					<?php foreach ( $technical_rows as $row ) : ?>
						<div class="pl-shopee-row">
							<div class="pl-shopee-row__label"><?php echo esc_html( $row['label'] ); ?></div>
							<div class="pl-shopee-row__value">
								<?php echo wp_kses_post( (string) $row['value'] ); ?>
							</div>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php if ( $technical_specs ) : ?>
					<div class="pl-shopee-block__body--desc pl-shopee-specs-text">
						<?php echo wp_kses_post( $technical_specs ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( $has_desc ) : ?>
		<div class="pl-shopee-block">
			<div class="pl-shopee-block__head"><?php esc_html_e( 'MÔ TẢ SẢN PHẨM', 'flatsome-child' ); ?></div>
			<div class="pl-shopee-block__body pl-shopee-block__body--desc">
				<?php the_content(); ?>
			</div>
		</div>
	<?php endif; ?>
</section>
