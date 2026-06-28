<?php
/**
 * Crawl product — field definitions for WooCommerce + ACF mapping.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All fields shown in crawl preview / import form.
 *
 * @return array<string, array{label:string,group:string,required:bool,type:string}>
 */
function dmc_tmp_crawl_field_definitions() {
	return [
		'name'               => [
			'label'    => __( 'Tên sản phẩm', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => true,
			'type'     => 'text',
		],
		'sku'                => [
			'label'    => __( 'SKU / Model', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => true,
			'type'     => 'text',
		],
		'short_description'  => [
			'label'    => __( 'Mô tả ngắn', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => false,
			'type'     => 'textarea',
		],
		'description'        => [
			'label'    => __( 'Mô tả chi tiết (HTML)', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => false,
			'type'     => 'textarea',
		],
		'regular_price'      => [
			'label'    => __( 'Giá gốc (đ)', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => true,
			'type'     => 'number',
		],
		'sale_price'         => [
			'label'    => __( 'Giá khuyến mãi (đ)', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => false,
			'type'     => 'number',
		],
		'categories'         => [
			'label'    => __( 'Danh mục (phân cách bằng dấu phẩy)', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => false,
			'type'     => 'text',
		],
		'brand'              => [
			'label'    => __( 'Thương hiệu', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => false,
			'type'     => 'text',
		],
		'featured_image'     => [
			'label'    => __( 'Ảnh đại diện (URL)', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => false,
			'type'     => 'url',
		],
		'gallery_images'     => [
			'label'    => __( 'Thư viện ảnh sản phẩm (URL, mỗi dòng 1 ảnh)', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => false,
			'type'     => 'textarea',
		],
		'weight'             => [
			'label'    => __( 'Cân nặng (kg)', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => false,
			'type'     => 'text',
		],
		'length'             => [
			'label'    => __( 'Dài (cm)', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => false,
			'type'     => 'text',
		],
		'width'              => [
			'label'    => __( 'Rộng (cm)', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => false,
			'type'     => 'text',
		],
		'height'             => [
			'label'    => __( 'Cao (cm)', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => false,
			'type'     => 'text',
		],
		'stock_status'       => [
			'label'    => __( 'Tình trạng kho', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => true,
			'type'     => 'select',
			'choices'  => [
				'instock'     => __( 'Còn hàng', 'dmc-tab-menu-product' ),
				'outofstock'  => __( 'Hết hàng', 'dmc-tab-menu-product' ),
				'onbackorder' => __( 'Đặt trước', 'dmc-tab-menu-product' ),
			],
		],
		'status'             => [
			'label'    => __( 'Trạng thái đăng', 'dmc-tab-menu-product' ),
			'group'    => 'woocommerce',
			'required' => true,
			'type'     => 'select',
			'choices'  => [
				'draft'   => __( 'Nháp', 'dmc-tab-menu-product' ),
				'publish' => __( 'Xuất bản', 'dmc-tab-menu-product' ),
			],
		],
		'pl_technical_specs' => [
			'label'    => __( 'Thông số kỹ thuật (HTML)', 'dmc-tab-menu-product' ),
			'group'    => 'acf',
			'required' => false,
			'type'     => 'textarea',
		],
		'pl_delivery_type'   => [
			'label'    => __( 'Loại giao hàng', 'dmc-tab-menu-product' ),
			'group'    => 'acf',
			'required' => true,
			'type'     => 'select',
			'choices'  => [
				'motorbike' => __( 'Xe máy', 'dmc-tab-menu-product' ),
				'car'       => __( 'Ô tô', 'dmc-tab-menu-product' ),
			],
		],
		'source_url'         => [
			'label'    => __( 'Link nguồn', 'dmc-tab-menu-product' ),
			'group'    => 'meta',
			'required' => false,
			'type'     => 'url',
		],
	];
}

/**
 * Normalize crawled values to flat strings for form display.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function dmc_tmp_crawl_value_to_string( $value ) {
	if ( is_array( $value ) ) {
		if ( isset( $value[0] ) && is_string( $value[0] ) ) {
			return implode( "\n", array_filter( array_map( 'strval', $value ) ) );
		}

		return wp_json_encode( $value );
	}

	return is_scalar( $value ) ? (string) $value : '';
}

/**
 * Whether a crawled field counts as "has data".
 *
 * @param mixed $value Field value.
 */
function dmc_tmp_crawl_field_has_value( $value ) {
	if ( is_array( $value ) ) {
		return ! empty( array_filter( $value, static function ( $item ) {
			return '' !== trim( (string) $item );
		} ) );
	}

	return '' !== trim( (string) $value );
}

/**
 * Build status summary for crawled data.
 *
 * @param array<string, mixed> $data Crawled data keyed by field.
 * @return array{filled: string[], missing: string[], required_missing: string[]}
 */
function dmc_tmp_crawl_status_summary( array $data ) {
	$defs              = dmc_tmp_crawl_field_definitions();
	$filled            = [];
	$missing           = [];
	$required_missing  = [];

	foreach ( $defs as $key => $def ) {
		if ( 'meta' === $def['group'] && 'source_url' === $key ) {
			if ( dmc_tmp_crawl_field_has_value( $data[ $key ] ?? '' ) ) {
				$filled[] = $key;
			}
			continue;
		}

		$value = $data[ $key ] ?? '';
		if ( dmc_tmp_crawl_field_has_value( $value ) ) {
			$filled[] = $key;
		} else {
			$missing[] = $key;
			if ( ! empty( $def['required'] ) ) {
				$required_missing[] = $key;
			}
		}
	}

	return compact( 'filled', 'missing', 'required_missing' );
}
