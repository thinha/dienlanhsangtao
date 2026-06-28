<?php
/**
 * My Account — setup, assets, endpoints.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/account/helpers.php';

/**
 * Register wishlist endpoint.
 */
function dmc_account_register_endpoints() {
	add_rewrite_endpoint( 'wishlist', EP_ROOT | EP_PAGES );
	add_rewrite_endpoint( 'vouchers', EP_ROOT | EP_PAGES );
}
add_action( 'init', 'dmc_account_register_endpoints' );

/**
 * Flush rewrite rules once after deploy.
 */
function dmc_account_maybe_flush_rewrites() {
	if ( get_option( 'dmc_account_rewrites_v2' ) ) {
		return;
	}

	flush_rewrite_rules();
	update_option( 'dmc_account_rewrites_v2', 1 );
}
add_action( 'init', 'dmc_account_maybe_flush_rewrites', 99 );

/**
 * Add wishlist query var.
 *
 * @param array $vars Query vars.
 */
function dmc_account_query_vars( $vars ) {
	$vars[] = 'wishlist';
	$vars[] = 'vouchers';
	return $vars;
}
add_filter( 'query_vars', 'dmc_account_query_vars' );

/**
 * Custom account menu.
 */
add_filter( 'woocommerce_account_menu_items', 'dmc_account_menu_items', 20 );

/**
 * Wishlist endpoint content.
 */
function dmc_account_wishlist_endpoint() {
	wc_get_template( 'myaccount/wishlist.php' );
}
add_action( 'woocommerce_account_wishlist_endpoint', 'dmc_account_wishlist_endpoint' );

/**
 * Vouchers endpoint content.
 */
function dmc_account_vouchers_endpoint() {
	wc_get_template( 'myaccount/vouchers.php' );
}
add_action( 'woocommerce_account_vouchers_endpoint', 'dmc_account_vouchers_endpoint' );

/**
 * Disable registration on My Account — members login only.
 */
function dmc_account_disable_registration( $value ) {
	if ( is_account_page() ) {
		return 'no';
	}
	return $value;
}
add_filter( 'pre_option_woocommerce_enable_myaccount_registration', 'dmc_account_disable_registration' );

/**
 * Hide default WooCommerce navigation (custom sidebar used instead).
 */
function dmc_account_remove_default_nav() {
	if ( ! dmc_is_account_layout() ) {
		return;
	}

	remove_action( 'woocommerce_before_account_navigation', 'woocommerce_output_all_notices', 10 );
}
add_action( 'template_redirect', 'dmc_account_remove_default_nav' );

/**
 * Enqueue account assets.
 */
function dmc_account_enqueue_assets() {
	if ( ! dmc_is_account_layout() ) {
		return;
	}

	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$js_file   = $theme_dir . '/assets/js/account.js';
	$homepage_js = $theme_dir . '/assets/js/homepage.js';

	wp_enqueue_script(
		'dmc-homepage',
		$theme_uri . '/assets/js/homepage.js',
		[],
		file_exists( $homepage_js ) ? filemtime( $homepage_js ) : '1.0.0',
		true
	);

	$cart_count = 0;
	if ( WC()->cart ) {
		$cart_count = WC()->cart->get_cart_contents_count();
	}

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

	wp_enqueue_script(
		'dmc-account',
		$theme_uri . '/assets/js/account.js',
		[],
		file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0',
		true
	);

	wp_localize_script(
		'dmc-account',
		'dmcAccount',
		[
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'dmc_wishlist' ),
			'loginUrl'     => wc_get_page_permalink( 'myaccount' ),
			'wishlistUrl'  => dmc_account_wishlist_url(),
			'wishlistCount'=> is_user_logged_in() ? dmc_wishlist_count() : 0,
		]
	);
}
add_action( 'wp_enqueue_scripts', 'dmc_account_enqueue_assets', 99 );

/**
 * Body class for account layout.
 *
 * @param string[] $classes Body classes.
 */
function dmc_account_body_class( $classes ) {
	if ( dmc_is_account_layout() ) {
		$classes[] = 'dmc-account-page';
	}

	return $classes;
}
add_filter( 'body_class', 'dmc_account_body_class' );

/**
 * AJAX toggle wishlist.
 */
function dmc_account_ajax_wishlist_toggle() {
	check_ajax_referer( 'dmc_wishlist', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error(
			[
				'message'  => __( 'Vui lòng đăng nhập để lưu sản phẩm yêu thích.', 'flatsome-child' ),
				'loginUrl' => wc_get_page_permalink( 'myaccount' ),
			],
			401
		);
	}

	$product_id = isset( $_POST['productId'] ) ? (int) $_POST['productId'] : 0;
	if ( ! $product_id || ! wc_get_product( $product_id ) ) {
		wp_send_json_error( [ 'message' => __( 'Sản phẩm không hợp lệ.', 'flatsome-child' ) ], 400 );
	}

	wp_send_json_success( dmc_wishlist_toggle( $product_id ) );
}
add_action( 'wp_ajax_dmc_wishlist_toggle', 'dmc_account_ajax_wishlist_toggle' );

/**
 * Redirect wishlist header link for guests to login.
 */
function dmc_account_wishlist_redirect() {
	if ( is_user_logged_in() || ! is_account_page() ) {
		return;
	}

	if ( isset( $_GET['redirect'] ) && 'wishlist' === $_GET['redirect'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
}
add_action( 'template_redirect', 'dmc_account_wishlist_redirect' );

/**
 * Force custom account template on WooCommerce account page.
 *
 * @param string $template Template path.
 */
function dmc_account_template_include( $template ) {
	if ( ! dmc_is_account_layout() ) {
		return $template;
	}

	$custom = get_stylesheet_directory() . '/page-my-account.php';

	return file_exists( $custom ) ? $custom : $template;
}
add_filter( 'template_include', 'dmc_account_template_include', 99 );
