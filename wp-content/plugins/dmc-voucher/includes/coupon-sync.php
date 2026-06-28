<?php
/**
 * Sync voucher CPT to WooCommerce coupon.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_Coupon_Sync {

	/**
	 * @var array<string, array{total: float, eligible: float, given: float}>
	 */
	private static $cart_discount_state = [];

	public static function init() {
		add_action( 'save_post_voucher', [ __CLASS__, 'sync' ], 20, 2 );
		add_action( 'before_delete_post', [ __CLASS__, 'delete_coupon' ] );
		add_action( 'woocommerce_checkout_order_processed', [ __CLASS__, 'increment_usage' ], 10, 3 );
		add_action( 'woocommerce_before_calculate_totals', [ __CLASS__, 'reset_cart_discount_state' ], 1 );
		add_filter( 'woocommerce_coupon_get_discount_amount', [ __CLASS__, 'cart_level_voucher_discount' ], 20, 5 );
	}

	public static function reset_cart_discount_state() {
		self::$cart_discount_state = [];
	}

	/**
	 * Ensure WC coupon exists for a voucher.
	 *
	 * @param int $voucher_id Voucher post ID.
	 */
	public static function ensure_coupon( $voucher_id ) {
		$post = get_post( $voucher_id );
		if ( $post && 'voucher' === $post->post_type ) {
			self::sync( $voucher_id, $post );
		}
	}

	/**
	 * @param int     $post_id Voucher post ID.
	 * @param WP_Post $post Post object.
	 */
	public static function sync( $post_id, $post ) {
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$code = get_post_meta( $post_id, '_dmc_voucher_code', true );
		if ( ! $code ) {
			return;
		}

		$coupon_id = (int) get_post_meta( $post_id, '_dmc_voucher_wc_coupon_id', true );
		$coupon    = $coupon_id ? new WC_Coupon( $coupon_id ) : new WC_Coupon( $code );

		if ( ! $coupon->get_id() ) {
			$coupon->set_code( $code );
		}

		$type   = get_post_meta( $post_id, '_dmc_voucher_type', true ) ?: 'fixed';
		$amount = (float) get_post_meta( $post_id, '_dmc_voucher_amount', true );

		$coupon->set_discount_type( 'percent' === $type ? 'percent' : 'fixed_cart' );
		$coupon->set_amount( $amount );
		$coupon->set_individual_use( false );
		$coupon->set_usage_limit( (int) get_post_meta( $post_id, '_dmc_voucher_usage_limit', true ) ?: 0 );
		$coupon->set_minimum_amount( (float) get_post_meta( $post_id, '_dmc_voucher_min_spend', true ) );

		$date_start = get_post_meta( $post_id, '_dmc_voucher_date_start', true );
		$date_end   = get_post_meta( $post_id, '_dmc_voucher_date_end', true );
		$coupon->set_date_expires( $date_end ? strtotime( $date_end . ' 23:59:59' ) : null );

		if ( $date_start ) {
			update_post_meta( $coupon->get_id() ?: 0, 'date_starts', $date_start );
		}

		$product_ids  = array_map( 'intval', (array) get_post_meta( $post_id, '_dmc_voucher_product_ids', true ) );
		$category_ids = array_map( 'intval', (array) get_post_meta( $post_id, '_dmc_voucher_category_ids', true ) );
		$coupon->set_product_ids( array_values( array_filter( $product_ids ) ) );
		$coupon->set_product_categories( array_values( array_filter( $category_ids ) ) );

		// WC maximum_amount = max cart spend, NOT max discount cap for % coupons.
		$coupon->set_maximum_amount( 0 );

		$coupon->save();

		$max_discount = (float) get_post_meta( $post_id, '_dmc_voucher_max_discount', true );
		if ( 'percent' === $type && $max_discount > 0 ) {
			update_post_meta( $coupon->get_id(), '_dmc_voucher_max_discount', $max_discount );
		} else {
			delete_post_meta( $coupon->get_id(), '_dmc_voucher_max_discount' );
		}

		if ( ! $coupon_id ) {
			update_post_meta( $post_id, '_dmc_voucher_wc_coupon_id', $coupon->get_id() );
		}
	}

	/**
	 * Apply voucher discount once on eligible cart subtotal, distributed by line share.
	 *
	 * @param float      $discount Discount amount.
	 * @param float      $discounting_amount Amount being discounted.
	 * @param array|null $cart_item Cart item.
	 * @param bool       $single Single item discount.
	 * @param WC_Coupon  $coupon Coupon object.
	 */
	public static function cart_level_voucher_discount( $discount, $discounting_amount, $cart_item, $single, $coupon ) {
		unset( $single );

		$voucher = DMC_Voucher_Engine::get_voucher_by_code( $coupon->get_code() );
		if ( ! $voucher || ! WC()->cart || empty( $cart_item ) ) {
			return (float) $discount;
		}

		$product_id = (int) ( $cart_item['product_id'] ?? 0 );
		if ( ! $product_id || ! DMC_Voucher_Engine::product_matches_voucher( $voucher->ID, $product_id ) ) {
			return 0;
		}

		$code = $coupon->get_code();
		if ( ! isset( self::$cart_discount_state[ $code ] ) ) {
			$eligible_total = 0.0;

			foreach ( WC()->cart->get_cart() as $item ) {
				$item_product_id = (int) ( $item['product_id'] ?? 0 );
				if ( ! $item_product_id || ! DMC_Voucher_Engine::product_matches_voucher( $voucher->ID, $item_product_id ) ) {
					continue;
				}

				$product = $item['data'] ?? wc_get_product( $item_product_id );
				if ( ! $product instanceof WC_Product ) {
					continue;
				}

				$eligible_total += (float) $product->get_price() * max( 1, (int) ( $item['quantity'] ?? 1 ) );
			}

			self::$cart_discount_state[ $code ] = [
				'total'    => DMC_Voucher_Engine::calculate_cart_discount( $voucher->ID ),
				'eligible' => $eligible_total,
				'given'    => 0.0,
			];
		}

		$state = &self::$cart_discount_state[ $code ];
		if ( $state['eligible'] <= 0 || $state['total'] <= 0 ) {
			return 0;
		}

		$line_total    = (float) $discounting_amount;
		$remaining     = max( 0, $state['total'] - $state['given'] );
		$line_discount = min( ( $line_total / $state['eligible'] ) * $state['total'], $remaining, $line_total );
		$state['given'] += $line_discount;

		return $line_discount;
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public static function delete_coupon( $post_id ) {
		if ( 'voucher' !== get_post_type( $post_id ) ) {
			return;
		}

		$coupon_id = (int) get_post_meta( $post_id, '_dmc_voucher_wc_coupon_id', true );
		if ( $coupon_id ) {
			wp_delete_post( $coupon_id, true );
		}
	}

	/**
	 * @param int      $order_id Order ID.
	 * @param array    $posted_data Posted data.
	 * @param WC_Order $order Order object.
	 */
	public static function increment_usage( $order_id, $posted_data, $order ) {
		unset( $posted_data );

		foreach ( $order->get_coupon_codes() as $code ) {
			$voucher = DMC_Voucher_Engine::get_voucher_by_code( $code );
			if ( ! $voucher ) {
				continue;
			}

			$used = (int) get_post_meta( $voucher->ID, '_dmc_voucher_usage_count', true );
			update_post_meta( $voucher->ID, '_dmc_voucher_usage_count', $used + 1 );
		}
	}
}
