<?php
/**
 * Homepage — vouchers section.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'dmc_voucher_render_homepage_section' ) ) {
	dmc_voucher_render_homepage_section(
		[
			'more_url' => function_exists( 'dmc_account_vouchers_url' )
				? dmc_account_vouchers_url()
				: ( class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : '#' ),
		]
	);
	return;
}
?>
<section class="card dmc-voucher-section">
	<div class="section-head">
		<h2><?php esc_html_e( 'VOUCHER DÀNH CHO BẠN', 'flatsome-child' ); ?></h2>
		<a href="#" class="more"><?php esc_html_e( 'Xem tất cả', 'flatsome-child' ); ?> &rsaquo;</a>
	</div>
	<div class="vouchers">
		<p><?php esc_html_e( 'Kích hoạt plugin DMC Voucher để hiển thị voucher.', 'flatsome-child' ); ?></p>
	</div>
</section>
