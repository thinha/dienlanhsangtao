<?php
/**
 * Plugin Name: DMC Voucher
 * Description: Quản lý voucher (CPT), ví voucher người dùng, đồng bộ WooCommerce coupon và hiển thị giá khuyến mãi trên trang sản phẩm.
 * Version:     1.0.0
 * Author:      DMC
 * Text Domain: dmc-voucher
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DMC_VOUCHER_VERSION', '1.0.2' );
define( 'DMC_VOUCHER_PATH', plugin_dir_path( __FILE__ ) );
define( 'DMC_VOUCHER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Check WooCommerce dependency.
 */
function dmc_voucher_check_dependencies() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-error"><p>';
				esc_html_e( 'DMC Voucher yêu cầu WooCommerce được kích hoạt.', 'dmc-voucher' );
				echo '</p></div>';
			}
		);
		return false;
	}

	return true;
}

/**
 * Bootstrap plugin.
 */
function dmc_voucher_init() {
	if ( ! dmc_voucher_check_dependencies() ) {
		return;
	}

	require_once DMC_VOUCHER_PATH . 'includes/post-type.php';
	require_once DMC_VOUCHER_PATH . 'includes/admin-meta-boxes.php';
	require_once DMC_VOUCHER_PATH . 'includes/coupon-sync.php';
	require_once DMC_VOUCHER_PATH . 'includes/voucher-engine.php';
	require_once DMC_VOUCHER_PATH . 'includes/voucher-card.php';
	require_once DMC_VOUCHER_PATH . 'includes/user-wallet.php';
	require_once DMC_VOUCHER_PATH . 'includes/session.php';
	require_once DMC_VOUCHER_PATH . 'includes/ajax.php';
	require_once DMC_VOUCHER_PATH . 'includes/frontend.php';
	require_once DMC_VOUCHER_PATH . 'includes/cart.php';
	require_once DMC_VOUCHER_PATH . 'includes/cart-frontend.php';
	require_once DMC_VOUCHER_PATH . 'includes/account.php';

	DMC_Voucher_Post_Type::init();
	DMC_Voucher_Admin_Meta_Boxes::init();
	DMC_Voucher_Coupon_Sync::init();
	DMC_Voucher_User_Wallet::init();
	DMC_Voucher_Session::init();
	DMC_Voucher_Ajax::init();
	DMC_Voucher_Frontend::init();
	DMC_Voucher_Cart::init();
	DMC_Voucher_Cart_Frontend::init();
	DMC_Voucher_Account::init();
}
add_action( 'plugins_loaded', 'dmc_voucher_init' );

register_activation_hook(
	__FILE__,
	function () {
		require_once DMC_VOUCHER_PATH . 'includes/post-type.php';
		DMC_Voucher_Post_Type::register();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);
