<?php
/**
 * Cart & checkout voucher integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_Cart {

	/**
	 * @var bool
	 */
	private static $prevent_session_apply = false;

	public static function init() {
		add_action( 'woocommerce_add_to_cart', [ __CLASS__, 'apply_session_coupon' ], 20 );
		add_action( 'woocommerce_cart_loaded_from_session', [ __CLASS__, 'apply_session_coupon' ] );
		add_action( 'woocommerce_applied_coupon', [ __CLASS__, 'on_applied_coupon' ] );
		add_action( 'woocommerce_removed_coupon', [ __CLASS__, 'on_removed_coupon' ] );
		add_filter( 'dmc_voucher_adjusted_sale_price', [ __CLASS__, 'filter_sale_price' ], 10, 2 );
	}

	/**
	 * @param callable $callback Callback to run while auto-apply is disabled.
	 * @return mixed
	 */
	public static function without_session_apply( $callback ) {
		self::$prevent_session_apply = true;

		try {
			return $callback();
		} finally {
			self::$prevent_session_apply = false;
		}
	}

	/**
	 * @param string $coupon_code Coupon code.
	 */
	public static function is_voucher_coupon( $coupon_code ) {
		$coupon = new WC_Coupon( $coupon_code );
		if ( $coupon->get_id() ) {
			$linked = get_posts(
				[
					'post_type'      => 'voucher',
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						[
							'key'   => '_dmc_voucher_wc_coupon_id',
							'value' => $coupon->get_id(),
						],
					],
				]
			);

			if ( ! empty( $linked ) ) {
				return true;
			}
		}

		if ( DMC_Voucher_Engine::get_voucher_by_code( $coupon_code ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Remove all DMC voucher coupons from the cart and clear session.
	 */
	public static function clear_voucher_from_cart() {
		if ( ! WC()->cart ) {
			DMC_Voucher_Session::clear_applied_code();
			return;
		}

		$session_code  = DMC_Voucher_Session::get_applied_code();
		$codes_to_remove = [];

		foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
			if ( self::is_voucher_coupon( $coupon_code ) ) {
				$codes_to_remove[] = $coupon_code;
				continue;
			}

			if ( $session_code && function_exists( 'wc_is_same_coupon' ) && wc_is_same_coupon( $coupon_code, $session_code ) ) {
				$codes_to_remove[] = $coupon_code;
			}
		}

		DMC_Voucher_Session::clear_applied_code();

		foreach ( array_unique( $codes_to_remove ) as $coupon_code ) {
			WC()->cart->remove_coupon( $coupon_code );
		}

		WC()->cart->calculate_totals();

		if ( WC()->cart->get_cart_contents_count() > 0 ) {
			WC()->cart->set_session();
		}
	}

	/**
	 * @return array{code: string, discount: float}
	 */
	public static function get_applied_cart_voucher_state() {
		$code     = '';
		$discount = 0.0;

		if ( ! WC()->cart ) {
			return [
				'code'     => $code,
				'discount' => $discount,
			];
		}

		foreach ( WC()->cart->get_applied_coupons() as $coupon_code ) {
			if ( ! self::is_voucher_coupon( $coupon_code ) ) {
				continue;
			}

			$code     = DMC_Voucher_Engine::sanitize_code( $coupon_code );
			$discount = (float) WC()->cart->get_coupon_discount_amount( $coupon_code, WC()->cart->display_cart_ex_tax );
			break;
		}

		return [
			'code'     => $code,
			'discount' => $discount,
		];
	}

	public static function apply_session_coupon() {
		if ( self::$prevent_session_apply || ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$code = DMC_Voucher_Session::get_applied_code();
		if ( ! $code ) {
			return;
		}

		$voucher = DMC_Voucher_Engine::get_voucher_by_code( $code );
		if ( ! $voucher ) {
			return;
		}

		// Ensure WooCommerce coupon exists (sync may have failed on save).
		if ( class_exists( 'DMC_Voucher_Coupon_Sync' ) ) {
			DMC_Voucher_Coupon_Sync::ensure_coupon( $voucher->ID );
		}

		if ( ! WC()->cart->has_discount( $code ) ) {
			$valid = DMC_Voucher_Engine::validate_for_cart( $voucher->ID );
			if ( is_wp_error( $valid ) ) {
				return;
			}

			WC()->cart->apply_coupon( $code );
		}
	}

	/**
	 * @param float      $sale_price Sale price.
	 * @param WC_Product $product Product.
	 */
	public static function filter_sale_price( $sale_price, $product ) {
		return DMC_Voucher_Engine::get_adjusted_sale_price( $product, $sale_price );
	}

	/**
	 * @param string $code Coupon code.
	 */
	public static function on_applied_coupon( $code ) {
		if ( self::is_voucher_coupon( $code ) ) {
			DMC_Voucher_Session::set_applied_code( $code );
		}
	}

	/**
	 * @param string $code Coupon code.
	 */
	public static function on_removed_coupon( $code ) {
		if ( self::is_voucher_coupon( $code ) ) {
			DMC_Voucher_Session::clear_applied_code();
			return;
		}

		$session_code = DMC_Voucher_Session::get_applied_code();
		if ( $session_code && function_exists( 'wc_is_same_coupon' ) && wc_is_same_coupon( $session_code, $code ) ) {
			DMC_Voucher_Session::clear_applied_code();
		}
	}
}
