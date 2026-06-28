<?php
/**
 * My Account — endpoint content router.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$endpoint = dmc_account_current_endpoint();
$titles   = [
	'dashboard'    => __( 'Tổng quan', 'flatsome-child' ),
	'orders'       => __( 'Đơn hàng', 'flatsome-child' ),
	'wishlist'     => __( 'Danh sách yêu thích', 'flatsome-child' ),
	'vouchers'     => __( 'Voucher của tôi', 'flatsome-child' ),
	'edit-address' => __( 'Địa chỉ giao hàng', 'flatsome-child' ),
	'edit-account' => __( 'Thông tin tài khoản', 'flatsome-child' ),
];

$title = $titles[ $endpoint ] ?? __( 'Tài khoản', 'flatsome-child' );
?>
<header class="dmc-account-content__head">
	<h1><?php echo esc_html( $title ); ?></h1>
</header>

<div class="dmc-account-content__body woocommerce-MyAccount-content">
	<?php
	if ( function_exists( 'woocommerce_account_content' ) ) {
		woocommerce_account_content();
	}
	?>
</div>
