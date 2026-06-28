<?php
/**
 * Thankyou page
 *
 * @package Flatsome_Child
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order dmc-checkout-thankyou">

	<?php
	if ( $order ) :

		do_action( 'woocommerce_before_thankyou', $order->get_id() );
		?>

		<?php if ( $order->has_status( 'failed' ) ) : ?>

			<div class="dmc-checkout-success dmc-checkout-success--failed">
				<div class="dmc-checkout-success__icon" aria-hidden="true">
					<?php echo dmc_icon( 'cart', [ 'size' => 40 ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed">
					<?php esc_html_e( 'Rất tiếc, đơn hàng không thể xử lý vì giao dịch bị từ chối. Vui lòng thử lại.', 'flatsome-child' ); ?>
				</p>
			</div>

			<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions dmc-checkout-received-actions">
				<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="dmc-btn dmc-btn--primary pay">
					<?php esc_html_e( 'Thanh toán lại', 'flatsome-child' ); ?>
				</a>
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="dmc-btn dmc-btn--outline pay">
						<?php esc_html_e( 'Tài khoản của tôi', 'flatsome-child' ); ?>
					</a>
				<?php endif; ?>
			</p>

		<?php else : ?>

			<?php wc_get_template( 'checkout/order-received.php', [ 'order' => $order ] ); ?>

			<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details dmc-checkout-order-details">
				<li class="woocommerce-order-overview__order order">
					<?php esc_html_e( 'Mã đơn hàng', 'flatsome-child' ); ?>
					<strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<li class="woocommerce-order-overview__date date">
					<?php esc_html_e( 'Ngày đặt', 'flatsome-child' ); ?>
					<strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
					<li class="woocommerce-order-overview__email email">
						<?php esc_html_e( 'Email', 'flatsome-child' ); ?>
						<strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
					</li>
				<?php endif; ?>

				<?php if ( $order->get_billing_phone() ) : ?>
					<li class="woocommerce-order-overview__phone phone">
						<?php esc_html_e( 'Điện thoại', 'flatsome-child' ); ?>
						<strong><?php echo esc_html( $order->get_billing_phone() ); ?></strong>
					</li>
				<?php endif; ?>

				<li class="woocommerce-order-overview__total total">
					<?php esc_html_e( 'Tổng cộng', 'flatsome-child' ); ?>
					<strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>

				<?php if ( $order->get_payment_method_title() ) : ?>
					<li class="woocommerce-order-overview__payment-method method">
						<?php esc_html_e( 'Phương thức', 'flatsome-child' ); ?>
						<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
					</li>
				<?php endif; ?>
			</ul>

		<?php endif; ?>

		<?php do_action( 'woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id() ); ?>
		<?php do_action( 'woocommerce_thankyou', $order->get_id() ); ?>

		<?php if ( ! $order->has_status( 'failed' ) ) : ?>
			<div class="dmc-checkout-received-actions">
				<?php foreach ( dmc_checkout_received_actions( $order ) as $action ) : ?>
					<a href="<?php echo esc_url( $action['url'] ); ?>" class="<?php echo esc_attr( $action['class'] ); ?>">
						<?php echo esc_html( $action['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	<?php else : ?>

		<?php wc_get_template( 'checkout/order-received.php', [ 'order' => false ] ); ?>

	<?php endif; ?>

</div>
