<?php
/**
 * Crawl product — admin page under WooCommerce Products.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register submenu under Products.
 */
function dmc_tmp_crawl_register_admin_menu() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	add_submenu_page(
		'edit.php?post_type=product',
		__( 'Crawl Product', 'dmc-tab-menu-product' ),
		__( 'Crawl Product', 'dmc-tab-menu-product' ),
		'edit_products',
		'dmc-crawl-product',
		'dmc_tmp_crawl_render_admin_page'
	);
}
add_action( 'admin_menu', 'dmc_tmp_crawl_register_admin_menu', 25 );

/**
 * Enqueue admin assets.
 *
 * @param string $hook Current admin page hook.
 */
function dmc_tmp_crawl_admin_assets( $hook ) {
	if ( 'product_page_dmc-crawl-product' !== $hook ) {
		return;
	}

	wp_enqueue_style(
		'dmc-crawl-product-admin',
		DMC_TMP_URL . 'assets/css/admin-crawl-product.css',
		[],
		DMC_TMP_VERSION
	);

	wp_enqueue_script(
		'dmc-crawl-product-admin',
		DMC_TMP_URL . 'assets/js/admin-crawl-product.js',
		[ 'jquery' ],
		DMC_TMP_VERSION,
		true
	);

	wp_localize_script(
		'dmc-crawl-product-admin',
		'dmcCrawlProduct',
		[
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'dmc_tmp_crawl_product' ),
			'fields'  => dmc_tmp_crawl_field_definitions(),
			'i18n'    => [
				'crawling'       => __( 'Đang crawl…', 'dmc-tab-menu-product' ),
				'creating'       => __( 'Đang tạo sản phẩm…', 'dmc-tab-menu-product' ),
				'error'          => __( 'Có lỗi xảy ra.', 'dmc-tab-menu-product' ),
				'invalidUrl'     => __( 'Vui lòng nhập URL hợp lệ.', 'dmc-tab-menu-product' ),
				'filled'         => __( 'Đã có', 'dmc-tab-menu-product' ),
				'missing'        => __( 'Chưa có', 'dmc-tab-menu-product' ),
				'required'       => __( 'Bắt buộc', 'dmc-tab-menu-product' ),
				'groupWoo'       => __( 'WooCommerce', 'dmc-tab-menu-product' ),
				'groupAcf'       => __( 'ACF — Chi tiết sản phẩm', 'dmc-tab-menu-product' ),
				'groupMeta'      => __( 'Meta', 'dmc-tab-menu-product' ),
				'summaryFilled'  => __( 'Đã crawl: %d trường', 'dmc-tab-menu-product' ),
				'summaryMissing' => __( 'Cần điền: %d trường', 'dmc-tab-menu-product' ),
				'summaryRequired'=> __( 'Bắt buộc còn thiếu: %d', 'dmc-tab-menu-product' ),
			],
		]
	);
}
add_action( 'admin_enqueue_scripts', 'dmc_tmp_crawl_admin_assets' );

/**
 * Render admin page.
 */
function dmc_tmp_crawl_render_admin_page() {
	if ( ! current_user_can( 'edit_products' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'dmc-tab-menu-product' ) );
	}

	$hosts = dmc_tmp_crawl_supported_hosts();
	?>
	<div class="wrap dmc-crawl-product">
		<h1><?php esc_html_e( 'Crawl Product', 'dmc-tab-menu-product' ); ?></h1>
		<p class="description">
			<?php esc_html_e( 'Nhập link sản phẩm nguồn, hệ thống sẽ crawl thông tin và hiển thị trường nào đã có / chưa có. Bạn điền phần còn thiếu rồi tạo sản phẩm WooCommerce.', 'dmc-tab-menu-product' ); ?>
		</p>

		<div class="dmc-crawl-product__sources notice notice-info inline">
			<p>
				<strong><?php esc_html_e( 'Nguồn hỗ trợ:', 'dmc-tab-menu-product' ); ?></strong>
				<?php
				echo esc_html(
					implode(
						', ',
						array_unique( array_values( $hosts ) )
					)
				);
				?>
			</p>
		</div>

		<div class="dmc-crawl-product__step dmc-crawl-product__step--url card">
			<h2><?php esc_html_e( '1. Nhập link sản phẩm', 'dmc-tab-menu-product' ); ?></h2>
			<div class="dmc-crawl-product__url-row">
				<input
					type="url"
					id="dmc-crawl-url"
					class="large-text"
					placeholder="https://sanaky.com.vn/san-pham/..."
					value=""
				/>
				<button type="button" class="button button-primary" id="dmc-crawl-run">
					<?php esc_html_e( 'Crawl', 'dmc-tab-menu-product' ); ?>
				</button>
				<span class="spinner" id="dmc-crawl-spinner"></span>
			</div>
		</div>

		<div id="dmc-crawl-error" class="notice notice-error inline" style="display:none;">
			<p></p>
		</div>

		<div id="dmc-crawl-result" class="dmc-crawl-product__step card" style="display:none;">
			<h2><?php esc_html_e( '2. Kiểm tra & điền thông tin', 'dmc-tab-menu-product' ); ?></h2>

			<div id="dmc-crawl-summary" class="dmc-crawl-product__summary" aria-live="polite"></div>

			<form id="dmc-crawl-form">
				<div id="dmc-crawl-fields"></div>

				<p class="submit">
					<button type="submit" class="button button-primary button-hero" id="dmc-crawl-create">
						<?php esc_html_e( 'Tạo sản phẩm WooCommerce', 'dmc-tab-menu-product' ); ?>
					</button>
					<span class="spinner" id="dmc-crawl-create-spinner"></span>
				</p>
			</form>
		</div>

		<div id="dmc-crawl-success" class="notice notice-success inline" style="display:none;">
			<p></p>
		</div>
	</div>
	<?php
}

/**
 * AJAX: crawl product URL.
 */
function dmc_tmp_ajax_crawl_product() {
	check_ajax_referer( 'dmc_tmp_crawl_product', 'nonce' );

	if ( ! current_user_can( 'edit_products' ) ) {
		wp_send_json_error( [ 'message' => __( 'Bạn không có quyền thực hiện thao tác này.', 'dmc-tab-menu-product' ) ], 403 );
	}

	$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing

	$result = dmc_tmp_crawl_product( $url );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( [ 'message' => $result->get_error_message() ] );
	}

	$form_values = [];
	$defs        = dmc_tmp_crawl_field_definitions();
	foreach ( $defs as $key => $def ) {
		$raw = $result['data'][ $key ] ?? '';
		if ( 'gallery_images' === $key && is_array( $raw ) ) {
			$form_values[ $key ] = implode( "\n", $raw );
		} else {
			$form_values[ $key ] = dmc_tmp_crawl_value_to_string( $raw );
		}
	}

	wp_send_json_success(
		[
			'parser'  => $result['parser'],
			'values'  => $form_values,
			'summary' => $result['summary'],
		]
	);
}
add_action( 'wp_ajax_dmc_tmp_crawl_product', 'dmc_tmp_ajax_crawl_product' );

/**
 * AJAX: create product from form.
 */
function dmc_tmp_ajax_create_crawled_product() {
	check_ajax_referer( 'dmc_tmp_crawl_product', 'nonce' );

	if ( ! current_user_can( 'edit_products' ) ) {
		wp_send_json_error( [ 'message' => __( 'Bạn không có quyền thực hiện thao tác này.', 'dmc-tab-menu-product' ) ], 403 );
	}

	$raw  = isset( $_POST['product'] ) ? wp_unslash( $_POST['product'] ) : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$data = dmc_tmp_crawl_sanitize_form( is_array( $raw ) ? $raw : [] );

	$result = dmc_tmp_crawl_create_product( $data );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error(
			[
				'message' => $result->get_error_message(),
				'fields'  => $result->get_error_data( 'fields' ) ?: $result->get_error_data(),
			]
		);
	}

	wp_send_json_success(
		[
			'message'    => __( 'Đã tạo sản phẩm thành công!', 'dmc-tab-menu-product' ),
			'product_id' => $result['product_id'],
			'edit_url'   => $result['edit_url'],
			'view_url'   => $result['view_url'] ?? '',
		]
	);
}
add_action( 'wp_ajax_dmc_tmp_create_crawled_product', 'dmc_tmp_ajax_create_crawled_product' );
