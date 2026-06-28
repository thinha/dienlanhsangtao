<?php
/**
 * ACF options — Homepage slide settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register Homepage options page under Flatsome menu slug.
 */
function dmc_tmp_register_homepage_options_page() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		[
			'page_title'  => 'Cài đặt Homepage',
			'menu_title'  => 'Homepage',
			'menu_slug'   => 'dmc-homepage-settings',
			'parent_slug' => 'flatsome-panel',
			'capability'  => 'manage_options',
			'redirect'    => false,
			'position'    => 24,
			'icon_url'    => 'dashicons-images-alt2',
		]
	);
}
add_action( 'acf/init', 'dmc_tmp_register_homepage_options_page' );

/**
 * Register Homepage field group.
 */
function dmc_tmp_register_homepage_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'      => 'group_dmc_homepage_slides',
			'title'    => 'Slide banner trang chủ',
			'fields'   => dmc_tmp_homepage_acf_field_definitions(),
			'location' => [
				[
					[
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'dmc-homepage-settings',
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
add_action( 'acf/init', 'dmc_tmp_register_homepage_acf_fields' );

/**
 * Homepage slide field definitions.
 *
 * Field names match theme helpers (homepage_slides, hp_slide_delay).
 *
 * @return array<int, array<string, mixed>>
 */
function dmc_tmp_homepage_acf_field_definitions() {
	return [
		[
			'key'       => 'field_tmp_hp_tab_banner',
			'label'     => 'Banner trượt',
			'type'      => 'tab',
			'placement' => 'left',
		],
		[
			'key'           => 'field_tmp_hp_hero_enable',
			'label'         => 'Bật slide banner',
			'name'          => 'hp_hero_enable',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
			'instructions'  => 'Hiển thị slider banner ở đầu trang chủ.',
		],
		[
			'key'          => 'field_tmp_hp_slides',
			'label'        => 'Slide banner',
			'name'         => 'homepage_slides',
			'type'         => 'repeater',
			'layout'       => 'block',
			'button_label' => 'Thêm slide',
			'instructions' => 'Upload ảnh banner. Mỗi slide hiển thị 1 ảnh, tự chuyển sau vài giây. Kích thước khuyến nghị: 1200×400 px trở lên.',
			'sub_fields'   => [
				[
					'key'           => 'field_tmp_hp_slide_enable',
					'label'         => 'Bật slide',
					'name'          => 'enable',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				],
				[
					'key'           => 'field_tmp_hp_slide_image',
					'label'         => 'Ảnh banner',
					'name'          => 'image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'medium',
					'library'       => 'all',
					'required'      => 1,
				],
				[
					'key'          => 'field_tmp_hp_slide_link',
					'label'        => 'Link khi bấm (tuỳ chọn)',
					'name'         => 'link',
					'type'         => 'url',
					'instructions' => 'Để trống nếu không cần link.',
				],
			],
		],
		[
			'key'           => 'field_tmp_hp_slide_delay',
			'label'         => 'Thời gian chuyển slide (giây)',
			'name'          => 'hp_slide_delay',
			'type'          => 'number',
			'default_value' => 4,
			'min'           => 2,
			'max'           => 15,
			'step'          => 1,
			'instructions'  => 'Thời gian mỗi slide hiển thị trước khi tự chuyển sang slide tiếp theo.',
		],
		[
			'key'       => 'field_tmp_hp_tab_benefits',
			'label'     => 'Cam kết dịch vụ',
			'type'      => 'tab',
			'placement' => 'left',
		],
		[
			'key'           => 'field_tmp_hp_benefits_enable',
			'label'         => 'Bật khối cam kết',
			'name'          => 'hp_benefits_enable',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
			'instructions'  => 'Hiển thị lưới icon + tiêu đề bên cạnh banner trượt.',
		],
		[
			'key'          => 'field_tmp_hp_benefits',
			'label'        => 'Danh sách cam kết',
			'name'         => 'hp_benefits',
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 6,
			'button_label' => 'Thêm mục',
			'instructions' => 'Mỗi mục gồm icon, tiêu đề và mô tả ngắn. Khuyến nghị 4 mục (lưới 2×2).',
			'sub_fields'   => [
				[
					'key'           => 'field_tmp_hp_benefit_enable',
					'label'         => 'Bật mục',
					'name'          => 'enable',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				],
				[
					'key'           => 'field_tmp_hp_benefit_icon_slug',
					'label'         => 'Icon',
					'name'          => 'icon_slug',
					'type'          => 'select',
					'choices'       => dmc_tmp_hp_benefit_icon_choices(),
					'default_value' => 'shield-check',
					'ui'            => 1,
					'instructions'  => 'Chọn icon có sẵn. Hoặc upload ảnh tuỳ chỉnh bên dưới.',
				],
				[
					'key'           => 'field_tmp_hp_benefit_icon_image',
					'label'         => 'Icon tuỳ chỉnh (tuỳ chọn)',
					'name'          => 'icon_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'thumbnail',
					'library'       => 'all',
					'instructions'  => 'Upload ảnh PNG/SVG nếu muốn thay icon mặc định.',
				],
				[
					'key'           => 'field_tmp_hp_benefit_title',
					'label'         => 'Tiêu đề',
					'name'          => 'title',
					'type'          => 'text',
					'required'      => 1,
					'placeholder'   => '100% Hàng chính hãng',
				],
				[
					'key'         => 'field_tmp_hp_benefit_subtitle',
					'label'       => 'Mô tả ngắn',
					'name'        => 'subtitle',
					'type'        => 'text',
					'placeholder' => 'Cam kết hàng mới 100%',
				],
			],
		],
		[
			'key'       => 'field_tmp_hp_tab_flash_sale',
			'label'     => 'Flash Sale',
			'type'      => 'tab',
			'placement' => 'left',
		],
		...dmc_tmp_flash_sale_acf_fields(),
		[
			'key'       => 'field_tmp_hp_tab_wide_sale',
			'label'     => 'Wide sale',
			'type'      => 'tab',
			'placement' => 'left',
		],
		[
			'key'           => 'field_tmp_hp_widesale_enable',
			'label'         => 'Bật banner Wide sale',
			'name'          => 'hp_widesale_enable',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
		],
		[
			'key'           => 'field_tmp_hp_widesale_image',
			'label'         => 'Ảnh banner',
			'name'          => 'hp_widesale_image',
			'type'          => 'image',
			'return_format' => 'array',
			'preview_size'  => 'medium',
			'library'       => 'all',
			'required'      => 1,
			'instructions'  => 'Upload 1 ảnh banner (text và thiết kế nằm trong ảnh). Kích thước khuyến nghị: 1200×120 px.',
		],
		...dmc_tmp_hp_brands_acf_fields(),
		[
			'key'       => 'field_tmp_hp_tab_product_sections',
			'label'     => 'Khối sản phẩm',
			'type'      => 'tab',
			'placement' => 'left',
		],
		[
			'key'          => 'field_tmp_hp_product_sections',
			'label'        => 'Các khối sản phẩm trang chủ',
			'name'         => 'tmp_hp_product_sections',
			'type'         => 'repeater',
			'layout'       => 'block',
			'button_label' => 'Thêm khối sản phẩm',
			'instructions' => 'Cấu hình các khối như "Sản phẩm bán chạy", "Gợi ý cho bạn"... Chọn nguồn theo danh mục, brand hoặc sản phẩm thủ công. Slider Swiper freeMode, tuỳ chọn autoplay.',
			'sub_fields'   => dmc_tmp_product_section_acf_subfields( 'tmp_hp_sec' ),
		],
		[
			'key'       => 'field_tmp_hp_tab_brand_sync',
			'label'     => 'Đồng bộ thương hiệu',
			'type'      => 'tab',
			'placement' => 'left',
		],
		[
			'key'          => 'field_tmp_hp_brand_sync_panel',
			'label'        => 'Đồng bộ brand sản phẩm',
			'name'         => '',
			'type'         => 'message',
			'message'      => '',
			'instructions' => 'Tự động gán thương hiệu cho sản phẩm WooCommerce dựa trên tên thương hiệu có trong tiêu đề.',
		],
	];
}

/**
 * Icon choices for homepage benefit cards.
 *
 * @return array<string, string>
 */
function dmc_tmp_hp_benefit_icon_choices() {
	return [
		'shield-check'  => 'Khiên / Chính hãng',
		'delivery-fast' => 'Giao hàng nhanh',
		'delivery-truck' => 'Xe giao hàng',
		'percent-zero'  => 'Trả góp 0%',
		'percent'       => 'Giảm giá %',
		'wrench'        => 'Bảo hành / Sửa chữa',
		'warranty'      => 'Bảo hành',
		'badge-check'   => 'Cam kết',
		'credit-card'   => 'Thanh toán',
		'gift'          => 'Quà tặng',
		'headset'       => 'Hỗ trợ',
		'phone'         => 'Hotline',
		'store'         => 'Cửa hàng',
		'clock'         => 'Thời gian',
		'return'        => 'Đổi trả',
		'star'          => 'Đánh giá',
		'heart'         => 'Yêu thích',
		'package'       => 'Sản phẩm',
	];
}
