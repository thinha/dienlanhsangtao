<?php
/**
 * Homepage — mobile bottom navigation.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_url    = home_url( '/' );
$account_url = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$shop_url    = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : '#';
?>
<nav class="mobile-bar">
	<a class="active" href="<?php echo esc_url( $home_url ); ?>">
		<?php echo dmc_icon( 'home', [ 'size' => 20, 'variant' => 'bar' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="mobile-bar__label"><?php esc_html_e( 'Trang chủ', 'flatsome-child' ); ?></span>
	</a>
	<button type="button" class="mobile-bar__item" id="dmcMobileCategory">
		<?php echo dmc_icon( 'grid', [ 'size' => 20, 'variant' => 'bar' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="mobile-bar__label"><?php esc_html_e( 'Danh mục', 'flatsome-child' ); ?></span>
	</button>
	<a href="#dmcSale">
		<?php echo dmc_icon( 'tag', [ 'size' => 20, 'variant' => 'bar' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="mobile-bar__label"><?php esc_html_e( 'Ưu đãi', 'flatsome-child' ); ?></span>
	</a>
	<a href="<?php echo esc_url( $account_url ); ?>">
		<?php echo dmc_icon( 'orders', [ 'size' => 20, 'variant' => 'bar' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="mobile-bar__label"><?php esc_html_e( 'Đơn hàng', 'flatsome-child' ); ?></span>
	</a>
	<a href="<?php echo esc_url( $account_url ); ?>">
		<?php echo dmc_icon( 'user', [ 'size' => 20, 'variant' => 'bar' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="mobile-bar__label"><?php esc_html_e( 'Tài khoản', 'flatsome-child' ); ?></span>
	</a>
</nav>
