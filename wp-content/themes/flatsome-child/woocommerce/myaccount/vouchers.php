<?php
/**
 * My Account — vouchers endpoint.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'dmc_voucher_render_account_page' ) ) {
	dmc_voucher_render_account_page();
	return;
}
?>
<div class="dmc-account-empty">
	<p><?php esc_html_e( 'Kích hoạt plugin DMC Voucher để xem voucher.', 'flatsome-child' ); ?></p>
</div>
