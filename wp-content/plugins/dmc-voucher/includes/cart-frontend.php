<?php
/**
 * Cart page voucher UI.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_Cart_Frontend {

	/**
	 * AJAX actions that need WooCommerce coupons enabled.
	 *
	 * @var array<int, string>
	 */
	private static $cart_coupon_ajax_actions = [
		'dmc_voucher_apply_cart',
		'dmc_voucher_remove_cart',
		'dmc_voucher_apply_cart_best',
	];

	public static function init() {
		add_action( 'dmc_cart_voucher_box', [ __CLASS__, 'render_cart_vouchers' ] );
		add_filter( 'woocommerce_coupons_enabled', [ __CLASS__, 'ensure_coupons_enabled' ] );
		add_filter( 'woocommerce_cart_totals_coupon_label', [ __CLASS__, 'coupon_label' ], 10, 2 );
		add_filter( 'woocommerce_cart_totals_coupon_html', [ __CLASS__, 'coupon_html' ], 10, 3 );
		add_action( 'template_redirect', [ __CLASS__, 'handle_cart_actions' ], 5 );
	}

	/**
	 * Whether coupons should be enabled for the current cart voucher request.
	 */
	public static function is_voucher_cart_request() {
		if ( is_cart() || is_checkout() ) {
			return true;
		}

		if ( ! wp_doing_ajax() || empty( $_POST['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return false;
		}

		$action = sanitize_text_field( wp_unslash( $_POST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		return in_array( $action, self::$cart_coupon_ajax_actions, true );
	}

	/**
	 * @param bool $enabled Whether coupons are enabled.
	 */
	public static function ensure_coupons_enabled( $enabled ) {
		if ( $enabled || self::is_voucher_cart_request() ) {
			return true;
		}

		return $enabled;
	}

	/**
	 * Handle apply-best via native cart POST.
	 */
	public static function handle_cart_actions() {
		if ( ! is_cart() || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		if ( empty( $_POST['dmc_apply_best_voucher'] ) ) {
			return;
		}

		if (
			empty( $_POST['woocommerce-cart-nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['woocommerce-cart-nonce'] ) ), 'woocommerce-cart' )
		) {
			return;
		}

		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$candidates = array_merge(
			DMC_Voucher_User_Wallet::get_saved_ids(),
			DMC_Voucher_Engine::get_active_voucher_ids()
		);
		$best = DMC_Voucher_Engine::get_best_voucher_for_cart( array_values( array_unique( $candidates ) ) );

		if ( $best ) {
			WC()->cart->apply_coupon( $best['code'] );
			DMC_Voucher_Session::set_applied_code( $best['code'] );
			wc_add_notice( __( 'Đã áp dụng voucher tốt nhất!', 'dmc-voucher' ), 'success' );
		} else {
			wc_add_notice( __( 'Không có voucher phù hợp cho giỏ hàng này.', 'dmc-voucher' ), 'error' );
		}

		wp_safe_redirect( wc_get_cart_url() );
		exit;
	}

	/**
	 * @param string    $label Coupon label.
	 * @param WC_Coupon $coupon Coupon object.
	 */
	public static function coupon_label( $label, $coupon ) {
		if ( DMC_Voucher_Engine::get_voucher_by_code( $coupon->get_code() ) ) {
			return sprintf(
				/* translators: %s: voucher code */
				__( 'Voucher (%s)', 'dmc-voucher' ),
				$coupon->get_code()
			);
		}

		return $label;
	}

	/**
	 * @param string    $html Coupon HTML.
	 * @param WC_Coupon $coupon Coupon object.
	 * @param string    $discount_amount_html Discount HTML.
	 */
	public static function coupon_html( $html, $coupon, $discount_amount_html ) {
		unset( $html );

		if ( ! DMC_Voucher_Engine::get_voucher_by_code( $coupon->get_code() ) ) {
			return $discount_amount_html;
		}

		return '<span class="dmc-cart-voucher-discount">' . wp_kses_post( $discount_amount_html ) . '</span>';
	}

	/**
	 * Render voucher picker on cart page.
	 */
	public static function render_cart_vouchers() {
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		DMC_Voucher_Cart::apply_session_coupon();

		$saved           = DMC_Voucher_User_Wallet::get_saved_cards();
		$applicable      = DMC_Voucher_Engine::get_applicable_vouchers_for_cart( 8 );
		$applied         = '';
		$applied_codes   = WC()->cart->get_applied_coupons();
		$applied_coupon  = null;

		foreach ( $applied_codes as $code ) {
			if ( DMC_Voucher_Engine::get_voucher_by_code( $code ) ) {
				$applied        = $code;
				$applied_coupon = new WC_Coupon( $code );
				break;
			}
		}

		if ( ! $applied ) {
			$applied = DMC_Voucher_Session::get_applied_code();
		}

		$merged = [];
		foreach ( array_merge( $saved, $applicable ) as $card ) {
			$merged[ $card['id'] ] = $card;
		}

		$cart_url = wc_get_cart_url();
		?>
		<div class="dmc-voucher-box dmc-voucher-box--cart" id="dmc-voucher-cart-box">
			<form class="dmc-voucher-cart-form" method="post" action="<?php echo esc_url( $cart_url ); ?>">
				<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>

				<div class="dmc-voucher-box__head">
					<span class="dmc-voucher-box__title"><?php esc_html_e( 'Voucher ưu đãi', 'dmc-voucher' ); ?></span>
					<?php if ( ! empty( $merged ) ) : ?>
						<button type="button" class="dmc-voucher-box__best" id="dmc-voucher-cart-best">
							<?php esc_html_e( 'Áp dụng tốt nhất', 'dmc-voucher' ); ?>
						</button>
					<?php endif; ?>
				</div>

				<div class="dmc-voucher-box__form">
					<input
						type="text"
						name="coupon_code"
						class="dmc-voucher-box__input"
						id="dmc-voucher-cart-code-input"
						placeholder="<?php esc_attr_e( 'Nhập mã voucher', 'dmc-voucher' ); ?>"
						value="<?php echo esc_attr( $applied ); ?>"
					>
					<button
						type="button"
						class="<?php echo $applied ? 'dmc-voucher-box__remove' : 'dmc-voucher-box__apply'; ?>"
						id="dmc-voucher-cart-action"
						data-applied="<?php echo $applied ? '1' : '0'; ?>"
					>
						<?php echo esc_html( $applied ? __( 'Gỡ bỏ', 'dmc-voucher' ) : __( 'Áp dụng', 'dmc-voucher' ) ); ?>
					</button>
				</div>

				<?php if ( $applied && $applied_coupon ) : ?>
					<div class="dmc-voucher-box__active" id="dmc-voucher-cart-active">
						<span>
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: voucher code, 2: discount amount */
									__( 'Đang dùng: %1$s (−%2$s)', 'dmc-voucher' ),
									$applied,
									DMC_Voucher_Engine::format_price_plain( WC()->cart->get_coupon_discount_amount( $applied, WC()->cart->display_cart_ex_tax ) )
								)
							);
							?>
						</span>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $merged ) ) : ?>
					<ul class="dmc-voucher-box__list dmc-voucher-box__list--stack">
						<?php foreach ( $merged as $card ) : ?>
							<?php
							$is_saved  = in_array( (int) $card['id'], DMC_Voucher_User_Wallet::get_saved_ids(), true );
							$is_active = $applied && strcasecmp( (string) $applied, (string) $card['code'] ) === 0;
							?>
							<li class="dmc-voucher-list-item<?php echo $is_active ? ' is-active' : ''; ?>">
								<?php
								DMC_Voucher_Card::render(
									$card,
									[
										'variant'   => 'cart',
										'tag'       => 'div',
										'is_active' => $is_active,
										'is_saved'  => $is_saved,
										'action'    => 'apply',
									]
								);
								?>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

			</form>
		</div>
		<?php
	}
}

/**
 * Render voucher box on cart page.
 */
function dmc_voucher_render_cart_box() {
	if ( class_exists( 'DMC_Voucher_Cart_Frontend' ) ) {
		DMC_Voucher_Cart_Frontend::render_cart_vouchers();
	}
}
