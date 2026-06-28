<?php
/**
 * ACF — Setting Web (logo & thông tin web).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Setting Web options page.
 */
function dmc_web_settings_register_options_page() {
	if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
		return;
	}

	acf_add_options_sub_page(
		[
			'page_title'  => 'Setting Web',
			'menu_title'  => 'Setting Web',
			'menu_slug'   => 'theme-web-settings',
			'parent_slug' => 'theme-general-settings',
			'capability'  => 'edit_posts',
			'position'    => 0,
		]
	);
}
add_action( 'acf/init', 'dmc_web_settings_register_options_page' );

/**
 * Register Setting Web field group.
 */
function dmc_web_settings_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'      => 'group_dmc_web_settings',
			'title'    => 'Setting Web',
			'fields'   => [
				[
					'key'   => 'field_dmc_web_tab_logo',
					'label' => 'Logo & Thương hiệu',
					'type'  => 'tab',
				],
				[
					'key'           => 'field_dmc_web_logo',
					'label'         => 'Logo website (dự phòng)',
					'name'          => 'web_logo',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
					'library'       => 'all',
					'instructions'  => 'Logo mặc định lấy từ Flatsome: Appearance → Customize → Header → Logo & Site Identity. Chỉ upload ở đây khi muốn ghi đè (Flatsome chưa cấu hình logo).',
				],
				[
					'key'           => 'field_dmc_web_logo_mobile',
					'label'         => 'Logo mobile (tuỳ chọn)',
					'name'          => 'web_logo_mobile',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
					'library'       => 'all',
					'instructions'  => 'Để trống sẽ dùng logo Flatsome (hoặc Sticky logo nếu có).',
				],
				[
					'key'           => 'field_dmc_web_logo_show_text',
					'label'         => 'Hiển thị tên website bên cạnh logo',
					'name'          => 'web_logo_show_text',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				],
				[
					'key'               => 'field_dmc_web_logo_text',
					'label'             => 'Tên hiển thị (tuỳ chọn)',
					'name'              => 'web_logo_text',
					'type'              => 'text',
					'instructions'      => 'Để trống sẽ lấy tên site WordPress.',
					'conditional_logic' => [
						[
							[
								'field'    => 'field_dmc_web_logo_show_text',
								'operator' => '==',
								'value'    => '1',
							],
						],
					],
				],
				[
					'key'   => 'field_dmc_web_tab_brands',
					'label' => 'Thương hiệu nổi bật',
					'type'  => 'tab',
				],
				[
					'key'          => 'field_dmc_web_featured_brands',
					'label'        => 'Thương hiệu nổi bật',
					'name'         => 'web_featured_brands',
					'type'         => 'repeater',
					'instructions' => 'Upload logo thương hiệu hiển thị trên trang chủ. Ảnh sẽ tự co giãn vừa khung (object-fit: contain). Khuyến nghị: PNG/JPG nền trắng hoặc trong suốt.',
					'layout'       => 'block',
					'button_label' => 'Thêm thương hiệu',
					'min'          => 0,
					'max'          => 12,
					'sub_fields'   => [
						[
							'key'           => 'field_dmc_web_brand_image',
							'label'         => 'Hình ảnh',
							'name'          => 'brand_image',
							'type'          => 'image',
							'return_format' => 'array',
							'preview_size'  => 'medium',
							'library'       => 'all',
							'required'      => 1,
						],
						[
							'key'          => 'field_dmc_web_brand_link',
							'label'        => 'Liên kết (tuỳ chọn)',
							'name'         => 'brand_link',
							'type'         => 'url',
							'instructions' => 'VD: trang danh mục thương hiệu hoặc bộ lọc sản phẩm.',
						],
						[
							'key'          => 'field_dmc_web_brand_name',
							'label'        => 'Tên thương hiệu (tuỳ chọn)',
							'name'         => 'brand_name',
							'type'         => 'text',
							'instructions' => 'Dùng cho alt text và accessibility. Để trống sẽ lấy từ ảnh.',
						],
					],
				],
				[
					'key'   => 'field_dmc_web_tab_contact',
					'label' => 'Liên hệ',
					'type'  => 'tab',
				],
				[
					'key'           => 'field_dmc_web_hotline',
					'label'         => 'Hotline',
					'name'          => 'web_hotline',
					'type'          => 'text',
					'default_value' => '1900 2323 88',
				],
				[
					'key'           => 'field_dmc_web_hotline_hours',
					'label'         => 'Giờ hỗ trợ',
					'name'          => 'web_hotline_hours',
					'type'          => 'text',
					'default_value' => '8:00 - 21:00',
				],
			],
			'location' => [
				[
					[
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'theme-web-settings',
					],
				],
			],
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'active'                => true,
		]
	);
}
add_action( 'acf/init', 'dmc_web_settings_register_acf_fields' );
