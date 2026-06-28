<?php
/**
 * Archive listing — products per page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Override Flatsome/WooCommerce products per page on catalog archives.
 */
function dmc_tmp_loop_shop_per_page( $per_page ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $per_page;
	}

	return dmc_tmp_get_archive_per_page();
}
add_filter( 'loop_shop_per_page', 'dmc_tmp_loop_shop_per_page', 30 );

/**
 * Whether query is a WooCommerce product archive/list request.
 */
function dmc_tmp_is_product_archive_query( $query ) {
	return $query->is_post_type_archive( 'product' )
		|| $query->is_tax( get_object_taxonomies( 'product' ) )
		|| ( $query->is_search() && 'product' === $query->get( 'post_type' ) );
}

/**
 * Apply to main product archive query (includes product search).
 */
function dmc_tmp_archive_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( ! dmc_tmp_is_product_archive_query( $query ) ) {
		return;
	}

	$query->set( 'posts_per_page', dmc_tmp_get_archive_per_page() );

	// Default sort from plugin settings when visitor has not chosen toolbar sort.
	if ( isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	switch ( dmc_tmp_get_archive_default_order() ) {
		case 'date':
			$query->set( 'orderby', 'date' );
			$query->set( 'order', 'DESC' );
			break;
		case 'random':
			$query->set( 'orderby', 'rand' );
			break;
	}
}
add_action( 'pre_get_posts', 'dmc_tmp_archive_pre_get_posts', 25 );

/**
 * Body class for archive grid column count (desktop).
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function dmc_tmp_archive_body_class( $classes ) {
	if ( is_admin() || ! function_exists( 'is_shop' ) ) {
		return $classes;
	}

	$is_archive = is_shop()
		|| is_product_taxonomy()
		|| ( is_search() && 'product' === get_query_var( 'post_type' ) );

	if ( ! $is_archive ) {
		return $classes;
	}

	$classes[] = 'dmc-archive-cols-' . dmc_tmp_get_archive_columns();

	return $classes;
}
add_filter( 'body_class', 'dmc_tmp_archive_body_class' );
