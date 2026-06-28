<?php
/**
 * Bulk select / delete for shipping locations repeater on Product settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue bulk-action assets on Product options page.
 */
function dmc_tmp_admin_shipping_bulk_assets() {
	if ( ! function_exists( 'acf' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || false === strpos( (string) $screen->id, 'dmc-tab-menu-product-settings' ) ) {
		return;
	}

	wp_enqueue_style(
		'dmc-tmp-admin-shipping-bulk',
		DMC_TMP_URL . 'assets/css/admin-shipping-bulk.css',
		[],
		DMC_TMP_VERSION
	);

	wp_enqueue_script(
		'dmc-tmp-admin-shipping-bulk',
		DMC_TMP_URL . 'assets/js/admin-shipping-bulk.js',
		[ 'acf-input' ],
		DMC_TMP_VERSION,
		true
	);
}
add_action( 'acf/input/admin_enqueue_scripts', 'dmc_tmp_admin_shipping_bulk_assets' );
