<?php
/**
 * ACF options — Tab menu product settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register options page under Flatsome menu slug.
 */
function dmc_tmp_register_options_page() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}

	acf_add_options_page(
		[
			'page_title'  => 'Product',
			'menu_title'  => 'Product',
			'menu_slug'   => 'dmc-tab-menu-product-settings',
			'parent_slug' => 'flatsome-panel',
			'capability'  => 'manage_options',
			'redirect'    => false,
			'position'    => 25,
		]
	);
}
add_action( 'acf/init', 'dmc_tmp_register_options_page' );

/**
 * Register field group.
 */
function dmc_tmp_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		[
			'key'      => 'group_dmc_tab_menu_product',
			'title'    => 'Setup product hiển thị trang chủ',
			'fields'   => dmc_tmp_acf_field_definitions(),
			'location' => [
				[
					[
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'dmc-tab-menu-product-settings',
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
add_action( 'acf/init', 'dmc_tmp_register_acf_fields' );

/**
 * Field definitions.
 *
 * @return array<int, array<string, mixed>>
 */
function dmc_tmp_acf_field_definitions() {
	return [
		[
			'key'   => 'field_tmp_tab_company',
			'label' => 'Company',
			'type'  => 'tab',
		],
		[
			'key'           => 'field_tmp_company_name',
			'label'         => 'Tên công ty',
			'name'          => 'tmp_company_name',
			'type'          => 'text',
			'instructions'  => 'Tên thương hiệu / công ty (dùng cho SEO, schema và các khối nội dung khác).',
			'placeholder'   => 'Điện lạnh Sáng Tạo',
		],
		[
			'key'           => 'field_tmp_company_hotline',
			'label'         => 'Hotline card sản phẩm',
			'name'          => 'tmp_company_hotline',
			'type'          => 'text',
			'default_value' => '1900 2323 88',
			'instructions'  => 'Số hiển thị trên card sản phẩm: "Gọi [số này] để được giảm thêm".',
			'placeholder'   => '1900 2323 88',
		],
		[
			'key'           => 'field_tmp_company_hotline_hours',
			'label'         => 'Giờ làm việc',
			'name'          => 'tmp_company_hotline_hours',
			'type'          => 'text',
			'default_value' => '8:00 - 21:00',
			'instructions'  => 'Khung giờ hỗ trợ khách hàng, hiển thị cùng hotline (header, drawer, v.v.).',
			'placeholder'   => '8:00 - 21:00',
		],
		[
			'key'   => 'field_tmp_tab_homepage',
			'label' => 'Trang chủ — Tab menu',
			'type'  => 'tab',
		],
		[
			'key'     => 'field_tmp_hp_sections_notice',
			'label'   => 'Khối sản phẩm trang chủ',
			'name'    => '',
			'type'    => 'message',
			'message' => 'Các khối "Sản phẩm bán chạy", "Gợi ý cho bạn"... cấu hình tại <strong>Flatsome → Homepage → Khối sản phẩm</strong>. Tab này dành cho slider tab danh mục.',
		],
		[
			'key'           => 'field_tmp_enable',
			'label'         => 'Bật Tab menu product',
			'name'          => 'tmp_enable',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
			'instructions'  => 'Hiển thị khối tab danh mục + slider sản phẩm trên trang chủ.',
		],
		[
			'key'               => 'field_tmp_slides_per_view',
			'label'             => 'Số sản phẩm hiển thị trên 1 hàng (desktop)',
			'name'              => 'tmp_slides_per_view',
			'type'              => 'number',
			'default_value'     => 5,
			'min'               => 2,
			'max'               => 8,
			'step'              => 1,
			'instructions'      => 'Slider Swiper freeMode — số thẻ sản phẩm nhìn thấy cùng lúc trên màn hình ≥1100px. Tablet/mobile tự giảm (2→3→4). Vuốt ngang hoặc bấm mũi tên để xem thêm.',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_tmp_enable',
						'operator' => '==',
						'value'    => '1',
					],
				],
			],
		],
		[
			'key'           => 'field_tmp_show_more',
			'label'         => 'Hiện link "Xem tất cả"',
			'name'          => 'tmp_show_more',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
		],
		[
			'key'               => 'field_tmp_more_text',
			'label'             => 'Chữ link "Xem tất cả"',
			'name'              => 'tmp_more_text',
			'type'              => 'text',
			'default_value'     => 'Xem tất cả ›',
			'placeholder'       => 'Xem tất cả ›',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_tmp_show_more',
						'operator' => '==',
						'value'    => '1',
					],
				],
			],
		],
		[
			'key'               => 'field_tmp_swiper_autoplay',
			'label'             => 'Bật autoplay slider',
			'name'              => 'tmp_swiper_autoplay',
			'type'              => 'true_false',
			'default_value'     => 0,
			'ui'                => 1,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_tmp_enable',
						'operator' => '==',
						'value'    => '1',
					],
				],
			],
		],
		[
			'key'               => 'field_tmp_swiper_autoplay_delay',
			'label'             => 'Thời gian autoplay (giây)',
			'name'              => 'tmp_swiper_autoplay_delay',
			'type'              => 'number',
			'default_value'     => 4,
			'min'               => 2,
			'max'               => 15,
			'step'              => 1,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_tmp_enable',
						'operator' => '==',
						'value'    => '1',
					],
					[
						'field'    => 'field_tmp_swiper_autoplay',
						'operator' => '==',
						'value'    => '1',
					],
				],
			],
		],
		[
			'key'          => 'field_tmp_tabs',
			'label'        => 'Tab danh mục sản phẩm',
			'name'         => 'tmp_tabs',
			'type'         => 'repeater',
			'layout'       => 'block',
			'button_label' => 'Thêm tab',
			'instructions' => 'Mỗi tab = 1 danh mục/icon trên cùng. Bấm tab sẽ đổi slider sản phẩm bên dưới.',
			'sub_fields'   => [
				[
					'key'           => 'field_tmp_tab_enable',
					'label'         => 'Bật tab',
					'name'          => 'enable',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				],
				[
					'key'      => 'field_tmp_tab_title',
					'label'    => 'Tên tab',
					'name'     => 'title',
					'type'     => 'text',
					'required' => 1,
				],
				[
					'key'           => 'field_tmp_tab_icon',
					'label'         => 'Icon tab (tuỳ chọn)',
					'name'          => 'icon',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'thumbnail',
					'library'       => 'all',
					'instructions'  => 'Hình tròn hiển thị trên tab. Để trống sẽ dùng icon danh mục WooCommerce.',
				],
				[
					'key'           => 'field_tmp_tab_source',
					'label'         => 'Nguồn sản phẩm',
					'name'          => 'source',
					'type'          => 'select',
					'choices'       => function_exists( 'dmc_tmp_tab_source_choices' ) ? dmc_tmp_tab_source_choices() : [
						'manual'   => 'Chọn sản phẩm thủ công',
						'newest'   => 'Sản phẩm mới đăng',
						'random'   => 'Sản phẩm ngẫu nhiên',
						'category' => 'Theo danh mục',
					],
					'default_value' => 'newest',
					'ui'            => 1,
					'instructions'  => 'Chọn cách lấy sản phẩm cho tab này.',
				],
				[
					'key'               => 'field_tmp_tab_category',
					'label'             => 'Danh mục sản phẩm',
					'name'              => 'category',
					'type'              => 'taxonomy',
					'taxonomy'          => 'product_cat',
					'field_type'        => 'select',
					'return_format'     => 'id',
					'allow_null'        => 0,
					'add_term'          => 0,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_tmp_tab_source',
								'operator' => '==',
								'value'    => 'category',
							],
						],
					],
				],
				[
					'key'               => 'field_tmp_tab_brand',
					'label'             => 'Thương hiệu',
					'name'              => 'brand',
					'type'              => 'taxonomy',
					'taxonomy'          => function_exists( 'dmc_tmp_brand_taxonomy' ) && dmc_tmp_brand_taxonomy() ? dmc_tmp_brand_taxonomy() : 'product_brand',
					'field_type'        => 'select',
					'return_format'     => 'id',
					'allow_null'        => 0,
					'add_term'          => 0,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_tmp_tab_source',
								'operator' => '==',
								'value'    => 'brand',
							],
						],
					],
				],
				[
					'key'               => 'field_tmp_tab_orderby',
					'label'             => 'Sắp xếp sản phẩm',
					'name'              => 'orderby',
					'type'              => 'select',
					'choices'           => function_exists( 'dmc_tmp_product_section_sort_choices' ) ? dmc_tmp_product_section_sort_choices() : [],
					'default_value'     => 'date',
					'ui'                => 1,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_tmp_tab_source',
								'operator' => '!=',
								'value'    => 'manual',
							],
						],
					],
				],
				[
					'key'               => 'field_tmp_tab_products',
					'label'             => 'Chọn sản phẩm',
					'name'              => 'products',
					'type'              => 'relationship',
					'post_type'         => [ 'product' ],
					'filters'           => [ 'search', 'taxonomy' ],
					'return_format'     => 'id',
					'min'               => 1,
					'conditional_logic' => [
						[
							[
								'field'    => 'field_tmp_tab_source',
								'operator' => '==',
								'value'    => 'manual',
							],
						],
					],
				],
				[
					'key'           => 'field_tmp_tab_limit',
					'label'         => 'Số lượng sản phẩm hiển thị',
					'name'          => 'limit',
					'type'          => 'number',
					'default_value' => 10,
					'min'           => 1,
					'max'           => 50,
					'step'          => 1,
					'instructions'  => 'Tổng số sản phẩm trong slider của tab này (vuốt/mũi tên để xem thêm).',
				],
				[
					'key'          => 'field_tmp_tab_more_url',
					'label'        => 'Link "Xem tất cả" (tuỳ chọn)',
					'name'         => 'more_url',
					'type'         => 'url',
					'instructions' => 'Để trống: tự lấy link danh mục hoặc trang Cửa hàng.',
				],
			],
		],
		[
			'key'   => 'field_tmp_tab_product_detail',
			'label' => 'Product detail',
			'type'  => 'tab',
		],
		[
			'key'           => 'field_tmp_product_trust_enable',
			'label'         => 'Bật khối cam kết (trust badges)',
			'name'          => 'tmp_product_trust_enable',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
			'instructions'  => 'Hiển thị lưới icon + nhãn bên cạnh thông tin mua hàng trên trang chi tiết sản phẩm.',
		],
		[
			'key'          => 'field_tmp_product_trust_badges',
			'label'        => 'Cam kết mua sắm',
			'name'         => 'tmp_product_trust_badges',
			'type'         => 'repeater',
			'layout'       => 'block',
			'min'          => 1,
			'max'          => 8,
			'button_label' => 'Thêm mục',
			'instructions' => 'Mỗi mục gồm icon và nhãn ngắn. Khuyến nghị 4 mục (lưới 2×2).',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_tmp_product_trust_enable',
						'operator' => '==',
						'value'    => '1',
					],
				],
			],
			'sub_fields'   => [
				[
					'key'           => 'field_tmp_product_trust_item_enable',
					'label'         => 'Bật mục',
					'name'          => 'enable',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				],
				[
					'key'           => 'field_tmp_product_trust_icon_slug',
					'label'         => 'Icon',
					'name'          => 'icon_slug',
					'type'          => 'select',
					'choices'       => function_exists( 'dmc_tmp_hp_benefit_icon_choices' ) ? dmc_tmp_hp_benefit_icon_choices() : [],
					'default_value' => 'badge-check',
					'ui'            => 1,
					'instructions'  => 'Chọn icon có sẵn. Hoặc upload ảnh tuỳ chỉnh bên dưới.',
				],
				[
					'key'           => 'field_tmp_product_trust_icon_image',
					'label'         => 'Icon tuỳ chỉnh (tuỳ chọn)',
					'name'          => 'icon_image',
					'type'          => 'image',
					'return_format' => 'array',
					'preview_size'  => 'thumbnail',
					'library'       => 'all',
				],
				[
					'key'           => 'field_tmp_product_trust_label',
					'label'         => 'Nhãn',
					'name'          => 'label',
					'type'          => 'text',
					'required'      => 1,
					'placeholder'   => 'Hàng chính hãng',
				],
			],
		],
		[
			'key'   => 'field_tmp_tab_shipping',
			'label' => 'Giao hàng',
			'type'  => 'tab',
		],
		[
			'key'          => 'field_tmp_shipping_locations',
			'label'        => 'Phí giao hàng theo khu vực',
			'name'         => 'tmp_shipping_locations',
			'type'         => 'repeater',
			'layout'       => 'table',
			'button_label' => 'Thêm khu vực',
			'instructions' => 'Danh sách khu vực hiển thị trong dropdown "Giao hàng đến" trên trang chi tiết sản phẩm. Phí (xe máy hoặc ô tô) được cộng vào giá khuyến mãi tùy loại giao hàng của từng sản phẩm.',
			'sub_fields'   => [
				[
					'key'           => 'field_tmp_shipping_location_enable',
					'label'         => 'Bật',
					'name'          => 'enable',
					'type'          => 'true_false',
					'default_value' => 1,
					'ui'            => 1,
				],
				[
					'key'      => 'field_tmp_shipping_location_name',
					'label'    => 'Khu vực',
					'name'     => 'name',
					'type'     => 'text',
					'required' => 1,
					'placeholder' => 'Quận 1',
				],
				[
					'key'           => 'field_tmp_shipping_location_fee',
					'label'         => 'Phí xe máy (đ)',
					'name'          => 'fee',
					'type'          => 'number',
					'required'      => 1,
					'min'           => 0,
					'step'          => 1000,
					'default_value' => 0,
				],
				[
					'key'           => 'field_tmp_shipping_location_fee_car',
					'label'         => 'Phí ô tô (đ)',
					'name'          => 'fee_car',
					'type'          => 'number',
					'required'      => 1,
					'min'           => 0,
					'step'          => 1000,
					'default_value' => 0,
				],
			],
		],
		[
			'key'   => 'field_tmp_tab_archive',
			'label' => 'Archive — Danh sách sản phẩm',
			'type'  => 'tab',
		],
		[
			'key'           => 'field_tmp_archive_columns',
			'label'         => 'Số sản phẩm trên 1 hàng (desktop)',
			'name'          => 'tmp_archive_columns',
			'type'          => 'select',
			'choices'       => [
				'3' => '3 cột',
				'4' => '4 cột',
			],
			'default_value' => '3',
			'ui'            => 1,
			'instructions'  => 'Lưới sản phẩm trên trang archive (Cửa hàng, danh mục, tìm kiếm). Tablet/mobile tự giảm cột cho dễ xem.',
		],
		[
			'key'           => 'field_tmp_archive_per_page',
			'label'         => 'Số lượng sản phẩm mỗi trang',
			'name'          => 'tmp_archive_per_page',
			'type'          => 'number',
			'default_value' => 24,
			'min'           => 6,
			'max'           => 60,
			'step'          => 1,
			'instructions'  => 'Áp dụng cho trang Cửa hàng, danh mục, thẻ sản phẩm và kết quả tìm kiếm.',
		],
		[
			'key'           => 'field_tmp_archive_default_order',
			'label'         => 'Sắp xếp mặc định',
			'name'          => 'tmp_archive_default_order',
			'type'          => 'select',
			'choices'       => [
				'relevance' => 'Liên quan (WooCommerce mặc định)',
				'date'      => 'Mới nhất',
				'random'    => 'Ngẫu nhiên',
			],
			'default_value' => 'relevance',
			'ui'            => 1,
			'instructions'  => 'Thứ tự khi khách vào trang archive lần đầu (chưa chọn sắp xếp trên toolbar).',
		],
	];
}
