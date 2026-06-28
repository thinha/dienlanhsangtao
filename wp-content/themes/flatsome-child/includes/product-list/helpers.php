<?php
/**
 * Product list & detail helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product delivery type: motorbike or car.
 *
 * @return string motorbike|car
 */
function dmc_pl_get_delivery_type( $product_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return 'motorbike';
	}

	$type = (string) get_field( 'pl_delivery_type', $product_id );

	return 'car' === $type ? 'car' : 'motorbike';
}

/**
 * Human-readable delivery type label.
 */
function dmc_pl_get_delivery_type_label( $type = 'motorbike' ) {
	return 'car' === $type
		? __( 'Ô tô', 'flatsome-child' )
		: __( 'Xe máy', 'flatsome-child' );
}

/**
 * Icon slug for delivery type.
 */
function dmc_pl_get_delivery_icon_slug( $type = 'motorbike' ) {
	return 'car' === $type ? 'delivery-truck' : 'motorbike';
}

/**
 * Fee array key for delivery type.
 */
function dmc_pl_get_delivery_fee_key( $type = 'motorbike' ) {
	return 'car' === $type ? 'fee_car' : 'fee';
}

/**
 * Resolve location fee for a delivery type.
 *
 * @param array{fee?:int,fee_car?:int} $location Location row.
 */
function dmc_pl_resolve_location_fee( array $location, $type = 'motorbike' ) {
	if ( function_exists( 'dmc_tmp_resolve_shipping_fee' ) ) {
		return dmc_tmp_resolve_shipping_fee( $location, $type );
	}

	$key = dmc_pl_get_delivery_fee_key( $type );
	$fee = (int) ( $location[ $key ] ?? $location['fee'] ?? 0 );

	if ( 'car' === $type && $fee <= 0 && ! empty( $location['fee'] ) ) {
		$fee = (int) round( (int) $location['fee'] * 1.5 );
	}

	return $fee;
}

/**
 * Render delivery type badge (Xe máy / Ô tô).
 *
 * @param int    $product_id Product ID.
 * @param string $class      Extra CSS classes.
 */
function dmc_pl_render_delivery_badge( $product_id, $class = '' ) {
	$type  = dmc_pl_get_delivery_type( $product_id );
	$label = dmc_pl_get_delivery_type_label( $type );
	$icon  = dmc_pl_get_delivery_icon_slug( $type );
	$classes = trim( 'pl-delivery-badge pl-delivery-badge--' . $type . ' ' . $class );

	if ( ! function_exists( 'dmc_icon' ) ) {
		printf( '<span class="%s">%s</span>', esc_attr( $classes ), esc_html( $label ) );
		return;
	}

	printf(
		'<span class="%1$s"><span class="pl-delivery-badge__icon" aria-hidden="true">%2$s</span><span class="pl-delivery-badge__label">%3$s</span></span>',
		esc_attr( $classes ),
		dmc_icon( $icon, [ 'size' => 14, 'variant' => 'blue', 'class' => 'pl-delivery-badge__svg' ] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html( $label )
	);
}

/**
 * Current sort key from query string.
 */
function dmc_pl_get_sort() {
	$allowed = [ 'relevance', 'date', 'popularity', 'price_asc', 'price_desc' ];
	$sort    = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'relevance'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return in_array( $sort, $allowed, true ) ? $sort : 'relevance';
}

/**
 * Sort options for toolbar.
 */
function dmc_pl_sort_options() {
	return [
		'relevance'  => __( 'Liên quan', 'flatsome-child' ),
		'date'       => __( 'Mới nhất', 'flatsome-child' ),
		'popularity' => __( 'Bán chạy', 'flatsome-child' ),
		'price_asc'  => __( 'Giá thấp → cao', 'flatsome-child' ),
		'price_desc' => __( 'Giá cao → thấp', 'flatsome-child' ),
	];
}

/**
 * Build sort URL preserving filters.
 */
function dmc_pl_sort_url( $sort ) {
	$args = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$args['orderby'] = $sort;

	return add_query_arg( $args );
}

/**
 * List page title.
 */
function dmc_pl_list_title() {
	if ( is_search() ) {
		return sprintf(
			/* translators: %s: search keyword */
			__( 'Kết quả tìm kiếm cho "%s"', 'flatsome-child' ),
			get_search_query()
		);
	}

	if ( is_product_category() ) {
		return single_term_title( '', false );
	}

	if ( is_product_tag() ) {
		return single_term_title( '', false );
	}

	return __( 'Danh sách sản phẩm', 'flatsome-child' );
}

/**
 * Breadcrumb items for list / detail pages.
 *
 * @return array<int, array{label:string,url:string|null}>
 */
function dmc_pl_breadcrumb_items() {
	$items   = [];
	$items[] = [
		'label' => get_bloginfo( 'name' ),
		'url'   => home_url( '/' ),
	];

	$shop_url = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
	$items[]  = [
		'label' => __( 'Sản phẩm', 'flatsome-child' ),
		'url'   => $shop_url,
	];

	if ( is_product() ) {
		global $product;

		if ( $product instanceof WC_Product ) {
			$terms = get_the_terms( $product->get_id(), 'product_cat' );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$term = $terms[0];
				$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );
				foreach ( $ancestors as $ancestor_id ) {
					$ancestor = get_term( $ancestor_id, 'product_cat' );
					if ( $ancestor && ! is_wp_error( $ancestor ) ) {
						$link = get_term_link( $ancestor );
						$items[] = [
							'label' => $ancestor->name,
							'url'   => is_wp_error( $link ) ? null : $link,
						];
					}
				}
				$link = get_term_link( $term );
				$items[] = [
					'label' => $term->name,
					'url'   => is_wp_error( $link ) ? null : $link,
				];
			}
		}

		$items[] = [
			'label' => get_the_title(),
			'url'   => null,
		];

		return $items;
	}

	if ( is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'product_cat' );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$link = get_term_link( $ancestor );
					$items[] = [
						'label' => $ancestor->name,
						'url'   => is_wp_error( $link ) ? null : $link,
					];
				}
			}
			$items[] = [
				'label' => $term->name,
				'url'   => null,
			];
		}

		return $items;
	}

	if ( is_search() ) {
		$items[] = [
			'label' => dmc_pl_list_title(),
			'url'   => null,
		];
	}

	return $items;
}

/**
 * Brand taxonomy slug (WooCommerce Brands or Perfect Brands).
 */
function dmc_pl_brand_taxonomy() {
	static $resolved = null;

	// Only cache a resolved taxonomy slug. An empty string can mean taxonomies
	// are not registered yet (ACF init vs WooCommerce init both run at priority 5).
	if ( is_string( $resolved ) && '' !== $resolved ) {
		return $resolved;
	}

	$candidates = array_filter(
		[
			taxonomy_exists( 'pwb-brand' ) ? 'pwb-brand' : '',
			taxonomy_exists( 'product_brand' ) ? 'product_brand' : '',
		]
	);

	foreach ( $candidates as $taxonomy ) {
		$count = wp_count_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		if ( ! is_wp_error( $count ) && (int) $count > 0 ) {
			$resolved = $taxonomy;
			return $resolved;
		}
	}

	if ( ! empty( $candidates ) ) {
		$resolved = (string) reset( $candidates );
		return $resolved;
	}

	return '';
}

/**
 * Filter brands for sidebar.
 */
function dmc_pl_filter_brands() {
	$taxonomy = dmc_pl_brand_taxonomy();
	if ( ! $taxonomy ) {
		return [];
	}

	return get_terms(
		[
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'number'     => 20,
		]
	);
}

/**
 * Selected filter brand IDs from query string.
 *
 * @return int[]
 */
function dmc_pl_selected_brands() {
	if ( empty( $_GET['filter_brand'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return [];
	}

	$raw = wp_unslash( $_GET['filter_brand'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( is_array( $raw ) ) {
		return array_map( 'absint', $raw );
	}

	return array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) );
}

/**
 * Min/max product prices across all published products.
 *
 * @return array{min:int,max:int,step:int}|null
 */
function dmc_pl_product_price_bounds() {
	global $wpdb;

	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		return null;
	}

	$row = $wpdb->get_row(
		"SELECT MIN(lkp.min_price) AS min_price, MAX(lkp.max_price) AS max_price
		FROM {$wpdb->wc_product_meta_lookup} lkp
		INNER JOIN {$wpdb->posts} p ON p.ID = lkp.product_id
		WHERE p.post_type = 'product'
		AND p.post_status = 'publish'
		AND lkp.min_price > 0"
	);

	if ( ! $row || null === $row->min_price || null === $row->max_price ) {
		$cache = null;
		return null;
	}

	$min_price = (float) $row->min_price;
	$max_price = (float) $row->max_price;

	if ( wc_tax_enabled() && ! wc_prices_include_tax() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) ) {
		$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' );
		$tax_rates = WC_Tax::get_rates( $tax_class );

		if ( $tax_rates ) {
			$min_price += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $min_price, $tax_rates ) );
			$max_price += WC_Tax::get_tax_total( WC_Tax::calc_exclusive_tax( $max_price, $tax_rates ) );
		}
	}

	$step = max( (int) apply_filters( 'woocommerce_price_filter_widget_step', 10000 ), 1 );

	$cache = [
		'min'  => (int) ( floor( $min_price / $step ) * $step ),
		'max'  => (int) ( ceil( $max_price / $step ) * $step ),
		'step' => $step,
	];

	return $cache;
}

/**
 * Current min/max price filter values from query string.
 *
 * @param array{min:int,max:int,step:int} $bounds Price bounds.
 * @return array{min:int,max:int}
 */
function dmc_pl_selected_price_range( $bounds ) {
	$step = $bounds['step'];
	$min  = $bounds['min'];
	$max  = $bounds['max'];

	if ( isset( $_GET['min_price'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$min = max( $bounds['min'], (int) ( floor( floatval( wp_unslash( $_GET['min_price'] ) ) / $step ) * $step ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	if ( isset( $_GET['max_price'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$max = min( $bounds['max'], (int) ( ceil( floatval( wp_unslash( $_GET['max_price'] ) ) / $step ) * $step ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	if ( $min > $max ) {
		$min = $bounds['min'];
		$max = $bounds['max'];
	}

	return [
		'min' => $min,
		'max' => $max,
	];
}

/**
 * Filter categories for sidebar.
 */
function dmc_pl_filter_categories() {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return [];
	}

	$args = [
		'taxonomy'   => 'product_cat',
		'hide_empty' => true,
		'parent'     => 0,
		'number'     => 12,
	];

	if ( function_exists( 'dmc_excluded_product_cat_ids' ) ) {
		$exclude = dmc_excluded_product_cat_ids();
		if ( $exclude ) {
			$args['exclude'] = $exclude;
		}
	}

	return get_terms( $args );
}

/**
 * Selected filter category IDs from query string.
 *
 * @return int[]
 */
function dmc_pl_selected_categories() {
	if ( empty( $_GET['filter_cat'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return [];
	}

	$raw = wp_unslash( $_GET['filter_cat'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( is_array( $raw ) ) {
		return array_map( 'absint', $raw );
	}

	return array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) );
}

/**
 * Filter URL helper.
 */
function dmc_pl_filter_url( $key, $value ) {
	$args = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( 'filter_cat' === $key || 'filter_brand' === $key ) {
		$selected = 'filter_cat' === $key ? dmc_pl_selected_categories() : dmc_pl_selected_brands();
		$value    = (int) $value;
		if ( in_array( $value, $selected, true ) ) {
			$selected = array_values( array_diff( $selected, [ $value ] ) );
		} else {
			$selected[] = $value;
		}
		if ( empty( $selected ) ) {
			unset( $args[ $key ] );
		} else {
			$args[ $key ] = implode( ',', $selected );
		}
	} else {
		$args[ $key ] = $value;
	}

	unset( $args['paged'] );

	return add_query_arg( $args );
}

/**
 * Normalize image field (ACF or attachment ID).
 *
 * @param mixed $image Image field value.
 * @return array{url:string,alt:string}|null
 */
function dmc_pl_normalize_image( $image ) {
	if ( function_exists( 'dmc_homepage_image_field' ) ) {
		return dmc_homepage_image_field( $image );
	}

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
 * Gift products configured on a product.
 *
 * @param int $product_id Product ID.
 * @return array<int, array{image:array{url:string,alt:string}|null,title:string,description:string}>
 */
function dmc_pl_get_gift_products( $product_id ) {
	$product_id = absint( $product_id );
	$gifts      = [];

	if ( ! function_exists( 'get_field' ) || ! get_field( 'pl_gift_enable', $product_id ) ) {
		return $gifts;
	}

	$rows = get_field( 'pl_gift_products', $product_id );
	if ( empty( $rows ) || ! is_array( $rows ) ) {
		return $gifts;
	}

	foreach ( $rows as $row ) {
		$image = dmc_pl_normalize_image( $row['gift_image'] ?? null );
		if ( ! $image ) {
			continue;
		}

		$gifts[] = [
			'image'       => $image,
			'title'       => sanitize_text_field( $row['gift_title'] ?? '' ),
			'description' => wp_kses_post( $row['gift_description'] ?? '' ),
		];
	}

	return $gifts;
}

/**
 * First gift product for card/gallery badge.
 *
 * @param int $product_id Product ID.
 * @return array{image:array{url:string,alt:string},title:string,description:string}|null
 */
function dmc_pl_get_primary_gift( $product_id ) {
	$gifts = dmc_pl_get_gift_products( $product_id );

	return ! empty( $gifts ) ? $gifts[0] : null;
}

/**
 * Render 80×80 gift badge overlay for product thumbnails.
 *
 * @param array{image:array{url:string,alt:string},title:string,description:string} $gift Gift data.
 * @param string                                                                     $class Extra class.
 * @param int                                                                        $size  Badge size in px (listing cards).
 */
function dmc_pl_render_gift_badge( array $gift, $class = '', $size = 48 ) {
	if ( empty( $gift['image']['url'] ) ) {
		return;
	}

	$classes = trim( 'prod-gift-badge ' . $class );
	$alt     = $gift['title'] ?: __( 'Quà tặng kèm', 'flatsome-child' );
	?>
	<span class="<?php echo esc_attr( $classes ); ?>" aria-hidden="true">
		<img
			src="<?php echo esc_url( $gift['image']['url'] ); ?>"
			alt="<?php echo esc_attr( $alt ); ?>"
			width="<?php echo esc_attr( (string) $size ); ?>"
			height="<?php echo esc_attr( (string) $size ); ?>"
			loading="lazy"
			class="prod-gift-badge__img"
		>
		<span class="prod-gift-badge__label"><?php esc_html_e( 'Tặng kèm', 'flatsome-child' ); ?></span>
	</span>
	<?php
}

/**
 * Gallery slides for Swiper (product images + gift images at the end).
 *
 * @param WC_Product $product Product.
 * @return array<int, array<string,mixed>>
 */
function dmc_pl_get_gallery_slides( WC_Product $product ) {
	$slides    = [];
	$image_ids = [];

	$featured_id = $product->get_image_id();
	if ( $featured_id ) {
		$image_ids[] = $featured_id;
	}

	foreach ( $product->get_gallery_image_ids() as $attachment_id ) {
		if ( ! in_array( $attachment_id, $image_ids, true ) ) {
			$image_ids[] = $attachment_id;
		}
	}

	foreach ( $image_ids as $attachment_id ) {
		$url = wp_get_attachment_image_url( $attachment_id, 'woocommerce_single' );
		if ( ! $url ) {
			continue;
		}

		$slides[] = [
			'type'  => 'product',
			'url'   => $url,
			'thumb' => wp_get_attachment_image_url( $attachment_id, 'woocommerce_gallery_thumbnail' ) ?: $url,
			'alt'   => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ?: $product->get_name(),
		];
	}

	if ( empty( $slides ) ) {
		$slides[] = [
			'type'  => 'product',
			'url'   => wc_placeholder_img_src( 'woocommerce_single' ),
			'thumb' => wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' ),
			'alt'   => $product->get_name(),
		];
	}

	foreach ( dmc_pl_get_gift_products( $product->get_id() ) as $gift ) {
		$slides[] = [
			'type'  => 'gift',
			'url'   => $gift['image']['url'],
			'thumb' => $gift['image']['url'],
			'alt'   => $gift['image']['alt'] ?: ( $gift['title'] ?: __( 'Quà tặng kèm', 'flatsome-child' ) ),
			'gift'  => $gift,
		];
	}

	return $slides;
}

/**
 * Discount label for product detail gallery.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function dmc_pl_discount_label( WC_Product $product ) {
	if ( function_exists( 'dmc_homepage_discount_label' ) ) {
		return (string) dmc_homepage_discount_label( $product );
	}

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
 * Technical specs table HTML from ACF (Add more detail product → Thông số kỹ thuật).
 *
 * @param int $product_id Product ID.
 * @return string
 */
function dmc_pl_get_technical_specs_content( $product_id ) {
	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$content = get_field( 'pl_technical_specs', $product_id );
	if ( empty( $content ) ) {
		return '';
	}

	return dmc_pl_normalize_technical_specs_html( (string) apply_filters( 'the_content', $content ) );
}

/**
 * Meta rows for CHI TIẾT SẢN PHẨM block.
 *
 * @param WC_Product $product Product.
 * @return array<int, array{label:string,value:string,html?:bool}>
 */
function dmc_pl_product_detail_rows( WC_Product $product ) {
	$rows  = [];
	$terms = get_the_terms( $product->get_id(), 'product_cat' );

	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		$term      = $terms[0];
		$crumbs    = [];
		$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );

		foreach ( $ancestors as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, 'product_cat' );
			if ( ! $ancestor || is_wp_error( $ancestor ) ) {
				continue;
			}
			$link = get_term_link( $ancestor );
			$crumbs[] = is_wp_error( $link )
				? esc_html( $ancestor->name )
				: '<a href="' . esc_url( $link ) . '">' . esc_html( $ancestor->name ) . '</a>';
		}

		$link = get_term_link( $term );
		$crumbs[] = is_wp_error( $link )
			? esc_html( $term->name )
			: '<a href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a>';

		$rows[] = [
			'label' => __( 'Danh Mục', 'flatsome-child' ),
			'value' => implode( ' &gt; ', $crumbs ),
			'html'  => true,
		];
	}

	$brand_tax = dmc_pl_brand_taxonomy();
	if ( $brand_tax ) {
		$brands = get_the_terms( $product->get_id(), $brand_tax );
		if ( ! empty( $brands ) && ! is_wp_error( $brands ) ) {
			$brand = $brands[0];
			$link  = get_term_link( $brand );
			$rows[] = [
				'label' => __( 'Thương hiệu', 'flatsome-child' ),
				'value' => is_wp_error( $link )
					? esc_html( $brand->name )
					: '<a href="' . esc_url( $link ) . '">' . esc_html( $brand->name ) . '</a>',
				'html'  => true,
			];
		}
	}

	if ( wc_product_sku_enabled() && $product->get_sku() ) {
		$rows[] = [
			'label' => __( 'SKU', 'flatsome-child' ),
			'value' => $product->get_sku(),
		];
	}

	return $rows;
}

/**
 * Create a DOMDocument configured for UTF-8 HTML fragments.
 *
 * @return DOMDocument
 */
function dmc_pl_dom_create_document() {
	$dom            = new DOMDocument( '1.0', 'UTF-8' );
	$dom->encoding  = 'UTF-8';
	$dom->formatOutput = false;

	return $dom;
}

/**
 * Load an HTML fragment into DOMDocument while preserving UTF-8 text.
 *
 * @param DOMDocument $dom  Document.
 * @param string      $html HTML fragment.
 * @return bool
 */
function dmc_pl_dom_load_html_fragment( DOMDocument $dom, $html ) {
	libxml_use_internal_errors( true );

	$dom->encoding = 'UTF-8';

	return (bool) $dom->loadHTML(
		'<?xml encoding="UTF-8" ?><div id="dmc-dom-root">' . (string) $html . '</div>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);
}

/**
 * Serialize DOMDocument fragment back to UTF-8 HTML.
 *
 * @param DOMDocument $dom Document.
 * @return string
 */
function dmc_pl_dom_save_html_fragment( DOMDocument $dom ) {
	$html = '';
	$root = $dom->getElementById( 'dmc-dom-root' );

	if ( $root instanceof DOMElement ) {
		foreach ( $root->childNodes as $child ) {
			$html .= $dom->saveHTML( $child );
		}
	} else {
		$html = (string) $dom->saveHTML();
	}

	libxml_clear_errors();

	$html = preg_replace( '/^<\?xml[^>]*>/', '', $html );
	$html = html_entity_decode( $html, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	return trim( $html );
}

/**
 * Plain text from a DOM node (collapsed whitespace).
 *
 * @param DOMNode $node Node.
 * @return string
 */
function dmc_pl_dom_node_text( DOMNode $node ) {
	return trim( preg_replace( '/\s+/u', ' ', $node->textContent ) );
}

/**
 * Whether a specs list item is a section title row.
 *
 * @param DOMElement $li List item.
 * @return bool
 */
function dmc_pl_is_specs_title_li( DOMElement $li ) {
	$class = $li->getAttribute( 'class' );
	if ( $class && false !== stripos( $class, 'title-specification' ) ) {
		return true;
	}

	$paragraphs = $li->getElementsByTagName( 'p' );
	$strongs    = $li->getElementsByTagName( 'strong' );

	if ( 1 === $paragraphs->length && $strongs->length > 0 ) {
		return true;
	}

	return 0 === $paragraphs->length && $strongs->length > 0;
}

/**
 * Convert pasted ul/li specs markup (e.g. nav.list_specifications) to a table.
 *
 * @param string $html HTML content.
 * @return string
 */
function dmc_pl_convert_specs_list_to_table( $html ) {
	$html = (string) $html;

	if ( '' === trim( $html ) || false === stripos( $html, '<ul' ) ) {
		return $html;
	}

	$dom = dmc_pl_dom_create_document();

	if ( ! dmc_pl_dom_load_html_fragment( $dom, $html ) ) {
		return $html;
	}

	$uls = [];
	foreach ( $dom->getElementsByTagName( 'ul' ) as $ul ) {
		if ( $ul instanceof DOMElement ) {
			$uls[] = $ul;
		}
	}

	foreach ( $uls as $ul ) {
		$lis = [];
		foreach ( $ul->childNodes as $child ) {
			if ( $child instanceof DOMElement && 'li' === strtolower( $child->tagName ) ) {
				$lis[] = $child;
			}
		}

		if ( empty( $lis ) ) {
			continue;
		}

		$rows          = [];
		$is_specs_list = false;

		foreach ( $lis as $li ) {
			if ( dmc_pl_is_specs_title_li( $li ) ) {
				$is_specs_list = true;
				$rows[]        = [
					'type' => 'title',
					'text' => dmc_pl_dom_node_text( $li ),
				];
				continue;
			}

			$paragraphs = [];
			foreach ( $li->childNodes as $child ) {
				if ( $child instanceof DOMElement && 'p' === strtolower( $child->tagName ) ) {
					$paragraphs[] = $child;
				}
			}

			if ( count( $paragraphs ) >= 2 ) {
				$is_specs_list = true;
				$rows[]        = [
					'type'  => 'row',
					'label' => dmc_pl_dom_node_text( $paragraphs[0] ),
					'value' => dmc_pl_dom_node_text( $paragraphs[1] ),
				];
			}
		}

		if ( ! $is_specs_list || empty( $rows ) ) {
			continue;
		}

		$table = $dom->createElement( 'table' );
		$table->setAttribute( 'class', 'thong-so-ky-thuat' );

		foreach ( $rows as $row ) {
			$tr = $dom->createElement( 'tr' );

			if ( 'title' === $row['type'] ) {
				$td = $dom->createElement( 'td' );
				$td->setAttribute( 'colspan', '2' );
				$strong = $dom->createElement( 'strong' );
				$strong->appendChild( $dom->createTextNode( $row['text'] ) );
				$td->appendChild( $strong );
				$tr->appendChild( $td );
			} else {
				$td_label = $dom->createElement( 'td' );
				$td_label->appendChild( $dom->createTextNode( $row['label'] ) );
				$td_value = $dom->createElement( 'td' );
				$td_value->appendChild( $dom->createTextNode( $row['value'] ) );
				$tr->appendChild( $td_label );
				$tr->appendChild( $td_value );
			}

			$table->appendChild( $tr );
		}

		$replace_target = $ul;
		$parent         = $ul->parentNode;
		if ( $parent instanceof DOMElement && 'nav' === strtolower( $parent->tagName ) ) {
			$replace_target = $parent;
		}

		if ( $replace_target->parentNode ) {
			$replace_target->parentNode->replaceChild( $table, $replace_target );
		}
	}

	return dmc_pl_dom_save_html_fragment( $dom );
}

/**
 * Remove inline style/class and unwrap decorative tags inside a specs table.
 *
 * @param DOMElement $table Table element.
 */
function dmc_pl_strip_specs_table_inline_markup( DOMElement $table ) {
	$unwrap_tags = [ 'span', 'font' ];
	$elements    = [];

	$collect = static function ( DOMNode $node ) use ( &$collect, &$elements ) {
		if ( ! $node instanceof DOMElement ) {
			return;
		}

		foreach ( $node->childNodes as $child ) {
			$collect( $child );
		}

		$elements[] = $node;
	};

	$collect( $table );

	foreach ( $elements as $node ) {
		if ( $node === $table ) {
			continue;
		}

		$tag = strtolower( $node->tagName );

		if ( in_array( $tag, $unwrap_tags, true ) ) {
			$parent = $node->parentNode;

			if ( ! $parent instanceof DOMNode ) {
				continue;
			}

			while ( $node->firstChild ) {
				$parent->insertBefore( $node->firstChild, $node );
			}

			$parent->removeChild( $node );
			continue;
		}

		if ( $node->hasAttribute( 'style' ) ) {
			$node->removeAttribute( 'style' );
		}

		if ( $node->hasAttribute( 'class' ) ) {
			$node->removeAttribute( 'class' );
		}
	}
}

/**
 * Normalize technical specs HTML: convert ul/li lists to table, strip extra classes.
 *
 * @param string $html HTML content.
 * @return string
 */
function dmc_pl_normalize_technical_specs_html( $html ) {
	$html = (string) $html;

	if ( '' === trim( $html ) ) {
		return $html;
	}

	if ( ! class_exists( 'DOMDocument' ) ) {
		return $html;
	}

	try {
		if ( false !== stripos( $html, '<ul' ) ) {
			$html = dmc_pl_convert_specs_list_to_table( $html );
		}

		if ( false === stripos( $html, '<table' ) ) {
			return $html;
		}

		$table_tags = [ 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'colgroup', 'col', 'caption' ];

		$dom = dmc_pl_dom_create_document();

		if ( ! dmc_pl_dom_load_html_fragment( $dom, $html ) ) {
			return $html;
		}

		foreach ( $table_tags as $tag ) {
			$nodes = $dom->getElementsByTagName( $tag );

			for ( $i = $nodes->length - 1; $i >= 0; $i-- ) {
				$node = $nodes->item( $i );

				if ( ! $node instanceof DOMElement ) {
					continue;
				}

				while ( $node->attributes->length > 0 ) {
					$node->removeAttribute( $node->attributes->item( 0 )->nodeName );
				}

				if ( 'table' === strtolower( $tag ) ) {
					$node->setAttribute( 'class', 'thong-so-ky-thuat' );
				}
			}
		}

		$tables = $dom->getElementsByTagName( 'table' );

		for ( $i = $tables->length - 1; $i >= 0; $i-- ) {
			$table = $tables->item( $i );

			if ( $table instanceof DOMElement ) {
				dmc_pl_strip_specs_table_inline_markup( $table );
			}
		}

		return dmc_pl_dom_save_html_fragment( $dom );
	} catch ( Exception $e ) {
		return $html;
	}
}

/**
 * Attribute rows for THÔNG SỐ KỸ THUẬT block.
 *
 * @param WC_Product $product Product.
 * @return array<int, array{label:string,value:string,html?:bool}>
 */
function dmc_pl_product_technical_rows( WC_Product $product ) {
	$rows = [];

	foreach ( $product->get_attributes() as $attribute ) {
		if ( ! $attribute->get_visible() ) {
			continue;
		}

		$rows[] = [
			'label' => wc_attribute_label( $attribute->get_name() ),
			'value' => (string) $product->get_attribute( $attribute->get_name() ),
			'html'  => true,
		];
	}

	return $rows;
}

/**
 * Key-value rows for Shopee-style product detail block.
 *
 * @param WC_Product $product Product.
 * @return array<int, array{label:string,value:string,html?:bool}>
 */
function dmc_pl_product_spec_rows( WC_Product $product ) {
	return array_merge(
		dmc_pl_product_detail_rows( $product ),
		dmc_pl_product_technical_rows( $product )
	);
}

/**
 * Related products for detail page.
 *
 * @param WC_Product $product Product.
 * @return WC_Product[]
 */
function dmc_pl_get_related_products( WC_Product $product ) {
	$limit = (int) apply_filters( 'dmc_pl_related_products_limit', 12 );
	$ids   = wc_get_related_products( $product->get_id(), $limit );

	$products = [];
	foreach ( $ids as $product_id ) {
		$related = wc_get_product( $product_id );
		if ( $related instanceof WC_Product ) {
			$products[] = $related;
		}
	}

	return $products;
}

/**
 * Render product card (reuse homepage renderer).
 */
function dmc_pl_render_product_card( WC_Product $product ) {
	if ( function_exists( 'dmc_homepage_render_product_card' ) ) {
		dmc_homepage_render_product_card( $product );
		return;
	}

	$permalink = $product->get_permalink();
	$thumb     = $product->get_image( 'woocommerce_thumbnail' );
	?>
	<article class="product">
		<a href="<?php echo esc_url( $permalink ); ?>" class="prod-image">
			<?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
		<a href="<?php echo esc_url( $permalink ); ?>" class="prod-name"><?php echo esc_html( $product->get_name() ); ?></a>
		<div class="price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
	</article>
	<?php
}
