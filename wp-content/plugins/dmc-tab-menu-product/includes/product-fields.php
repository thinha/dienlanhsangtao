<?php
/**
 * ACF product fields — discount overlay & card info.
 *
 * Moved to flatsome-child → Add more detail product (tab Hiển thị Card).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Legacy field group — kept for reference; registration disabled.
 */
function dmc_tmp_register_product_fields() {
	// Fields now live in flatsome-child/includes/product-list/acf-fields.php
}
add_action( 'acf/init', 'dmc_tmp_register_product_fields' );

/**
 * @return array<int, array<string, mixed>>
 */
function dmc_tmp_product_field_definitions() {
	return function_exists( 'dmc_product_detail_card_field_definitions' )
		? dmc_product_detail_card_field_definitions()
		: [];
}
