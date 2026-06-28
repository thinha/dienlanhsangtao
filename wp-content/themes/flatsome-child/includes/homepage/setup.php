<?php
/**
 * Homepage assets & body class.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/homepage/helpers.php';
require_once get_stylesheet_directory() . '/includes/homepage/sections.php';
require_once get_stylesheet_directory() . '/includes/homepage/acf-fields.php';
require_once get_stylesheet_directory() . '/includes/homepage/acf-web-settings.php';

/**
 * Whether current request uses homepage layout.
 */
function dmc_is_homepage_layout() {
	return is_front_page() || is_page_template( 'page-templates/homepage.php' );
}

/**
 * Enqueue homepage assets.
 */
function dmc_homepage_enqueue_assets() {
	if ( ! dmc_is_homepage_layout() ) {
		return;
	}

	$theme_uri = get_stylesheet_directory_uri();

	wp_enqueue_style(
		'swiper',
		'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
		[],
		'11'
	);

	wp_enqueue_script(
		'swiper',
		'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
		[],
		'11',
		true
	);

	wp_enqueue_script(
		'dmc-homepage',
		$theme_uri . '/assets/js/homepage.js',
		[ 'swiper' ],
		filemtime( get_stylesheet_directory() . '/assets/js/homepage.js' ),
		true
	);

	$flash_end = dmc_homepage_flash_countdown_seconds();
	$slide_delay = function_exists( 'dmc_tmp_get_hp_slide_delay' )
		? dmc_tmp_get_hp_slide_delay()
		: (int) dmc_homepage_option( 'hp_slide_delay', 4 );

	$cart_count = 0;
	if ( class_exists( 'WooCommerce' ) && WC()->cart ) {
		$cart_count = WC()->cart->get_cart_contents_count();
	}

	wp_localize_script(
		'dmc-homepage',
		'dmcHomepage',
		[
			'cartCount'   => $cart_count,
			'flashEnd'    => $flash_end,
			'searchUrl'   => home_url( '/' ),
			'shopUrl'     => class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
			'slideDelay'  => max( 2, $slide_delay ) * 1000,
			'slideSpeed'  => 600,
		]
	);
}
add_action( 'wp_enqueue_scripts', 'dmc_homepage_enqueue_assets', 99 );

/**
 * Body class for homepage layout.
 */
function dmc_homepage_body_class( $classes ) {
	if ( dmc_is_homepage_layout() ) {
		$classes[] = 'dmc-homepage-page';
	}

	return $classes;
}
add_filter( 'body_class', 'dmc_homepage_body_class' );
