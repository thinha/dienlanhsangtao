<?php
/**
 * Homepage — service highlights (desktop) & customer support (mobile/tablet).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hotline_tel = function_exists( 'dmc_tmp_get_company_hotline' )
	? dmc_tmp_get_company_hotline()
	: preg_replace( '/\s+/', '', (string) dmc_web_option( 'web_hotline', '19002628' ) );
$orders_url  = class_exists( 'WooCommerce' )
	? wc_get_account_endpoint_url( 'orders' )
	: home_url( '/' );
$account_url = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
?>
<section class="card services-section">
	<div class="services-compact">
		<div class="section-head">
			<h2><?php esc_html_e( 'Hỗ trợ khách hàng', 'flatsome-child' ); ?></h2>
		</div>
		<div class="services services--support">
			<a class="service service--support" href="<?php echo esc_url( 'tel:' . $hotline_tel ); ?>">
				<span class="service__icon"><?php echo dmc_icon( 'phone', [ 'size' => 22, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="service__text">
					<b><?php esc_html_e( 'Gọi hotline', 'flatsome-child' ); ?></b>
					<span><?php esc_html_e( 'Tư vấn 24/7', 'flatsome-child' ); ?></span>
				</span>
			</a>
			<a class="service service--support" href="<?php echo esc_url( home_url( '/he-thong-cua-hang/' ) ); ?>">
				<span class="service__icon"><?php echo dmc_icon( 'store', [ 'size' => 22, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="service__text">
					<b><?php esc_html_e( 'Hệ thống cửa hàng', 'flatsome-child' ); ?></b>
					<span><?php esc_html_e( 'Xem địa chỉ', 'flatsome-child' ); ?></span>
				</span>
			</a>
			<a class="service service--support" href="<?php echo esc_url( home_url( '/tra-cuu-bao-hanh/' ) ); ?>">
				<span class="service__icon"><?php echo dmc_icon( 'shield-check', [ 'size' => 22, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="service__text">
					<b><?php esc_html_e( 'Tra cứu bảo hành', 'flatsome-child' ); ?></b>
					<span><?php esc_html_e( 'Nhập SĐT/IMEI', 'flatsome-child' ); ?></span>
				</span>
			</a>
			<a class="service service--support" href="<?php echo esc_url( is_user_logged_in() ? $orders_url : $account_url ); ?>">
				<span class="service__icon"><?php echo dmc_icon( 'orders', [ 'size' => 22, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<span class="service__text">
					<b><?php esc_html_e( 'Theo dõi đơn hàng', 'flatsome-child' ); ?></b>
					<span><?php esc_html_e( 'Kiểm tra đơn', 'flatsome-child' ); ?></span>
				</span>
			</a>
		</div>
	</div>

	<div class="services services--highlights">
		<div class="service"><span class="service__icon"><?php echo dmc_icon( 'chat', [ 'size' => 22, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><div><b><?php esc_html_e( 'Tư vấn miễn phí', 'flatsome-child' ); ?></b><span><?php esc_html_e( 'Phục vụ tận tâm', 'flatsome-child' ); ?></span></div></div>
		<div class="service"><span class="service__icon"><?php echo dmc_icon( 'return', [ 'size' => 22, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><div><b><?php esc_html_e( 'Đổi trả dễ dàng', 'flatsome-child' ); ?></b><span><?php esc_html_e( 'Trong 7 ngày', 'flatsome-child' ); ?></span></div></div>
		<div class="service"><span class="service__icon"><?php echo dmc_icon( 'credit-card', [ 'size' => 22, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><div><b><?php esc_html_e( 'Thanh toán linh hoạt', 'flatsome-child' ); ?></b><span><?php esc_html_e( 'Nhiều phương thức', 'flatsome-child' ); ?></span></div></div>
		<div class="service"><span class="service__icon"><?php echo dmc_icon( 'warranty', [ 'size' => 22, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><div><b><?php esc_html_e( 'Bảo hành chính hãng', 'flatsome-child' ); ?></b><span><?php esc_html_e( 'Tận nơi, nhanh chóng', 'flatsome-child' ); ?></span></div></div>
		<div class="service"><span class="service__icon"><?php echo dmc_icon( 'shield-check', [ 'size' => 22, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span><div><b><?php esc_html_e( 'Kiểm tra trước khi nhận', 'flatsome-child' ); ?></b><span><?php esc_html_e( 'An tâm mua sắm', 'flatsome-child' ); ?></span></div></div>
	</div>
</section>
