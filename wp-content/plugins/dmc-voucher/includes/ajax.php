<?php
/**
 * AJAX handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_Ajax {

	public static function init() {
		$actions = [
			'dmc_voucher_save',
			'dmc_voucher_apply',
			'dmc_voucher_remove',
			'dmc_voucher_apply_best',
			'dmc_voucher_preview',
			'dmc_voucher_apply_cart',
			'dmc_voucher_remove_cart',
			'dmc_voucher_apply_cart_best',
		];

		foreach ( $actions as $action ) {
			add_action( "wp_ajax_{$action}", [ __CLASS__, str_replace( 'dmc_voucher_', 'handle_', $action ) ] );
			add_action( "wp_ajax_nopriv_{$action}", [ __CLASS__, str_replace( 'dmc_voucher_', 'handle_', $action ) ] );
		}
	}

	private static function verify() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dmc_voucher' ) ) {
			wp_send_json_error( [ 'message' => __( 'Phiên làm việc không hợp lệ.', 'dmc-voucher' ) ] );
		}

		self::ensure_cart();
	}

	private static function ensure_cart() {
		if ( null === WC()->cart && function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		if ( WC()->session && ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
	}

	/**
	 * Save voucher to wallet.
	 */
	public static function handle_save() {
		self::verify();

		$voucher_id = (int) ( $_POST['voucher_id'] ?? 0 );
		if ( ! $voucher_id ) {
			wp_send_json_error( [ 'message' => __( 'Voucher không hợp lệ.', 'dmc-voucher' ) ] );
		}

		if ( ! DMC_Voucher_User_Wallet::save( $voucher_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Không thể lưu voucher.', 'dmc-voucher' ) ] );
		}

		wp_send_json_success(
			[
				'message' => __( 'Đã lưu voucher!', 'dmc-voucher' ),
				'saved'   => DMC_Voucher_User_Wallet::get_saved_cards(),
			]
		);
	}

	/**
	 * Apply voucher code on product page.
	 */
	public static function handle_apply() {
		self::verify();

		$product_id = (int) ( $_POST['product_id'] ?? 0 );
		$code       = DMC_Voucher_Engine::sanitize_code( wp_unslash( $_POST['code'] ?? '' ) );
		$qty        = max( 1, (int) ( $_POST['qty'] ?? 1 ) );

		$product = wc_get_product( $product_id );
		if ( ! $product || ! $code ) {
			wp_send_json_error( [ 'message' => __( 'Dữ liệu không hợp lệ.', 'dmc-voucher' ) ] );
		}

		$base_price = DMC_Voucher_Frontend::get_product_base_price( $product );
		$voucher    = DMC_Voucher_Engine::get_voucher_by_code( $code );

		if ( ! $voucher ) {
			wp_send_json_error( [ 'message' => __( 'Mã voucher không tồn tại.', 'dmc-voucher' ) ] );
		}

		$line_total = $base_price * $qty;
		$valid      = DMC_Voucher_Engine::validate_for_product( $voucher->ID, $product_id, $line_total );
		if ( is_wp_error( $valid ) ) {
			wp_send_json_error( [ 'message' => $valid->get_error_message() ] );
		}

		$discount = DMC_Voucher_Engine::calculate_discount( $voucher->ID, $base_price, $qty );
		DMC_Voucher_Session::set_applied_code( $code );

		wp_send_json_success( self::build_price_response( $product, $base_price, $qty, $code, $discount ) );
	}

	/**
	 * Remove applied voucher.
	 */
	public static function handle_remove() {
		self::verify();

		$product_id = (int) ( $_POST['product_id'] ?? 0 );
		$qty        = max( 1, (int) ( $_POST['qty'] ?? 1 ) );
		$product    = wc_get_product( $product_id );

		DMC_Voucher_Session::clear_applied_code();

		if ( WC()->cart ) {
			foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
				$voucher = DMC_Voucher_Engine::get_voucher_by_code( $coupon_code );
				if ( $voucher ) {
					WC()->cart->remove_coupon( $coupon_code );
				}
			}
		}

		if ( ! $product ) {
			wp_send_json_success( [ 'message' => __( 'Đã bỏ voucher.', 'dmc-voucher' ) ] );
		}

		$base_price = DMC_Voucher_Frontend::get_product_base_price( $product );
		wp_send_json_success( self::build_price_response( $product, $base_price, $qty, '', 0 ) );
	}

	/**
	 * Apply best voucher from wallet + all active.
	 */
	public static function handle_apply_best() {
		self::verify();

		$product_id = (int) ( $_POST['product_id'] ?? 0 );
		$qty        = max( 1, (int) ( $_POST['qty'] ?? 1 ) );
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error( [ 'message' => __( 'Sản phẩm không hợp lệ.', 'dmc-voucher' ) ] );
		}

		$base_price  = DMC_Voucher_Frontend::get_product_base_price( $product );
		$candidates  = array_merge(
			DMC_Voucher_User_Wallet::get_saved_ids(),
			DMC_Voucher_Engine::get_active_voucher_ids()
		);
		$candidates  = array_values( array_unique( $candidates ) );
		$best        = DMC_Voucher_Engine::get_best_voucher( $product_id, $base_price, $qty, $candidates );

		if ( ! $best ) {
			wp_send_json_error( [ 'message' => __( 'Không có voucher phù hợp cho sản phẩm này.', 'dmc-voucher' ) ] );
		}

		DMC_Voucher_Session::set_applied_code( $best['code'] );

		wp_send_json_success(
			self::build_price_response( $product, $base_price, $qty, $best['code'], $best['discount'] )
		);
	}

	/**
	 * Preview price with voucher (no session change).
	 */
	public static function handle_preview() {
		self::verify();

		$product_id = (int) ( $_POST['product_id'] ?? 0 );
		$code       = DMC_Voucher_Engine::sanitize_code( wp_unslash( $_POST['code'] ?? '' ) );
		$qty        = max( 1, (int) ( $_POST['qty'] ?? 1 ) );
		$product    = wc_get_product( $product_id );

		if ( ! $product ) {
			wp_send_json_error( [ 'message' => __( 'Sản phẩm không hợp lệ.', 'dmc-voucher' ) ] );
		}

		$base_price = DMC_Voucher_Frontend::get_product_base_price( $product );
		$voucher    = DMC_Voucher_Engine::get_voucher_by_code( $code );

		if ( ! $voucher ) {
			wp_send_json_error( [ 'message' => __( 'Mã voucher không tồn tại.', 'dmc-voucher' ) ] );
		}

		$line_total = $base_price * $qty;
		$valid      = DMC_Voucher_Engine::validate_for_product( $voucher->ID, $product_id, $line_total );
		if ( is_wp_error( $valid ) ) {
			wp_send_json_error( [ 'message' => $valid->get_error_message() ] );
		}

		$discount = DMC_Voucher_Engine::calculate_discount( $voucher->ID, $base_price, $qty );

		wp_send_json_success( self::build_price_response( $product, $base_price, $qty, $code, $discount ) );
	}

	/**
	 * Apply voucher on cart page.
	 */
	public static function handle_apply_cart() {
		self::verify();

		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			wp_send_json_error( [ 'message' => __( 'Giỏ hàng trống.', 'dmc-voucher' ) ] );
		}

		$code = DMC_Voucher_Engine::sanitize_code( wp_unslash( $_POST['code'] ?? '' ) );
		if ( ! $code ) {
			wp_send_json_error( [ 'message' => __( 'Vui lòng nhập mã voucher.', 'dmc-voucher' ) ] );
		}

		$voucher = DMC_Voucher_Engine::get_voucher_by_code( $code );
		if ( ! $voucher ) {
			wp_send_json_error( [ 'message' => __( 'Mã voucher không tồn tại.', 'dmc-voucher' ) ] );
		}

		$valid = DMC_Voucher_Engine::validate_for_cart( $voucher->ID );
		if ( is_wp_error( $valid ) ) {
			wp_send_json_error( [ 'message' => $valid->get_error_message() ] );
		}

		if ( class_exists( 'DMC_Voucher_Coupon_Sync' ) ) {
			DMC_Voucher_Coupon_Sync::ensure_coupon( $voucher->ID );
		}

		$applied = false;

		DMC_Voucher_Cart::without_session_apply(
			function () use ( $code, &$applied ) {
				foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
					if ( DMC_Voucher_Cart::is_voucher_coupon( $coupon_code ) ) {
						WC()->cart->remove_coupon( $coupon_code );
					}
				}

				$applied = (bool) WC()->cart->apply_coupon( $code );
			}
		);

		if ( ! $applied || ! WC()->cart->has_discount( $code ) ) {
			$message = __( 'Không thể áp dụng voucher này.', 'dmc-voucher' );

			if ( ! wc_coupons_enabled() ) {
				$message = __( 'Hệ thống voucher chưa sẵn sàng. Vui lòng tải lại trang và thử lại.', 'dmc-voucher' );
			} else {
				$coupon = new WC_Coupon( $code );
				if ( $coupon->get_error_message() ) {
					$message = $coupon->get_error_message();
				}
			}

			$notices = wc_get_notices( 'error' );
			if ( ! empty( $notices[0]['notice'] ) ) {
				$message = wp_strip_all_tags( $notices[0]['notice'] );
			}
			wc_clear_notices();
			wp_send_json_error( [ 'message' => $message ] );
		}

		DMC_Voucher_Session::set_applied_code( $code );
		WC()->cart->calculate_totals();

		if ( WC()->cart->get_cart_contents_count() > 0 ) {
			WC()->cart->set_session();
		}

		wp_send_json_success( self::build_cart_ajax_response() );
	}

	/**
	 * Remove voucher from cart.
	 */
	public static function handle_remove_cart() {
		self::verify();

		DMC_Voucher_Cart::without_session_apply(
			function () {
				DMC_Voucher_Cart::clear_voucher_from_cart();
			}
		);

		wp_send_json_success( self::build_cart_ajax_response() );
	}

	/**
	 * Apply best voucher for cart.
	 */
	public static function handle_apply_cart_best() {
		self::verify();

		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			wp_send_json_error( [ 'message' => __( 'Giỏ hàng trống.', 'dmc-voucher' ) ] );
		}

		$candidates = array_merge(
			DMC_Voucher_User_Wallet::get_saved_ids(),
			DMC_Voucher_Engine::get_active_voucher_ids()
		);
		$best = DMC_Voucher_Engine::get_best_voucher_for_cart( array_values( array_unique( $candidates ) ) );

		if ( ! $best ) {
			wp_send_json_error( [ 'message' => __( 'Không có voucher phù hợp cho giỏ hàng này.', 'dmc-voucher' ) ] );
		}

		$result = WC()->cart->apply_coupon( $best['code'] );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		DMC_Voucher_Session::set_applied_code( $best['code'] );
		WC()->cart->calculate_totals();

		if ( WC()->cart->get_cart_contents_count() > 0 ) {
			WC()->cart->set_session();
		}

		wp_send_json_success( self::build_cart_ajax_response() );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function build_cart_ajax_response() {
		$state = DMC_Voucher_Cart::get_applied_cart_voucher_state();
		$code  = $state['code'];
		$discount = (float) $state['discount'];

		if ( $code ) {
			DMC_Voucher_Session::set_applied_code( $code );
		} else {
			DMC_Voucher_Session::clear_applied_code();
		}

		$response = [
			'message'      => $code ? __( 'Đã áp dụng voucher!', 'dmc-voucher' ) : __( 'Đã bỏ voucher.', 'dmc-voucher' ),
			'code'         => $code,
			'discount'     => $discount,
			'discount_fmt' => DMC_Voucher_Engine::format_price_plain( $discount ),
		];

		if ( function_exists( 'dmc_cart_get_totals_html' ) ) {
			$response['totals_html'] = dmc_cart_get_totals_html();
		}

		return $response;
	}

	/**
	 * @param WC_Product $product Product.
	 * @param float      $base_price Base unit price.
	 * @param int        $qty Quantity.
	 * @param string     $code Applied code.
	 * @param float      $discount Total discount.
	 * @return array<string, mixed>
	 */
	private static function build_price_response( $product, $base_price, $qty, $code, $discount ) {
		$unit_after  = max( 0, $base_price - ( $discount / max( 1, $qty ) ) );
		$sale_total  = $unit_after * $qty;

		return [
			'message'        => $code ? __( 'Đã áp dụng voucher!', 'dmc-voucher' ) : __( 'Đã bỏ voucher.', 'dmc-voucher' ),
			'code'           => $code,
			'base_price'     => $base_price,
			'unit_after'     => $unit_after,
			'discount'       => $discount,
			'sale_total'     => $sale_total,
			'sale_total_fmt' => DMC_Voucher_Engine::format_price_plain( $sale_total ),
			'unit_after_fmt' => DMC_Voucher_Engine::format_price_plain( $unit_after ),
			'discount_fmt'   => DMC_Voucher_Engine::format_price_plain( $discount ),
			'product_id'     => $product->get_id(),
		];
	}
}
