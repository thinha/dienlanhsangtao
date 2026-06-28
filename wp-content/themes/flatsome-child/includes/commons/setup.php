<?php
/**
 * Shared template parts — widget areas.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/commons/search.php';
require_once get_stylesheet_directory() . '/includes/commons/icons.php';
require_once get_stylesheet_directory() . '/includes/commons/category-brands.php';

/**
 * Footer column widget area IDs (ordered).
 *
 * @return string[]
 */
function dmc_footer_column_sidebars() {
	return [
		'dmc-footer-col-1',
		'dmc-footer-col-2',
		'dmc-footer-col-3',
		'dmc-footer-col-4',
		'dmc-footer-col-5',
	];
}

/**
 * Register footer widget areas.
 */
function dmc_commons_register_sidebars() {
	$columns = [
		'dmc-footer-col-1' => __( 'Footer cột 1 — Giới thiệu', 'flatsome-child' ),
		'dmc-footer-col-2' => __( 'Footer cột 2 — Chính sách', 'flatsome-child' ),
		'dmc-footer-col-3' => __( 'Footer cột 3 — Hướng dẫn', 'flatsome-child' ),
		'dmc-footer-col-4' => __( 'Footer cột 4 — Liên hệ', 'flatsome-child' ),
		'dmc-footer-col-5' => __( 'Footer cột 5 — Tải ứng dụng', 'flatsome-child' ),
	];

	foreach ( $columns as $id => $name ) {
		register_sidebar(
			[
				'name'          => $name,
				'id'            => $id,
				'description'   => __( 'Nội dung hiển thị trong footer.', 'flatsome-child' ),
				'before_widget' => '<div id="%1$s" class="footer-col widget %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h4>',
				'after_title'   => '</h4>',
			]
		);
	}

	register_sidebar(
		[
			'name'          => __( 'Footer — Bản quyền', 'flatsome-child' ),
			'id'            => 'dmc-footer-copyright',
			'description'   => __( 'Dòng bản quyền cuối footer.', 'flatsome-child' ),
			'before_widget' => '<div id="%1$s" class="footer-copyright widget %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '',
			'after_title'   => '',
		]
	);
}
add_action( 'widgets_init', 'dmc_commons_register_sidebars' );

/**
 * Remove default Flatsome footer widget areas (replaced by dmc-footer-*).
 */
function dmc_unregister_flatsome_footer_sidebars() {
	unregister_sidebar( 'sidebar-footer-1' );
	unregister_sidebar( 'sidebar-footer-2' );
}
add_action( 'widgets_init', 'dmc_unregister_flatsome_footer_sidebars', 11 );

/**
 * Register bundled theme styles (printed last in <head>).
 */
function dmc_register_global_styles() {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$css_file  = $theme_dir . '/assets/css/styles.css';

	if ( ! file_exists( $css_file ) ) {
		return;
	}

	wp_register_style(
		'dmc-styles',
		$theme_uri . '/assets/css/styles.css',
		[],
		filemtime( $css_file )
	);
}
add_action( 'wp_enqueue_scripts', 'dmc_register_global_styles', 10 );

/**
 * Print theme styles after all other wp_head output.
 */
function dmc_print_global_styles() {
	if ( ! wp_style_is( 'dmc-styles', 'registered' ) ) {
		return;
	}

	wp_enqueue_style( 'dmc-styles' );
	wp_print_styles( 'dmc-styles' );
}
add_action( 'wp_head', 'dmc_print_global_styles', 99999 );
