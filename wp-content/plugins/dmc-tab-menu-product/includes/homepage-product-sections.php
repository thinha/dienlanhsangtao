<?php
/**
 * Homepage product sections — ACF fields & product resolver.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product sort choices for homepage blocks & tab menu.
 *
 * @return array<string, string>
 */
function dmc_tmp_product_section_sort_choices() {
	return [
		'popularity'  => 'Bán chạy (phổ biến)',
		'date'        => 'Mới nhất',
		'price_asc'   => 'Giá thấp → cao',
		'price_desc'  => 'Giá cao → thấp',
		'title'       => 'Tên A → Z',
		'rand'        => 'Ngẫu nhiên',
		'menu_order'  => 'Thứ tự sắp xếp thủ công',
	];
}

/**
 * Product source choices for homepage blocks.
 *
 * @return array<string, string>
 */
function dmc_tmp_product_section_source_choices() {
	$choices = [
		'manual'     => 'Chọn sản phẩm thủ công',
		'category'   => 'Theo danh mục sản phẩm',
		'brand'      => 'Theo thương hiệu (brand)',
		'bestseller' => 'Sản phẩm bán chạy',
		'latest'     => 'Sản phẩm mới nhất',
		'on_sale'    => 'Sản phẩm đang giảm giá',
		'featured'   => 'Sản phẩm nổi bật (Featured)',
		'random'     => 'Gợi ý ngẫu nhiên',
	];

	if ( ! dmc_tmp_brand_taxonomy() ) {
		unset( $choices['brand'] );
	}

	return $choices;
}

/**
 * Tab menu source choices (subset + filter variants).
 *
 * @return array<string, string>
 */
function dmc_tmp_tab_source_choices() {
	$choices = [
		'manual'   => 'Chọn sản phẩm thủ công',
		'category' => 'Theo danh mục',
		'brand'    => 'Theo thương hiệu (brand)',
		'newest'   => 'Sản phẩm mới đăng',
		'random'   => 'Sản phẩm ngẫu nhiên',
	];

	if ( ! dmc_tmp_brand_taxonomy() ) {
		unset( $choices['brand'] );
	}

	return $choices;
}

/**
 * Shared ACF sub-fields for a product section row.
 *
 * @param string $prefix Field key prefix (e.g. tmp_hp_sec).
 * @return array<int, array<string, mixed>>
 */
function dmc_tmp_product_section_acf_subfields( $prefix = 'tmp_hp_sec' ) {
	$brand_tax = dmc_tmp_brand_taxonomy();

	return [
		[
			'key'           => 'field_' . $prefix . '_enable',
			'label'         => 'Bật khối này',
			'name'          => 'enable',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
		],
		[
			'key'           => 'field_' . $prefix . '_title',
			'label'         => 'Tiêu đề khối',
			'name'          => 'title',
			'type'          => 'text',
			'default_value' => 'SẢN PHẨM NỔI BẬT',
			'required'      => 1,
		],
		[
			'key'     => 'field_' . $prefix . '_decor_message',
			'label'   => 'Trang trí / quảng cáo theo mùa',
			'name'    => '',
			'type'    => 'message',
			'message' => 'Upload ảnh nền (banner mùa) và chỉnh <strong>padding top</strong> để phần quảng cáo hiển thị vừa khít. Có thể cấu hình riêng cho PC, tablet và mobile.',
		],
		[
			'key'           => 'field_' . $prefix . '_bg_pc',
			'label'         => 'Ảnh nền — PC',
			'name'          => 'bg_pc',
			'type'          => 'image',
			'return_format' => 'array',
			'preview_size'  => 'medium',
			'library'       => 'all',
			'instructions'  => 'Ảnh banner nền cho màn hình ≥1100px. Để trống nếu không dùng.',
		],
		[
			'key'           => 'field_' . $prefix . '_bg_tablet',
			'label'         => 'Ảnh nền — Tablet',
			'name'          => 'bg_tablet',
			'type'          => 'image',
			'return_format' => 'array',
			'preview_size'  => 'medium',
			'library'       => 'all',
			'instructions'  => '901px–1099px. Để trống: dùng ảnh PC.',
		],
		[
			'key'           => 'field_' . $prefix . '_bg_mobile',
			'label'         => 'Ảnh nền — Mobile',
			'name'          => 'bg_mobile',
			'type'          => 'image',
			'return_format' => 'array',
			'preview_size'  => 'medium',
			'library'       => 'all',
			'instructions'  => '≤900px. Để trống: dùng ảnh tablet hoặc PC.',
		],
		[
			'key'           => 'field_' . $prefix . '_padding_top_pc',
			'label'         => 'Padding top — PC (px)',
			'name'          => 'padding_top_pc',
			'type'          => 'number',
			'default_value' => 0,
			'min'           => 0,
			'max'           => 500,
			'step'          => 1,
			'append'        => 'px',
			'instructions'  => 'Khoảng trống phía trên nội dung để lộ phần banner trong ảnh nền.',
		],
		[
			'key'           => 'field_' . $prefix . '_padding_top_tablet',
			'label'         => 'Padding top — Tablet (px)',
			'name'          => 'padding_top_tablet',
			'type'          => 'number',
			'default_value' => 0,
			'min'           => 0,
			'max'           => 500,
			'step'          => 1,
			'append'        => 'px',
			'instructions'  => 'Để trống hoặc 0: dùng giá trị PC.',
		],
		[
			'key'           => 'field_' . $prefix . '_padding_top_mobile',
			'label'         => 'Padding top — Mobile (px)',
			'name'          => 'padding_top_mobile',
			'type'          => 'number',
			'default_value' => 0,
			'min'           => 0,
			'max'           => 500,
			'step'          => 1,
			'append'        => 'px',
			'instructions'  => 'Để trống hoặc 0: dùng giá trị tablet hoặc PC.',
		],
		[
			'key'           => 'field_' . $prefix . '_layout',
			'label'         => 'Kiểu hiển thị',
			'name'          => 'layout',
			'type'          => 'select',
			'choices'       => [
				'showcase' => 'Tiêu đề dọc + lưới 6 sản phẩm (Sản phẩm nổi bật)',
				'swiper'   => 'Slider Swiper (1 hàng, freeMode)',
				'grid'     => 'Lưới sản phẩm (desktop) / cuộn ngang (mobile)',
			],
			'default_value' => 'showcase',
			'ui'            => 1,
		],
		[
			'key'           => 'field_' . $prefix . '_source',
			'label'         => 'Nguồn sản phẩm',
			'name'          => 'source',
			'type'          => 'select',
			'choices'       => dmc_tmp_product_section_source_choices(),
			'default_value' => 'bestseller',
			'ui'            => 1,
		],
		[
			'key'               => 'field_' . $prefix . '_products',
			'label'             => 'Chọn sản phẩm',
			'name'              => 'products',
			'type'              => 'relationship',
			'post_type'         => [ 'product' ],
			'filters'           => [ 'search', 'taxonomy' ],
			'return_format'     => 'id',
			'min'               => 1,
			'instructions'      => 'Tìm sản phẩm theo tên hoặc lọc danh mục. Kéo thả để sắp xếp thứ tự.',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $prefix . '_source',
						'operator' => '==',
						'value'    => 'manual',
					],
				],
			],
		],
		[
			'key'               => 'field_' . $prefix . '_category',
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
						'field'    => 'field_' . $prefix . '_source',
						'operator' => '==',
						'value'    => 'category',
					],
				],
			],
		],
		[
			'key'               => 'field_' . $prefix . '_brand',
			'label'             => 'Thương hiệu',
			'name'              => 'brand',
			'type'              => 'taxonomy',
			'taxonomy'          => $brand_tax ?: 'product_brand',
			'field_type'        => 'select',
			'return_format'     => 'id',
			'allow_null'        => 0,
			'add_term'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $prefix . '_source',
						'operator' => '==',
						'value'    => 'brand',
					],
				],
			],
		],
		[
			'key'           => 'field_' . $prefix . '_orderby',
			'label'         => 'Sắp xếp sản phẩm',
			'name'          => 'orderby',
			'type'          => 'select',
			'choices'       => dmc_tmp_product_section_sort_choices(),
			'default_value' => 'popularity',
			'ui'            => 1,
			'instructions'  => 'Không áp dụng khi chọn sản phẩm thủ công (giữ thứ tự đã kéo thả).',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $prefix . '_source',
						'operator' => '!=',
						'value'    => 'manual',
					],
				],
			],
		],
		[
			'key'           => 'field_' . $prefix . '_limit',
			'label'         => 'Số sản phẩm',
			'name'          => 'limit',
			'type'          => 'number',
			'default_value' => 8,
			'min'           => 3,
			'max'           => 24,
		],
		[
			'key'               => 'field_' . $prefix . '_slides_per_view',
			'label'             => 'Số sản phẩm trên 1 hàng (Swiper)',
			'name'              => 'slides_per_view',
			'type'              => 'number',
			'default_value'     => 4,
			'min'               => 2,
			'max'               => 8,
			'instructions'      => 'Áp dụng khi kiểu hiển thị là Swiper.',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $prefix . '_layout',
						'operator' => '==',
						'value'    => 'swiper',
					],
				],
			],
		],
		[
			'key'               => 'field_' . $prefix . '_swiper_autoplay',
			'label'             => 'Bật autoplay slider',
			'name'              => 'swiper_autoplay',
			'type'              => 'true_false',
			'default_value'     => 0,
			'ui'                => 1,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $prefix . '_layout',
						'operator' => '==',
						'value'    => 'swiper',
					],
				],
			],
		],
		[
			'key'               => 'field_' . $prefix . '_swiper_autoplay_delay',
			'label'             => 'Thời gian autoplay (giây)',
			'name'              => 'swiper_autoplay_delay',
			'type'              => 'number',
			'default_value'     => 4,
			'min'               => 2,
			'max'               => 15,
			'step'              => 1,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $prefix . '_layout',
						'operator' => '==',
						'value'    => 'swiper',
					],
					[
						'field'    => 'field_' . $prefix . '_swiper_autoplay',
						'operator' => '==',
						'value'    => '1',
					],
				],
			],
		],
		[
			'key'           => 'field_' . $prefix . '_show_more',
			'label'         => 'Hiện link "Xem tất cả"',
			'name'          => 'show_more',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
		],
		[
			'key'               => 'field_' . $prefix . '_more_text',
			'label'             => 'Chữ link "Xem tất cả"',
			'name'              => 'more_text',
			'type'              => 'text',
			'default_value'     => 'Xem tất cả ›',
			'placeholder'       => 'Xem tất cả ›',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $prefix . '_show_more',
						'operator' => '==',
						'value'    => '1',
					],
				],
			],
		],
		[
			'key'               => 'field_' . $prefix . '_more_url',
			'label'             => 'Link "Xem tất cả" (tuỳ chọn)',
			'name'              => 'more_url',
			'type'              => 'url',
			'instructions'      => 'Để trống: tự lấy link danh mục, brand hoặc trang Cửa hàng.',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $prefix . '_show_more',
						'operator' => '==',
						'value'    => '1',
					],
				],
			],
		],
	];
}

/**
 * Set brand taxonomy on ACF brand fields dynamically.
 *
 * @param array<string, mixed> $field ACF field.
 * @return array<string, mixed>
 */
function dmc_tmp_acf_load_brand_taxonomy_field( $field ) {
	$taxonomy = dmc_tmp_brand_taxonomy();
	if ( $taxonomy ) {
		$field['taxonomy'] = $taxonomy;
	}

	return $field;
}
add_filter( 'acf/load_field/name=brand', 'dmc_tmp_acf_load_brand_taxonomy_field' );

/**
 * Clamp section padding top (px).
 *
 * @param mixed $value Raw value.
 * @return int
 */
function dmc_tmp_normalize_product_section_padding( $value ) {
	return max( 0, min( 500, (int) $value ) );
}

/**
 * Inline decor attrs (background + padding top) for a product section.
 *
 * @param array<string, mixed> $config Section config.
 * @return array{class:string,style:string}
 */
function dmc_tmp_product_section_decor_attrs( array $config ) {
	$config = dmc_tmp_normalize_product_section_row( $config );

	$padding_pc  = (int) ( $config['padding_top_pc'] ?? 0 );
	$padding_tab = (int) ( $config['padding_top_tablet'] ?? 0 );
	$padding_mob = (int) ( $config['padding_top_mobile'] ?? 0 );

	$bg_pc  = $config['bg_pc'] ?? null;
	$bg_tab = $config['bg_tablet'] ?? null;
	$bg_mob = $config['bg_mobile'] ?? null;

	$has_decor = $padding_pc || $padding_tab || $padding_mob || $bg_pc || $bg_tab || $bg_mob;

	if ( ! $has_decor ) {
		return [
			'class' => '',
			'style' => '',
		];
	}

	$vars = [];

	if ( $padding_pc ) {
		$vars[] = '--dmc-sec-pt-pc:' . $padding_pc . 'px';
	}
	if ( $padding_tab ) {
		$vars[] = '--dmc-sec-pt-tab:' . $padding_tab . 'px';
	}
	if ( $padding_mob ) {
		$vars[] = '--dmc-sec-pt-mob:' . $padding_mob . 'px';
	}

	if ( ! empty( $bg_pc['url'] ) ) {
		$vars[] = '--dmc-sec-bg-pc:url(' . esc_url( $bg_pc['url'] ) . ')';
	}
	if ( ! empty( $bg_tab['url'] ) ) {
		$vars[] = '--dmc-sec-bg-tab:url(' . esc_url( $bg_tab['url'] ) . ')';
	}
	if ( ! empty( $bg_mob['url'] ) ) {
		$vars[] = '--dmc-sec-bg-mob:url(' . esc_url( $bg_mob['url'] ) . ')';
	}

	return [
		'class' => 'dmc-product-section--decor',
		'style' => implode( ';', $vars ),
	];
}

/**
 * Normalize product section row.
 *
 * @param array<string, mixed> $row Raw row.
 * @return array<string, mixed>
 */
function dmc_tmp_normalize_product_section_row( array $row ) {
	$allowed_sort = array_keys( dmc_tmp_product_section_sort_choices() );
	$orderby      = (string) ( $row['orderby'] ?? 'popularity' );

	return [
		'enable'                => ! empty( $row['enable'] ),
		'title'                 => (string) ( $row['title'] ?? '' ),
		'layout'                => in_array( $row['layout'] ?? 'swiper', [ 'grid', 'swiper', 'flash' ], true ) ? $row['layout'] : 'swiper',
		'source'                => (string) ( $row['source'] ?? 'bestseller' ),
		'products'              => array_map( 'intval', (array) ( $row['products'] ?? [] ) ),
		'category'              => (int) ( $row['category'] ?? 0 ),
		'brand'                 => (int) ( $row['brand'] ?? 0 ),
		'orderby'               => in_array( $orderby, $allowed_sort, true ) ? $orderby : 'popularity',
		'limit'                 => max( 1, min( 24, (int) ( $row['limit'] ?? 8 ) ) ),
		'slides_per_view'       => max( 2, min( 8, (int) ( $row['slides_per_view'] ?? 4 ) ) ),
		'swiper_autoplay'       => ! empty( $row['swiper_autoplay'] ),
		'swiper_autoplay_delay' => max( 2, min( 15, (int) ( $row['swiper_autoplay_delay'] ?? 4 ) ) ),
		'show_more'             => ! isset( $row['show_more'] ) || ! empty( $row['show_more'] ),
		'more_text'             => trim( (string) ( $row['more_text'] ?? 'Xem tất cả ›' ) ) ?: 'Xem tất cả ›',
		'more_url'              => (string) ( $row['more_url'] ?? '' ),
		'bg_pc'                 => dmc_tmp_image_field( $row['bg_pc'] ?? null ),
		'bg_tablet'             => dmc_tmp_image_field( $row['bg_tablet'] ?? null ),
		'bg_mobile'             => dmc_tmp_image_field( $row['bg_mobile'] ?? null ),
		'padding_top_pc'        => dmc_tmp_normalize_product_section_padding( $row['padding_top_pc'] ?? 0 ),
		'padding_top_tablet'    => dmc_tmp_normalize_product_section_padding( $row['padding_top_tablet'] ?? 0 ),
		'padding_top_mobile'    => dmc_tmp_normalize_product_section_padding( $row['padding_top_mobile'] ?? 0 ),
	];
}

/**
 * Default homepage product sections.
 *
 * @return array<int, array<string, mixed>>
 */
function dmc_tmp_default_hp_product_sections() {
	return [
		dmc_tmp_normalize_product_section_row(
			[
				'enable'    => true,
				'title'     => 'SẢN PHẨM NỔI BẬT',
				'layout'    => 'showcase',
				'source'    => 'featured',
				'orderby'   => 'popularity',
				'limit'     => 6,
				'show_more' => true,
				'more_text' => 'Xem tất cả ›',
			]
		),
		dmc_tmp_normalize_product_section_row(
			[
				'enable'    => true,
				'title'     => 'GỢI Ý CHO BẠN',
				'layout'    => 'swiper',
				'source'    => 'random',
				'orderby'   => 'rand',
				'limit'     => 8,
				'show_more' => true,
				'more_text' => 'Xem tất cả ›',
			]
		),
	];
}

/**
 * Pre-fill product sections when options have never been saved.
 *
 * @param mixed $value   Field value.
 * @param mixed $post_id Options page id.
 * @return mixed
 */
function dmc_tmp_load_default_hp_product_sections( $value, $post_id, $field ) {
	if ( 'options' !== $post_id || ! empty( $value ) ) {
		return $value;
	}

	$rows = [];

	foreach ( dmc_tmp_default_hp_product_sections() as $section ) {
		$rows[] = [
			'enable'                => ! empty( $section['enable'] ) ? 1 : 0,
			'title'                 => (string) ( $section['title'] ?? '' ),
			'layout'                => (string) ( $section['layout'] ?? 'swiper' ),
			'source'                => (string) ( $section['source'] ?? 'bestseller' ),
			'products'              => [],
			'category'              => 0,
			'brand'                 => 0,
			'orderby'               => (string) ( $section['orderby'] ?? 'popularity' ),
			'limit'                 => (int) ( $section['limit'] ?? 8 ),
			'slides_per_view'       => (int) ( $section['slides_per_view'] ?? 4 ),
			'swiper_autoplay'       => 0,
			'swiper_autoplay_delay' => 4,
			'show_more'             => ! empty( $section['show_more'] ) ? 1 : 0,
			'more_text'             => (string) ( $section['more_text'] ?? 'Xem tất cả ›' ),
			'more_url'              => '',
		];
	}

	return $rows;
}
add_filter( 'acf/load_value/name=tmp_hp_product_sections', 'dmc_tmp_load_default_hp_product_sections', 10, 3 );

/**
 * Map sort key to WooCommerce query args.
 *
 * @param string $orderby Sort key.
 * @return array{orderby:string,order:string}
 */
function dmc_tmp_product_query_order_args( $orderby ) {
	switch ( $orderby ) {
		case 'price_asc':
			return [ 'orderby' => 'price', 'order' => 'ASC' ];
		case 'price_desc':
			return [ 'orderby' => 'price', 'order' => 'DESC' ];
		case 'title':
			return [ 'orderby' => 'title', 'order' => 'ASC' ];
		case 'rand':
			return [ 'orderby' => 'rand', 'order' => 'DESC' ];
		case 'menu_order':
			return [ 'orderby' => 'menu_order', 'order' => 'ASC' ];
		case 'date':
			return [ 'orderby' => 'date', 'order' => 'DESC' ];
		case 'popularity':
		default:
			return [ 'orderby' => 'popularity', 'order' => 'DESC' ];
	}
}

/**
 * Apply taxonomy filter to product query args.
 *
 * @param array<string, mixed> $query_args Query args.
 * @param array<string, mixed> $config       Section config.
 * @return array<string, mixed>
 */
function dmc_tmp_apply_product_section_tax_filter( array $query_args, array $config ) {
	$source = $config['source'] ?? '';

	if ( 'category' === $source ) {
		$cat_id = (int) ( $config['category'] ?? 0 );
		if ( $cat_id ) {
			$term = get_term( $cat_id, 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$query_args['category'] = [ $term->slug ];
			}
		}
	}

	if ( 'brand' === $source ) {
		$brand_id  = (int) ( $config['brand'] ?? 0 );
		$taxonomy  = dmc_tmp_brand_taxonomy();
		if ( $brand_id && $taxonomy ) {
			$term = get_term( $brand_id, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$query_args['tax_query'] = [
					[
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => [ $brand_id ],
					],
				];
			}
		}
	}

	return $query_args;
}

/**
 * Resolve source-specific orderby when not explicitly set.
 *
 * @param string $source  Source key.
 * @param string $orderby Explicit orderby.
 * @return string
 */
function dmc_tmp_resolve_source_orderby( $source, $orderby ) {
	if ( $orderby && 'popularity' !== $orderby ) {
		return $orderby;
	}

	switch ( $source ) {
		case 'bestseller':
			return 'popularity';
		case 'random':
			return 'rand';
		case 'latest':
			return 'date';
		default:
			return $orderby ?: 'popularity';
	}
}

/**
 * Resolve WooCommerce products from section config.
 *
 * @param array<string, mixed> $config Section config.
 * @return WC_Product[]
 */
function dmc_tmp_resolve_product_section_products( array $config ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return [];
	}

	$config = dmc_tmp_normalize_product_section_row( $config );
	$limit  = $config['limit'];
	$source = $config['source'];
	$ids    = array_filter( $config['products'] );

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

	$orderby_key = dmc_tmp_resolve_source_orderby( $source, $config['orderby'] );
	$order_args  = dmc_tmp_product_query_order_args( $orderby_key );

	$query_args = [
		'limit'   => $limit,
		'status'  => 'publish',
		'orderby' => $order_args['orderby'],
		'order'   => $order_args['order'],
	];

	switch ( $source ) {
		case 'on_sale':
			$query_args['on_sale'] = true;
			break;
		case 'featured':
			$query_args['featured'] = true;
			break;
		case 'bestseller':
			$query_args['orderby'] = 'popularity';
			break;
		case 'random':
			$query_args['orderby'] = 'rand';
			break;
		case 'latest':
			$query_args['orderby'] = 'date';
			break;
	}

	$query_args = dmc_tmp_apply_product_section_tax_filter( $query_args, $config );

	$query = new WC_Product_Query( $query_args );

	return $query->get_products();
}

/**
 * "Xem tất cả" URL for a product section.
 *
 * @param array<string, mixed> $config Section config.
 * @return string
 */
function dmc_tmp_product_section_more_url( array $config ) {
	$config = dmc_tmp_normalize_product_section_row( $config );

	if ( ! empty( $config['more_url'] ) ) {
		return $config['more_url'];
	}

	if ( 'category' === $config['source'] && ! empty( $config['category'] ) ) {
		$link = get_term_link( (int) $config['category'], 'product_cat' );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}

	if ( 'brand' === $config['source'] && ! empty( $config['brand'] ) ) {
		$taxonomy = dmc_tmp_brand_taxonomy();
		if ( $taxonomy ) {
			$link = get_term_link( (int) $config['brand'], $taxonomy );
			if ( ! is_wp_error( $link ) ) {
				return $link;
			}
		}
	}

	return class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
}

/**
 * Flash sale product source choices.
 *
 * @return array<string, string>
 */
function dmc_tmp_flash_sale_source_choices() {
	$choices = [
		'manual'   => 'Chọn sản phẩm cụ thể',
		'category' => 'Theo danh mục sản phẩm',
		'brand'    => 'Theo thương hiệu (brand)',
		'on_sale'  => 'Tự động: sản phẩm đang giảm giá',
	];

	if ( ! dmc_tmp_brand_taxonomy() ) {
		unset( $choices['brand'] );
	}

	return $choices;
}

/**
 * ACF field definitions for homepage Flash Sale block.
 *
 * @return array<int, array<string, mixed>>
 */
function dmc_tmp_flash_sale_acf_fields() {
	$brand_tax   = dmc_tmp_brand_taxonomy();
	$when_enable = [
		[
			[
				'field'    => 'field_tmp_hp_flash_enable',
				'operator' => '==',
				'value'    => '1',
			],
		],
	];

	return [
		[
			'key'           => 'field_tmp_hp_flash_enable',
			'label'         => 'Hiển thị Flash Sale',
			'name'          => 'hp_flash_enable',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
			'instructions'  => 'Khối Flash Sale với countdown và sản phẩm giảm giá trên trang chủ.',
		],
		[
			'key'               => 'field_tmp_hp_flash_title',
			'label'             => 'Tiêu đề',
			'name'              => 'hp_flash_title',
			'type'              => 'text',
			'default_value'     => 'FLASH SALE',
			'conditional_logic' => $when_enable,
		],
		[
			'key'               => 'field_tmp_hp_flash_subtitle',
			'label'             => 'Phụ đề',
			'name'              => 'hp_flash_subtitle',
			'type'              => 'text',
			'default_value'     => 'GIÁ SỐC HÔM NAY',
			'conditional_logic' => $when_enable,
		],
		[
			'key'               => 'field_tmp_hp_flash_source',
			'label'             => 'Nguồn sản phẩm',
			'name'              => 'hp_flash_source',
			'type'              => 'select',
			'choices'           => dmc_tmp_flash_sale_source_choices(),
			'default_value'     => 'on_sale',
			'ui'                => 1,
			'instructions'      => 'Chọn sản phẩm thủ công, lọc theo danh mục/brand, hoặc tự động lấy sản phẩm đang giảm giá.',
			'conditional_logic' => $when_enable,
		],
		[
			'key'               => 'field_tmp_hp_flash_products',
			'label'             => 'Chọn sản phẩm Flash Sale',
			'name'              => 'hp_flash_products',
			'type'              => 'relationship',
			'post_type'         => [ 'product' ],
			'filters'           => [ 'search', 'taxonomy' ],
			'return_format'     => 'id',
			'min'               => 1,
			'max'               => 12,
			'instructions'      => 'Tìm sản phẩm theo tên hoặc lọc danh mục. Kéo thả để sắp xếp thứ tự.',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_tmp_hp_flash_enable',
						'operator' => '==',
						'value'    => '1',
					],
					[
						'field'    => 'field_tmp_hp_flash_source',
						'operator' => '==',
						'value'    => 'manual',
					],
				],
			],
		],
		[
			'key'               => 'field_tmp_hp_flash_category',
			'label'             => 'Danh mục sản phẩm',
			'name'              => 'hp_flash_category',
			'type'              => 'taxonomy',
			'taxonomy'          => 'product_cat',
			'field_type'        => 'select',
			'return_format'     => 'id',
			'allow_null'        => 0,
			'add_term'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_tmp_hp_flash_enable',
						'operator' => '==',
						'value'    => '1',
					],
					[
						'field'    => 'field_tmp_hp_flash_source',
						'operator' => '==',
						'value'    => 'category',
					],
				],
			],
		],
		[
			'key'               => 'field_tmp_hp_flash_brand',
			'label'             => 'Thương hiệu',
			'name'              => 'hp_flash_brand',
			'type'              => 'taxonomy',
			'taxonomy'          => $brand_tax ?: 'product_brand',
			'field_type'        => 'select',
			'return_format'     => 'id',
			'allow_null'        => 0,
			'add_term'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_tmp_hp_flash_enable',
						'operator' => '==',
						'value'    => '1',
					],
					[
						'field'    => 'field_tmp_hp_flash_source',
						'operator' => '==',
						'value'    => 'brand',
					],
				],
			],
		],
		[
			'key'               => 'field_tmp_hp_flash_on_sale_only',
			'label'             => 'Chỉ sản phẩm đang giảm giá',
			'name'              => 'hp_flash_on_sale_only',
			'type'              => 'true_false',
			'default_value'     => 1,
			'ui'                => 1,
			'instructions'      => 'Bật để chỉ hiển thị sản phẩm có giá sale (phù hợp Flash Sale).',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_tmp_hp_flash_enable',
						'operator' => '==',
						'value'    => '1',
					],
					[
						'field'    => 'field_tmp_hp_flash_source',
						'operator' => '!=',
						'value'    => 'on_sale',
					],
				],
			],
		],
		[
			'key'               => 'field_tmp_hp_flash_orderby',
			'label'             => 'Sắp xếp sản phẩm',
			'name'              => 'hp_flash_orderby',
			'type'              => 'select',
			'choices'           => dmc_tmp_product_section_sort_choices(),
			'default_value'     => 'popularity',
			'ui'                => 1,
			'instructions'      => 'Không áp dụng khi chọn sản phẩm thủ công (giữ thứ tự đã kéo thả).',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_tmp_hp_flash_enable',
						'operator' => '==',
						'value'    => '1',
					],
					[
						'field'    => 'field_tmp_hp_flash_source',
						'operator' => '!=',
						'value'    => 'manual',
					],
				],
			],
		],
		[
			'key'               => 'field_tmp_hp_flash_limit',
			'label'             => 'Số sản phẩm hiển thị',
			'name'              => 'hp_flash_limit',
			'type'              => 'number',
			'default_value'     => 5,
			'min'               => 3,
			'max'               => 12,
			'conditional_logic' => $when_enable,
		],
		[
			'key'               => 'field_tmp_hp_flash_end',
			'label'             => 'Giờ kết thúc Flash Sale',
			'name'              => 'hp_flash_end_time',
			'type'              => 'time_picker',
			'display_format'    => 'H:i',
			'return_format'     => 'H:i',
			'instructions'      => 'Countdown đếm ngược đến giờ này mỗi ngày. Sau giờ kết thúc sẽ tự chuyển sang ngày hôm sau.',
			'conditional_logic' => $when_enable,
		],
		[
			'key'               => 'field_tmp_hp_flash_more',
			'label'             => 'Link "Xem tất cả" (tuỳ chọn)',
			'name'              => 'hp_flash_more_url',
			'type'              => 'url',
			'instructions'      => 'Để trống: tự lấy link danh mục, brand hoặc trang Cửa hàng.',
			'conditional_logic' => $when_enable,
		],
	];
}

/**
 * Normalize Flash Sale config from ACF.
 *
 * @return array<string, mixed>
 */
function dmc_tmp_normalize_flash_sale_config() {
	$allowed_sort = array_keys( dmc_tmp_product_section_sort_choices() );
	$source       = (string) dmc_tmp_option( 'hp_flash_source', 'on_sale' );
	$allowed_src  = array_keys( dmc_tmp_flash_sale_source_choices() );

	if ( ! in_array( $source, $allowed_src, true ) ) {
		$source = 'on_sale';
	}

	$orderby = (string) dmc_tmp_option( 'hp_flash_orderby', 'popularity' );

	$enable_raw = function_exists( 'get_field' ) ? get_field( 'hp_flash_enable', 'option' ) : null;
	$sale_raw   = function_exists( 'get_field' ) ? get_field( 'hp_flash_on_sale_only', 'option' ) : null;

	return [
		'enable'       => ! isset( $enable_raw ) || $enable_raw,
		'title'        => (string) dmc_tmp_option( 'hp_flash_title', 'FLASH SALE' ),
		'subtitle'     => (string) dmc_tmp_option( 'hp_flash_subtitle', 'GIÁ SỐC HÔM NAY' ),
		'source'       => $source,
		'products'     => array_map( 'intval', (array) dmc_tmp_option( 'hp_flash_products', [] ) ),
		'category'     => (int) dmc_tmp_option( 'hp_flash_category', 0 ),
		'brand'        => (int) dmc_tmp_option( 'hp_flash_brand', 0 ),
		'on_sale_only' => ! isset( $sale_raw ) || $sale_raw,
		'orderby'      => in_array( $orderby, $allowed_sort, true ) ? $orderby : 'popularity',
		'limit'        => max( 3, min( 12, (int) dmc_tmp_option( 'hp_flash_limit', 5 ) ) ),
		'end_time'     => (string) dmc_tmp_option( 'hp_flash_end_time', '' ),
		'more_url'     => (string) dmc_tmp_option( 'hp_flash_more_url', '' ),
	];
}

/**
 * Flash Sale config for homepage.
 *
 * @return array<string, mixed>
 */
function dmc_tmp_get_hp_flash_config() {
	$config = dmc_tmp_normalize_flash_sale_config();

	if ( empty( $config['more_url'] ) ) {
		$config['more_url'] = dmc_tmp_hp_flash_more_url( $config );
	}

	return $config;
}

/**
 * "Xem tất cả" URL for Flash Sale block.
 *
 * @param array<string, mixed> $config Flash config.
 * @return string
 */
function dmc_tmp_hp_flash_more_url( array $config ) {
	if ( ! empty( $config['more_url'] ) ) {
		return $config['more_url'];
	}

	if ( 'category' === $config['source'] && ! empty( $config['category'] ) ) {
		$link = get_term_link( (int) $config['category'], 'product_cat' );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}

	if ( 'brand' === $config['source'] && ! empty( $config['brand'] ) ) {
		$taxonomy = dmc_tmp_brand_taxonomy();
		if ( $taxonomy ) {
			$link = get_term_link( (int) $config['brand'], $taxonomy );
			if ( ! is_wp_error( $link ) ) {
				return $link;
			}
		}
	}

	return class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
}

/**
 * Flash sale countdown seconds until end time today.
 */
function dmc_tmp_hp_flash_countdown_seconds() {
	$end_time = (string) dmc_tmp_option( 'hp_flash_end_time', '' );

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
 * Resolve Flash Sale products from plugin config.
 *
 * @param int|null $limit Optional limit override.
 * @return WC_Product[]
 */
function dmc_tmp_get_hp_flash_products( $limit = null ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return [];
	}

	$config = dmc_tmp_get_hp_flash_config();
	$limit  = $limit ?? $config['limit'];

	$query_config = [
		'source'   => $config['source'],
		'products' => $config['products'],
		'category' => $config['category'],
		'brand'    => $config['brand'],
		'orderby'  => $config['orderby'],
		'limit'    => $limit,
	];

	if ( 'on_sale' === $config['source'] || ! empty( $config['on_sale_only'] ) ) {
		$query_config['on_sale_only'] = true;
	}

	$products = dmc_tmp_resolve_flash_sale_products( $query_config );

	// Legacy ACF flashsale_products repeater.
	if ( empty( $products ) && 'manual' === $config['source'] && function_exists( 'have_rows' ) && have_rows( 'flashsale_products', 'option' ) ) {
		while ( have_rows( 'flashsale_products', 'option' ) ) {
			the_row();
			$post = get_sub_field( 'flashsale' );
			if ( $post instanceof WP_Post ) {
				$product = wc_get_product( $post->ID );
				if ( $product && $product->is_visible() ) {
					$products[] = $product;
				}
			}
			if ( count( $products ) >= $limit ) {
				break;
			}
		}
	}

	// Fallback: any on-sale products.
	if ( empty( $products ) ) {
		$products = dmc_tmp_resolve_flash_sale_products(
			[
				'source'       => 'on_sale',
				'limit'        => $limit,
				'orderby'      => $config['orderby'],
				'on_sale_only' => true,
			]
		);
	}

	return array_slice( $products, 0, $limit );
}

/**
 * Resolve WooCommerce products for Flash Sale query config.
 *
 * @param array<string, mixed> $config Query config.
 * @return WC_Product[]
 */
function dmc_tmp_resolve_flash_sale_products( array $config ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return [];
	}

	$section_config = dmc_tmp_normalize_product_section_row(
		[
			'source'   => $config['source'] ?? 'on_sale',
			'products' => $config['products'] ?? [],
			'category' => $config['category'] ?? 0,
			'brand'    => $config['brand'] ?? 0,
			'orderby'  => $config['orderby'] ?? 'popularity',
			'limit'    => $config['limit'] ?? 5,
		]
	);

	$limit  = $section_config['limit'];
	$source = $section_config['source'];
	$ids    = array_filter( $section_config['products'] );

	if ( 'manual' === $source && ! empty( $ids ) ) {
		$products = [];
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product || ! $product->is_visible() ) {
				continue;
			}
			if ( ! empty( $config['on_sale_only'] ) && ! $product->is_on_sale() ) {
				continue;
			}
			$products[] = $product;
		}

		return array_slice( $products, 0, $limit );
	}

	$orderby_key = dmc_tmp_resolve_source_orderby( $source, $section_config['orderby'] );
	$order_args  = dmc_tmp_product_query_order_args( $orderby_key );

	$query_args = [
		'limit'   => $limit,
		'status'  => 'publish',
		'orderby' => $order_args['orderby'],
		'order'   => $order_args['order'],
	];

	if ( ! empty( $config['on_sale_only'] ) || 'on_sale' === $source ) {
		$query_args['on_sale'] = true;
	}

	switch ( $source ) {
		case 'on_sale':
			$query_args['on_sale'] = true;
			break;
		case 'bestseller':
			$query_args['orderby'] = 'popularity';
			break;
		case 'random':
			$query_args['orderby'] = 'rand';
			break;
		case 'latest':
			$query_args['orderby'] = 'date';
			break;
	}

	$query_args = dmc_tmp_apply_product_section_tax_filter( $query_args, $section_config );

	$query = new WC_Product_Query( $query_args );

	return $query->get_products();
}

/**
 * Load brand taxonomy on Flash Sale brand field.
 *
 * @param array<string, mixed> $field ACF field.
 * @return array<string, mixed>
 */
function dmc_tmp_acf_load_flash_brand_field( $field ) {
	$taxonomy = dmc_tmp_brand_taxonomy();
	if ( $taxonomy ) {
		$field['taxonomy'] = $taxonomy;
	}

	return $field;
}
add_filter( 'acf/load_field/name=hp_flash_brand', 'dmc_tmp_acf_load_flash_brand_field' );

/**
 * ACF fields for homepage featured brands (shown on Wide sale block).
 *
 * @return array<int, array<string, mixed>>
 */
function dmc_tmp_hp_brands_acf_fields() {
	$brand_tax   = dmc_tmp_brand_taxonomy();
	$when_enable = [
		[
			[
				'field'    => 'field_tmp_hp_brands_enable',
				'operator' => '==',
				'value'    => '1',
			],
		],
	];

	$fields = [
		[
			'key'           => 'field_tmp_hp_brands_enable',
			'label'         => 'Hiển thị thương hiệu',
			'name'          => 'hp_brands_enable',
			'type'          => 'true_false',
			'default_value' => 1,
			'ui'            => 1,
			'instructions'  => 'Hiển thị hàng logo thương hiệu phía trên banner Wide sale.',
		],
		[
			'key'               => 'field_tmp_hp_brands_title',
			'label'             => 'Tiêu đề khối thương hiệu',
			'name'              => 'hp_brands_title',
			'type'              => 'text',
			'default_value'     => 'THƯƠNG HIỆU NỔI BẬT',
			'conditional_logic' => $when_enable,
		],
		[
			'key'               => 'field_tmp_hp_brands_mode',
			'label'             => 'Nguồn thương hiệu',
			'name'              => 'hp_brands_mode',
			'type'              => 'select',
			'choices'           => [
				'all'      => 'Tất cả thương hiệu',
				'selected' => 'Chọn thương hiệu cụ thể',
			],
			'default_value'     => 'all',
			'ui'                => 1,
			'instructions'      => 'Chọn "Tất cả" để hiển thị mọi thương hiệu trong hệ thống, hoặc chọn từng brand bên dưới.',
			'conditional_logic' => $when_enable,
		],
	];

	if ( $brand_tax ) {
		$fields[] = [
			'key'               => 'field_tmp_hp_brands_terms',
			'label'             => 'Chọn thương hiệu',
			'name'              => 'hp_brands_terms',
			'type'              => 'taxonomy',
			'taxonomy'          => $brand_tax,
			'field_type'        => 'multi_select',
			'return_format'     => 'id',
			'add_term'          => 0,
			'instructions'      => 'Giữ Ctrl (Windows) hoặc Cmd (Mac) để chọn nhiều. Thứ tự chọn sẽ được giữ khi hiển thị.',
			'conditional_logic' => [
				[
					[
						'field'    => 'field_tmp_hp_brands_enable',
						'operator' => '==',
						'value'    => '1',
					],
					[
						'field'    => 'field_tmp_hp_brands_mode',
						'operator' => '==',
						'value'    => 'selected',
					],
				],
			],
		];
	} else {
		$fields[] = [
			'key'               => 'field_tmp_hp_brands_no_tax',
			'label'             => 'Thương hiệu',
			'name'              => '',
			'type'              => 'message',
			'message'           => 'Chưa phát hiện taxonomy thương hiệu (WooCommerce Brands hoặc Perfect Brands). Vui lòng kích hoạt plugin brand trước.',
			'conditional_logic' => $when_enable,
		];
	}

	return $fields;
}

/**
 * Normalize homepage brands config.
 *
 * @return array{enable:bool,title:string,mode:string,term_ids:int[]}
 */
function dmc_tmp_get_hp_brands_config() {
	$mode = (string) dmc_tmp_option( 'hp_brands_mode', 'all' );

	if ( ! in_array( $mode, [ 'all', 'selected' ], true ) ) {
		$mode = 'all';
	}

	$enable_raw = function_exists( 'get_field' ) ? get_field( 'hp_brands_enable', 'option' ) : null;

	return [
		'enable'   => ! isset( $enable_raw ) || $enable_raw,
		'title'    => trim( (string) dmc_tmp_option( 'hp_brands_title', 'THƯƠNG HIỆU NỔI BẬT' ) ) ?: 'THƯƠNG HIỆU NỔI BẬT',
		'mode'     => $mode,
		'term_ids' => array_values( array_filter( array_map( 'intval', (array) dmc_tmp_option( 'hp_brands_terms', [] ) ) ) ),
	];
}

/**
 * Whether homepage brands row is enabled.
 */
function dmc_tmp_hp_brands_enabled() {
	return dmc_tmp_get_hp_brands_config()['enable'];
}

/**
 * Brand display item from taxonomy term.
 *
 * @param WP_Term $brand Brand term.
 * @return array{url:string,alt:string,link:string,name:string}|null
 */
function dmc_tmp_brand_item_from_term( $brand ) {
	if ( ! $brand || is_wp_error( $brand ) ) {
		return null;
	}

	$link = get_term_link( $brand );
	$link = is_wp_error( $link ) ? '' : $link;

	$url = '';
	$alt = $brand->name;

	if ( function_exists( 'dmc_brand_term_attachment_id' ) ) {
		$attachment_id = dmc_brand_term_attachment_id( $brand );
		if ( $attachment_id ) {
			$url = (string) wp_get_attachment_image_url( $attachment_id, 'full' );
			$alt = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ?: $brand->name;
		}
	}

	if ( ! $url && function_exists( 'wc_get_brand_thumbnail_url' ) && 'product_brand' === $brand->taxonomy ) {
		$thumb = wc_get_brand_thumbnail_url( $brand, 'full' );
		if ( $thumb && ! str_contains( (string) $thumb, 'placeholder' ) ) {
			$url = (string) $thumb;
		}
	}

	return [
		'url'  => $url,
		'alt'  => $alt,
		'link' => $link,
		'name' => $brand->name,
	];
}

/**
 * Featured brands for homepage Wide sale block.
 *
 * @return array<int, array{url:string,alt:string,link:string,name:string}>
 */
function dmc_tmp_get_hp_brands() {
	$config = dmc_tmp_get_hp_brands_config();

	if ( ! $config['enable'] ) {
		return [];
	}

	$taxonomy = dmc_tmp_brand_taxonomy();
	if ( ! $taxonomy ) {
		return dmc_tmp_get_hp_brands_legacy();
	}

	$terms = [];

	if ( 'selected' === $config['mode'] && ! empty( $config['term_ids'] ) ) {
		foreach ( $config['term_ids'] as $term_id ) {
			$term = get_term( $term_id, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$terms[] = $term;
			}
		}
	} else {
		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			$terms = [];
		}
	}

	$brands = [];
	foreach ( $terms as $term ) {
		$item = dmc_tmp_brand_item_from_term( $term );
		if ( $item ) {
			$brands[] = $item;
		}
	}

	if ( ! empty( $brands ) ) {
		return $brands;
	}

	return dmc_tmp_get_hp_brands_legacy();
}

/**
 * Legacy featured brands from Setting Web (web_featured_brands).
 *
 * @return array<int, array{url:string,alt:string,link:string,name:string}>
 */
function dmc_tmp_get_hp_brands_legacy() {
	if ( ! function_exists( 'get_field' ) ) {
		return [];
	}

	$rows   = get_field( 'web_featured_brands', 'option' );
	$brands = [];

	if ( empty( $rows ) || ! is_array( $rows ) ) {
		return [];
	}

	foreach ( $rows as $row ) {
		$image = function_exists( 'dmc_tmp_image_field' )
			? dmc_tmp_image_field( $row['brand_image'] ?? null )
			: null;

		if ( ! $image && function_exists( 'dmc_homepage_image_field' ) ) {
			$image = dmc_homepage_image_field( $row['brand_image'] ?? null );
		}

		if ( ! $image ) {
			continue;
		}

		$name = trim( (string) ( $row['brand_name'] ?? '' ) );

		$brands[] = [
			'url'  => (string) $image['url'],
			'alt'  => $name ?: (string) ( $image['alt'] ?: __( 'Thương hiệu', 'flatsome-child' ) ),
			'link' => esc_url( $row['brand_link'] ?? '' ) ?: '',
			'name' => $name ?: (string) ( $image['alt'] ?? '' ),
		];
	}

	return $brands;
}

/**
 * Load brand taxonomy on homepage brands multi-select field.
 *
 * @param array<string, mixed> $field ACF field.
 * @return array<string, mixed>
 */
function dmc_tmp_acf_load_hp_brands_terms_field( $field ) {
	$taxonomy = dmc_tmp_brand_taxonomy();
	if ( $taxonomy ) {
		$field['taxonomy'] = $taxonomy;
	}

	return $field;
}
add_filter( 'acf/load_field/name=hp_brands_terms', 'dmc_tmp_acf_load_hp_brands_terms_field' );

/**
 * Get configured homepage product sections from plugin ACF.
 *
 * @return array<int, array<string, mixed>>
 */
function dmc_tmp_get_hp_product_sections() {
	$sections = [];

	if ( function_exists( 'have_rows' ) && have_rows( 'tmp_hp_product_sections', 'option' ) ) {
		while ( have_rows( 'tmp_hp_product_sections', 'option' ) ) {
			the_row();

			$row = dmc_tmp_normalize_product_section_row(
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
		$sections = dmc_tmp_default_hp_product_sections();
	}

	return $sections;
}
