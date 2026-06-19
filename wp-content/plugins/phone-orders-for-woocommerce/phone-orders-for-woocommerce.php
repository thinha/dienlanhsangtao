<?php
/**
 * Plugin Name: Phone Orders for WooCommerce
 * Plugin URI:
 * Description: Create manual/phone orders in WooCommerce quickly
 * Author: AlgolPlus
 * Author URI: http://algolplus.com/
 * Version: 3.5.2
 * Text Domain: phone-orders-for-woocommerce
 * WC requires at least: 3.3
 * WC tested up to: 4.8
 *
 * Copyright: (c) 2017 AlgolPlus LLC. (algol.plus@gmail.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package     phone-orders-for-woocommerce
 * @author      AlgolPlus LLC
 * @Category    Plugin
 * @copyright   Copyright (c) 2017 AlgolPlus LLC
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


//Stop if another version is active!
if( defined( 'WC_PHONE_ORDERS_PLUGIN_PATH' ) ) {
	add_action('admin_notices', function() {
		?>
		<div class="notice notice-warning is-dismissible">
		<p><?php _e( 'Please, <a href="plugins.php">deactivate</a> Free version of Phone Orders for WooCommerce!', 'phone-orders-for-woocommerce' ); ?></p>
		</div>
		<?php
	});
	return;
}


define( 'WC_PHONE_ORDERS_VERSION', '3.5.2' );
define( 'WC_PHONE_ORDERS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WC_PHONE_ORDERS_PLUGIN_URL', plugins_url( '/', __FILE__ ) );
include_once 'classes/class-wc-phone-orders-loader.php';
$WC_Phone_Orders_Loader = new WC_Phone_Orders_Loader();
register_activation_hook( __FILE__, array( $WC_Phone_Orders_Loader, 'activate') );
register_deactivation_hook( __FILE__, array( $WC_Phone_Orders_Loader, 'deactivate' ) );

//Advanced version
$extension_file = WC_PHONE_ORDERS_PLUGIN_PATH . 'pro_version/class-wc-phone-orders-loader-pro.php';
if ( file_exists( $extension_file ) ) {
	include_once $extension_file;
}

