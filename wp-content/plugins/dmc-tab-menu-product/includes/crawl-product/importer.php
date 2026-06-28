<?php
/**
 * Crawl product — create WooCommerce product from crawled data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Download remote image into media library.
 *
 * @param string   $url     Image URL.
 * @param int      $post_id Attach to post.
 * @param string[] $cache   Optional URL => attachment ID cache (by reference).
 * @return int|WP_Error Attachment ID.
 */
function dmc_tmp_crawl_sideload_image( $url, $post_id = 0, array &$cache = [] ) {
	$url = esc_url_raw( trim( (string) $url ) );
	if ( '' === $url ) {
		return new WP_Error( 'empty_image', __( 'URL ảnh trống.', 'dmc-tab-menu-product' ) );
	}

	$cache_key = function_exists( 'dmc_tmp_crawl_image_dedupe_key' )
		? dmc_tmp_crawl_image_dedupe_key( $url )
		: strtolower( $url );

	if ( isset( $cache[ $cache_key ] ) ) {
		return (int) $cache[ $cache_key ];
	}

	if ( ! function_exists( 'media_sideload_image' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	$attachment_id = media_sideload_image( $url, $post_id, null, 'id' );

	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}

	$attachment_id = (int) $attachment_id;
	$cache[ $cache_key ] = $attachment_id;

	return $attachment_id;
}

/**
 * Resolve or create product category terms from comma-separated names.
 *
 * @param string $raw Comma-separated category names.
 * @return int[] Term IDs.
 */
function dmc_tmp_crawl_resolve_categories( $raw ) {
	$names = array_filter(
		array_map(
			static function ( $name ) {
				return trim( (string) $name );
			},
			preg_split( '/\s*,\s*/', (string) $raw ) ?: []
		)
	);

	if ( empty( $names ) ) {
		return [];
	}

	$ids = [];
	foreach ( $names as $name ) {
		$term = term_exists( $name, 'product_cat' );
		if ( ! $term ) {
			$term = wp_insert_term( $name, 'product_cat' );
		}
		if ( is_wp_error( $term ) ) {
			continue;
		}
		$ids[] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Resolve or create brand term.
 *
 * @param string $brand_name Brand name.
 * @return int 0 if unavailable.
 */
function dmc_tmp_crawl_resolve_brand( $brand_name ) {
	$brand_name = trim( (string) $brand_name );
	if ( '' === $brand_name ) {
		return 0;
	}

	$taxonomy = function_exists( 'dmc_tmp_brand_taxonomy' ) ? dmc_tmp_brand_taxonomy() : '';
	if ( ! $taxonomy ) {
		if ( taxonomy_exists( 'product_brand' ) ) {
			$taxonomy = 'product_brand';
		} elseif ( taxonomy_exists( 'pwb-brand' ) ) {
			$taxonomy = 'pwb-brand';
		}
	}

	if ( ! $taxonomy ) {
		return 0;
	}

	$term = term_exists( $brand_name, $taxonomy );
	if ( ! $term ) {
		$term = wp_insert_term( $brand_name, $taxonomy );
	}

	if ( is_wp_error( $term ) ) {
		return 0;
	}

	return (int) ( is_array( $term ) ? $term['term_id'] : $term );
}

/**
 * Sanitize submitted crawl form payload.
 *
 * @param array<string, mixed> $input Raw POST fields.
 * @return array<string, mixed>
 */
function dmc_tmp_crawl_sanitize_form( array $input ) {
	$data = [];

	$data['name']              = sanitize_text_field( $input['name'] ?? '' );
	$data['sku']               = sanitize_text_field( $input['sku'] ?? '' );
	$data['short_description'] = wp_kses_post( $input['short_description'] ?? '' );
	$data['description']       = wp_kses_post( $input['description'] ?? '' );
	$data['regular_price']     = wc_format_decimal( $input['regular_price'] ?? '' );
	$data['sale_price']        = wc_format_decimal( $input['sale_price'] ?? '' );
	$data['categories']        = sanitize_text_field( $input['categories'] ?? '' );
	$data['brand']             = sanitize_text_field( $input['brand'] ?? '' );
	$data['featured_image']    = esc_url_raw( $input['featured_image'] ?? '' );
	$data['gallery_images']    = sanitize_textarea_field( $input['gallery_images'] ?? '' );
	$data['weight']            = wc_format_decimal( $input['weight'] ?? '' );
	$data['length']            = wc_format_decimal( $input['length'] ?? '' );
	$data['width']             = wc_format_decimal( $input['width'] ?? '' );
	$data['height']            = wc_format_decimal( $input['height'] ?? '' );
	$data['stock_status']      = sanitize_key( $input['stock_status'] ?? 'instock' );
	$data['status']            = sanitize_key( $input['status'] ?? 'draft' );
	$data['pl_technical_specs'] = wp_kses_post( $input['pl_technical_specs'] ?? '' );
	$data['pl_delivery_type']  = sanitize_key( $input['pl_delivery_type'] ?? 'motorbike' );
	$data['source_url']        = esc_url_raw( $input['source_url'] ?? '' );

	$allowed_stock = [ 'instock', 'outofstock', 'onbackorder' ];
	if ( ! in_array( $data['stock_status'], $allowed_stock, true ) ) {
		$data['stock_status'] = 'instock';
	}

	$allowed_status = [ 'draft', 'publish', 'pending', 'private' ];
	if ( ! in_array( $data['status'], $allowed_status, true ) ) {
		$data['status'] = 'draft';
	}

	if ( ! in_array( $data['pl_delivery_type'], [ 'motorbike', 'car' ], true ) ) {
		$data['pl_delivery_type'] = 'motorbike';
	}

	return $data;
}

/**
 * Validate required fields before import.
 *
 * @param array<string, mixed> $data Sanitized data.
 * @return string[] Missing required field keys.
 */
function dmc_tmp_crawl_validate_import( array $data ) {
	$defs   = dmc_tmp_crawl_field_definitions();
	$errors = [];

	foreach ( $defs as $key => $def ) {
		if ( empty( $def['required'] ) ) {
			continue;
		}
		if ( ! dmc_tmp_crawl_field_has_value( $data[ $key ] ?? '' ) ) {
			$errors[] = $key;
		}
	}

	return $errors;
}

/**
 * Create WooCommerce simple product.
 *
 * @param array<string, mixed> $data Sanitized form data.
 * @return array{product_id:int,edit_url:string}|WP_Error
 */
function dmc_tmp_crawl_create_product( array $data ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return new WP_Error( 'no_wc', __( 'WooCommerce chưa được kích hoạt.', 'dmc-tab-menu-product' ) );
	}

	$missing = dmc_tmp_crawl_validate_import( $data );
	if ( ! empty( $missing ) ) {
		$labels = [];
		$defs   = dmc_tmp_crawl_field_definitions();
		foreach ( $missing as $key ) {
			$labels[] = $defs[ $key ]['label'] ?? $key;
		}
		return new WP_Error(
			'missing_required',
			sprintf(
				/* translators: %s: comma-separated field labels */
				__( 'Thiếu trường bắt buộc: %s', 'dmc-tab-menu-product' ),
				implode( ', ', $labels )
			),
			[ 'fields' => $missing ]
		);
	}

	// Duplicate SKU check.
	if ( '' !== $data['sku'] ) {
		$existing_id = wc_get_product_id_by_sku( $data['sku'] );
		if ( $existing_id ) {
			return new WP_Error(
				'duplicate_sku',
				sprintf(
					/* translators: 1: SKU, 2: product edit URL */
					__( 'SKU %1$s đã tồn tại (sản phẩm #%2$d).', 'dmc-tab-menu-product' ),
					esc_html( $data['sku'] ),
					$existing_id
				),
				[ 'product_id' => $existing_id ]
			);
		}
	}

	$product = new WC_Product_Simple();
	$product->set_name( dmc_tmp_crawl_normalize_title( $data['name'] ) );
	$product->set_status( $data['status'] );
	$product->set_catalog_visibility( 'visible' );
	$product->set_description( $data['description'] );
	$product->set_short_description( $data['short_description'] );
	$product->set_sku( $data['sku'] );
	$product->set_regular_price( $data['regular_price'] );

	if ( '' !== $data['sale_price'] && (float) $data['sale_price'] < (float) $data['regular_price'] ) {
		$product->set_sale_price( $data['sale_price'] );
	}

	$product->set_stock_status( $data['stock_status'] );

	if ( '' !== $data['weight'] ) {
		$product->set_weight( $data['weight'] );
	}
	if ( '' !== $data['length'] ) {
		$product->set_length( $data['length'] );
	}
	if ( '' !== $data['width'] ) {
		$product->set_width( $data['width'] );
	}
	if ( '' !== $data['height'] ) {
		$product->set_height( $data['height'] );
	}

	$product_id = $product->save();
	if ( ! $product_id ) {
		return new WP_Error( 'save_failed', __( 'Không thể lưu sản phẩm.', 'dmc-tab-menu-product' ) );
	}

	// Categories.
	$cat_ids = dmc_tmp_crawl_resolve_categories( $data['categories'] );
	if ( ! empty( $cat_ids ) ) {
		wp_set_object_terms( $product_id, $cat_ids, 'product_cat' );
	}

	// Brand.
	$brand_id = dmc_tmp_crawl_resolve_brand( $data['brand'] );
	if ( $brand_id ) {
		$taxonomy = function_exists( 'dmc_tmp_brand_taxonomy' ) ? dmc_tmp_brand_taxonomy() : 'product_brand';
		if ( $taxonomy ) {
			wp_set_object_terms( $product_id, [ $brand_id ], $taxonomy );
		}
	}

	// Featured image + product gallery (WooCommerce album).
	$gallery_urls = array_filter(
		array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $data['gallery_images'] ) ?: [] )
	);
	$sideload_cache = [];
	$featured_id    = 0;

	if ( '' !== $data['featured_image'] ) {
		$thumb_id = dmc_tmp_crawl_sideload_image( $data['featured_image'], $product_id, $sideload_cache );
		if ( ! is_wp_error( $thumb_id ) ) {
			$featured_id = (int) $thumb_id;
			$product->set_image_id( $featured_id );
		}
	}

	$gallery_ids = [];
	foreach ( $gallery_urls as $gallery_url ) {
		$gid = dmc_tmp_crawl_sideload_image( $gallery_url, $product_id, $sideload_cache );
		if ( is_wp_error( $gid ) ) {
			continue;
		}
		$gid = (int) $gid;
		if ( $gid === $featured_id ) {
			continue;
		}
		$gallery_ids[] = $gid;
	}

	$gallery_ids = array_values( array_unique( $gallery_ids ) );
	if ( ! empty( $gallery_ids ) ) {
		$product->set_gallery_image_ids( $gallery_ids );
	}

	$product->save();

	// ACF fields.
	if ( function_exists( 'update_field' ) ) {
		if ( '' !== $data['pl_technical_specs'] ) {
			update_field( 'pl_technical_specs', $data['pl_technical_specs'], $product_id );
		}
		update_field( 'pl_delivery_type', $data['pl_delivery_type'], $product_id );
	} else {
		if ( '' !== $data['pl_technical_specs'] ) {
			update_post_meta( $product_id, 'pl_technical_specs', $data['pl_technical_specs'] );
		}
		update_post_meta( $product_id, 'pl_delivery_type', $data['pl_delivery_type'] );
	}

	if ( '' !== $data['source_url'] ) {
		update_post_meta( $product_id, '_dmc_crawl_source_url', $data['source_url'] );
	}

	return [
		'product_id' => $product_id,
		'edit_url'   => get_edit_post_link( $product_id, 'raw' ),
		'view_url'   => get_permalink( $product_id ),
	];
}
