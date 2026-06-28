<?php
/**
 * Order received message.
 *
 * @package Flatsome_Child
 * @version 8.8.0
 *
 * @var WC_Order|false $order
 */

defined( 'ABSPATH' ) || exit;

$message = apply_filters(
	'woocommerce_thankyou_order_received_text',
	esc_html( __( 'Cảm ơn bạn. Đơn hàng đã được tiếp nhận.', 'flatsome-child' ) ),
	$order
);
?>
<div class="dmc-checkout-success">
	<div class="dmc-checkout-success__icon" aria-hidden="true">
		<?php echo dmc_icon( 'badge-check', [ 'size' => 40 ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
		<?php echo $message; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</p>
</div>
