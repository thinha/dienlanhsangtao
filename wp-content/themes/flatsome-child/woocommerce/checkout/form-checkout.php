<?php
/**
 * Checkout Form
 *
 * @package Flatsome_Child
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'Bạn cần đăng nhập để thanh toán.', 'flatsome-child' ) ) );
	return;
}
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout dmc-checkout-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Thanh toán', 'flatsome-child' ); ?>">

	<div class="dmc-checkout-layout">
		<div class="dmc-checkout-main">
			<?php if ( $checkout->get_checkout_fields() ) : ?>

				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

				<div id="customer_details" class="dmc-checkout-fields">
					<div class="dmc-checkout-card dmc-checkout-card--billing">
						<?php do_action( 'woocommerce_checkout_billing' ); ?>
					</div>

					<div class="dmc-checkout-card dmc-checkout-card--shipping">
						<?php do_action( 'woocommerce_checkout_shipping' ); ?>
					</div>
				</div>

				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

			<?php endif; ?>
		</div>

		<aside class="dmc-checkout-sidebar">
			<div class="dmc-checkout-card dmc-checkout-card--review">
				<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

				<h3 id="order_review_heading" class="dmc-checkout-card__title">
					<?php esc_html_e( 'Đơn hàng của bạn', 'flatsome-child' ); ?>
				</h3>

				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>

				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
			</div>
		</aside>
	</div>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
