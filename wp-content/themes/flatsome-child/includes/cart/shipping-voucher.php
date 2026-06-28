<?php
/**
 * Cart — shipping fee & voucher sync from product detail.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const DMC_CART_SHIPPING_SESSION_KEY = 'dmc_cart_shipping_location';

/**
 * Resolve shipping fee for a location name and delivery type.
 */
function dmc_cart_resolve_fee_for_location( $location_name, $delivery_type = 'motorbike' ) {
	$locations = dmc_get_shipping_locations();
	if ( ! isset( $locations[ $location_name ] ) ) {
		return 0.0;
	}

	return (float) dmc_pl_resolve_location_fee( $locations[ $location_name ], $delivery_type );
}

/**
 * Get selected cart shipping location (session or first item from detail).
 */
function dmc_cart_get_selected_shipping_location() {
	if ( WC()->session ) {
		$session_location = (string) WC()->session->get( DMC_CART_SHIPPING_SESSION_KEY );
		if ( '' !== $session_location ) {
			return $session_location;
		}
	}

	if ( WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item['dmc_shipping_location'] ) ) {
				return (string) $cart_item['dmc_shipping_location'];
			}
		}
	}

	return '';
}

/**
 * Hidden fields inside add-to-cart form (optional — detail shipping is preview only).
 */
function dmc_cart_add_to_cart_hidden_fields() {
	global $product;

	if ( ! $product instanceof WC_Product || ! is_product() ) {
		return;
	}

	$delivery_type = dmc_pl_get_delivery_type( $product->get_id() );
	?>
	<input type="hidden" name="dmc_shipping_location" value="">
	<input type="hidden" name="dmc_shipping_fee" value="">
	<input type="hidden" name="dmc_delivery_type" value="<?php echo esc_attr( $delivery_type ); ?>">
	<?php
}
add_action( 'woocommerce_before_add_to_cart_button', 'dmc_cart_add_to_cart_hidden_fields' );

/**
 * @param array<string, mixed> $cart_item_data Cart item data.
 */
function dmc_cart_add_shipping_item_data( $cart_item_data, $product_id, $variation_id ) {
	unset( $variation_id );

	$location = isset( $_POST['dmc_shipping_location'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		? sanitize_text_field( wp_unslash( $_POST['dmc_shipping_location'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		: '';
	$fee = isset( $_POST['dmc_shipping_fee'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		? (float) wp_unslash( $_POST['dmc_shipping_fee'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		: 0;
	$delivery_type = isset( $_POST['dmc_delivery_type'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		? sanitize_key( wp_unslash( $_POST['dmc_delivery_type'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		: 'motorbike';

	if ( '' === $location || $fee <= 0 ) {
		return $cart_item_data;
	}

	$cart_item_data['dmc_shipping_location'] = $location;
	$cart_item_data['dmc_shipping_fee']      = $fee;
	$cart_item_data['dmc_delivery_type']     = 'car' === $delivery_type ? 'car' : 'motorbike';

	if ( WC()->session ) {
		WC()->session->set( DMC_CART_SHIPPING_SESSION_KEY, $location );
	}

	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'dmc_cart_add_shipping_item_data', 10, 3 );

/**
 * @param array<string, mixed> $cart_item Cart item.
 * @param array<string, mixed> $values    Session values.
 */
function dmc_cart_restore_shipping_from_session( $cart_item, $values ) {
	foreach ( [ 'dmc_shipping_location', 'dmc_shipping_fee', 'dmc_delivery_type' ] as $key ) {
		if ( isset( $values[ $key ] ) ) {
			$cart_item[ $key ] = $values[ $key ];
		}
	}

	return $cart_item;
}
add_filter( 'woocommerce_get_cart_item_from_session', 'dmc_cart_restore_shipping_from_session', 10, 2 );

/**
 * @param array<int, array{key: string, value: string}> $item_data Item data rows.
 * @param array<string, mixed>                          $cart_item Cart item.
 */
function dmc_cart_display_shipping_item_data( $item_data, $cart_item ) {
	$location = dmc_cart_get_selected_shipping_location();
	if ( '' === $location && empty( $cart_item['dmc_shipping_location'] ) ) {
		return $item_data;
	}

	if ( empty( $cart_item['dmc_shipping_location'] ) && '' === $location ) {
		return $item_data;
	}

	$display_location = ! empty( $cart_item['dmc_shipping_location'] )
		? (string) $cart_item['dmc_shipping_location']
		: $location;
	$delivery_type    = $cart_item['dmc_delivery_type'] ?? 'motorbike';
	$delivery_label   = dmc_pl_get_delivery_type_label( $delivery_type );

	$item_data[] = [
		'key'   => __( 'Giao hàng', 'flatsome-child' ),
		'value' => sprintf(
			'%s — %s',
			$delivery_label,
			$display_location
		),
	];

	return $item_data;
}
add_filter( 'woocommerce_get_item_data', 'dmc_cart_display_shipping_item_data', 10, 2 );

/**
 * Build shipping fee groups for cart totals.
 *
 * @return array<string, float>
 */
function dmc_cart_get_shipping_fee_groups() {
	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		return [];
	}

	$cart_location = dmc_cart_get_selected_shipping_location();
	$fee_groups    = [];

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$delivery_type = $cart_item['dmc_delivery_type'] ?? 'motorbike';
		$location      = $cart_location ?: (string) ( $cart_item['dmc_shipping_location'] ?? '' );

		if ( '' === $location ) {
			continue;
		}

		if ( $cart_location ) {
			$fee = dmc_cart_resolve_fee_for_location( $location, $delivery_type );
		} else {
			$fee = (float) ( $cart_item['dmc_shipping_fee'] ?? 0 );
		}

		if ( $fee <= 0 ) {
			continue;
		}

		$delivery_label = dmc_pl_get_delivery_type_label( $delivery_type );
		$label          = sprintf(
			/* translators: 1: delivery type, 2: district name */
			__( 'Phí giao hàng (%1$s) — %2$s', 'flatsome-child' ),
			$delivery_label,
			$location
		);

		if ( ! isset( $fee_groups[ $label ] ) ) {
			$fee_groups[ $label ] = 0;
		}

		$fee_groups[ $label ] += $fee;
	}

	return $fee_groups;
}

/**
 * Add shipping fees to cart totals.
 *
 * @param WC_Cart $cart Cart object.
 */
function dmc_cart_add_shipping_fees( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}

	if ( ! $cart instanceof WC_Cart ) {
		return;
	}

	foreach ( dmc_cart_get_shipping_fee_groups() as $label => $amount ) {
		if ( $amount > 0 ) {
			$cart->add_fee( $label, $amount, false );
		}
	}
}
add_action( 'woocommerce_cart_calculate_fees', 'dmc_cart_add_shipping_fees' );

/**
 * Hide WooCommerce default shipping row on cart — we use custom district fees.
 *
 * @param bool $show Whether to show shipping.
 */
function dmc_cart_hide_wc_shipping( $show ) {
	if ( dmc_is_cart_layout() || is_checkout() ) {
		return false;
	}

	return $show;
}

add_filter( 'woocommerce_cart_needs_shipping_address', function ( $needs ) {
	if ( dmc_is_cart_layout() ) {
		return false;
	}

	return $needs;
} );
add_filter( 'woocommerce_cart_ready_to_calc_shipping', 'dmc_cart_hide_wc_shipping' );
add_filter( 'woocommerce_cart_show_shipping', 'dmc_cart_hide_wc_shipping' );

/**
 * Apply or clear shipping location on all cart items.
 *
 * @param string $location Location name or empty to clear.
 */
function dmc_cart_apply_location_to_items( $location ) {
	if ( ! WC()->cart ) {
		return;
	}

	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$product_id = (int) ( $cart_item['product_id'] ?? 0 );
		if ( ! $product_id ) {
			continue;
		}

		$delivery_type = $cart_item['dmc_delivery_type'] ?? dmc_pl_get_delivery_type( $product_id );

		if ( '' === $location ) {
			unset( WC()->cart->cart_contents[ $cart_item_key ]['dmc_shipping_location'] );
			unset( WC()->cart->cart_contents[ $cart_item_key ]['dmc_shipping_fee'] );
			continue;
		}

		WC()->cart->cart_contents[ $cart_item_key ]['dmc_shipping_location'] = $location;
		WC()->cart->cart_contents[ $cart_item_key ]['dmc_shipping_fee']      = dmc_cart_resolve_fee_for_location( $location, $delivery_type );
		WC()->cart->cart_contents[ $cart_item_key ]['dmc_delivery_type']     = $delivery_type;
	}

	WC()->cart->set_session();
}

/**
 * Persist shipping location in session and cart items, then recalculate.
 *
 * @param string $location Location name or empty to clear.
 */
function dmc_cart_save_shipping_location( $location ) {
	$location = sanitize_text_field( $location );

	if ( WC()->session ) {
		WC()->session->set( DMC_CART_SHIPPING_SESSION_KEY, $location );
	}

	dmc_cart_apply_location_to_items( $location );

	if ( WC()->cart ) {
		WC()->cart->calculate_totals();
	}

	return $location;
}

/**
 * Render cart totals HTML for AJAX fragment refresh.
 */
function dmc_cart_get_totals_html() {
	ob_start();
	woocommerce_cart_totals();
	return ob_get_clean();
}

/**
 * Verify cart shipping AJAX nonce.
 */
function dmc_cart_verify_shipping_ajax() {
	if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'dmc_cart_shipping' ) ) {
		wp_send_json_error( [ 'message' => __( 'Phiên không hợp lệ.', 'flatsome-child' ) ] );
	}

	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		wp_send_json_error( [ 'message' => __( 'Giỏ hàng trống.', 'flatsome-child' ) ] );
	}
}

/**
 * AJAX — update cart shipping location.
 */
function dmc_cart_ajax_update_shipping() {
	dmc_cart_verify_shipping_ajax();

	$location = isset( $_POST['location'] )
		? sanitize_text_field( wp_unslash( $_POST['location'] ) )
		: '';

	if ( '' === $location ) {
		wp_send_json_error( [ 'message' => __( 'Vui lòng chọn khu vực.', 'flatsome-child' ) ] );
	}

	$locations = dmc_get_shipping_locations();
	if ( ! isset( $locations[ $location ] ) ) {
		wp_send_json_error( [ 'message' => __( 'Khu vực không hợp lệ.', 'flatsome-child' ) ] );
	}

	dmc_cart_save_shipping_location( $location );

	wp_send_json_success(
		[
			'location'    => $location,
			'totals_html' => dmc_cart_get_totals_html(),
		]
	);
}
add_action( 'wp_ajax_dmc_cart_update_shipping', 'dmc_cart_ajax_update_shipping' );
add_action( 'wp_ajax_nopriv_dmc_cart_update_shipping', 'dmc_cart_ajax_update_shipping' );

/**
 * AJAX — remove cart shipping.
 */
function dmc_cart_ajax_remove_shipping() {
	dmc_cart_verify_shipping_ajax();

	dmc_cart_save_shipping_location( '' );

	wp_send_json_success(
		[
			'location'    => '',
			'totals_html' => dmc_cart_get_totals_html(),
		]
	);
}
add_action( 'wp_ajax_dmc_cart_remove_shipping', 'dmc_cart_ajax_remove_shipping' );
add_action( 'wp_ajax_nopriv_dmc_cart_remove_shipping', 'dmc_cart_ajax_remove_shipping' );

/**
 * Handle cart shipping location update (fallback POST).
 */
function dmc_cart_handle_shipping_update() {
	if ( ! is_cart() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}

	if ( empty( $_POST['dmc_update_cart_shipping'] ) && empty( $_POST['dmc_remove_cart_shipping'] ) ) {
		return;
	}

	if (
		empty( $_POST['woocommerce-cart-nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-cart-nonce'] ) ), 'woocommerce-cart' )
	) {
		return;
	}

	if ( ! empty( $_POST['dmc_remove_cart_shipping'] ) ) {
		dmc_cart_save_shipping_location( '' );
		wc_add_notice( __( 'Đã gỡ phí giao hàng.', 'flatsome-child' ), 'success' );
	} else {
		$location = isset( $_POST['dmc_cart_shipping_location'] )
			? sanitize_text_field( wp_unslash( $_POST['dmc_cart_shipping_location'] ) )
			: '';
		dmc_cart_save_shipping_location( $location );
		if ( '' !== $location ) {
			wc_add_notice( __( 'Đã cập nhật khu vực giao hàng.', 'flatsome-child' ), 'success' );
		}
	}

	wp_safe_redirect( wc_get_cart_url() );
	exit;
}
add_action( 'template_redirect', 'dmc_cart_handle_shipping_update', 5 );

/**
 * Render shipping selector on cart page.
 */
function dmc_cart_render_shipping_box() {
	if ( ! dmc_is_cart_layout() || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	$locations         = dmc_get_shipping_locations();
	$selected_location = dmc_cart_get_selected_shipping_location();

	if ( empty( $locations ) ) {
		return;
	}
	?>
	<div class="dmc-cart-shipping-box" id="dmc-cart-shipping-box">
		<div class="dmc-cart-shipping-box__head">
			<span class="dmc-cart-shipping-box__title"><?php esc_html_e( 'Giao hàng', 'flatsome-child' ); ?></span>
		</div>

		<label class="dmc-cart-shipping-box__label" for="dmc-cart-shipping-location">
			<?php esc_html_e( 'Chọn khu vực giao hàng', 'flatsome-child' ); ?>
		</label>

		<select
			class="dmc-cart-shipping-box__select"
			id="dmc-cart-shipping-location"
			name="dmc_cart_shipping_location"
		>
			<option value=""><?php esc_html_e( '— Chọn khu vực —', 'flatsome-child' ); ?></option>
			<?php foreach ( $locations as $location => $value ) : ?>
				<?php
				$fee_motorbike = dmc_pl_resolve_location_fee( $value, 'motorbike' );
				$fee_car       = dmc_pl_resolve_location_fee( $value, 'car' );
				?>
				<option
					value="<?php echo esc_attr( $location ); ?>"
					data-fee-motorbike="<?php echo esc_attr( (string) $fee_motorbike ); ?>"
					data-fee-car="<?php echo esc_attr( (string) $fee_car ); ?>"
					<?php selected( $selected_location, $location ); ?>
				>
					<?php
					echo esc_html( $location );
					echo ' — ';
					echo wp_strip_all_tags( wc_price( $fee_motorbike ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</option>
			<?php endforeach; ?>
		</select>

		<?php if ( '' !== $selected_location ) : ?>
			<div class="dmc-cart-shipping-box__active" id="dmc-cart-shipping-active">
				<span><?php echo esc_html( sprintf( __( 'Đang giao đến: %s', 'flatsome-child' ), $selected_location ) ); ?></span>
			</div>
		<?php endif; ?>

		<p class="dmc-cart-shipping-box__hint">
			<?php esc_html_e( 'Phí giao hàng được tính theo từng sản phẩm trong giỏ.', 'flatsome-child' ); ?>
		</p>
	</div>
	<?php
}
add_action( 'dmc_cart_shipping_box', 'dmc_cart_render_shipping_box' );

/**
 * Apply session voucher when cart page loads.
 */
function dmc_cart_apply_session_voucher() {
	if ( ! is_cart() || ! class_exists( 'DMC_Voucher_Cart' ) ) {
		return;
	}

	DMC_Voucher_Cart::apply_session_coupon();
}
add_action( 'wp', 'dmc_cart_apply_session_voucher', 20 );

/**
 * Re-sync WC coupon types when cart loads (fixed_cart vs fixed_product).
 */
function dmc_cart_resync_voucher_coupons() {
	if ( ! is_cart() || ! class_exists( 'DMC_Voucher_Coupon_Sync' ) ) {
		return;
	}

	$code = class_exists( 'DMC_Voucher_Session' ) ? DMC_Voucher_Session::get_applied_code() : '';
	if ( ! $code ) {
		return;
	}

	$voucher = DMC_Voucher_Engine::get_voucher_by_code( $code );
	if ( $voucher ) {
		DMC_Voucher_Coupon_Sync::ensure_coupon( $voucher->ID );
	}
}
add_action( 'wp', 'dmc_cart_resync_voucher_coupons', 15 );
