<?php
/**
 * Product category ↔ brand mapping (mega menu).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Term meta key storing selected brand IDs for a product category.
 */
function dmc_category_brands_meta_key() {
	return 'dmc_category_brands';
}

/**
 * Brand IDs assigned to a product category.
 *
 * @param int $term_id Product category term ID.
 * @return int[]
 */
function dmc_category_get_brand_ids( $term_id ) {
	$raw = get_term_meta( (int) $term_id, dmc_category_brands_meta_key(), true );

	if ( empty( $raw ) ) {
		return [];
	}

	if ( ! is_array( $raw ) ) {
		$raw = array_map( 'absint', explode( ',', (string) $raw ) );
	}

	return array_values( array_filter( array_map( 'absint', $raw ) ) );
}

/**
 * Brand terms for mega menu display (preserves admin selection order).
 *
 * @param int $term_id Product category term ID.
 * @return array<int, array{label:string,url:string,icon:string}>
 */
function dmc_category_get_brands_for_menu( $term_id ) {
	$brand_ids = dmc_category_get_brand_ids( $term_id );
	if ( empty( $brand_ids ) ) {
		return [];
	}

	$taxonomy = function_exists( 'dmc_pl_brand_taxonomy' ) ? dmc_pl_brand_taxonomy() : '';
	if ( ! $taxonomy ) {
		return [];
	}

	$items = [];
	foreach ( $brand_ids as $brand_id ) {
		$brand = get_term( $brand_id, $taxonomy );
		if ( ! $brand || is_wp_error( $brand ) ) {
			continue;
		}

		$link = get_term_link( $brand );
		if ( is_wp_error( $link ) ) {
			continue;
		}

		$items[] = [
			'label' => $brand->name,
			'url'   => $link,
			'icon'  => dmc_brand_term_icon_html( $brand ),
		];
	}

	return $items;
}

/**
 * Brand logo attachment ID from admin (Perfect Brands or WooCommerce Brands).
 *
 * @param WP_Term $brand Brand term.
 * @return int
 */
function dmc_brand_term_attachment_id( $brand ) {
	if ( ! $brand || is_wp_error( $brand ) ) {
		return 0;
	}

	$meta_keys = [ 'pwb_brand_image', 'thumbnail_id' ];
	foreach ( $meta_keys as $meta_key ) {
		$attachment_id = (int) get_term_meta( $brand->term_id, $meta_key, true );
		if ( $attachment_id > 0 ) {
			return $attachment_id;
		}
	}

	return 0;
}

/**
 * Render brand icon: logo from taxonomy meta or fallback icon.
 *
 * @param WP_Term $brand Brand term.
 * @param array   $args  Passed to dmc_icon().
 */
function dmc_brand_term_icon_html( $brand, $args = [] ) {
	$icon_args = wp_parse_args(
		$args,
		[
			'size'    => 28,
			'variant' => 'blue',
			'class'   => 'dmc-icon--mega',
		]
	);

	if ( ! $brand || is_wp_error( $brand ) ) {
		return dmc_icon( 'package', $icon_args );
	}

	if ( function_exists( 'wc_get_brand_thumbnail_image' ) && 'product_brand' === $brand->taxonomy ) {
		$image = wc_get_brand_thumbnail_image( $brand, 'woocommerce_thumbnail' );
		if ( $image && ! str_contains( $image, 'placeholder' ) ) {
			return str_replace( 'class="brand-thumbnail"', 'class="term-thumb brand-thumbnail"', $image );
		}
	}

	$attachment_id = dmc_brand_term_attachment_id( $brand );
	if ( $attachment_id ) {
		$image = wp_get_attachment_image(
			$attachment_id,
			'woocommerce_thumbnail',
			false,
			[
				'class'   => 'term-thumb',
				'alt'     => $brand->name,
				'loading' => 'lazy',
			]
		);

		if ( $image ) {
			return $image;
		}
	}

	return dmc_icon( 'package', $icon_args );
}

/**
 * All brand terms for admin multi-select.
 *
 * @return WP_Term[]
 */
function dmc_category_admin_brand_options() {
	$taxonomy = function_exists( 'dmc_pl_brand_taxonomy' ) ? dmc_pl_brand_taxonomy() : '';
	if ( ! $taxonomy ) {
		return [];
	}

	$terms = get_terms(
		[
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		]
	);

	return ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms : [];
}

/**
 * Add form field on new product category.
 */
function dmc_category_brands_add_form_field() {
	$brands = dmc_category_admin_brand_options();
	?>
	<div class="form-field term-dmc-brands-wrap">
		<label for="dmc_category_brands"><?php esc_html_e( 'Thương hiệu (mega menu)', 'flatsome-child' ); ?></label>
		<?php dmc_category_brands_render_field( [], $brands ); ?>
		<p class="description">
			<?php esc_html_e( 'Chọn các thương hiệu hiển thị khi hover danh mục này trong menu "Danh mục sản phẩm". Giữ Ctrl (Windows) hoặc Cmd (Mac) để chọn nhiều.', 'flatsome-child' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'dmc_category_brands_add_form_field' );

/**
 * Edit form field on existing product category.
 *
 * @param WP_Term $term Current term.
 */
function dmc_category_brands_edit_form_field( $term ) {
	$selected = dmc_category_get_brand_ids( $term->term_id );
	$brands   = dmc_category_admin_brand_options();
	?>
	<tr class="form-field term-dmc-brands-wrap">
		<th scope="row">
			<label for="dmc_category_brands"><?php esc_html_e( 'Thương hiệu (mega menu)', 'flatsome-child' ); ?></label>
		</th>
		<td>
			<?php dmc_category_brands_render_field( $selected, $brands ); ?>
			<p class="description">
				<?php esc_html_e( 'Chọn các thương hiệu hiển thị khi hover danh mục này trong menu "Danh mục sản phẩm". Giữ Ctrl (Windows) hoặc Cmd (Mac) để chọn nhiều.', 'flatsome-child' ); ?>
			</p>
		</td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'dmc_category_brands_edit_form_field' );

/**
 * Render multi-select field.
 *
 * @param int[]     $selected Selected brand term IDs.
 * @param WP_Term[] $brands   Available brands.
 */
function dmc_category_brands_render_field( $selected, $brands ) {
	wp_nonce_field( 'dmc_save_category_brands', 'dmc_category_brands_nonce' );

	if ( empty( $brands ) ) {
		echo '<p><em>' . esc_html__( 'Chưa có thương hiệu. Vui lòng tạo thương hiệu trước (Perfect Brands hoặc WooCommerce Brands).', 'flatsome-child' ) . '</em></p>';
		return;
	}

	echo '<select name="dmc_category_brands[]" id="dmc_category_brands" multiple size="10" style="min-width:280px;max-width:100%;">';
	foreach ( $brands as $brand ) {
		printf(
			'<option value="%1$d"%2$s>%3$s</option>',
			(int) $brand->term_id,
			selected( in_array( (int) $brand->term_id, $selected, true ), true, false ),
			esc_html( $brand->name )
		);
	}
	echo '</select>';
}

/**
 * Save category ↔ brand mapping.
 *
 * @param int $term_id Product category term ID.
 */
function dmc_category_brands_save( $term_id ) {
	if ( ! isset( $_POST['dmc_category_brands_nonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dmc_category_brands_nonce'] ) ), 'dmc_save_category_brands' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		return;
	}

	if ( ! current_user_can( 'manage_product_terms' ) ) {
		return;
	}

	// Keep existing mapping when brand taxonomy is unavailable and the admin field is hidden.
	if ( empty( dmc_category_admin_brand_options() ) ) {
		return;
	}

	$taxonomy = function_exists( 'dmc_pl_brand_taxonomy' ) ? dmc_pl_brand_taxonomy() : '';
	$raw      = isset( $_POST['dmc_category_brands'] ) ? (array) wp_unslash( $_POST['dmc_category_brands'] ) : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$ids      = [];

	foreach ( array_map( 'absint', $raw ) as $brand_id ) {
		if ( $brand_id <= 0 ) {
			continue;
		}

		if ( $taxonomy ) {
			$term = get_term( $brand_id, $taxonomy );
			if ( ! $term || is_wp_error( $term ) ) {
				continue;
			}
		}

		$ids[] = $brand_id;
	}

	update_term_meta( (int) $term_id, dmc_category_brands_meta_key(), array_values( array_unique( $ids ) ) );
}
add_action( 'created_product_cat', 'dmc_category_brands_save' );
add_action( 'edited_product_cat', 'dmc_category_brands_save' );
