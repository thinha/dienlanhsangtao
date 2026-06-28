<?php
/**
 * Mobile / tablet service highlights below header.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="header-benefits">
	<div class="container header-benefits__inner">
		<div class="header-benefits__item">
			<span class="header-benefits__icon"><?php echo dmc_icon( 'shield-check', [ 'size' => 20, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<div>
				<b><?php esc_html_e( '100%', 'flatsome-child' ); ?></b>
				<span><?php esc_html_e( 'Hàng chính hãng', 'flatsome-child' ); ?></span>
			</div>
		</div>
		<div class="header-benefits__item">
			<span class="header-benefits__icon"><?php echo dmc_icon( 'delivery-truck', [ 'size' => 20, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<div>
				<b><?php esc_html_e( 'Giao nhanh', 'flatsome-child' ); ?></b>
				<span><?php esc_html_e( '2h - 24h', 'flatsome-child' ); ?></span>
			</div>
		</div>
		<div class="header-benefits__item">
			<span class="header-benefits__icon"><?php echo dmc_icon( 'return', [ 'size' => 20, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<div>
				<b><?php esc_html_e( 'Đổi trả dễ dàng', 'flatsome-child' ); ?></b>
				<span><?php esc_html_e( 'Trong 7 ngày', 'flatsome-child' ); ?></span>
			</div>
		</div>
		<div class="header-benefits__item">
			<span class="header-benefits__icon"><?php echo dmc_icon( 'warranty', [ 'size' => 20, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<div>
				<b><?php esc_html_e( 'Bảo hành', 'flatsome-child' ); ?></b>
				<span><?php esc_html_e( 'chính hãng', 'flatsome-child' ); ?></span>
			</div>
		</div>
	</div>
</div>
