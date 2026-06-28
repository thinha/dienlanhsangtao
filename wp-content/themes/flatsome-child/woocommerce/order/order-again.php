<?php
/**
 * Order again button
 *
 * @package Flatsome_Child
 * @version 7.8.0
 */

defined( 'ABSPATH' ) || exit;
?>

<p class="order-again dmc-order-again">
	<a href="<?php echo esc_url( $order_again_url ); ?>" class="dmc-btn dmc-btn--outline button<?php echo esc_attr( $wp_button_class ); ?>">
		<?php esc_html_e( 'Đặt lại đơn hàng', 'flatsome-child' ); ?>
	</a>
</p>
