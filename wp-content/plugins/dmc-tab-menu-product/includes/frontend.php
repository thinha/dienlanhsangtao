<?php
/**
 * Frontend — tab menu product section on homepage.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue assets on homepage.
 */
function dmc_tmp_enqueue_assets() {
	if ( ! function_exists( 'dmc_is_homepage_layout' ) || ! dmc_is_homepage_layout() ) {
		return;
	}

	if ( ! dmc_tmp_option( 'tmp_enable', true ) || empty( dmc_tmp_get_tabs() ) ) {
		return;
	}

	// Ensure Swiper is available (theme enqueues on homepage; fallback if not).
	if ( ! wp_script_is( 'swiper', 'registered' ) ) {
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
	} else {
		wp_enqueue_script( 'swiper' );
		wp_enqueue_style( 'swiper' );
	}

	wp_enqueue_style(
		'dmc-tab-menu-product',
		DMC_TMP_URL . 'assets/css/tab-menu-product.css',
		[],
		DMC_TMP_VERSION
	);

	wp_enqueue_script(
		'dmc-tab-menu-product',
		DMC_TMP_URL . 'assets/js/tab-menu-product.js',
		[ 'swiper' ],
		DMC_TMP_VERSION,
		true
	);

	wp_localize_script(
		'dmc-tab-menu-product',
		'dmcTabMenuProduct',
		[
			'slidesPerView'   => max( 2, min( 8, (int) dmc_tmp_option( 'tmp_slides_per_view', 5 ) ) ),
			'autoplay'        => (bool) dmc_tmp_option( 'tmp_swiper_autoplay', false ),
			'autoplayDelay'   => max( 2, min( 15, (int) dmc_tmp_option( 'tmp_swiper_autoplay_delay', 4 ) ) ) * 1000,
		]
	);
}
add_action( 'wp_enqueue_scripts', 'dmc_tmp_enqueue_assets', 100 );

/**
 * Render section after categories on homepage.
 */
function dmc_tmp_render_homepage_section() {
	if ( ! function_exists( 'dmc_is_homepage_layout' ) || ! dmc_is_homepage_layout() ) {
		return;
	}

	if ( ! dmc_tmp_option( 'tmp_enable', true ) ) {
		return;
	}

	$tabs = dmc_tmp_get_tabs();
	if ( empty( $tabs ) ) {
		return;
	}

	$slides_per_view = max( 2, min( 8, (int) dmc_tmp_option( 'tmp_slides_per_view', 5 ) ) );
	$show_more       = (bool) dmc_tmp_option( 'tmp_show_more', true );
	$more_text       = trim( (string) dmc_tmp_option( 'tmp_more_text', 'Xem tất cả ›' ) ) ?: 'Xem tất cả ›';
	$swiper_autoplay = (bool) dmc_tmp_option( 'tmp_swiper_autoplay', false );
	$swiper_autoplay_delay = max( 2, min( 15, (int) dmc_tmp_option( 'tmp_swiper_autoplay_delay', 4 ) ) );

	include DMC_TMP_PATH . 'templates/tab-menu-section.php';
}
add_action( 'dmc_homepage_after_categories', 'dmc_tmp_render_homepage_section' );
