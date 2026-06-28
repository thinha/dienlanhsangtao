<?php
/**
 * Simple product add to cart
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $product ); // WPCS: XSS ok.

if ( $product->is_in_stock() ) : ?>

	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
		<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

		<div class="pl-cart-row">
			<div class="pl-qty-stepper">
				<button type="button" class="pl-qty-stepper__btn" data-qty="minus" aria-label="<?php esc_attr_e( 'Giảm số lượng', 'flatsome-child' ); ?>">−</button>
				<?php
				do_action( 'woocommerce_before_add_to_cart_quantity' );

				woocommerce_quantity_input(
					[
						'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
						'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
						'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security.NonceVerification.Missing
					]
				);

				do_action( 'woocommerce_after_add_to_cart_quantity' );
				?>
				<button type="button" class="pl-qty-stepper__btn" data-qty="plus" aria-label="<?php esc_attr_e( 'Tăng số lượng', 'flatsome-child' ); ?>">+</button>
			</div>

			<button id="add-to-cart" type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" class="single_add_to_cart_button button alt">
				<span class="pl-cart-btn__main">
					<?php echo dmc_icon( 'cart', [ 'size' => 18, 'variant' => 'white', 'class' => 'pl-cart-btn__icon' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php esc_html_e( 'ĐẶT HÀNG TRỰC TUYẾN', 'flatsome-child' ); ?>
				</span>
				<span class="pl-cart-btn__sub"><?php esc_html_e( 'Mua online rẻ hơn khi đến cửa hàng', 'flatsome-child' ); ?></span>
			</button>
		</div>

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<?php endif; ?>
