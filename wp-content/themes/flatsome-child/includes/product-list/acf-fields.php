<?php
/**
 * ACF — Add more detail product (WooCommerce admin only).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register product detail ACF fields in admin.
 */
function dmc_product_detail_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'                   => 'group_dmc_product_detail',
			'title'                 => 'Add more detail product',
			'fields'                => dmc_product_detail_acf_field_definitions(),
			'location'              => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'product',
					],
				],
			],
			'menu_order'            => 25,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		]
	);
}
add_action( 'acf/init', 'dmc_product_detail_register_acf_fields' );

/**
 * Deactivate legacy ACF field groups on product edit screen.
 *
 * @param array<string, mixed> $field_group Field group.
 * @return array<string, mixed>
 */
function dmc_product_detail_deactivate_legacy_field_groups( $field_group ) {
	if ( ! is_array( $field_group ) ) {
		return $field_group;
	}

	$inactive_keys = [
		'group_dmc_product_card',   // Moved into Add more detail product.
		'group_6072684522264',      // Promotion Loop Item (Promotion, Promotion Img, Note Product).
	];

	if ( in_array( $field_group['key'] ?? '', $inactive_keys, true ) ) {
		$field_group['active'] = false;
	}

	return $field_group;
}
add_filter( 'acf/load_field_group', 'dmc_product_detail_deactivate_legacy_field_groups' );
add_filter( 'acf/load_field_group/key=group_dmc_product_card', 'dmc_product_detail_deactivate_legacy_field_groups' );
add_filter( 'acf/load_field_group/key=group_6072684522264', 'dmc_product_detail_deactivate_legacy_field_groups' );

/**
 * ACF field definitions with left tabs (admin).
 *
 * @return array<int, array<string, mixed>>
 */
function dmc_product_detail_acf_field_definitions() {
	return [
		[
			'key'       => 'field_pl_admin_tab_info',
			'label'     => 'Chi tiết',
			'name'      => '',
			'type'      => 'tab',
			'placement' => 'left',
			'endpoint'  => 0,
		],
		[
			'key'           => 'field_pl_delivery_type',
			'label'         => 'Loại giao hàng',
			'name'          => 'pl_delivery_type',
			'type'          => 'select',
			'choices'       => [
				'motorbike' => 'Xe máy',
				'car'       => 'Ô tô',
			],
			'default_value' => 'motorbike',
			'ui'            => 1,
			'required'      => 1,
			'instructions'  => 'Chọn phương tiện giao hàng cho sản phẩm này. Phí tương ứng lấy từ cài đặt Flatsome → Product → Giao hàng.',
		],
		[
			'key'       => 'field_pl_admin_tab_gifts',
			'label'     => 'Quà Tặng & Khuyến Mãi',
			'name'      => '',
			'type'      => 'tab',
			'placement' => 'left',
			'endpoint'  => 0,
		],
		[
			'key'           => 'field_pl_gift_enable',
			'label'         => 'Có quà tặng kèm',
			'name'          => 'pl_gift_enable',
			'type'          => 'true_false',
			'ui'            => 1,
			'default_value' => 0,
			'instructions'  => 'Bật để thêm hình quà tặng hiển thị trên trang sản phẩm (góc gallery 80×80 và slide cuối).',
		],
		[
			'key'               => 'field_pl_gift_products',
			'label'             => 'Sản phẩm tặng kèm',
			'name'              => 'pl_gift_products',
			'type'              => 'repeater',
			'instructions'      => 'Thêm hình và mô tả quà tặng đi kèm.',
			'layout'            => 'block',
			'button_label'      => 'Thêm quà tặng',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_pl_gift_enable',
						'operator' => '==',
						'value'    => '1',
					],
				],
			],
			'sub_fields'        => [
				[
					'key'           => 'field_pl_gift_image',
					'label'         => 'Hình ảnh quà tặng',
					'name'          => 'gift_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'thumbnail',
					'library'       => 'all',
					'required'      => 1,
				],
				[
					'key'   => 'field_pl_gift_title',
					'label' => 'Tên quà tặng',
					'name'  => 'gift_title',
					'type'  => 'text',
				],
				[
					'key'   => 'field_pl_gift_description',
					'label' => 'Mô tả quà tặng',
					'name'  => 'gift_description',
					'type'  => 'textarea',
					'rows'  => 3,
				],
			],
		],
		[
			'key'       => 'field_pl_admin_tab_specs',
			'label'     => 'Thông số kỹ thuật',
			'name'      => '',
			'type'      => 'tab',
			'placement' => 'left',
			'endpoint'  => 0,
		],
		[
			'key'       => 'field_pl_technical_specs_toolbar',
			'label'     => '',
			'name'      => '',
			'type'      => 'message',
			'message'   => '<div class="dmc-specs-format-bar"><button type="button" class="button button-primary" id="dmc-format-specs-btn">Format</button><span class="description">ul/li → table · table → xóa class &amp; style, giữ <code>thong-so-ky-thuat</code></span></div>',
			'new_lines' => '',
		],
		[
			'key'          => 'field_pl_technical_specs',
			'label'        => 'Bảng thông số kỹ thuật',
			'name'         => 'pl_technical_specs',
			'type'         => 'wysiwyg',
			'instructions' => 'Dán HTML (table hoặc ul/li), bấm nút Format để chuẩn hóa trước khi lưu.',
			'tabs'         => 'all',
			'toolbar'      => 'full',
			'media_upload' => 0,
			'delay'        => 0,
		],
		[
			'key'       => 'field_pl_admin_tab_card',
			'label'     => 'Hiển thị (Card)',
			'name'      => '',
			'type'      => 'tab',
			'placement' => 'left',
			'endpoint'  => 0,
		],
		...dmc_product_detail_card_field_definitions(),
	];
}

/**
 * Product card display fields (listing / homepage card).
 *
 * @return array<int, array<string, mixed>>
 */
function dmc_product_detail_card_field_definitions() {
	return [
		[
			'key'           => 'field_pl_discount_overlay',
			'label'         => 'Hình discount tự thiết kế',
			'name'          => 'pl_discount_overlay',
			'type'          => 'image',
			'return_format' => 'array',
			'preview_size'  => 'medium',
			'library'       => 'all',
			'instructions'  => 'Upload banner/khung giảm giá hiển thị đè lên ảnh sản phẩm (phía dưới ảnh). Chỉ hiện khi sản phẩm đang giảm giá hoặc có hình upload.',
		],
		[
			'key'           => 'field_pl_price_label',
			'label'         => 'Nhãn giá trên card',
			'name'          => 'pl_price_label',
			'type'          => 'select',
			'choices'       => [
				'Giá khuyến mãi:' => 'Giá khuyến mãi:',
				'Rẻ hơn:'         => 'Rẻ hơn:',
				'Giá chỉ:'        => 'Giá chỉ:',
			],
			'default_value' => 'Giá khuyến mãi:',
			'allow_null'    => 0,
			'ui'            => 1,
		],
		[
			'key'          => 'field_pl_gift_value',
			'label'        => 'Quà tặng trị giá',
			'name'         => 'pl_gift_value',
			'type'         => 'text',
			'instructions' => 'VD: 32.000.000đ — hiển thị dòng "Quà tặng trị giá ..." trên card.',
			'placeholder'  => '32.000.000đ',
		],
		[
			'key'           => 'field_pl_show_call_cta',
			'label'         => 'Hiện dòng gọi hotline giảm thêm',
			'name'          => 'pl_show_call_cta',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
		],
	];
}

/**
 * Admin styles & scripts for Add more detail product metabox.
 */
function dmc_product_detail_admin_assets() {
	static $done = false;

	if ( $done ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || 'product' !== $screen->post_type ) {
		return;
	}

	$done = true;

	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$js_file   = $theme_dir . '/assets/js/product-detail-admin.js';

	wp_enqueue_script(
		'dmc-product-detail-admin',
		$theme_uri . '/assets/js/product-detail-admin.js',
		[ 'jquery' ],
		file_exists( $js_file ) ? filemtime( $js_file ) : '1.0.0',
		true
	);

	wp_localize_script(
		'dmc-product-detail-admin',
		'dmcProductDetailAdmin',
		[
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'dmc_format_specs' ),
		]
	);

	$sidebar_width = 220;

	$css = '
		#acf-group_dmc_product_detail .acf-fields.-sidebar {
			padding-left: ' . $sidebar_width . 'px !important;
		}

		#acf-group_dmc_product_detail .acf-fields.-sidebar:before {
			width: ' . $sidebar_width . 'px;
			background: #f6f7f7;
			border-right-color: #c3c4c7;
		}

		#acf-group_dmc_product_detail .acf-field.acf-field-tab {
			display: none !important;
		}

		#acf-group_dmc_product_detail .acf-tab-wrap.-left {
			width: ' . $sidebar_width . 'px;
			max-width: ' . $sidebar_width . 'px;
			margin: 0;
			padding: 0;
			box-sizing: border-box;
			overflow: hidden;
			background: transparent;
		}

		#acf-group_dmc_product_detail .acf-fields.-sidebar > .acf-tab-wrap.-left .acf-tab-group {
			position: absolute;
			left: 0;
			top: 0;
			width: ' . $sidebar_width . 'px;
			max-width: ' . $sidebar_width . 'px;
			border: 0;
			margin: 0;
			padding: 0 !important;
			box-sizing: border-box;
		}

		#acf-group_dmc_product_detail .acf-tab-wrap.-left .acf-tab-group li {
			width: 100%;
			max-width: 100%;
			margin: 0;
			float: none;
			border: 0;
		}

		#acf-group_dmc_product_detail .acf-tab-wrap.-left .acf-tab-group li a {
			display: block;
			width: 100%;
			max-width: 100%;
			padding: 11px 14px;
			font-weight: 600;
			border: 0;
			border-left: 3px solid transparent;
			border-bottom: 1px solid #e2e4e7;
			border-radius: 0;
			box-sizing: border-box;
			white-space: normal;
			line-height: 1.35;
			margin: 0 !important;
			background: transparent;
			color: #1d2327;
		}

		#acf-group_dmc_product_detail .acf-tab-wrap.-left .acf-tab-group li a:hover {
			color: #2271b1;
		}

		#acf-group_dmc_product_detail .acf-tab-wrap.-left .acf-tab-group li.active a {
			background: #fff;
			border-left-color: #2271b1;
			color: #1d2327;
			margin-right: 0 !important;
		}

		#acf-group_dmc_product_detail .acf-tab-wrap.-left:not(:first-of-type) {
			display: none !important;
		}

		#acf-group_dmc_product_detail .acf-field.acf-hidden {
			display: none !important;
		}

		#acf-group_dmc_product_detail .acf-field > .acf-input {
			max-width: 100%;
		}

		#acf-group_dmc_product_detail .acf-field .wp-editor-wrap,
		#acf-group_dmc_product_detail .acf-field .wp-editor-container {
			max-width: 100%;
		}

		#acf-group_dmc_product_detail .acf-field[data-key="field_pl_technical_specs_toolbar"] > .acf-label {
			display: none;
		}

		#acf-group_dmc_product_detail .acf-field[data-key="field_pl_technical_specs_toolbar"] > .acf-input {
			width: 100%;
		}

		#acf-group_dmc_product_detail .dmc-specs-format-bar {
			display: flex;
			align-items: center;
			flex-wrap: wrap;
			gap: 10px 14px;
			margin: 0;
			padding: 10px 12px;
			background: #f6f7f7;
			border: 1px solid #dcdcde;
			border-radius: 4px;
		}

		#acf-group_dmc_product_detail .dmc-specs-format-bar .description {
			margin: 0;
			font-style: normal;
			color: #646970;
		}

		#acf-group_dmc_product_detail .dmc-specs-format-bar code {
			font-size: 12px;
		}

		#acf-group_dmc_product_detail #dmc-format-specs-btn {
			min-width: 90px;
		}
	';

	wp_register_style( 'dmc-product-detail-admin', false, [], '1.1.0' );
	wp_enqueue_style( 'dmc-product-detail-admin' );
	wp_add_inline_style( 'dmc-product-detail-admin', $css );
}
add_action( 'acf/input/admin_enqueue_scripts', 'dmc_product_detail_admin_assets' );
add_action( 'admin_enqueue_scripts', 'dmc_product_detail_admin_assets' );

/**
 * AJAX — format technical specs HTML in admin (Format button).
 */
function dmc_ajax_format_technical_specs() {
	check_ajax_referer( 'dmc_format_specs', 'nonce' );

	if ( ! current_user_can( 'edit_products' ) ) {
		wp_send_json_error( [ 'message' => __( 'Bạn không có quyền thực hiện thao tác này.', 'flatsome-child' ) ] );
	}

	$html = isset( $_POST['html'] ) ? wp_unslash( $_POST['html'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	if ( '' === trim( $html ) ) {
		wp_send_json_error( [ 'message' => __( 'Chưa có nội dung để format.', 'flatsome-child' ) ] );
	}

	$formatted = dmc_pl_normalize_technical_specs_html( $html );

	if ( false === stripos( $formatted, '<table' ) ) {
		wp_send_json_error(
			[
				'message' => __( 'Không nhận diện được bảng hoặc danh sách thông số (ul/li).', 'flatsome-child' ),
			]
		);
	}

	wp_send_json_success( [ 'html' => $formatted ] );
}
add_action( 'wp_ajax_dmc_format_technical_specs', 'dmc_ajax_format_technical_specs' );
