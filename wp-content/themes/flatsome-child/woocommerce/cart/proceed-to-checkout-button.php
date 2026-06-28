<?php
/**
 * Proceed to checkout button
 *
 * @package Flatsome_Child
 * @version 8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>">
	<?php esc_html_e( 'Tiến hành thanh toán', 'flatsome-child' ); ?>
</a>
