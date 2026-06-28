<?php
/**
 * Plugin Name: DMC Tab Menu Product
 * Description: Thêm menu Flatsome — cấu hình slide banner trang chủ, tab danh mục và slider sản phẩm (Swiper freeMode + mũi tên).
 * Version:     1.6.0
 * Author:      DMC
 * Text Domain: dmc-tab-menu-product
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DMC_TMP_VERSION', '1.5.0' );
define( 'DMC_TMP_PATH', plugin_dir_path( __FILE__ ) );
define( 'DMC_TMP_URL', plugin_dir_url( __FILE__ ) );

require_once DMC_TMP_PATH . 'includes/helpers.php';
require_once DMC_TMP_PATH . 'includes/brand-sync.php';
require_once DMC_TMP_PATH . 'includes/homepage-product-sections.php';
require_once DMC_TMP_PATH . 'includes/homepage-acf-fields.php';
require_once DMC_TMP_PATH . 'includes/acf-fields.php';
require_once DMC_TMP_PATH . 'includes/admin-validation.php';
require_once DMC_TMP_PATH . 'includes/admin-shipping-bulk.php';
require_once DMC_TMP_PATH . 'includes/product-fields.php';
require_once DMC_TMP_PATH . 'includes/crawl-product/fields.php';
require_once DMC_TMP_PATH . 'includes/crawl-product/crawler.php';
require_once DMC_TMP_PATH . 'includes/crawl-product/parsers/helpers.php';
require_once DMC_TMP_PATH . 'includes/crawl-product/parsers/sanaky.php';
require_once DMC_TMP_PATH . 'includes/crawl-product/parsers/dienmaycholon.php';
require_once DMC_TMP_PATH . 'includes/crawl-product/parsers/dienmayxanh.php';
require_once DMC_TMP_PATH . 'includes/crawl-product/importer.php';
require_once DMC_TMP_PATH . 'includes/crawl-product/admin-page.php';
require_once DMC_TMP_PATH . 'includes/archive.php';
require_once DMC_TMP_PATH . 'includes/shipping.php';
require_once DMC_TMP_PATH . 'includes/frontend.php';

/**
 * Activation — flush rewrite rules if needed.
 */
function dmc_tmp_activate() {
	// Reserved for future use.
}
register_activation_hook( __FILE__, 'dmc_tmp_activate' );
