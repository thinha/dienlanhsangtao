<?php
/**
 * ACF fields — Cấu hình Trang chủ (WooCommerce).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Homepage options page.
 */
function dmc_homepage_register_options_page() {
	if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
		return;
	}

	acf_add_options_sub_page(
		[
			'page_title'  => 'Cấu hình Trang chủ',
			'menu_title'  => 'Trang chủ',
			'menu_slug'   => 'theme-homepage-settings',
			'parent_slug' => 'theme-general-settings',
			'capability'  => 'edit_posts',
			'position'    => 1,
		]
	);
}
add_action( 'acf/init', 'dmc_homepage_register_options_page' );

/**
 * ACF JSON save/load paths.
 */
function dmc_homepage_acf_json_save( $path ) {
	return get_stylesheet_directory() . '/acf-json';
}
add_filter( 'acf/settings/save_json', 'dmc_homepage_acf_json_save' );

function dmc_homepage_acf_json_load( $paths ) {
	$paths[] = get_stylesheet_directory() . '/acf-json';
	return $paths;
}
add_filter( 'acf/settings/load_json', 'dmc_homepage_acf_json_load' );

/**
 * Register field group.
 */
function dmc_homepage_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'      => 'group_dmc_homepage',
			'title'    => 'Cấu hình Trang chủ',
			'fields'   => dmc_homepage_get_acf_field_definitions(),
			'location' => [
				[
					[
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'theme-homepage-settings',
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
add_action( 'acf/init', 'dmc_homepage_register_acf_fields' );

/**
 * Field definitions.
 */
function dmc_homepage_get_acf_field_definitions() {
	return [
		// Categories
		[
			'key'   => 'field_dmc_tab_categories',
			'label' => 'Danh mục nổi bật',
			'type'  => 'tab',
		],
		[
			'key'           => 'field_dmc_hp_cat_enable',
			'label'         => 'Hiển thị danh mục',
			'name'          => 'hp_categories_enable',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
		],
		[
			'key'               => 'field_dmc_hp_cat_limit',
			'label'             => 'Số danh mục hiển thị',
			'name'              => 'hp_categories_limit',
			'type'              => 'number',
			'default_value'     => 9,
			'min'               => 4,
			'max'               => 12,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_dmc_hp_cat_enable',
						'operator' => '==',
						'value'    => '1',
					],
				],
			],
		],

		// Flash sale — managed in plugin (Flatsome → Homepage).
		[
			'key'   => 'field_dmc_tab_flash',
			'label' => 'Flash Sale',
			'type'  => 'tab',
		],
		[
			'key'     => 'field_dmc_hp_flash_notice',
			'label'   => 'Cấu hình Flash Sale',
			'name'    => '',
			'type'    => 'message',
			'message' => 'Khối Flash Sale (countdown + sản phẩm giảm giá) được cấu hình tại <strong>Flatsome → Homepage → Flash Sale</strong> (plugin DMC Tab Menu Product).',
		],

		// Product sections — managed in plugin (Flatsome → Homepage).
		[
			'key'   => 'field_dmc_tab_products',
			'label' => 'Khối sản phẩm',
			'type'  => 'tab',
		],
		[
			'key'     => 'field_dmc_hp_product_sections_notice',
			'label'   => 'Cấu hình khối sản phẩm',
			'name'    => '',
			'type'    => 'message',
			'message' => 'Các khối "Sản phẩm bán chạy", "Gợi ý cho bạn"... được cấu hình tại <strong>Flatsome → Homepage → Khối sản phẩm</strong> (plugin DMC Tab Menu Product).',
		],

		// Other blocks
		[
			'key'   => 'field_dmc_tab_blocks',
			'label' => 'Khối khác',
			'type'  => 'tab',
		],
		[
			'key'           => 'field_dmc_hp_vouchers_enable',
			'label'         => 'Hiển thị Voucher',
			'name'          => 'hp_vouchers_enable',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
		],
		[
			'key'           => 'field_dmc_hp_services_enable',
			'label'         => 'Hiển thị Dịch vụ / Cam kết',
			'name'          => 'hp_services_enable',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
		],
	];
}

/**
 * Helper: conditional logic when field is true.
 */
function dmc_homepage_acf_when( $field_key ) {
	return [
		[
			[
				'field'    => $field_key,
				'operator' => '==',
				'value'    => '1',
			],
		],
	];
}
