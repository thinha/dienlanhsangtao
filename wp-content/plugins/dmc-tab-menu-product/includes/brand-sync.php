<?php
/**
 * Sync WooCommerce product brands from product titles.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Brand taxonomy slug (WooCommerce Brands or Perfect Brands).
 */
function dmc_tmp_brand_taxonomy() {
	if ( function_exists( 'dmc_pl_brand_taxonomy' ) ) {
		return dmc_pl_brand_taxonomy();
	}

	if ( taxonomy_exists( 'product_brand' ) ) {
		return 'product_brand';
	}

	if ( taxonomy_exists( 'pwb-brand' ) ) {
		return 'pwb-brand';
	}

	return '';
}

/**
 * All brand terms sorted by name length (longest first) for matching.
 *
 * @return WP_Term[]
 */
function dmc_tmp_brand_sync_terms() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$taxonomy = dmc_tmp_brand_taxonomy();
	if ( ! $taxonomy ) {
		$cache = [];
		return $cache;
	}

	$terms = get_terms(
		[
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		]
	);

	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		$cache = [];
		return $cache;
	}

	usort(
		$terms,
		static function ( $a, $b ) {
			return mb_strlen( $b->name, 'UTF-8' ) - mb_strlen( $a->name, 'UTF-8' );
		}
	);

	$cache = $terms;
	return $cache;
}

/**
 * Find brand term IDs whose name appears in the product title (case-insensitive).
 *
 * @param string   $title  Product title.
 * @param WP_Term[] $brands Brand terms.
 * @return int[]
 */
function dmc_tmp_brand_ids_from_title( $title, array $brands ) {
	$title_norm = mb_strtolower( trim( (string) $title ), 'UTF-8' );
	if ( '' === $title_norm ) {
		return [];
	}

	$matched = [];
	foreach ( $brands as $brand ) {
		$name_norm = mb_strtolower( trim( $brand->name ), 'UTF-8' );
		if ( '' === $name_norm ) {
			continue;
		}

		if ( false !== mb_strpos( $title_norm, $name_norm, 0, 'UTF-8' ) ) {
			$matched[] = (int) $brand->term_id;
		}
	}

	return array_values( array_unique( $matched ) );
}

/**
 * Assign matched brands to a single product.
 *
 * @param int       $product_id Product ID.
 * @param WP_Term[] $brands     Brand terms for matching.
 * @return array{updated:bool,matched:int[],assigned:int[]}
 */
function dmc_tmp_sync_product_brands( $product_id, array $brands ) {
	$taxonomy = dmc_tmp_brand_taxonomy();
	$result   = [
		'updated'  => false,
		'matched'  => [],
		'assigned' => [],
	];

	if ( ! $taxonomy || $product_id <= 0 ) {
		return $result;
	}

	$title = get_the_title( $product_id );
	$matched = dmc_tmp_brand_ids_from_title( $title, $brands );
	$result['matched'] = $matched;

	if ( empty( $matched ) ) {
		return $result;
	}

	$existing = wp_get_object_terms( $product_id, $taxonomy, [ 'fields' => 'ids' ] );
	if ( is_wp_error( $existing ) ) {
		$existing = [];
	}

	$existing = array_map( 'intval', $existing );
	$to_add   = array_values( array_diff( $matched, $existing ) );

	if ( empty( $to_add ) ) {
		return $result;
	}

	$merged = array_values( array_unique( array_merge( $existing, $to_add ) ) );
	$set    = wp_set_object_terms( $product_id, $merged, $taxonomy, false );

	if ( ! is_wp_error( $set ) ) {
		$result['updated']  = true;
		$result['assigned'] = $to_add;
	}

	return $result;
}

/**
 * Count published products (excluding variations).
 */
function dmc_tmp_brand_sync_product_count() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return 0;
	}

	$query = new WP_Query(
		[
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		]
	);

	return (int) $query->found_posts;
}

/**
 * Process one batch of products.
 *
 * @param int $offset     Batch offset.
 * @param int $batch_size Products per batch.
 * @return array<string, mixed>
 */
function dmc_tmp_brand_sync_batch( $offset, $batch_size = 50 ) {
	$offset     = max( 0, (int) $offset );
	$batch_size = max( 1, min( 100, (int) $batch_size ) );

	$brands = dmc_tmp_brand_sync_terms();
	$total  = dmc_tmp_brand_sync_product_count();

	$stats = [
		'total'     => $total,
		'offset'    => $offset,
		'processed' => 0,
		'updated'   => 0,
		'no_match'  => 0,
		'already'   => 0,
		'done'      => false,
		'brands'    => count( $brands ),
	];

	if ( empty( $brands ) || ! class_exists( 'WooCommerce' ) ) {
		$stats['done'] = true;
		return $stats;
	}

	$query = new WP_Query(
		[
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $batch_size,
			'offset'         => $offset,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
			'no_found_rows'  => true,
		]
	);

	foreach ( $query->posts as $product_id ) {
		++$stats['processed'];

		$sync = dmc_tmp_sync_product_brands( (int) $product_id, $brands );

		if ( $sync['updated'] ) {
			++$stats['updated'];
		} elseif ( empty( $sync['matched'] ) ) {
			++$stats['no_match'];
		} else {
			++$stats['already'];
		}
	}

	$stats['done'] = ( $offset + $stats['processed'] ) >= $total;

	return $stats;
}

/**
 * AJAX: sync product brands in batches.
 */
function dmc_tmp_ajax_sync_product_brands() {
	check_ajax_referer( 'dmc_tmp_sync_brands', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( [ 'message' => __( 'Bạn không có quyền thực hiện thao tác này.', 'dmc-tab-menu-product' ) ], 403 );
	}

	if ( ! dmc_tmp_brand_taxonomy() ) {
		wp_send_json_error( [ 'message' => __( 'Chưa tìm thấy taxonomy thương hiệu (WooCommerce Brands hoặc Perfect Brands).', 'dmc-tab-menu-product' ) ] );
	}

	$offset = isset( $_POST['offset'] ) ? (int) $_POST['offset'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	wp_send_json_success( dmc_tmp_brand_sync_batch( $offset ) );
}
add_action( 'wp_ajax_dmc_tmp_sync_product_brands', 'dmc_tmp_ajax_sync_product_brands' );

/**
 * Render brand sync panel on Homepage settings.
 *
 * @param array $field ACF field.
 */
function dmc_tmp_render_brand_sync_field( $field ) {
	$taxonomy = dmc_tmp_brand_taxonomy();
	$brands   = dmc_tmp_brand_sync_terms();
	$total    = dmc_tmp_brand_sync_product_count();
	?>
	<div class="dmc-brand-sync-panel">
		<p>
			<?php
			if ( ! $taxonomy ) {
				esc_html_e( 'Chưa phát hiện taxonomy thương hiệu. Vui lòng kích hoạt WooCommerce Brands hoặc Perfect Brands.', 'dmc-tab-menu-product' );
			} else {
				printf(
					/* translators: 1: brand count, 2: product count, 3: taxonomy slug */
					esc_html__( 'Quét %1$d thương hiệu trên %2$d sản phẩm. So khớp không phân biệt hoa thường theo tiêu đề sản phẩm (taxonomy: %3$s).', 'dmc-tab-menu-product' ),
					count( $brands ),
					$total,
					esc_html( $taxonomy )
				);
			}
			?>
		</p>
		<p class="description">
			<?php esc_html_e( 'Ví dụ: sản phẩm "Máy lạnh Daikin 1HP" sẽ được gán thương hiệu "Daikin" nếu tên thương hiệu xuất hiện trong tiêu đề. Thương hiệu đã gán sẽ không bị gỡ.', 'dmc-tab-menu-product' ); ?>
		</p>
		<p>
			<button type="button" class="button button-primary" id="dmc-brand-sync-run" <?php disabled( ! $taxonomy || empty( $brands ) || $total <= 0 ); ?>>
				<?php esc_html_e( 'Chạy đồng bộ brand', 'dmc-tab-menu-product' ); ?>
			</button>
			<span class="spinner" id="dmc-brand-sync-spinner" style="float:none;margin-top:0;"></span>
		</p>
		<div id="dmc-brand-sync-progress" style="display:none;max-width:520px;">
			<progress id="dmc-brand-sync-bar" max="100" value="0" style="width:100%;height:22px;"></progress>
			<p id="dmc-brand-sync-status" aria-live="polite"></p>
		</div>
		<div id="dmc-brand-sync-result" style="display:none;" class="notice notice-success inline"><p></p></div>
		<div id="dmc-brand-sync-error" style="display:none;" class="notice notice-error inline"><p></p></div>
	</div>
	<?php
}
add_action( 'acf/render_field/key=field_tmp_hp_brand_sync_panel', 'dmc_tmp_render_brand_sync_field' );

/**
 * Enqueue admin script on Homepage settings page.
 *
 * @param string $hook Current admin page hook.
 */
function dmc_tmp_brand_sync_admin_assets( $hook ) {
	unset( $hook );

	if ( empty( $_GET['page'] ) || 'dmc-homepage-settings' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	wp_enqueue_script(
		'dmc-brand-sync',
		DMC_TMP_URL . 'assets/js/brand-sync.js',
		[ 'jquery' ],
		DMC_TMP_VERSION,
		true
	);

	wp_localize_script(
		'dmc-brand-sync',
		'dmcBrandSync',
		[
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'dmc_tmp_sync_brands' ),
			'i18n'    => [
				'running'  => __( 'Đang đồng bộ…', 'dmc-tab-menu-product' ),
				'done'     => __( 'Hoàn tất!', 'dmc-tab-menu-product' ),
				'error'    => __( 'Có lỗi xảy ra. Vui lòng thử lại.', 'dmc-tab-menu-product' ),
				'progress' => __( 'Đã xử lý %1$d / %2$d sản phẩm — cập nhật: %3$d, đã có brand: %4$d, không khớp: %5$d', 'dmc-tab-menu-product' ),
				'summary'  => __( 'Đồng bộ xong: %1$d sản phẩm được gán thêm brand, %2$d đã đúng từ trước, %3$d không khớp brand nào.', 'dmc-tab-menu-product' ),
			],
		]
	);
}
add_action( 'admin_enqueue_scripts', 'dmc_tmp_brand_sync_admin_assets' );
