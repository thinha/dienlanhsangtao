<?php
/**
 * Shared helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get plugin option.
 */
function dmc_tmp_option( $key, $default = null ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$value = get_field( $key, 'option' );

	return ( null === $value || false === $value || '' === $value ) ? $default : $value;
}

/**
 * Normalize image field to url + alt.
 */
function dmc_tmp_image_field( $image ) {
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
			'alt' => get_post_meta( (int) $image, '_wp_attachment_image_alt', true ) ?: '',
		];
	}

	if ( is_array( $image ) && ! empty( $image['url'] ) ) {
		return [
			'url' => $image['url'],
			'alt' => $image['alt'] ?? '',
		];
	}

	return null;
}

/**
 * Custom discount overlay image for a product.
 */
function dmc_tmp_get_discount_overlay( $product_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}

	return dmc_tmp_image_field( get_field( 'pl_discount_overlay', $product_id ) );
}

/**
 * Gift value text for product card.
 */
function dmc_tmp_get_gift_value( $product_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	return trim( (string) get_field( 'pl_gift_value', $product_id ) );
}

/**
 * Price label prefix on card.
 */
function dmc_tmp_get_price_label( $product_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return 'Giá khuyến mãi:';
	}

	$label = get_field( 'pl_price_label', $product_id );

	return $label ? (string) $label : 'Giá khuyến mãi:';
}

/**
 * Company hotline for product card CTA (tel: link, digits only).
 */
function dmc_tmp_get_company_hotline() {
	$hotline = trim( (string) dmc_tmp_option( 'tmp_company_hotline', '1900 2323 88' ) );

	return preg_replace( '/\s+/', '', $hotline );
}

/**
 * Company hotline display text on product cards.
 */
function dmc_tmp_get_company_hotline_display() {
	$hotline = trim( (string) dmc_tmp_option( 'tmp_company_hotline', '1900 2323 88' ) );

	if ( '' === $hotline ) {
		return '';
	}

	return preg_replace( '/\s+/', ' ', $hotline );
}

/**
 * Company working hours (hotline support window).
 */
function dmc_tmp_get_company_hotline_hours() {
	return trim( (string) dmc_tmp_option( 'tmp_company_hotline_hours', '8:00 - 21:00' ) );
}

/**
 * Company name from plugin settings.
 */
function dmc_tmp_get_company_name() {
	return trim( (string) dmc_tmp_option( 'tmp_company_name', '' ) );
}

/**
 * Show call CTA on product card.
 */
function dmc_tmp_show_call_cta( $product_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return true;
	}

	$value = get_field( 'pl_show_call_cta', $product_id );

	return ! isset( $value ) || $value;
}

/**
 * Product columns per row on shop/archive listing (desktop).
 *
 * @return int 3|4
 */
function dmc_tmp_get_archive_columns() {
	$value = (int) dmc_tmp_option( 'tmp_archive_columns', 3 );

	return in_array( $value, [ 3, 4 ], true ) ? $value : 3;
}

/**
 * Products per page on shop/archive listing.
 */
function dmc_tmp_get_archive_per_page() {
	$value = (int) dmc_tmp_option( 'tmp_archive_per_page', 24 );

	return max( 6, min( 60, $value ?: 24 ) );
}

/**
 * Default product order on archive when visitor has not chosen sort.
 *
 * @return string relevance|date|random
 */
function dmc_tmp_get_archive_default_order() {
	$allowed = [ 'relevance', 'date', 'random' ];
	$value   = (string) dmc_tmp_option( 'tmp_archive_default_order', 'relevance' );

	return in_array( $value, $allowed, true ) ? $value : 'relevance';
}

/**
 * Get configured tab menu tabs.
 *
 * @return array<int, array<string, mixed>>
 */
function dmc_tmp_get_tabs() {
	$tabs = [];

	if ( ! function_exists( 'have_rows' ) || ! have_rows( 'tmp_tabs', 'option' ) ) {
		return $tabs;
	}

	while ( have_rows( 'tmp_tabs', 'option' ) ) {
		the_row();

		$row = [
			'enable'   => ! empty( get_sub_field( 'enable' ) ),
			'title'    => (string) ( get_sub_field( 'title' ) ?? '' ),
			'icon'     => dmc_tmp_image_field( get_sub_field( 'icon' ) ),
			'source'   => get_sub_field( 'source' ) ?: 'newest',
			'category' => (int) ( get_sub_field( 'category' ) ?? 0 ),
			'brand'    => (int) ( get_sub_field( 'brand' ) ?? 0 ),
			'orderby'  => (string) ( get_sub_field( 'orderby' ) ?? 'date' ),
			'products' => array_map( 'intval', (array) ( get_sub_field( 'products' ) ?? [] ) ),
			'limit'    => max( 1, min( 50, (int) ( get_sub_field( 'limit' ) ?? 10 ) ) ),
			'more_url' => (string) ( get_sub_field( 'more_url' ) ?? '' ),
		];

		if ( $row['enable'] && $row['title'] ) {
			$tabs[] = $row;
		}
	}

	return $tabs;
}

/**
 * Resolve products for a tab config.
 *
 * @return WC_Product[]
 */
function dmc_tmp_resolve_tab_products( array $tab ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return [];
	}

	$config = [
		'source'   => $tab['source'] ?? 'newest',
		'products' => $tab['products'] ?? [],
		'category' => $tab['category'] ?? 0,
		'brand'    => $tab['brand'] ?? 0,
		'orderby'  => $tab['orderby'] ?? 'date',
		'limit'    => $tab['limit'] ?? 10,
	];

	$source = $config['source'];

	if ( 'newest' === $source ) {
		$config['source']  = 'latest';
		$config['orderby'] = $config['orderby'] ?: 'date';
	}

	if ( 'random' === $source ) {
		$config['source']  = 'random';
		$config['orderby'] = 'rand';
	}

	if ( 'category' === $source || 'brand' === $source ) {
		$config['source'] = $source;
	}

	return dmc_tmp_resolve_product_section_products( $config );
}

/**
 * Whether hero banner slider is enabled.
 */
function dmc_tmp_hp_hero_enabled() {
	if ( ! function_exists( 'get_field' ) ) {
		return true;
	}

	$value = get_field( 'hp_hero_enable', 'option' );

	return ! isset( $value ) || $value;
}

/**
 * Homepage banner slides for Swiper.
 *
 * ACF option repeater: homepage_slides (sub fields: enable, image, link).
 *
 * @return array<int, array{url:string,src:string,alt:string}>
 */
function dmc_tmp_get_homepage_slides() {
	$slides = [];

	if ( ! function_exists( 'have_rows' ) || ! have_rows( 'homepage_slides', 'option' ) ) {
		return $slides;
	}

	while ( have_rows( 'homepage_slides', 'option' ) ) {
		the_row();

		if ( ! get_sub_field( 'enable' ) && null !== get_sub_field( 'enable' ) ) {
			continue;
		}

		$image = dmc_tmp_image_field( get_sub_field( 'image' ) );
		if ( ! $image ) {
			continue;
		}

		$link = trim( (string) ( get_sub_field( 'link' ) ?? '' ) );

		$slides[] = [
			'url' => $link ?: '#',
			'src' => $image['url'],
			'alt' => $image['alt'],
		];
	}

	return $slides;
}

/**
 * Slide autoplay delay in seconds.
 */
function dmc_tmp_get_hp_slide_delay() {
	$value = (int) dmc_tmp_option( 'hp_slide_delay', 4 );

	return max( 2, min( 15, $value ?: 4 ) );
}

/**
 * Default homepage benefit items.
 *
 * @return array<int, array{enable:bool,icon_slug:string,icon:array<string,string>|null,title:string,subtitle:string}>
 */
function dmc_tmp_hp_benefits_defaults() {
	return [
		[
			'enable'    => true,
			'icon_slug' => 'shield-check',
			'icon'      => null,
			'title'     => '100% Hàng chính hãng',
			'subtitle'  => 'Cam kết hàng mới 100%',
		],
		[
			'enable'    => true,
			'icon_slug' => 'delivery-fast',
			'icon'      => null,
			'title'     => 'Giao siêu nhanh 2H',
			'subtitle'  => 'Nội thành HCM & HN',
		],
		[
			'enable'    => true,
			'icon_slug' => 'percent-zero',
			'icon'      => null,
			'title'     => 'Trả góp 0%',
			'subtitle'  => 'Dễ đăng ký, nhanh chóng',
		],
		[
			'enable'    => true,
			'icon_slug' => 'wrench',
			'icon'      => null,
			'title'     => 'Bảo hành tận nơi',
			'subtitle'  => 'An tâm tuyệt đối',
		],
	];
}

/**
 * Whether hero benefits block is enabled.
 */
function dmc_tmp_hp_benefits_enabled() {
	if ( ! function_exists( 'get_field' ) ) {
		return true;
	}

	$value = get_field( 'hp_benefits_enable', 'option' );

	return ! isset( $value ) || $value;
}

/**
 * Homepage benefit cards beside hero slider.
 *
 * @return array<int, array{enable:bool,icon_slug:string,icon:array<string,string>|null,title:string,subtitle:string}>
 */
function dmc_tmp_get_hp_benefits() {
	$benefits = [];

	if ( function_exists( 'have_rows' ) && have_rows( 'hp_benefits', 'option' ) ) {
		while ( have_rows( 'hp_benefits', 'option' ) ) {
			the_row();

			if ( ! get_sub_field( 'enable' ) && null !== get_sub_field( 'enable' ) ) {
				continue;
			}

			$title = trim( (string) ( get_sub_field( 'title' ) ?? '' ) );
			if ( '' === $title ) {
				continue;
			}

			$icon_slug = sanitize_file_name( (string) ( get_sub_field( 'icon_slug' ) ?? 'package' ) );
			$icon      = dmc_tmp_image_field( get_sub_field( 'icon_image' ) );

			$benefits[] = [
				'enable'    => true,
				'icon_slug' => $icon_slug ?: 'package',
				'icon'      => $icon,
				'title'     => $title,
				'subtitle'  => trim( (string) ( get_sub_field( 'subtitle' ) ?? '' ) ),
			];
		}
	}

	if ( ! empty( $benefits ) ) {
		return $benefits;
	}

	return dmc_tmp_hp_benefits_defaults();
}

/**
 * Whether wide-sale banner is enabled.
 */
function dmc_tmp_hp_widesale_enabled() {
	if ( ! function_exists( 'get_field' ) ) {
		return true;
	}

	$value = get_field( 'hp_widesale_enable', 'option' );

	return ! isset( $value ) || $value;
}

/**
 * Wide-sale banner image for homepage.
 *
 * @return array{image: array<string, string>}|null
 */
function dmc_tmp_get_hp_widesale() {
	if ( ! function_exists( 'get_field' ) ) {
		return null;
	}

	$image = dmc_tmp_image_field( dmc_tmp_option( 'hp_widesale_image' ) );
	if ( ! $image ) {
		$image = dmc_tmp_image_field( dmc_tmp_option( 'hp_widesale_bg' ) );
	}

	if ( ! $image ) {
		return null;
	}

	return [
		'image' => $image,
	];
}

/**
 * Render benefit icon HTML.
 *
 * @param array{icon_slug:string,icon:array<string,string>|null,title:string} $benefit Benefit row.
 */
function dmc_tmp_render_benefit_icon( array $benefit ) {
	if ( ! empty( $benefit['icon']['url'] ) ) {
		printf(
			'<img src="%s" alt="%s" class="benefit__icon-img" width="28" height="28" loading="lazy">',
			esc_url( $benefit['icon']['url'] ),
			esc_attr( $benefit['icon']['alt'] ?: $benefit['title'] )
		);
		return;
	}

	if ( function_exists( 'dmc_icon' ) ) {
		echo dmc_icon( $benefit['icon_slug'] ?? 'package', [ 'size' => 28, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		return;
	}

	echo '<span class="emoji" aria-hidden="true">✓</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Default product detail trust badges.
 *
 * @return array<int, array{enable:bool,icon_slug:string,icon:array<string,string>|null,label:string}>
 */
function dmc_tmp_product_trust_defaults() {
	return [
		[
			'enable'    => true,
			'icon_slug' => 'badge-check',
			'icon'      => null,
			'label'     => 'Hàng chính hãng',
		],
		[
			'enable'    => true,
			'icon_slug' => 'delivery-truck',
			'icon'      => null,
			'label'     => 'Giao nhanh toàn quốc',
		],
		[
			'enable'    => true,
			'icon_slug' => 'shield-check',
			'icon'      => null,
			'label'     => 'Bảo hành chính hãng',
		],
		[
			'enable'    => true,
			'icon_slug' => 'return',
			'icon'      => null,
			'label'     => 'Đổi trả 7 ngày',
		],
	];
}

/**
 * Pre-fill trust badges repeater when options have never been saved.
 *
 * @param mixed $value   Field value.
 * @param mixed $post_id Options page id.
 * @return mixed
 */
function dmc_tmp_load_default_product_trust_badges( $value, $post_id, $field ) {
	if ( 'options' !== $post_id || ! empty( $value ) ) {
		return $value;
	}

	$rows = [];

	foreach ( dmc_tmp_product_trust_defaults() as $badge ) {
		$rows[] = [
			'enable'     => ! empty( $badge['enable'] ) ? 1 : 0,
			'icon_slug'  => (string) ( $badge['icon_slug'] ?? 'badge-check' ),
			'icon_image' => '',
			'label'      => (string) ( $badge['label'] ?? '' ),
		];
	}

	return $rows;
}
add_filter( 'acf/load_value/name=tmp_product_trust_badges', 'dmc_tmp_load_default_product_trust_badges', 10, 3 );

/**
 * Whether product detail trust badges are enabled.
 */
function dmc_tmp_product_trust_enabled() {
	if ( ! function_exists( 'get_field' ) ) {
		return true;
	}

	$value = get_field( 'tmp_product_trust_enable', 'option' );

	return ! isset( $value ) || $value;
}

/**
 * Product detail trust badges for single product page.
 *
 * @return array<int, array{enable:bool,icon_slug:string,icon:array<string,string>|null,label:string}>
 */
function dmc_tmp_get_product_trust_badges() {
	$badges = [];

	if ( function_exists( 'have_rows' ) && have_rows( 'tmp_product_trust_badges', 'option' ) ) {
		while ( have_rows( 'tmp_product_trust_badges', 'option' ) ) {
			the_row();

			if ( ! get_sub_field( 'enable' ) && null !== get_sub_field( 'enable' ) ) {
				continue;
			}

			$label = trim( (string) ( get_sub_field( 'label' ) ?? '' ) );
			if ( '' === $label ) {
				continue;
			}

			$icon_slug = sanitize_file_name( (string) ( get_sub_field( 'icon_slug' ) ?? 'package' ) );
			$icon      = dmc_tmp_image_field( get_sub_field( 'icon_image' ) );

			$badges[] = [
				'enable'    => true,
				'icon_slug' => $icon_slug ?: 'package',
				'icon'      => $icon,
				'label'     => $label,
			];
		}
	}

	if ( ! empty( $badges ) ) {
		return $badges;
	}

	return dmc_tmp_product_trust_defaults();
}

/**
 * Render product detail trust badge icon.
 *
 * @param array{icon_slug:string,icon:array<string,string>|null,label:string} $badge Badge row.
 */
function dmc_tmp_render_product_trust_icon( array $badge ) {
	if ( ! empty( $badge['icon']['url'] ) ) {
		printf(
			'<img src="%s" alt="%s" width="18" height="18" loading="lazy">',
			esc_url( $badge['icon']['url'] ),
			esc_attr( $badge['icon']['alt'] ?: $badge['label'] )
		);
		return;
	}

	if ( function_exists( 'dmc_icon' ) ) {
		echo dmc_icon( $badge['icon_slug'] ?? 'package', [ 'size' => 18, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/**
 * Output product detail trust badges markup.
 */
function dmc_tmp_render_product_trust_badges() {
	if ( ! dmc_tmp_product_trust_enabled() ) {
		return;
	}

	$badges = dmc_tmp_get_product_trust_badges();
	if ( empty( $badges ) ) {
		return;
	}
	?>
	<ul class="pl-detail__trust">
		<?php foreach ( $badges as $badge ) : ?>
			<li>
				<span class="pl-detail__trust-icon"><?php dmc_tmp_render_product_trust_icon( $badge ); ?></span>
				<span><?php echo esc_html( $badge['label'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * "Xem tất cả" URL for tab.
 */
function dmc_tmp_tab_more_url( array $tab ) {
	if ( ! empty( $tab['more_url'] ) ) {
		return $tab['more_url'];
	}

	$source = $tab['source'] ?? '';

	if ( 'category' === $source && ! empty( $tab['category'] ) ) {
		$link = get_term_link( (int) $tab['category'], 'product_cat' );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}

	if ( 'brand' === $source && ! empty( $tab['brand'] ) ) {
		$taxonomy = dmc_tmp_brand_taxonomy();
		if ( $taxonomy ) {
			$link = get_term_link( (int) $tab['brand'], $taxonomy );
			if ( ! is_wp_error( $link ) ) {
				return $link;
			}
		}
	}

	if ( ! empty( $tab['category'] ) ) {
		$link = get_term_link( (int) $tab['category'], 'product_cat' );
		if ( ! is_wp_error( $link ) ) {
			return $link;
		}
	}

	return class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
}
