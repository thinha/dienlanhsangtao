<?php
/**
 * About page — setup, assets, template routing.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/about/helpers.php';

/**
 * Whether current request uses about layout.
 */
function dmc_is_about_layout() {
	return is_page_template( 'page-templates/about.php' ) || is_page( 'gioi-thieu' );
}

/**
 * Force custom template for Giới thiệu page slug.
 *
 * @param string $template Current template path.
 */
function dmc_about_page_template( $template ) {
	if ( ! is_page( 'gioi-thieu' ) ) {
		return $template;
	}

	$about_template = get_stylesheet_directory() . '/page-templates/about.php';

	if ( file_exists( $about_template ) ) {
		return $about_template;
	}

	return $template;
}
add_filter( 'template_include', 'dmc_about_page_template', 20 );

/**
 * Enqueue assets for about page (reuses homepage drawer / mobile bar JS).
 */
function dmc_about_enqueue_assets() {
	if ( ! dmc_is_about_layout() ) {
		return;
	}

	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$js_file   = $theme_dir . '/assets/js/homepage.js';

	if ( file_exists( $js_file ) ) {
		wp_enqueue_script(
			'dmc-homepage',
			$theme_uri . '/assets/js/homepage.js',
			[],
			filemtime( $js_file ),
			true
		);

		$cart_count = 0;
		if ( class_exists( 'WooCommerce' ) && WC()->cart ) {
			$cart_count = WC()->cart->get_cart_contents_count();
		}

		wp_localize_script(
			'dmc-homepage',
			'dmcHomepage',
			[
				'cartCount'  => $cart_count,
				'flashEnd'   => 0,
				'searchUrl'  => home_url( '/' ),
				'shopUrl'    => class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
				'slideDelay' => 4000,
				'slideSpeed' => 600,
			]
		);
	}
}
add_action( 'wp_enqueue_scripts', 'dmc_about_enqueue_assets', 99 );

/**
 * Body class for about layout.
 *
 * @param string[] $classes Body classes.
 */
function dmc_about_body_class( $classes ) {
	if ( dmc_is_about_layout() ) {
		$classes[] = 'dmc-about-page';
	}

	return $classes;
}
add_filter( 'body_class', 'dmc_about_body_class' );
