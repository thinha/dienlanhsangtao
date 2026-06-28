<?php
/**
 * Cart — helpers.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether current request uses custom cart layout.
 */
function dmc_is_cart_layout() {
	return class_exists( 'WooCommerce' ) && is_cart();
}

/**
 * Breadcrumb items for cart page.
 *
 * @return array<int, array{label: string, url: string}>
 */
function dmc_cart_breadcrumb_items() {
	$items = [
		[
			'label' => __( 'Trang chủ', 'flatsome-child' ),
			'url'   => home_url( '/' ),
		],
	];

	if ( class_exists( 'WooCommerce' ) ) {
		$shop_url = wc_get_page_permalink( 'shop' );
		if ( $shop_url ) {
			$items[] = [
				'label' => __( 'Sản phẩm', 'flatsome-child' ),
				'url'   => $shop_url,
			];
		}
	}

	$items[] = [
		'label' => __( 'Giỏ hàng', 'flatsome-child' ),
		'url'   => '',
	];

	return $items;
}

/**
 * Cart item count for header badge.
 */
function dmc_cart_item_count() {
	if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) {
		return 0;
	}

	return WC()->cart->get_cart_contents_count();
}
