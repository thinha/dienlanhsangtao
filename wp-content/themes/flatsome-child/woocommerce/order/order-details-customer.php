<?php
/**
 * Order Customer Details
 *
 * @package Flatsome_Child
 * @version 8.7.0
 */

defined( 'ABSPATH' ) || exit;

$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();
?>
<section class="woocommerce-customer-details dmc-order-customer">

	<?php if ( $show_shipping ) : ?>
	<section class="woocommerce-columns woocommerce-columns--2 woocommerce-columns--addresses col2-set addresses dmc-order-customer__grid">
		<div class="woocommerce-column woocommerce-column--1 woocommerce-column--billing-address col-1 dmc-order-customer__card">
	<?php else : ?>
		<div class="dmc-order-customer__card dmc-order-customer__card--single">
	<?php endif; ?>

	<h2 class="woocommerce-column__title dmc-order-customer__title">
		<?php esc_html_e( 'Địa chỉ thanh toán', 'flatsome-child' ); ?>
	</h2>

	<address>
		<?php echo wp_kses_post( $order->get_formatted_billing_address( esc_html__( 'N/A', 'woocommerce' ) ) ); ?>

		<?php if ( $order->get_billing_phone() ) : ?>
			<p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_billing_phone() ); ?></p>
		<?php endif; ?>

		<?php if ( $order->get_billing_email() ) : ?>
			<p class="woocommerce-customer-details--email"><?php echo esc_html( $order->get_billing_email() ); ?></p>
		<?php endif; ?>

		<?php do_action( 'woocommerce_order_details_after_customer_address', 'billing', $order ); ?>
	</address>

	<?php if ( $show_shipping ) : ?>
		</div>

		<div class="woocommerce-column woocommerce-column--2 woocommerce-column--shipping-address col-2 dmc-order-customer__card">
			<h2 class="woocommerce-column__title dmc-order-customer__title">
				<?php esc_html_e( 'Địa chỉ giao hàng', 'flatsome-child' ); ?>
			</h2>
			<address>
				<?php echo wp_kses_post( $order->get_formatted_shipping_address( esc_html__( 'N/A', 'woocommerce' ) ) ); ?>

				<?php if ( $order->get_shipping_phone() ) : ?>
					<p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_shipping_phone() ); ?></p>
				<?php endif; ?>

				<?php do_action( 'woocommerce_order_details_after_customer_address', 'shipping', $order ); ?>
			</address>
		</div>
	</section>
	<?php else : ?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>

</section>
