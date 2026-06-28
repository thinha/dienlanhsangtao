<?php
/**
 * Cart — setup, assets, template override.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/cart/helpers.php';
require_once get_stylesheet_directory() . '/includes/cart/shipping-voucher.php';

/**
 * Enqueue cart page assets.
 */
function dmc_cart_enqueue_assets() {
	if ( ! dmc_is_cart_layout() ) {
		return;
	}

	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$js_file   = $theme_dir . '/assets/js/homepage.js';
	$cart_js   = $theme_dir . '/assets/js/cart.js';

	wp_enqueue_script(
		'dmc-homepage',
		$theme_uri . '/assets/js/homepage.js',
		[],
		file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0',
		true
	);

	wp_enqueue_script(
		'dmc-cart',
		$theme_uri . '/assets/js/cart.js',
		[ 'jquery' ],
		file_exists( $cart_js ) ? filemtime( $cart_js ) : '1.0.0',
		true
	);

	wp_localize_script(
		'dmc-cart',
		'dmcCart',
		[
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'dmc_cart_shipping' ),
			'i18n'    => [
				'activePrefix'  => __( 'Đang giao đến: ', 'flatsome-child' ),
				'itemCountOne'  => __( '1 sản phẩm', 'flatsome-child' ),
				'itemCountMany' => __( '%d sản phẩm', 'flatsome-child' ),
			],
		]
	);

	wp_localize_script(
		'dmc-homepage',
		'dmcHomepage',
		[
			'cartCount'  => dmc_cart_item_count(),
			'flashEnd'   => 0,
			'searchUrl'  => home_url( '/' ),
			'shopUrl'    => wc_get_page_permalink( 'shop' ),
			'slideDelay' => 4000,
			'slideSpeed' => 600,
		]
	);
}
add_action( 'wp_enqueue_scripts', 'dmc_cart_enqueue_assets', 99 );

/**
 * Body class for cart layout.
 *
 * @param string[] $classes Body classes.
 */
function dmc_cart_body_class( $classes ) {
	if ( dmc_is_cart_layout() ) {
		$classes[] = 'dmc-cart-page';
	}

	return $classes;
}
add_filter( 'body_class', 'dmc_cart_body_class' );

/**
 * Force custom cart page template.
 *
 * @param string $template Template path.
 */
function dmc_cart_template_include( $template ) {
	if ( ! dmc_is_cart_layout() ) {
		return $template;
	}

	$custom = get_stylesheet_directory() . '/page-cart.php';

	return file_exists( $custom ) ? $custom : $template;
}
add_filter( 'template_include', 'dmc_cart_template_include', 99 );

/**
 * Vietnamese empty cart message.
 */
function dmc_cart_empty_message( $message ) {
	if ( dmc_is_cart_layout() ) {
		return __( 'Giỏ hàng của bạn đang trống.', 'flatsome-child' );
	}

	return $message;
}
add_filter( 'wc_empty_cart_message', 'dmc_cart_empty_message' );

/**
 * Remove Flatsome default "Cart totals" table header (Cộng giỏ hàng).
 */
function dmc_cart_remove_flatsome_cart_totals_header() {
	remove_action( 'woocommerce_before_cart_totals', 'flatsome_woocommerce_before_cart_totals' );
}
add_action( 'after_setup_theme', 'dmc_cart_remove_flatsome_cart_totals_header', 20 );
