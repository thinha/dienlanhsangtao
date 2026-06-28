<?php
/**
 * Empty cart page
 *
 * @package Flatsome_Child
 * @version 8.0.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="dmc-cart-empty">
	<div class="dmc-cart-empty__icon" aria-hidden="true">
		<?php echo dmc_icon( 'cart', [ 'size' => 48 ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>

	<?php do_action( 'woocommerce_cart_is_empty' ); ?>

	<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
		<p class="dmc-cart-empty__actions return-to-shop">
			<a class="dmc-btn dmc-btn--primary wc-backward" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
				<?php echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Tiếp tục mua sắm', 'flatsome-child' ) ) ); ?>
			</a>
		</p>
	<?php endif; ?>
</div>
