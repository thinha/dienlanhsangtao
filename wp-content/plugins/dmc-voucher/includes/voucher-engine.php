<?php
/**
 * Voucher calculation & validation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_Engine {

	/**
	 * @param string $code Voucher code.
	 * @return WP_Post|null
	 */
	public static function get_voucher_by_code( $code ) {
		$code = self::sanitize_code( $code );
		if ( '' === $code ) {
			return null;
		}

		$posts = get_posts(
			[
				'post_type'      => 'voucher',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'   => '_dmc_voucher_code',
						'value' => $code,
					],
				],
			]
		);

		return ! empty( $posts ) ? $posts[0] : null;
	}

	/**
	 * @param int $voucher_id Voucher post ID.
	 * @return string
	 */
	public static function sanitize_code( $code ) {
		return strtoupper( trim( sanitize_text_field( (string) $code ) ) );
	}

	/**
	 * Plain-text price for labels, AJAX payloads and JS .text() updates.
	 *
	 * @param float $amount Amount.
	 * @return string
	 */
	public static function format_price_plain( $amount ) {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * @param int $voucher_id Voucher post ID.
	 */
	public static function is_voucher_active( $voucher_id ) {
		$post = get_post( $voucher_id );
		if ( ! $post || 'voucher' !== $post->post_type || 'publish' !== $post->post_status ) {
			return false;
		}

		$now = current_time( 'timestamp' );

		$start = get_post_meta( $voucher_id, '_dmc_voucher_date_start', true );
		if ( $start && strtotime( $start . ' 00:00:00' ) > $now ) {
			return false;
		}

		$end = get_post_meta( $voucher_id, '_dmc_voucher_date_end', true );
		if ( $end && strtotime( $end . ' 23:59:59' ) < $now ) {
			return false;
		}

		$limit = (int) get_post_meta( $voucher_id, '_dmc_voucher_usage_limit', true );
		$used  = (int) get_post_meta( $voucher_id, '_dmc_voucher_usage_count', true );
		if ( $limit > 0 && $used >= $limit ) {
			return false;
		}

		return true;
	}

	/**
	 * @param int   $voucher_id Voucher post ID.
	 * @param int   $product_id Product ID.
	 * @param float $line_total Line total before voucher.
	 * @return true|WP_Error
	 */
	public static function validate_for_product( $voucher_id, $product_id, $line_total ) {
		if ( ! self::is_voucher_active( $voucher_id ) ) {
			return new WP_Error( 'inactive', __( 'Voucher không còn hiệu lực.', 'dmc-voucher' ) );
		}

		$min_spend = (float) get_post_meta( $voucher_id, '_dmc_voucher_min_spend', true );
		if ( $min_spend > 0 && $line_total < $min_spend ) {
			return new WP_Error(
				'min_spend',
				sprintf(
					/* translators: %s: minimum order amount */
					__( 'Đơn hàng tối thiểu %s để dùng voucher này.', 'dmc-voucher' ),
					wp_strip_all_tags( wc_price( $min_spend ) )
				)
			);
		}

		if ( ! self::product_matches_voucher( $voucher_id, $product_id ) ) {
			return new WP_Error( 'product', __( 'Voucher không áp dụng cho sản phẩm này.', 'dmc-voucher' ) );
		}

		return true;
	}

	/**
	 * @param int $voucher_id Voucher post ID.
	 * @param int $product_id Product ID.
	 */
	public static function product_matches_voucher( $voucher_id, $product_id ) {
		$product_ids  = (array) get_post_meta( $voucher_id, '_dmc_voucher_product_ids', true );
		$category_ids = (array) get_post_meta( $voucher_id, '_dmc_voucher_category_ids', true );

		$product_ids  = array_filter( array_map( 'intval', $product_ids ) );
		$category_ids = array_filter( array_map( 'intval', $category_ids ) );

		if ( empty( $product_ids ) && empty( $category_ids ) ) {
			return true;
		}

		if ( ! empty( $product_ids ) && in_array( (int) $product_id, $product_ids, true ) ) {
			return true;
		}

		if ( ! empty( $category_ids ) ) {
			$terms = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] );
			if ( ! is_wp_error( $terms ) && array_intersect( $category_ids, $terms ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param int   $voucher_id Voucher post ID.
	 * @param float $unit_price Unit price.
	 * @param int   $qty Quantity.
	 */
	public static function calculate_discount( $voucher_id, $unit_price, $qty = 1 ) {
		$line_total = (float) $unit_price * max( 1, (int) $qty );
		$type       = get_post_meta( $voucher_id, '_dmc_voucher_type', true ) ?: 'fixed';
		$amount     = (float) get_post_meta( $voucher_id, '_dmc_voucher_amount', true );

		if ( 'percent' === $type ) {
			$discount    = $line_total * ( $amount / 100 );
			$max_discount = (float) get_post_meta( $voucher_id, '_dmc_voucher_max_discount', true );
			if ( $max_discount > 0 ) {
				$discount = min( $discount, $max_discount );
			}
		} else {
			$discount = min( $amount * max( 1, (int) $qty ), $line_total );
		}

		return max( 0, round( $discount, wc_get_price_decimals() ) );
	}

	/**
	 * @param int        $product_id Product ID.
	 * @param float      $unit_price Unit price.
	 * @param int        $qty Quantity.
	 * @param array<int> $voucher_ids Candidate voucher IDs.
	 * @return array{voucher_id:int,code:string,discount:float,final_price:float}|null
	 */
	public static function get_best_voucher( $product_id, $unit_price, $qty = 1, $voucher_ids = [] ) {
		$line_total = (float) $unit_price * max( 1, (int) $qty );
		$best       = null;

		if ( empty( $voucher_ids ) ) {
			$voucher_ids = self::get_active_voucher_ids();
		}

		foreach ( $voucher_ids as $voucher_id ) {
			$voucher_id = (int) $voucher_id;
			$valid      = self::validate_for_product( $voucher_id, $product_id, $line_total );
			if ( is_wp_error( $valid ) ) {
				continue;
			}

			$discount = self::calculate_discount( $voucher_id, $unit_price, $qty );
			if ( $discount <= 0 ) {
				continue;
			}

			if ( null === $best || $discount > $best['discount'] ) {
				$best = [
					'voucher_id'  => $voucher_id,
					'code'        => get_post_meta( $voucher_id, '_dmc_voucher_code', true ),
					'discount'    => $discount,
					'final_price' => max( 0, $line_total - $discount ),
				];
			}
		}

		return $best;
	}

	/**
	 * @return float
	 */
	public static function get_cart_subtotal() {
		if ( ! WC()->cart ) {
			return 0.0;
		}

		return (float) WC()->cart->get_displayed_subtotal();
	}

	/**
	 * @param int $voucher_id Voucher post ID.
	 */
	public static function cart_matches_voucher( $voucher_id ) {
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return false;
		}

		$product_ids  = (array) get_post_meta( $voucher_id, '_dmc_voucher_product_ids', true );
		$category_ids = (array) get_post_meta( $voucher_id, '_dmc_voucher_category_ids', true );
		$product_ids  = array_filter( array_map( 'intval', $product_ids ) );
		$category_ids = array_filter( array_map( 'intval', $category_ids ) );

		if ( empty( $product_ids ) && empty( $category_ids ) ) {
			return true;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_id = (int) ( $cart_item['product_id'] ?? 0 );
			if ( $product_id && self::product_matches_voucher( $voucher_id, $product_id ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param int $voucher_id Voucher post ID.
	 */
	public static function validate_for_cart( $voucher_id ) {
		if ( ! self::is_voucher_active( $voucher_id ) ) {
			return new WP_Error( 'inactive', __( 'Voucher không còn hiệu lực.', 'dmc-voucher' ) );
		}

		if ( ! self::cart_matches_voucher( $voucher_id ) ) {
			return new WP_Error( 'product', __( 'Voucher không áp dụng cho giỏ hàng này.', 'dmc-voucher' ) );
		}

		$min_spend = (float) get_post_meta( $voucher_id, '_dmc_voucher_min_spend', true );
		$subtotal  = self::get_cart_subtotal();
		if ( $min_spend > 0 && $subtotal < $min_spend ) {
			return new WP_Error(
				'min_spend',
				sprintf(
					/* translators: %s: minimum order amount */
					__( 'Đơn hàng tối thiểu %s để dùng voucher này.', 'dmc-voucher' ),
					wp_strip_all_tags( wc_price( $min_spend ) )
				)
			);
		}

		return true;
	}

	/**
	 * @param int $voucher_id Voucher post ID.
	 */
	public static function calculate_cart_discount( $voucher_id ) {
		if ( ! WC()->cart ) {
			return 0.0;
		}

		$type           = get_post_meta( $voucher_id, '_dmc_voucher_type', true ) ?: 'fixed';
		$amount         = (float) get_post_meta( $voucher_id, '_dmc_voucher_amount', true );
		$eligible_total = 0.0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_id = (int) ( $cart_item['product_id'] ?? 0 );
			if ( ! $product_id || ! self::product_matches_voucher( $voucher_id, $product_id ) ) {
				continue;
			}

			$product = $cart_item['data'] ?? wc_get_product( $product_id );
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			$qty             = max( 1, (int) ( $cart_item['quantity'] ?? 1 ) );
			$eligible_total += (float) $product->get_price() * $qty;
		}

		if ( $eligible_total <= 0 ) {
			return 0.0;
		}

		if ( 'percent' === $type ) {
			$total = $eligible_total * ( $amount / 100 );
			$max   = (float) get_post_meta( $voucher_id, '_dmc_voucher_max_discount', true );
			if ( $max > 0 ) {
				$total = min( $total, $max );
			}
		} else {
			$total = min( $amount, $eligible_total );
		}

		return max( 0, round( $total, wc_get_price_decimals() ) );
	}

	/**
	 * @param array<int> $voucher_ids Candidate voucher IDs.
	 * @return array{voucher_id:int,code:string,discount:float}|null
	 */
	public static function get_best_voucher_for_cart( $voucher_ids = [] ) {
		$best = null;

		if ( empty( $voucher_ids ) ) {
			$voucher_ids = self::get_active_voucher_ids();
		}

		foreach ( $voucher_ids as $voucher_id ) {
			$voucher_id = (int) $voucher_id;
			$valid      = self::validate_for_cart( $voucher_id );
			if ( is_wp_error( $valid ) ) {
				continue;
			}

			$discount = self::calculate_cart_discount( $voucher_id );
			if ( $discount <= 0 ) {
				continue;
			}

			if ( null === $best || $discount > $best['discount'] ) {
				$best = [
					'voucher_id' => $voucher_id,
					'code'       => get_post_meta( $voucher_id, '_dmc_voucher_code', true ),
					'discount'   => $discount,
				];
			}
		}

		return $best;
	}

	/**
	 * @param int $limit Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_applicable_vouchers_for_cart( $limit = 8 ) {
		$result = [];

		foreach ( self::get_active_voucher_ids() as $voucher_id ) {
			$valid = self::validate_for_cart( $voucher_id );
			if ( is_wp_error( $valid ) ) {
				continue;
			}

			$discount = self::calculate_cart_discount( $voucher_id );
			if ( $discount <= 0 ) {
				continue;
			}

			$result[] = self::format_voucher_card( $voucher_id, $discount );
		}

		usort(
			$result,
			function ( $a, $b ) {
				return $b['discount'] <=> $a['discount'];
			}
		);

		return array_slice( $result, 0, $limit );
	}

	/**
	 * @return array<int>
	 */
	public static function get_active_voucher_ids() {
		$posts = get_posts(
			[
				'post_type'      => 'voucher',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		return array_values(
			array_filter(
				array_map( 'intval', $posts ),
				[ __CLASS__, 'is_voucher_active' ]
			)
		);
	}

	/**
	 * @param int   $product_id Product ID.
	 * @param float $unit_price Unit price.
	 * @param int   $limit Limit.
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_applicable_vouchers_for_product( $product_id, $unit_price, $limit = 10 ) {
		$result = [];

		foreach ( self::get_active_voucher_ids() as $voucher_id ) {
			$valid = self::validate_for_product( $voucher_id, $product_id, $unit_price );
			if ( is_wp_error( $valid ) ) {
				continue;
			}

			$discount = self::calculate_discount( $voucher_id, $unit_price, 1 );
			if ( $discount <= 0 ) {
				continue;
			}

			$result[] = self::format_voucher_card( $voucher_id, $discount );
		}

		usort(
			$result,
			function ( $a, $b ) {
				return $b['discount'] <=> $a['discount'];
			}
		);

		return array_slice( $result, 0, $limit );
	}

	/**
	 * @param int   $voucher_id Voucher post ID.
	 * @param float $discount Optional precomputed discount.
	 * @return array<string, mixed>
	 */
	public static function format_voucher_card( $voucher_id, $discount = 0 ) {
		$type        = get_post_meta( $voucher_id, '_dmc_voucher_type', true ) ?: 'fixed';
		$amount      = (float) get_post_meta( $voucher_id, '_dmc_voucher_amount', true );
		$min_spend   = (float) get_post_meta( $voucher_id, '_dmc_voucher_min_spend', true );
		$max_discount = (float) get_post_meta( $voucher_id, '_dmc_voucher_max_discount', true );
		$code        = get_post_meta( $voucher_id, '_dmc_voucher_code', true );

		if ( 'percent' === $type ) {
			$amount_display = rtrim( rtrim( number_format( $amount, 2, '.', '' ), '0' ), '.' ) . '%';
			$title          = sprintf( __( 'Giảm %s%%', 'dmc-voucher' ), rtrim( rtrim( number_format( $amount, 2, '.', '' ), '0' ), '.' ) );
			$label          = __( 'Giảm thêm', 'dmc-voucher' );
			$subtitle       = $max_discount > 0
				? sprintf( __( 'Tối đa %s', 'dmc-voucher' ), self::format_price_plain( $max_discount ) )
				: ( $min_spend > 0 ? sprintf( __( 'Đơn từ %s', 'dmc-voucher' ), self::format_price_plain( $min_spend ) ) : '' );
		} else {
			$amount_display = self::format_price_plain( $amount );
			$title          = $amount_display;
			$label          = __( 'Giảm ngay', 'dmc-voucher' );
			$subtitle       = $min_spend > 0 ? sprintf( __( 'Đơn từ %s', 'dmc-voucher' ), self::format_price_plain( $min_spend ) ) : '';
		}

		return [
			'id'             => $voucher_id,
			'code'           => $code,
			'title'          => $title,
			'label'          => $label,
			'amount_display' => $amount_display,
			'subtitle'       => $subtitle,
			'discount'       => $discount,
			'type'           => $type,
		];
	}

	/**
	 * @param int $voucher_id Voucher post ID.
	 */
	public static function format_voucher_amount( $voucher_id ) {
		$type   = get_post_meta( $voucher_id, '_dmc_voucher_type', true ) ?: 'fixed';
		$amount = (float) get_post_meta( $voucher_id, '_dmc_voucher_amount', true );

		if ( 'percent' === $type ) {
			return rtrim( rtrim( number_format( $amount, 2, '.', '' ), '0' ), '.' ) . '%';
		}

		return wc_price( $amount );
	}

	/**
	 * @param int   $product Product or variation.
	 * @param float $sale_price Base sale price.
	 */
	public static function get_adjusted_sale_price( $product, $sale_price ) {
		$product_id = $product instanceof WC_Product ? $product->get_id() : (int) $product;
		$code       = DMC_Voucher_Session::get_applied_code();

		if ( ! $code ) {
			return (float) $sale_price;
		}

		$voucher = self::get_voucher_by_code( $code );
		if ( ! $voucher ) {
			return (float) $sale_price;
		}

		$valid = self::validate_for_product( $voucher->ID, $product_id, (float) $sale_price );
		if ( is_wp_error( $valid ) ) {
			return (float) $sale_price;
		}

		$discount = self::calculate_discount( $voucher->ID, (float) $sale_price, 1 );

		return max( 0, (float) $sale_price - $discount );
	}
}
