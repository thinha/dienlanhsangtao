<?php
/**
 * Product detail — Swiper gallery with gift overlay.
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

$slides    = dmc_pl_get_gallery_slides( $product );
$gifts     = dmc_pl_get_gift_products( $product->get_id() );
$discount  = dmc_pl_discount_label( $product );
$gift_jump = 0;

foreach ( $slides as $index => $slide ) {
	if ( 'gift' === $slide['type'] ) {
		$gift_jump = $index;
		break;
	}
}
?>
<div class="pl-gallery" data-gift-slide="<?php echo esc_attr( (string) $gift_jump ); ?>">
	<div class="pl-gallery__stage">
		<?php if ( $discount ) : ?>
			<span class="pl-gallery__discount"><?php echo esc_html( sprintf( __( 'Giảm %s', 'flatsome-child' ), $discount ) ); ?></span>
		<?php endif; ?>

		<?php if ( ! empty( $gifts ) ) : ?>
			<button type="button" class="pl-gallery__gift-badge" aria-label="<?php esc_attr_e( 'Xem quà tặng kèm', 'flatsome-child' ); ?>" data-slide="<?php echo esc_attr( (string) $gift_jump ); ?>">
				<img
					src="<?php echo esc_url( $gifts[0]['image']['url'] ); ?>"
					alt="<?php echo esc_attr( $gifts[0]['title'] ?: __( 'Quà tặng kèm', 'flatsome-child' ) ); ?>"
					width="80"
					height="80"
					loading="lazy"
				>
				<span class="pl-gallery__gift-badge-label"><?php esc_html_e( 'Tặng kèm', 'flatsome-child' ); ?></span>
			</button>
		<?php endif; ?>

		<div class="swiper pl-gallery-main">
			<div class="swiper-wrapper">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<div class="swiper-slide pl-gallery__slide pl-gallery__slide--<?php echo esc_attr( $slide['type'] ); ?>">
						<div class="pl-gallery__frame">
							<img
								src="<?php echo esc_url( $slide['url'] ); ?>"
								alt="<?php echo esc_attr( $slide['alt'] ); ?>"
								loading="<?php echo 0 === $index ? 'eager' : 'lazy'; ?>"
							>
							<?php if ( 'gift' === $slide['type'] && ! empty( $slide['gift']['title'] ) ) : ?>
								<div class="pl-gallery__gift-caption">
									<strong><?php echo esc_html( $slide['gift']['title'] ); ?></strong>
									<span><?php esc_html_e( 'Quà tặng kèm', 'flatsome-child' ); ?></span>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( count( $slides ) > 1 ) : ?>
				<button type="button" class="pl-gallery__nav pl-gallery__nav--prev" aria-label="<?php esc_attr_e( 'Ảnh trước', 'flatsome-child' ); ?>">
					<svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M10 3L5 8l5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
				<button type="button" class="pl-gallery__nav pl-gallery__nav--next" aria-label="<?php esc_attr_e( 'Ảnh sau', 'flatsome-child' ); ?>">
					<svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M6 3l5 5-5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
				<div class="pl-gallery__counter"><span class="pl-gallery__counter-current">1</span> / <?php echo esc_html( (string) count( $slides ) ); ?></div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( count( $slides ) > 1 ) : ?>
		<div class="swiper pl-gallery-thumbs">
			<div class="swiper-wrapper">
				<?php foreach ( $slides as $slide ) : ?>
					<div class="swiper-slide pl-gallery__thumb pl-gallery__thumb--<?php echo esc_attr( $slide['type'] ); ?>">
						<img
							src="<?php echo esc_url( $slide['thumb'] ); ?>"
							alt="<?php echo esc_attr( $slide['alt'] ); ?>"
							loading="lazy"
						>
						<?php if ( 'gift' === $slide['type'] ) : ?>
							<span class="pl-gallery__thumb-gift"><?php esc_html_e( 'Tặng', 'flatsome-child' ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</div>
