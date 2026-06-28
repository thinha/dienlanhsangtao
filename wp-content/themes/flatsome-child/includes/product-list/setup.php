<?php
/**
 * Product list & detail — assets, body class, query tweaks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/product-list/helpers.php';
require_once get_stylesheet_directory() . '/includes/product-list/acf-fields.php';

/**
 * Whether current request uses product list layout.
 */
function dmc_is_product_list_layout() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	return is_shop()
		|| is_product_taxonomy()
		|| ( is_search() && 'product' === get_query_var( 'post_type' ) )
		|| is_product();
}

/**
 * Enqueue product list assets.
 */
function dmc_product_list_enqueue_assets() {
	if ( ! dmc_is_product_list_layout() ) {
		return;
	}

	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$js_file   = $theme_dir . '/assets/js/product-list.js';
	$homepage_js = $theme_dir . '/assets/js/homepage.js';

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
		file_exists( $homepage_js ) ? filemtime( $homepage_js ) : '1.0.0',
		true
	);

	wp_enqueue_script( 'jquery-ui-slider' );
	wp_enqueue_script(
		'dmc-product-list',
		$theme_uri . '/assets/js/product-list.js',
		[ 'jquery', 'jquery-ui-slider', 'swiper' ],
		file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0',
		true
	);

	wp_localize_script(
		'dmc-product-list',
		'dmcProductList',
		[
			'currencySymbol'       => get_woocommerce_currency_symbol(),
			'currencyDecimalSep'   => wc_get_price_decimal_separator(),
			'currencyThousandSep'  => wc_get_price_thousand_separator(),
			'currencyNumDecimals'  => wc_get_price_decimals(),
		]
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
}
add_action( 'wp_enqueue_scripts', 'dmc_product_list_enqueue_assets', 99 );

/**
 * Body class for product list layout.
 */
function dmc_product_list_body_class( $classes ) {
	if ( dmc_is_product_list_layout() ) {
		$classes[] = 'dmc-product-list-page';
		if ( is_product() ) {
			$classes[] = 'dmc-product-detail-page';
		}
	}

	return $classes;
}
add_filter( 'body_class', 'dmc_product_list_body_class' );

/**
 * Add star class to review comments for filtering.
 */
function dmc_pl_review_comment_class( $classes, $class, $comment_id, $comment ) {
	if ( ! $comment instanceof WP_Comment ) {
		return $classes;
	}

	if ( 'product' !== get_post_type( (int) $comment->comment_post_ID ) ) {
		return $classes;
	}

	$rating = (int) get_comment_meta( $comment_id, 'rating', true );
	if ( $rating > 0 ) {
		$classes[] = 'pl-review-item';
		$classes[] = 'pl-review-item--' . $rating . 'star';
	}

	return $classes;
}
add_filter( 'comment_class', 'dmc_pl_review_comment_class', 10, 4 );

/**
 * Whether query is a product archive/list request.
 */
function dmc_product_list_is_archive_query( $query ) {
	return $query->is_post_type_archive( 'product' )
		|| $query->is_tax( get_object_taxonomies( 'product' ) )
		|| ( $query->is_search() && 'product' === $query->get( 'post_type' ) );
}

/**
 * Related products args on detail page.
 */
function dmc_product_list_related_args( $args ) {
	if ( is_product() ) {
		$args['columns']        = 6;
		$args['posts_per_page'] = 12;
	}

	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'dmc_product_list_related_args' );

/**
 * Apply sidebar category filter to main query.
 */
function dmc_product_list_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! dmc_product_list_is_archive_query( $query ) ) {
		return;
	}

	$selected = dmc_pl_selected_categories();
	if ( ! empty( $selected ) ) {
		$tax_query   = (array) $query->get( 'tax_query' );
		$tax_query[] = [
			'taxonomy' => 'product_cat',
			'field'    => 'term_id',
			'terms'    => $selected,
			'operator' => 'IN',
		];
		$query->set( 'tax_query', $tax_query );
	}

	$brand_tax = dmc_pl_brand_taxonomy();
	$brands    = dmc_pl_selected_brands();
	if ( $brand_tax && ! empty( $brands ) ) {
		$tax_query   = (array) $query->get( 'tax_query' );
		$tax_query[] = [
			'taxonomy' => $brand_tax,
			'field'    => 'term_id',
			'terms'    => $brands,
			'operator' => 'IN',
		];
		$query->set( 'tax_query', $tax_query );
	}

	switch ( dmc_pl_get_sort() ) {
		case 'date':
			$query->set( 'orderby', 'date' );
			$query->set( 'order', 'DESC' );
			break;
		case 'popularity':
			$query->set( 'meta_key', 'total_sales' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
			break;
		case 'price_asc':
			$query->set( 'meta_key', '_price' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'ASC' );
			break;
		case 'price_desc':
			$query->set( 'meta_key', '_price' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'DESC' );
			break;
	}
}
add_action( 'pre_get_posts', 'dmc_product_list_pre_get_posts' );
