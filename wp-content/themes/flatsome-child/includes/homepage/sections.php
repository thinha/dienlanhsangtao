<?php
/**
 * Homepage section resolver — reads ACF config & queries WooCommerce.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get ACF option with fallback.
 */
function dmc_homepage_option( $key, $default = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $key, 'option' );

	return ( null === $value || false === $value || '' === $value ) ? $default : $value;
}

/**
 * Is section/block enabled.
 */
function dmc_homepage_is_enabled( $key, $default = true ) {
	$value = dmc_homepage_option( $key, $default );
	return (bool) $value;
}

/**
 * Normalize repeater row from ACF.
 */
function dmc_homepage_normalize_section_row( array $row ) {
	if ( function_exists( 'dmc_tmp_normalize_product_section_row' ) ) {
		return dmc_tmp_normalize_product_section_row( $row );
	}

	return [
		'enable'          => ! empty( $row['enable'] ),
		'title'           => $row['title'] ?? '',
		'layout'          => $row['layout'] ?? 'swiper',
		'source'          => $row['source'] ?? 'bestseller',
		'products'        => array_map( 'intval', (array) ( $row['products'] ?? [] ) ),
		'category'        => (int) ( $row['category'] ?? 0 ),
		'brand'           => (int) ( $row['brand'] ?? 0 ),
		'orderby'         => $row['orderby'] ?? 'popularity',
		'limit'           => max( 1, min( 24, (int) ( $row['limit'] ?? 8 ) ) ),
		'slides_per_view' => max( 2, min( 8, (int) ( $row['slides_per_view'] ?? 4 ) ) ),
		'swiper_autoplay' => ! empty( $row['swiper_autoplay'] ),
		'swiper_autoplay_delay' => max( 2, min( 15, (int) ( $row['swiper_autoplay_delay'] ?? 4 ) ) ),
		'show_more'       => ! isset( $row['show_more'] ) || ! empty( $row['show_more'] ),
		'more_text'       => trim( (string) ( $row['more_text'] ?? 'Xem tất cả ›' ) ) ?: 'Xem tất cả ›',
		'more_url'        => $row['more_url'] ?? '',
		'bg_pc'           => null,
		'bg_tablet'       => null,
		'bg_mobile'       => null,
		'padding_top_pc'        => 0,
		'padding_top_tablet'    => 0,
		'padding_top_mobile'    => 0,
	];
}

/**
 * Get configured product sections from ACF.
 */
function dmc_homepage_get_product_sections() {
	if ( function_exists( 'dmc_tmp_get_hp_product_sections' ) ) {
		return dmc_tmp_get_hp_product_sections();
	}

	$sections = [];

	if ( function_exists( 'have_rows' ) && have_rows( 'hp_product_sections', 'option' ) ) {
		while ( have_rows( 'hp_product_sections', 'option' ) ) {
			the_row();
			$row = dmc_homepage_normalize_section_row(
				[
					'enable'                => get_sub_field( 'enable' ),
					'title'                 => get_sub_field( 'title' ),
					'layout'                => get_sub_field( 'layout' ),
					'source'                => get_sub_field( 'source' ),
					'products'              => get_sub_field( 'products' ),
					'category'              => get_sub_field( 'category' ),
					'brand'                 => get_sub_field( 'brand' ),
					'orderby'               => get_sub_field( 'orderby' ),
					'limit'                 => get_sub_field( 'limit' ),
					'slides_per_view'       => get_sub_field( 'slides_per_view' ),
					'swiper_autoplay'       => get_sub_field( 'swiper_autoplay' ),
					'swiper_autoplay_delay' => get_sub_field( 'swiper_autoplay_delay' ),
					'show_more'             => get_sub_field( 'show_more' ),
					'more_text'             => get_sub_field( 'more_text' ),
					'more_url'              => get_sub_field( 'more_url' ),
					'bg_pc'                 => get_sub_field( 'bg_pc' ),
					'bg_tablet'             => get_sub_field( 'bg_tablet' ),
					'bg_mobile'             => get_sub_field( 'bg_mobile' ),
					'padding_top_pc'        => get_sub_field( 'padding_top_pc' ),
					'padding_top_tablet'    => get_sub_field( 'padding_top_tablet' ),
					'padding_top_mobile'    => get_sub_field( 'padding_top_mobile' ),
				]
			);

			if ( $row['enable'] && $row['title'] ) {
				$sections[] = $row;
			}
		}
	}

	if ( empty( $sections ) ) {
		$sections = dmc_homepage_default_product_sections();
	}

	return $sections;
}

/**
 * Fallback sections when ACF chưa cấu hình.
 */
function dmc_homepage_default_product_sections() {
	if ( function_exists( 'dmc_tmp_default_hp_product_sections' ) ) {
		return dmc_tmp_default_hp_product_sections();
	}

	return [
		[
			'enable'    => true,
			'title'     => __( 'SẢN PHẨM BÁN CHẠY', 'flatsome-child' ),
			'layout'    => 'swiper',
			'source'    => 'bestseller',
			'products'  => [],
			'category'  => 0,
			'limit'     => 8,
			'show_more' => true,
			'more_text' => 'Xem tất cả ›',
			'more_url'  => '',
		],
		[
			'enable'    => true,
			'title'     => __( 'GỢI Ý CHO BẠN', 'flatsome-child' ),
			'layout'    => 'swiper',
			'source'    => 'random',
			'products'  => [],
			'category'  => 0,
			'limit'     => 8,
			'show_more' => true,
			'more_text' => 'Xem tất cả ›',
			'more_url'  => '',
		],
	];
}

/**
 * Resolve WooCommerce products from section config.
 */
function dmc_homepage_resolve_products( array $config ) {
	if ( function_exists( 'dmc_tmp_resolve_product_section_products' ) ) {
		return dmc_tmp_resolve_product_section_products( $config );
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return [];
	}

	$limit  = $config['limit'] ?? 6;
	$source = $config['source'] ?? 'bestseller';
	$ids    = array_filter( array_map( 'intval', (array) ( $config['products'] ?? [] ) ) );

	if ( 'manual' === $source && ! empty( $ids ) ) {
		$products = [];
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product && $product->is_visible() ) {
				$products[] = $product;
			}
		}
		return array_slice( $products, 0, $limit );
	}

	$query_args = [
		'limit'  => $limit,
		'status' => 'publish',
		'order'  => 'DESC',
	];

	switch ( $source ) {
		case 'category':
			$cat_id = (int) ( $config['category'] ?? 0 );
			if ( $cat_id ) {
				$term = get_term( $cat_id, 'product_cat' );
				if ( $term && ! is_wp_error( $term ) ) {
					$query_args['category'] = [ $term->slug ];
				}
			}
			$query_args['orderby'] = 'date';
			break;

		case 'on_sale':
			$query_args['on_sale'] = true;
			$query_args['orderby'] = 'date';
			break;

		case 'bestseller':
			$query_args['orderby'] = 'popularity';
			break;

		case 'latest':
			$query_args['orderby'] = 'date';
			break;

		case 'featured':
			$query_args['featured'] = true;
			$query_args['orderby']  = 'date';
			break;

		case 'random':
			$query_args['orderby'] = 'rand';
			break;

		default:
			$query_args['orderby'] = 'popularity';
			break;
	}

	return dmc_homepage_get_products( $query_args );
}

/**
 * "Xem tất cả" URL for a section.
 */
function dmc_homepage_section_more_url( array $config ) {
	if ( function_exists( 'dmc_tmp_product_section_more_url' ) ) {
		return dmc_tmp_product_section_more_url( $config );
	}

	if ( ! empty( $config['more_url'] ) ) {
		return $config['more_url'];
	}

	if ( 'category' === ( $config['source'] ?? '' ) && ! empty( $config['category'] ) ) {
		$link = get_term_link( (int) $config['category'], 'product_cat' );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}

	return class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
}

/**
 * Flash sale config from ACF.
 */
function dmc_homepage_get_flash_config() {
	if ( function_exists( 'dmc_tmp_get_hp_flash_config' ) ) {
		return dmc_tmp_get_hp_flash_config();
	}

	return [
		'enable'    => dmc_homepage_is_enabled( 'hp_flash_enable', true ),
		'title'     => dmc_homepage_option( 'hp_flash_title', 'FLASH SALE' ),
		'subtitle'  => dmc_homepage_option( 'hp_flash_subtitle', 'GIÁ SỐC HÔM NAY' ),
		'source'    => dmc_homepage_option( 'hp_flash_source', 'manual' ),
		'products'  => array_map( 'intval', (array) dmc_homepage_option( 'hp_flash_products', [] ) ),
		'limit'     => max( 3, min( 10, (int) dmc_homepage_option( 'hp_flash_limit', 5 ) ) ),
		'end_time'  => dmc_homepage_option( 'hp_flash_end_time', '' ),
		'more_url'  => dmc_homepage_option( 'hp_flash_more_url', '' ),
	];
}

/**
 * Flash sale countdown seconds until end time today.
 */
function dmc_homepage_flash_countdown_seconds() {
	if ( function_exists( 'dmc_tmp_hp_flash_countdown_seconds' ) ) {
		return dmc_tmp_hp_flash_countdown_seconds();
	}

	$end_time = dmc_homepage_option( 'hp_flash_end_time', '' );

	if ( ! $end_time && function_exists( 'get_field' ) ) {
		$hour   = (int) get_field( 'flashsale_hours_end', 'option' );
		$minute = (int) get_field( 'flashsale_minutes_end', 'option' );
		if ( $hour || $minute ) {
			$end_time = sprintf( '%02d:%02d', $hour, $minute );
		}
	}

	if ( ! $end_time ) {
		return 4 * 3600 + 23 * 60 + 59;
	}

	$now       = current_time( 'timestamp' );
	$today_end = strtotime( date( 'Y-m-d', $now ) . ' ' . $end_time . ':00' );

	if ( $today_end <= $now ) {
		$today_end = strtotime( '+1 day', $today_end );
	}

	return max( 60, $today_end - $now );
}

/**
 * Render a product section by config.
 */
function dmc_homepage_render_product_section( array $config, $index = 0 ) {
	$products = dmc_homepage_resolve_products( $config );
	if ( empty( $products ) ) {
		return;
	}

	$layout = $config['layout'] ?? 'grid';

	if ( 0 === (int) $index ) {
		$layout   = 'showcase';
		$products = array_slice( $products, 0, 6 );
	}

	get_template_part(
		'template-parts/homepage/section-products',
		null,
		[
			'config'   => $config,
			'products' => $products,
			'layout'   => $layout,
			'index'    => $index,
			'more_url' => dmc_homepage_section_more_url( $config ),
		]
	);
}
