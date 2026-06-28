<?php
/**
 * Voucher admin meta boxes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_Admin_Meta_Boxes {

	public static function init() {
		add_action( 'add_meta_boxes', [ __CLASS__, 'register' ] );
		add_action( 'save_post_voucher', [ __CLASS__, 'save' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
	}

	public static function register() {
		add_meta_box(
			'dmc_voucher_settings',
			__( 'Cài đặt voucher', 'dmc-voucher' ),
			[ __CLASS__, 'render' ],
			'voucher',
			'normal',
			'high'
		);
	}

	/**
	 * @param WP_Post $post Post object.
	 */
	public static function render( $post ) {
		wp_nonce_field( 'dmc_voucher_save', 'dmc_voucher_nonce' );

		$code           = get_post_meta( $post->ID, '_dmc_voucher_code', true );
		$type           = get_post_meta( $post->ID, '_dmc_voucher_type', true ) ?: 'fixed';
		$amount         = get_post_meta( $post->ID, '_dmc_voucher_amount', true );
		$min_spend      = get_post_meta( $post->ID, '_dmc_voucher_min_spend', true );
		$max_discount   = get_post_meta( $post->ID, '_dmc_voucher_max_discount', true );
		$date_start     = get_post_meta( $post->ID, '_dmc_voucher_date_start', true );
		$date_end       = get_post_meta( $post->ID, '_dmc_voucher_date_end', true );
		$usage_limit    = get_post_meta( $post->ID, '_dmc_voucher_usage_limit', true );
		$product_ids    = (array) get_post_meta( $post->ID, '_dmc_voucher_product_ids', true );
		$category_ids   = (array) get_post_meta( $post->ID, '_dmc_voucher_category_ids', true );
		$show_homepage  = get_post_meta( $post->ID, '_dmc_voucher_show_homepage', true );
		$wc_coupon_id   = (int) get_post_meta( $post->ID, '_dmc_voucher_wc_coupon_id', true );

		$products = wc_get_products(
			[
				'limit'  => 200,
				'status' => 'publish',
				'return' => 'ids',
			]
		);

		$categories = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			]
		);
		?>
		<table class="form-table dmc-voucher-admin-table">
			<tr>
				<th><label for="dmc_voucher_code"><?php esc_html_e( 'Mã voucher', 'dmc-voucher' ); ?></label></th>
				<td>
					<input type="text" class="regular-text" id="dmc_voucher_code" name="dmc_voucher_code" value="<?php echo esc_attr( $code ); ?>" required>
					<p class="description"><?php esc_html_e( 'Mã khách hàng nhập hoặc lưu voucher. Tự động viết hoa.', 'dmc-voucher' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="dmc_voucher_type"><?php esc_html_e( 'Loại giảm giá', 'dmc-voucher' ); ?></label></th>
				<td>
					<select id="dmc_voucher_type" name="dmc_voucher_type">
						<option value="fixed" <?php selected( $type, 'fixed' ); ?>><?php esc_html_e( 'Giảm cố định (VNĐ)', 'dmc-voucher' ); ?></option>
						<option value="percent" <?php selected( $type, 'percent' ); ?>><?php esc_html_e( 'Giảm theo %', 'dmc-voucher' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="dmc_voucher_amount"><?php esc_html_e( 'Giá trị giảm', 'dmc-voucher' ); ?></label></th>
				<td>
					<input type="number" step="0.01" min="0" id="dmc_voucher_amount" name="dmc_voucher_amount" value="<?php echo esc_attr( $amount ); ?>" required>
					<p class="description"><?php esc_html_e( 'VNĐ nếu cố định, hoặc % nếu giảm theo phần trăm.', 'dmc-voucher' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="dmc_voucher_min_spend"><?php esc_html_e( 'Đơn tối thiểu', 'dmc-voucher' ); ?></label></th>
				<td>
					<input type="number" step="1" min="0" id="dmc_voucher_min_spend" name="dmc_voucher_min_spend" value="<?php echo esc_attr( $min_spend ); ?>">
				</td>
			</tr>
			<tr class="dmc-voucher-row-percent">
				<th><label for="dmc_voucher_max_discount"><?php esc_html_e( 'Giảm tối đa', 'dmc-voucher' ); ?></label></th>
				<td>
					<input type="number" step="1" min="0" id="dmc_voucher_max_discount" name="dmc_voucher_max_discount" value="<?php echo esc_attr( $max_discount ); ?>">
					<p class="description"><?php esc_html_e( 'Chỉ áp dụng khi loại giảm theo %.', 'dmc-voucher' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="dmc_voucher_date_start"><?php esc_html_e( 'Ngày bắt đầu', 'dmc-voucher' ); ?></label></th>
				<td><input type="date" id="dmc_voucher_date_start" name="dmc_voucher_date_start" value="<?php echo esc_attr( $date_start ); ?>"></td>
			</tr>
			<tr>
				<th><label for="dmc_voucher_date_end"><?php esc_html_e( 'Ngày kết thúc', 'dmc-voucher' ); ?></label></th>
				<td><input type="date" id="dmc_voucher_date_end" name="dmc_voucher_date_end" value="<?php echo esc_attr( $date_end ); ?>"></td>
			</tr>
			<tr>
				<th><label for="dmc_voucher_usage_limit"><?php esc_html_e( 'Giới hạn sử dụng', 'dmc-voucher' ); ?></label></th>
				<td>
					<input type="number" step="1" min="0" id="dmc_voucher_usage_limit" name="dmc_voucher_usage_limit" value="<?php echo esc_attr( $usage_limit ); ?>">
					<p class="description"><?php esc_html_e( 'Để trống hoặc 0 = không giới hạn.', 'dmc-voucher' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="dmc_voucher_product_ids"><?php esc_html_e( 'Sản phẩm áp dụng', 'dmc-voucher' ); ?></label></th>
				<td>
					<select id="dmc_voucher_product_ids" name="dmc_voucher_product_ids[]" multiple style="width:100%;max-width:480px;">
						<?php foreach ( $products as $product_id ) : ?>
							<option value="<?php echo esc_attr( (string) $product_id ); ?>" <?php selected( in_array( $product_id, $product_ids, true ) ); ?>>
								<?php echo esc_html( get_the_title( $product_id ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description"><?php esc_html_e( 'Để trống = áp dụng tất cả sản phẩm.', 'dmc-voucher' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="dmc_voucher_category_ids"><?php esc_html_e( 'Danh mục áp dụng', 'dmc-voucher' ); ?></label></th>
				<td>
					<select id="dmc_voucher_category_ids" name="dmc_voucher_category_ids[]" multiple style="width:100%;max-width:480px;">
						<?php if ( ! is_wp_error( $categories ) ) : ?>
							<?php foreach ( $categories as $term ) : ?>
								<option value="<?php echo esc_attr( (string) $term->term_id ); ?>" <?php selected( in_array( $term->term_id, $category_ids, true ) ); ?>>
									<?php echo esc_html( $term->name ); ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Hiển thị trang chủ', 'dmc-voucher' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="dmc_voucher_show_homepage" value="1" <?php checked( $show_homepage, '1' ); ?>>
						<?php esc_html_e( 'Hiện trong mục Voucher ưu đãi trang chủ', 'dmc-voucher' ); ?>
					</label>
				</td>
			</tr>
			<?php if ( $wc_coupon_id ) : ?>
				<tr>
					<th><?php esc_html_e( 'WooCommerce Coupon', 'dmc-voucher' ); ?></th>
					<td>
						<a href="<?php echo esc_url( get_edit_post_link( $wc_coupon_id ) ); ?>">
							<?php esc_html_e( 'Xem coupon đồng bộ', 'dmc-voucher' ); ?>
						</a>
					</td>
				</tr>
			<?php endif; ?>
		</table>
		<?php
	}

	/**
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 */
	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['dmc_voucher_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dmc_voucher_nonce'] ) ), 'dmc_voucher_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$code = DMC_Voucher_Engine::sanitize_code( wp_unslash( $_POST['dmc_voucher_code'] ?? '' ) );
		if ( '' === $code ) {
			return;
		}

		$existing = DMC_Voucher_Engine::get_voucher_by_code( $code );
		if ( $existing && (int) $existing->ID !== (int) $post_id ) {
			return;
		}

		update_post_meta( $post_id, '_dmc_voucher_code', $code );
		update_post_meta( $post_id, '_dmc_voucher_type', sanitize_text_field( wp_unslash( $_POST['dmc_voucher_type'] ?? 'fixed' ) ) );
		update_post_meta( $post_id, '_dmc_voucher_amount', (float) ( $_POST['dmc_voucher_amount'] ?? 0 ) );
		update_post_meta( $post_id, '_dmc_voucher_min_spend', (float) ( $_POST['dmc_voucher_min_spend'] ?? 0 ) );
		update_post_meta( $post_id, '_dmc_voucher_max_discount', (float) ( $_POST['dmc_voucher_max_discount'] ?? 0 ) );
		update_post_meta( $post_id, '_dmc_voucher_date_start', sanitize_text_field( wp_unslash( $_POST['dmc_voucher_date_start'] ?? '' ) ) );
		update_post_meta( $post_id, '_dmc_voucher_date_end', sanitize_text_field( wp_unslash( $_POST['dmc_voucher_date_end'] ?? '' ) ) );
		update_post_meta( $post_id, '_dmc_voucher_usage_limit', (int) ( $_POST['dmc_voucher_usage_limit'] ?? 0 ) );
		update_post_meta( $post_id, '_dmc_voucher_show_homepage', isset( $_POST['dmc_voucher_show_homepage'] ) ? '1' : '0' );

		$product_ids = array_map( 'intval', (array) ( $_POST['dmc_voucher_product_ids'] ?? [] ) );
		$category_ids = array_map( 'intval', (array) ( $_POST['dmc_voucher_category_ids'] ?? [] ) );
		update_post_meta( $post_id, '_dmc_voucher_product_ids', array_values( array_filter( $product_ids ) ) );
		update_post_meta( $post_id, '_dmc_voucher_category_ids', array_values( array_filter( $category_ids ) ) );

		if ( ! get_post_meta( $post_id, '_dmc_voucher_usage_count', true ) ) {
			update_post_meta( $post_id, '_dmc_voucher_usage_count', 0 );
		}

		if ( empty( $post->post_title ) || 'auto-draft' === $post->post_title ) {
			wp_update_post(
				[
					'ID'         => $post_id,
					'post_title' => $code,
				]
			);
		}
	}

	public static function enqueue( $hook ) {
		global $post_type;
		if ( 'voucher' !== $post_type ) {
			return;
		}

		wp_enqueue_style( 'dmc-voucher-admin', DMC_VOUCHER_URL . 'assets/css/admin.css', [], DMC_VOUCHER_VERSION );
		wp_enqueue_script( 'dmc-voucher-admin', DMC_VOUCHER_URL . 'assets/js/admin.js', [ 'jquery' ], DMC_VOUCHER_VERSION, true );
	}
}
