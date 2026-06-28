<?php
/**
 * My Account — dashboard (Vietnamese).
 *
 * @package Flatsome_Child
 * @version 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$allowed_html = [
	'a' => [
		'href' => [],
	],
	'strong' => [],
];
?>
<p>
	<?php
	printf(
		wp_kses(
			__( 'Xin chào %1$s (<a href="%2$s">Đăng xuất</a>)', 'flatsome-child' ),
			$allowed_html
		),
		'<strong>' . esc_html( $current_user->display_name ) . '</strong>',
		esc_url( wc_logout_url() )
	);
	?>
</p>

<p>
	<?php
	printf(
		wp_kses(
			__( 'Từ trang tổng quan, bạn có thể xem <a href="%1$s">đơn hàng gần đây</a>, quản lý <a href="%2$s">voucher</a>, <a href="%3$s">địa chỉ giao hàng</a> và <a href="%4$s">cập nhật thông tin tài khoản</a>.', 'flatsome-child' ),
			$allowed_html
		),
		esc_url( wc_get_endpoint_url( 'orders' ) ),
		esc_url( function_exists( 'dmc_account_vouchers_url' ) ? dmc_account_vouchers_url() : wc_get_endpoint_url( 'orders' ) ),
		esc_url( wc_get_endpoint_url( 'edit-address' ) ),
		esc_url( wc_get_endpoint_url( 'edit-account' ) )
	);
	?>
</p>

<?php do_action( 'woocommerce_account_dashboard' ); ?>
