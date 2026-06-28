<?php
/**
 * Homepage helpers — product queries & card rendering.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get web option (Setting Web page).
 */
function dmc_web_option( $key, $default = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $key, 'option' );

	return ( null === $value || false === $value || '' === $value ) ? $default : $value;
}

/**
 * Normalize ACF image field to url + alt.
 */
function dmc_homepage_image_field( $image ) {
	if ( empty( $image ) ) {
		return null;
	}

	if ( is_numeric( $image ) ) {
		$url = wp_get_attachment_image_url( (int) $image, 'full' );
		if ( ! $url ) {
			return null;
		}

		return [
			'url' => $url,
			'alt' => get_post_meta( (int) $image, '_wp_attachment_image_alt', true ) ?: get_bloginfo( 'name' ),
		];
	}

	if ( is_array( $image ) && ! empty( $image['url'] ) ) {
		return [
			'url' => $image['url'],
			'alt' => $image['alt'] ?? get_bloginfo( 'name' ),
		];
	}

	return null;
}

/**
 * Resolve Flatsome theme mod logo URL (Customizer → Logo & Site Identity).
 */
function dmc_homepage_get_flatsome_logo_url( $mod = 'site_logo' ) {
	if ( ! function_exists( 'get_theme_mod' ) ) {
		return '';
	}

	$url = get_theme_mod( $mod, '' );

	if ( is_string( $url ) && $url !== '' && function_exists( 'do_shortcode' ) ) {
		$url = do_shortcode( $url );
	}

	return is_string( $url ) ? $url : '';
}

/**
 * Logo data for homepage header / drawer.
 *
 * Primary source: Flatsome Customizer (Appearance → Customize → Header → Logo & Site Identity).
 * Header custom luôn nền xanh → ưu tiên "Logo image" (site_logo), sau đó "Light Version" (site_logo_dark).
 */
function dmc_homepage_get_logo() {
	$site_name = get_bloginfo( 'name' );
	$logo      = null;

	// Flatsome — main logo first (field users edit most), then light version for dark headers.
	$flatsome_url = dmc_homepage_get_flatsome_logo_url( 'site_logo' );
	if ( ! $flatsome_url ) {
		$flatsome_url = dmc_homepage_get_flatsome_logo_url( 'site_logo_dark' );
	}

	if ( $flatsome_url ) {
		$logo = [
			'url' => $flatsome_url,
			'alt' => $site_name,
		];
	}

	// WordPress Site Identity → Logo.
	if ( ! $logo && get_theme_mod( 'custom_logo' ) ) {
		$logo = dmc_homepage_image_field( get_theme_mod( 'custom_logo' ) );
	}

	// Optional override from Setting Web (only when Flatsome logo is empty).
	if ( ! $logo ) {
		$logo = dmc_homepage_image_field( dmc_web_option( 'web_logo' ) );
	}

	$logo_mobile = dmc_homepage_image_field( dmc_web_option( 'web_logo_mobile' ) );

	if ( ! $logo_mobile ) {
		$sticky_url = dmc_homepage_get_flatsome_logo_url( 'site_logo_sticky' );
		if ( $sticky_url ) {
			$logo_mobile = [
				'url' => $sticky_url,
				'alt' => $site_name,
			];
		}
	}

	if ( ! $logo_mobile && $logo ) {
		$logo_mobile = $logo;
	}

	$max_width = get_theme_mod( 'logo_max_width', '' );
	$width     = (int) get_theme_mod( 'logo_width', 200 );

	return [
		'url'        => $logo['url'] ?? '',
		'mobile_url' => $logo_mobile['url'] ?? ( $logo['url'] ?? '' ),
		'alt'        => $logo['alt'] ?? $site_name,
		'has_image'  => ! empty( $logo['url'] ),
		'max_width'  => $max_width !== '' && $max_width !== null ? (int) $max_width : null,
		'width'      => $width,
	];
}

/**
 * Format price for homepage cards.
 */
function dmc_homepage_format_price( $price ) {
	if ( ! $price ) {
		return '';
	}

	return number_format( (float) $price, 0, '', '.' ) . '₫';
}

/**
 * Discount percentage label.
 */
function dmc_homepage_discount_label( WC_Product $product ) {
	if ( ! $product->is_on_sale() ) {
		return '';
	}

	$regular = (float) $product->get_regular_price();
	$sale    = (float) $product->get_sale_price();

	if ( ! $regular || ! $sale ) {
		return '';
	}

	return '-' . round( ( ( $regular - $sale ) / $regular ) * 100 ) . '%';
}

/**
 * Query WooCommerce products.
 */
function dmc_homepage_get_products( array $args = [] ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return [];
	}

	$defaults = [
		'limit'   => 6,
		'status'  => 'publish',
		'orderby' => 'date',
		'order'   => 'DESC',
	];

	$query = new WC_Product_Query( wp_parse_args( $args, $defaults ) );

	return $query->get_products();
}

/**
 * Flash sale products from ACF config.
 */
function dmc_homepage_get_flash_products( $limit = null ) {
	if ( function_exists( 'dmc_tmp_get_hp_flash_products' ) ) {
		return dmc_tmp_get_hp_flash_products( $limit );
	}

	$config = dmc_homepage_get_flash_config();
	$limit  = $limit ?? $config['limit'];

	$products = dmc_homepage_resolve_products(
		[
			'source'   => $config['source'],
			'products' => $config['products'],
			'limit'    => $limit,
		]
	);

	// Legacy ACF flashsale_products repeater.
	if ( empty( $products ) && 'manual' === $config['source'] && function_exists( 'have_rows' ) && have_rows( 'flashsale_products', 'option' ) ) {
		while ( have_rows( 'flashsale_products', 'option' ) ) {
			the_row();
			$post = get_sub_field( 'flashsale' );
			if ( $post instanceof WP_Post ) {
				$product = wc_get_product( $post->ID );
				if ( $product ) {
					$products[] = $product;
				}
			}
			if ( count( $products ) >= $limit ) {
				break;
			}
		}
	}

	// Fallback: sản phẩm giảm giá.
	if ( empty( $products ) ) {
		$products = dmc_homepage_resolve_products(
			[
				'source' => 'on_sale',
				'limit'  => $limit,
			]
		);
	}

	return array_slice( $products, 0, $limit );
}

/**
 * Best selling products.
 */
function dmc_homepage_get_bestsellers( $limit = 6 ) {
	return dmc_homepage_get_products(
		[
			'limit'    => $limit,
			'orderby'  => 'popularity',
			'order'    => 'DESC',
		]
	);
}

/**
 * Suggested / latest products.
 */
function dmc_homepage_get_suggested( $limit = 6 ) {
	return dmc_homepage_get_products(
		[
			'limit'   => $limit,
			'orderby' => 'rand',
		]
	);
}

/**
 * Product category term IDs excluded from public category lists.
 *
 * @return int[]
 */
function dmc_excluded_product_cat_ids() {
	$ids        = [];
	$default_id = (int) get_option( 'default_product_cat' );

	if ( $default_id > 0 ) {
		$ids[] = $default_id;
	}

	foreach ( [ 'uncategorized', 'chua-phan-loai' ] as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$ids[] = (int) $term->term_id;
		}
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Top-level product categories for homepage.
 */
function dmc_homepage_get_categories( $limit = null ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return [];
	}

	$limit = $limit ?? (int) dmc_homepage_option( 'hp_categories_limit', 9 );

	$args = [
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
		'number'     => $limit,
	];

	$exclude = dmc_excluded_product_cat_ids();
	if ( $exclude ) {
		$args['exclude'] = $exclude;
	}

	return get_terms( $args );
}

/**
 * @deprecated Use dmc_homepage_category_icon_slug().
 */
function dmc_homepage_category_icon( $slug ) {
	return dmc_homepage_category_icon_slug( $slug );
}

/**
 * Render flash sale product card.
 */
function dmc_homepage_render_flash_card( WC_Product $product ) {
	$permalink = $product->get_permalink();
	$thumb     = $product->get_image( 'woocommerce_thumbnail' );
	$discount  = dmc_homepage_discount_label( $product );
	$sale      = dmc_homepage_format_price( $product->get_sale_price() ?: $product->get_price() );
	$regular   = dmc_homepage_format_price( $product->get_regular_price() );
	$rating    = $product->get_average_rating();
	$count     = $product->get_rating_count();
	?>
	<article class="flash-card">
		<a href="<?php echo esc_url( $permalink ); ?>" class="flash-thumb">
			<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php
			if ( function_exists( 'dmc_pl_get_primary_gift' ) ) {
				$flash_gift = dmc_pl_get_primary_gift( $product->get_id() );
				if ( $flash_gift ) {
					dmc_pl_render_gift_badge( $flash_gift );
				}
			}
			?>
			<?php if ( $discount ) : ?>
				<span class="flash-discount"><?php echo esc_html( $discount ); ?></span>
			<?php endif; ?>
		</a>
		<div class="flash-info">
			<a href="<?php echo esc_url( $permalink ); ?>" class="flash-name"><?php echo esc_html( $product->get_name() ); ?></a>
			<div class="flash-price"><?php echo esc_html( $sale ); ?></div>
			<?php if ( $product->is_on_sale() && $regular ) : ?>
				<div class="flash-old"><?php echo esc_html( $regular ); ?></div>
			<?php endif; ?>
			<div class="flash-bottom">
				<div class="flash-stars">
					<span class="flash-stars__icons" aria-hidden="true">★★★★★</span>
					<span class="flash-stars__count">(<?php echo esc_html( max( 1, (int) $count ) ); ?>)</span>
				</div>
				<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="flash-cart" aria-label="<?php esc_attr_e( 'Thêm vào giỏ', 'flatsome-child' ); ?>"><?php echo dmc_icon( 'cart', [ 'size' => 16, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Render standard product card.
 */
function dmc_homepage_render_product_card( WC_Product $product ) {
	$product_id = $product->get_id();
	$permalink  = $product->get_permalink();
	$thumb      = $product->get_image( 'woocommerce_thumbnail' );
	$discount   = dmc_homepage_discount_label( $product );
	$sale       = dmc_homepage_format_price( $product->get_sale_price() ?: $product->get_price() );
	$regular    = dmc_homepage_format_price( $product->get_regular_price() );
	$rating     = (float) $product->get_average_rating();
	$count      = (int) $product->get_rating_count();
	$on_sale    = $product->is_on_sale() && $product->get_regular_price();

	$overlay = function_exists( 'dmc_tmp_get_discount_overlay' )
		? dmc_tmp_get_discount_overlay( $product_id )
		: null;

	$primary_gift = function_exists( 'dmc_pl_get_primary_gift' )
		? dmc_pl_get_primary_gift( $product_id )
		: null;

	$gift_value = function_exists( 'dmc_tmp_get_gift_value' )
		? dmc_tmp_get_gift_value( $product_id )
		: '';

	$price_label = function_exists( 'dmc_tmp_get_price_label' )
		? dmc_tmp_get_price_label( $product_id )
		: 'Giá khuyến mãi:';

	$show_call = function_exists( 'dmc_tmp_show_call_cta' )
		? dmc_tmp_show_call_cta( $product_id )
		: true;

	$hotline = function_exists( 'dmc_tmp_get_company_hotline' )
		? dmc_tmp_get_company_hotline()
		: preg_replace( '/\s+/', '', (string) dmc_web_option( 'web_hotline', '19002628' ) );
	$hotline_show = function_exists( 'dmc_tmp_get_company_hotline_display' )
		? dmc_tmp_get_company_hotline_display()
		: $hotline;

	$display_rating = $rating > 0 ? $rating : 5;
	?>
	<article class="product dmc-product-card">
		<a href="<?php echo esc_url( $permalink ); ?>" class="prod-image">
			<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php if ( $primary_gift ) : ?>
				<?php dmc_pl_render_gift_badge( $primary_gift ); ?>
			<?php endif; ?>
			<?php if ( $on_sale && $overlay && ! empty( $overlay['url'] ) ) : ?>
				<img
					class="prod-discount-overlay"
					src="<?php echo esc_url( $overlay['url'] ); ?>"
					alt="<?php echo esc_attr( $overlay['alt'] ?: __( 'Khuyến mãi', 'flatsome-child' ) ); ?>"
					loading="lazy"
				>
			<?php endif; ?>
		</a>

		<div class="prod-body">
			<a href="<?php echo esc_url( $permalink ); ?>" class="prod-name"><?php echo esc_html( $product->get_name() ); ?></a>

			<?php if ( $show_call && $hotline ) : ?>
				<p class="prod-call-cta">
					<?php esc_html_e( 'Gọi', 'flatsome-child' ); ?>
					<span class="prod-call-icon" aria-hidden="true">📞</span>
					<a href="tel:<?php echo esc_attr( $hotline ); ?>"><?php echo esc_html( $hotline_show ); ?></a>
					<?php esc_html_e( 'để được giảm thêm', 'flatsome-child' ); ?>
				</p>
			<?php endif; ?>

			<div class="prod-price-wrap">
				<?php if ( $on_sale ) : ?>
					<div class="prod-sale-line">
						<span class="prod-price-label"><?php echo esc_html( $price_label ); ?></span>
						<strong class="prod-sale-price"><?php echo esc_html( $sale ); ?></strong>
					</div>
					<div class="prod-price-row">
						<span class="prod-regular"><?php echo esc_html( $regular ); ?></span>
						<?php if ( $discount ) : ?>
							<span class="prod-discount-badge"><?php echo esc_html( $discount ); ?></span>
						<?php endif; ?>
					</div>
				<?php else : ?>
					<div class="prod-sale-line">
						<strong class="prod-sale-price"><?php echo esc_html( $sale ); ?></strong>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( $primary_gift && ! empty( $primary_gift['title'] ) ) : ?>
				<p class="prod-gift">
					<?php
					printf(
						/* translators: %s: gift name */
						esc_html__( 'Tặng kèm: %s', 'flatsome-child' ),
						esc_html( $primary_gift['title'] )
					);
					?>
				</p>
			<?php elseif ( $gift_value ) : ?>
				<p class="prod-gift">
					<?php
					printf(
						/* translators: %s: gift value */
						esc_html__( 'Quà tặng trị giá %s', 'flatsome-child' ),
						esc_html( $gift_value )
					);
					?>
				</p>
			<?php endif; ?>

			<div class="prod-rating">
				<span class="prod-rating-star" aria-hidden="true">★</span>
				<?php
				printf(
					/* translators: 1: rating score, 2: review count */
					esc_html__( 'Đánh giá %1$s/5 (%2$d)', 'flatsome-child' ),
					esc_html( number_format( $display_rating, 1 ) ),
					(int) max( 0, $count )
				);
				?>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Homepage banner slides for Swiper.
 *
 * Reads from plugin settings (homepage_slides) with legacy theme fallback.
 */
function dmc_homepage_get_slides() {
	if ( function_exists( 'dmc_tmp_get_homepage_slides' ) ) {
		return dmc_tmp_get_homepage_slides();
	}

	$slides = [];

	if ( function_exists( 'have_rows' ) && have_rows( 'homepage_slides', 'option' ) ) {
		while ( have_rows( 'homepage_slides', 'option' ) ) {
			the_row();
			$image = get_sub_field( 'image' );
			if ( ! $image ) {
				continue;
			}

			$slides[] = [
				'url' => get_sub_field( 'link' ) ?: '#',
				'src' => is_array( $image ) ? $image['url'] : wp_get_attachment_image_url( $image, 'full' ),
				'alt' => is_array( $image ) ? ( $image['alt'] ?? '' ) : get_post_meta( $image, '_wp_attachment_image_alt', true ),
			];
		}
	}

	if ( empty( $slides ) && function_exists( 'get_field' ) ) {
		$gallery = get_field( 'homepage_banner_gallery', 'option' );
		if ( is_array( $gallery ) ) {
			foreach ( $gallery as $image ) {
				if ( ! $image ) {
					continue;
				}
				$slides[] = [
					'url' => '#',
					'src' => is_array( $image ) ? $image['url'] : wp_get_attachment_image_url( $image, 'full' ),
					'alt' => is_array( $image ) ? ( $image['alt'] ?? '' ) : '',
				];
			}
		}
	}

	return $slides;
}

/**
 * Featured brands from Setting Web (web_featured_brands repeater).
 *
 * @return array<int, array{url:string,alt:string,link:string}>
 */
function dmc_homepage_get_featured_brands() {
	if ( function_exists( 'dmc_tmp_get_hp_brands' ) ) {
		return dmc_tmp_get_hp_brands();
	}

	if ( ! function_exists( 'get_field' ) ) {
		return [];
	}

	$rows   = get_field( 'web_featured_brands', 'option' );
	$brands = [];

	if ( ! empty( $rows ) && is_array( $rows ) ) {
		foreach ( $rows as $row ) {
			$image = dmc_homepage_image_field( $row['brand_image'] ?? null );
			if ( ! $image ) {
				continue;
			}

			$name = trim( (string) ( $row['brand_name'] ?? '' ) );

			$brands[] = [
				'url'  => $image['url'],
				'alt'  => $name ?: ( $image['alt'] ?: __( 'Thương hiệu', 'flatsome-child' ) ),
				'link' => esc_url( $row['brand_link'] ?? '' ) ?: '',
			];
		}
	}

	return $brands;
}

/**
 * WooCommerce category thumbnail attachment ID (Sửa danh mục → Hình thu nhỏ).
 */
function dmc_homepage_term_thumbnail_id( $term ) {
	if ( is_numeric( $term ) ) {
		$term = get_term( (int) $term, 'product_cat' );
	}

	if ( ! $term || is_wp_error( $term ) ) {
		return 0;
	}

	return (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
}

/**
 * Mega menu data: parent categories + child grid (WooCommerce).
 */
function dmc_homepage_mega_menu_data() {
	$args = [
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'parent'     => 0,
		'orderby'    => 'menu_order',
		'order'      => 'ASC',
		'number'     => 15,
	];

	$exclude = dmc_excluded_product_cat_ids();
	if ( $exclude ) {
		$args['exclude'] = $exclude;
	}

	$parents = get_terms( $args );

	if ( empty( $parents ) || is_wp_error( $parents ) ) {
		return dmc_homepage_mega_menu_defaults();
	}

	$groups = [];
	foreach ( $parents as $parent ) {
		$children = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'parent'     => $parent->term_id,
				'orderby'    => 'menu_order',
				'order'      => 'ASC',
				'number'     => 18,
			]
		);

		$subs = [];
		if ( ! empty( $children ) && ! is_wp_error( $children ) ) {
			foreach ( $children as $child ) {
				$link = get_term_link( $child );
				if ( is_wp_error( $link ) ) {
					continue;
				}
				$subs[] = [
					'label' => $child->name,
					'url'   => $link,
					'icon'  => dmc_homepage_term_icon_html( $child ),
				];
			}
		}

		$parent_link = get_term_link( $parent );
		if ( is_wp_error( $parent_link ) ) {
			$parent_link = '#';
		}

		$brands = function_exists( 'dmc_category_get_brands_for_menu' )
			? dmc_category_get_brands_for_menu( $parent->term_id )
			: [];

		$groups[] = [
			'label'    => $parent->name,
			'url'      => $parent_link,
			'children' => $subs,
			'brands'   => $brands,
		];
	}

	return $groups;
}

/**
 * Fallback mega menu when no WooCommerce categories.
 */
function dmc_homepage_mega_menu_defaults() {
	$map = [
		[
			'label' => 'Điện tử, điện lạnh',
			'slug'  => 'dien-lanh',
			'kids'  => [
				[ 'Máy lạnh', 'dieu-hoa' ],
				[ 'Máy giặt', 'may-giat' ],
				[ 'Tủ lạnh', 'tu-lanh' ],
				[ 'Tivi', 'tivi' ],
				[ 'Máy nước nóng', 'gia-dung' ],
				[ 'Máy sấy quần áo', 'may-giat' ],
				[ 'Tủ mát', 'tu-lanh' ],
				[ 'Tủ đông', 'tu-lanh' ],
				[ 'Loa Karaoke', 'am-thanh' ],
				[ 'Máy rửa chén', 'gia-dung' ],
				[ 'Loa', 'am-thanh' ],
				[ 'Dàn âm thanh', 'am-thanh' ],
			],
		],
		[
			'label' => 'Điện gia dụng',
			'slug'  => 'gia-dung',
			'kids'  => [
				[ 'Nồi cơm điện', 'gia-dung' ],
				[ 'Nồi chiên không dầu', 'gia-dung' ],
				[ 'Lò vi sóng', 'gia-dung' ],
				[ 'Máy hút bụi', 'gia-dung' ],
				[ 'Quạt điện', 'dieu-hoa' ],
				[ 'Bếp từ', 'gia-dung' ],
			],
		],
		[
			'label' => 'Điện thoại, Laptop',
			'slug'  => 'dien-thoai',
			'kids'  => [
				[ 'iPhone', 'dien-thoai' ],
				[ 'Samsung', 'dien-thoai' ],
				[ 'Laptop', 'laptop' ],
				[ 'Tablet', 'dien-thoai' ],
				[ 'Phụ kiện', 'phu-kien' ],
			],
		],
	];

	$shop = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : '#';
	$groups = [];

	foreach ( $map as $group ) {
		$children = [];
		foreach ( $group['kids'] as $kid ) {
			$children[] = [
				'label' => $kid[0],
				'url'   => $shop,
				'icon'  => dmc_icon( $kid[1], [ 'size' => 28, 'variant' => 'blue', 'class' => 'dmc-icon--mega' ] ),
			];
		}
		$groups[] = [
			'label'    => $group['label'],
			'url'      => $shop,
			'children' => $children,
			'brands'   => [],
		];
	}

	return $groups;
}

/**
 * Navigation categories for mega menu / drawer.
 */
function dmc_homepage_nav_categories() {
	$groups = dmc_homepage_mega_menu_data();
	$items  = [];

	foreach ( $groups as $group ) {
		$links = [];

		if ( ! empty( $group['brands'] ) ) {
			foreach ( $group['brands'] as $brand ) {
				$links[] = [
					'label' => $brand['label'],
					'url'   => $brand['url'],
				];
			}
		} else {
			foreach ( $group['children'] as $child ) {
				$links[] = [
					'label' => $child['label'],
					'url'   => $child['url'],
				];
			}
		}

		$items[] = [
			'label' => $group['label'],
			'url'   => $group['url'],
			'links' => $links,
		];
	}

	return $items;
}
