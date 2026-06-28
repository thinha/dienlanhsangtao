<?php
/**
 * Product detail — Shopee-style reviews section.
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

$rating       = (float) $product->get_average_rating();
$review_count = (int) $product->get_review_count();
$rating_counts = $product->get_rating_counts();
?>
<section class="pl-reviews card" id="pl-detail-reviews">
	<div class="pl-shopee-block pl-shopee-block--reviews">
		<div class="pl-shopee-block__head"><?php esc_html_e( 'ĐÁNH GIÁ SẢN PHẨM', 'flatsome-child' ); ?></div>
		<div class="pl-shopee-block__body pl-shopee-block__body--reviews">
			<div class="pl-reviews__summary">
				<div class="pl-reviews__score">
					<span class="pl-reviews__score-value"><?php echo esc_html( number_format( $rating, 1 ) ); ?></span>
					<span class="pl-reviews__score-text"><?php esc_html_e( 'trên 5', 'flatsome-child' ); ?></span>
					<div class="pl-reviews__stars">
						<?php echo wc_get_rating_html( $rating, $review_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>

				<div class="pl-reviews__filters" data-pl-review-filters>
					<button type="button" class="pl-reviews__filter is-active" data-filter="all">
						<?php esc_html_e( 'Tất Cả', 'flatsome-child' ); ?>
					</button>
					<?php for ( $star = 5; $star >= 1; $star-- ) : ?>
						<?php
						$count = isset( $rating_counts[ $star ] ) ? (int) $rating_counts[ $star ] : 0;
						if ( $count > 0 ) :
							?>
							<button type="button" class="pl-reviews__filter" data-filter="<?php echo esc_attr( (string) $star ); ?>">
								<?php echo esc_html( $star ); ?> <?php esc_html_e( 'Sao', 'flatsome-child' ); ?>
								(<?php echo esc_html( (string) $count ); ?>)
							</button>
						<?php endif; ?>
					<?php endfor; ?>
				</div>
			</div>

			<div class="pl-reviews__list">
				<?php comments_template(); ?>
			</div>
		</div>
	</div>
</section>
