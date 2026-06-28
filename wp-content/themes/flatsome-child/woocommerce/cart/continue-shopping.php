<?php
/**
 * Continue Shopping Button
 *
 * @package Flatsome_Child
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="continue-shopping pull-left text-left">
	<a class="button-continue-shopping" href="<?php echo esc_url( apply_filters( 'woocommerce_continue_shopping_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
		<?php echo ( is_rtl() ? '&#8594;' : '&#8592;' ) . '&nbsp;' . esc_html__( 'Tiếp tục xem sản phẩm', 'flatsome-child' ); ?>
	</a>
</div>
