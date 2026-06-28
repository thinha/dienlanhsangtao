<?php
/**
 * Checkout — setup, assets, template override.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/checkout/helpers.php';

/**
 * Enqueue checkout page assets.
 */
function dmc_checkout_enqueue_assets() {
	if ( ! dmc_is_checkout_layout() ) {
		return;
	}

	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$js_file   = $theme_dir . '/assets/js/homepage.js';

	wp_enqueue_script(
		'dmc-homepage',
		$theme_uri . '/assets/js/homepage.js',
		[],
		file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0',
		true
	);

	$cart_count = function_exists( 'dmc_cart_item_count' ) ? dmc_cart_item_count() : 0;

	wp_localize_script(
		'dmc-homepage',
		'dmcHomepage',
		[
			'cartCount'  => $cart_count,
			'flashEnd'   => 0,
			'searchUrl'  => home_url( '/' ),
			'shopUrl'    => wc_get_page_permalink( 'shop' ),
			'slideDelay' => 4000,
			'slideSpeed' => 600,
		]
	);
}
add_action( 'wp_enqueue_scripts', 'dmc_checkout_enqueue_assets', 99 );

/**
 * Body class for checkout layout.
 *
 * @param string[] $classes Body classes.
 */
function dmc_checkout_body_class( $classes ) {
	if ( dmc_is_checkout_layout() ) {
		$classes[] = 'dmc-checkout-page';

		if ( dmc_is_order_received_page() ) {
			$classes[] = 'dmc-checkout-received-page';
		}
	}

	return $classes;
}
add_filter( 'body_class', 'dmc_checkout_body_class' );

/**
 * Force custom checkout page template.
 *
 * @param string $template Template path.
 */
function dmc_checkout_template_include( $template ) {
	if ( ! dmc_is_checkout_layout() ) {
		return $template;
	}

	$custom = get_stylesheet_directory() . '/page-checkout.php';

	return file_exists( $custom ) ? $custom : $template;
}
add_filter( 'template_include', 'dmc_checkout_template_include', 99 );

/**
 * Vietnamese place order button text.
 *
 * @param string $text Button text.
 */
function dmc_checkout_order_button_text( $text ) {
	if ( dmc_is_checkout_layout() && ! dmc_is_order_received_page() ) {
		return __( 'Đặt hàng', 'flatsome-child' );
	}

	return $text;
}
add_filter( 'woocommerce_order_button_text', 'dmc_checkout_order_button_text' );

/**
 * Disable coupon/voucher input on checkout.
 */
function dmc_checkout_disable_coupon_form() {
	if ( ! dmc_is_checkout_layout() || dmc_is_order_received_page() ) {
		return;
	}

	remove_action( 'woocommerce_before_checkout_form', 'woocommerce_checkout_coupon_form', 10 );
}
add_action( 'wp', 'dmc_checkout_disable_coupon_form' );

/**
 * Vietnamese thank you message.
 *
 * @param string        $text  Message.
 * @param WC_Order|bool $order Order.
 */
function dmc_checkout_thankyou_text( $text, $order ) {
	if ( dmc_is_checkout_layout() ) {
		return __( 'Cảm ơn bạn. Đơn hàng đã được tiếp nhận.', 'flatsome-child' );
	}

	return $text;
}
add_filter( 'woocommerce_thankyou_order_received_text', 'dmc_checkout_thankyou_text', 10, 2 );
