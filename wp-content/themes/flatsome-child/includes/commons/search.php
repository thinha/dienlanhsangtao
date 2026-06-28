<?php
/**
 * Live search — AJAX suggestions for products & categories.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current page renders the custom header search.
 */
function dmc_has_live_search() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	if ( function_exists( 'dmc_is_homepage_layout' ) && dmc_is_homepage_layout() ) {
		return true;
	}

	if ( function_exists( 'dmc_is_product_list_layout' ) && dmc_is_product_list_layout() ) {
		return true;
	}

	if ( function_exists( 'dmc_is_account_layout' ) && dmc_is_account_layout() ) {
		return true;
	}

	return false;
}

/**
 * Enqueue live search assets.
 */
function dmc_live_search_enqueue_assets() {
	if ( ! dmc_has_live_search() ) {
		return;
	}

	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$js_file   = $theme_dir . '/assets/js/live-search.js';

	wp_enqueue_script(
		'dmc-live-search',
		$theme_uri . '/assets/js/live-search.js',
		[],
		file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0',
		true
	);

	wp_localize_script(
		'dmc-live-search',
		'dmcLiveSearch',
		[
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'dmc_live_search' ),
			'debounce' => 400,
			'minChars' => 2,
			'labels'   => [
				'categories' => __( 'Danh mục', 'flatsome-child' ),
				'products'   => __( 'Sản phẩm', 'flatsome-child' ),
				'empty'      => __( 'Không tìm thấy kết quả.', 'flatsome-child' ),
				'loading'    => __( 'Đang tìm...', 'flatsome-child' ),
			],
		]
	);
}
add_action( 'wp_enqueue_scripts', 'dmc_live_search_enqueue_assets', 100 );

/**
 * AJAX: search product categories and products by keyword.
 */
function dmc_live_search_ajax() {
	check_ajax_referer( 'dmc_live_search', 'nonce' );

	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_send_json_error( [ 'message' => __( 'WooCommerce chưa được kích hoạt.', 'flatsome-child' ) ], 400 );
	}

	$keyword = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

	if ( mb_strlen( $keyword ) < 2 ) {
		wp_send_json_success(
			[
				'categories' => [],
				'products'   => [],
			]
		);
	}

	wp_send_json_success(
		[
			'categories' => dmc_live_search_categories( $keyword ),
			'products'   => dmc_live_search_products( $keyword ),
		]
	);
}
add_action( 'wp_ajax_dmc_live_search', 'dmc_live_search_ajax' );
add_action( 'wp_ajax_nopriv_dmc_live_search', 'dmc_live_search_ajax' );

/**
 * Search product categories by name.
 *
 * @param string $keyword Search term.
 * @return array<int, array{name: string, url: string, count: int}>
 */
function dmc_live_search_categories( $keyword ) {
	$args = [
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'number'     => 5,
		'search'     => $keyword,
	];

	if ( function_exists( 'dmc_excluded_product_cat_ids' ) ) {
		$exclude = dmc_excluded_product_cat_ids();
		if ( $exclude ) {
			$args['exclude'] = $exclude;
		}
	}

	$terms = get_terms( $args );

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return [];
	}

	$results = [];

	foreach ( $terms as $term ) {
		$link = get_term_link( $term );

		if ( is_wp_error( $link ) ) {
			continue;
		}

		$results[] = [
			'name'  => $term->name,
			'url'   => $link,
			'count' => (int) $term->count,
		];
	}

	return $results;
}

/**
 * Format product price for live search suggestions.
 *
 * @param WC_Product $product Product.
 * @return array{current: string, regular: string}
 */
function dmc_live_search_product_price( WC_Product $product ) {
	$format = function_exists( 'dmc_homepage_format_price' )
		? 'dmc_homepage_format_price'
		: function ( $price ) {
			if ( ! $price ) {
				return '';
			}

			return number_format( (float) $price, 0, '', '.' ) . '₫';
		};

	$current = $format( $product->get_sale_price() ?: $product->get_price() );
	$regular = '';

	if ( $product->is_on_sale() ) {
		$regular_price = $product->get_regular_price();
		if ( $regular_price ) {
			$regular = $format( $regular_price );
		}
	}

	return [
		'current' => $current,
		'regular' => $regular,
	];
}

/**
 * Whether a product is visible in storefront search.
 *
 * @param WC_Product $product Product.
 */
function dmc_search_product_is_visible( WC_Product $product ) {
	if ( 'publish' !== $product->get_status() ) {
		return false;
	}

	return in_array( $product->get_catalog_visibility(), [ 'visible', 'search' ], true );
}

/**
 * Find published product IDs matching a keyword (title + SKU only).
 *
 * WordPress default search also scans post_content, which pulls in unrelated
 * products when descriptions mention other categories (e.g. "tủ đông" matching
 * washing machines that list related appliances in their body copy).
 *
 * @param string $keyword Search term.
 * @param int    $limit   Max IDs to return. 0 = no limit.
 * @return int[]
 */
function dmc_search_product_ids( $keyword, $limit = 0 ) {
	global $wpdb;

	$keyword = trim( wp_strip_all_tags( $keyword ) );
	if ( '' === $keyword ) {
		return [];
	}

	$phrase_like = '%' . $wpdb->esc_like( $keyword ) . '%';
	$terms       = preg_split( '/[\s,\+]+/u', $keyword, -1, PREG_SPLIT_NO_EMPTY );
	$terms       = array_values(
		array_filter(
			$terms,
			static function ( $term ) {
				return mb_strlen( $term ) >= 1;
			}
		)
	);

	if ( empty( $terms ) ) {
		return [];
	}

	$base_sql = "
		SELECT DISTINCT p.ID
		FROM {$wpdb->posts} p
		LEFT JOIN {$wpdb->wc_product_meta_lookup} lookup ON lookup.product_id = p.ID
		WHERE p.post_type = 'product'
		AND p.post_status = 'publish'
	";

	$phrase_ids = array_map(
		'intval',
		(array) $wpdb->get_col(
			$wpdb->prepare(
				$base_sql . ' AND ( p.post_title LIKE %s OR lookup.sku LIKE %s ) ORDER BY p.post_title ASC',
				$phrase_like,
				$phrase_like
			)
		)
	);

	$term_ids = [];
	if ( count( $terms ) > 1 ) {
		$title_clauses = [];
		$prepare_args  = [];

		foreach ( $terms as $term ) {
			$title_clauses[] = 'p.post_title LIKE %s';
			$prepare_args[]  = '%' . $wpdb->esc_like( $term ) . '%';
		}

		$term_ids = array_map(
			'intval',
			(array) $wpdb->get_col(
				$wpdb->prepare(
					$base_sql . ' AND ( ' . implode( ' AND ', $title_clauses ) . ' ) ORDER BY p.post_title ASC',
					...$prepare_args
				)
			)
		);
	}

	$ids = array_values( array_unique( array_merge( $phrase_ids, $term_ids ) ) );

	if ( empty( $ids ) ) {
		return [];
	}

	$visible_ids = [];

	foreach ( $ids as $product_id ) {
		$product = wc_get_product( $product_id );
		if ( ! $product || ! dmc_search_product_is_visible( $product ) ) {
			continue;
		}

		$visible_ids[] = $product_id;

		if ( $limit > 0 && count( $visible_ids ) >= $limit ) {
			break;
		}
	}

	return $visible_ids;
}

/**
 * Replace default WP product search with title/SKU matching on the results page.
 *
 * @param WP_Query $query Query.
 */
function dmc_product_search_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
		return;
	}

	if ( 'product' !== $query->get( 'post_type' ) ) {
		return;
	}

	$keyword = $query->get( 's' );
	if ( '' === trim( (string) $keyword ) ) {
		return;
	}

	$ids = dmc_search_product_ids( $keyword );

	$query->set( 's', '' );
	$query->set( 'post__in', $ids ? $ids : [ 0 ] );

	if ( ! function_exists( 'dmc_pl_get_sort' ) || 'relevance' === dmc_pl_get_sort() ) {
		$query->set( 'orderby', 'post__in' );
	}
}
add_action( 'pre_get_posts', 'dmc_product_search_pre_get_posts', 20 );

/**
 * Search published products by title/SKU.
 *
 * @param string $keyword Search term.
 * @return array<int, array{id: int, name: string, url: string, price: array{current: string, regular: string}, image: string}>
 */
function dmc_live_search_products( $keyword ) {
	$ids = dmc_search_product_ids( $keyword, 8 );

	if ( empty( $ids ) ) {
		return [];
	}

	$results = [];

	foreach ( $ids as $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			continue;
		}

		$image_id = $product->get_image_id();
		$image    = $image_id
			? wp_get_attachment_image_url( $image_id, 'woocommerce_thumbnail' )
			: wc_placeholder_img_src( 'woocommerce_thumbnail' );

		$results[] = [
			'id'    => $product->get_id(),
			'name'  => $product->get_name(),
			'url'   => get_permalink( $product->get_id() ),
			'price' => dmc_live_search_product_price( $product ),
			'image' => $image ? $image : '',
		];
	}

	return $results;
}
