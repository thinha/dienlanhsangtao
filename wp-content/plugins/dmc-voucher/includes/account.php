<?php
/**
 * My Account — voucher wallet endpoint.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_Account {

	public static function init() {
		add_action( 'wp_login', [ __CLASS__, 'merge_guest_wallet' ], 10, 2 );
		add_action( 'wp_ajax_dmc_voucher_wallet_remove', [ __CLASS__, 'ajax_remove' ] );
	}

	/**
	 * @return int
	 */
	public static function saved_count() {
		return count( DMC_Voucher_User_Wallet::get_saved_cards() );
	}

	/**
	 * @param string $user_login Username.
	 * @param WP_User $user User object.
	 */
	public static function merge_guest_wallet( $user_login, $user ) {
		unset( $user_login );

		if ( ! isset( $_COOKIE[ DMC_Voucher_User_Wallet::COOKIE_KEY ] ) ) {
			return;
		}

		$raw = sanitize_text_field( wp_unslash( $_COOKIE[ DMC_Voucher_User_Wallet::COOKIE_KEY ] ) );
		$ids = json_decode( $raw, true );
		if ( ! is_array( $ids ) ) {
			return;
		}

		$existing = get_user_meta( $user->ID, DMC_Voucher_User_Wallet::META_KEY, true );
		$merged   = array_values(
			array_unique(
				array_merge(
					array_map( 'intval', (array) $existing ),
					array_map( 'intval', $ids )
				)
			)
		);

		update_user_meta( $user->ID, DMC_Voucher_User_Wallet::META_KEY, $merged );
		setcookie( DMC_Voucher_User_Wallet::COOKIE_KEY, '', time() - HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
	}

	public static function ajax_remove() {
		check_ajax_referer( 'dmc_voucher', 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [ 'message' => __( 'Vui lòng đăng nhập.', 'dmc-voucher' ) ], 401 );
		}

		$voucher_id = (int) ( $_POST['voucher_id'] ?? 0 );
		if ( ! $voucher_id || ! DMC_Voucher_User_Wallet::remove( $voucher_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Không thể xóa voucher.', 'dmc-voucher' ) ] );
		}

		wp_send_json_success(
			[
				'message' => __( 'Đã xóa voucher khỏi ví.', 'dmc-voucher' ),
				'count'   => self::saved_count(),
			]
		);
	}

	/**
	 * Render account vouchers page.
	 */
	public static function render_page() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_style( 'dmc-voucher' );
		wp_enqueue_script( 'dmc-voucher' );

		$saved      = DMC_Voucher_User_Wallet::get_saved_cards();
		$available  = [];
		$saved_ids  = DMC_Voucher_User_Wallet::get_saved_ids();

		foreach ( DMC_Voucher_Engine::get_active_voucher_ids() as $voucher_id ) {
			if ( in_array( $voucher_id, $saved_ids, true ) ) {
				continue;
			}
			$available[] = DMC_Voucher_Engine::format_voucher_card( $voucher_id );
		}

		?>
		<div class="dmc-voucher-wallet" id="dmc-voucher-wallet">
			<?php if ( empty( $saved ) && empty( $available ) ) : ?>
				<div class="dmc-account-empty">
					<p><?php esc_html_e( 'Bạn chưa có voucher nào.', 'dmc-voucher' ); ?></p>
					<a class="dmc-btn dmc-btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
						<?php esc_html_e( 'Mua sắm ngay', 'dmc-voucher' ); ?>
					</a>
				</div>
			<?php else : ?>
				<?php if ( ! empty( $saved ) ) : ?>
					<section class="dmc-voucher-wallet__section">
						<h2 class="dmc-voucher-wallet__heading"><?php esc_html_e( 'Voucher đã lưu', 'dmc-voucher' ); ?></h2>
						<div class="dmc-voucher-wallet__grid">
							<?php foreach ( $saved as $card ) : ?>
								<?php self::render_card( $card, true ); ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<?php if ( ! empty( $available ) ) : ?>
					<section class="dmc-voucher-wallet__section">
						<h2 class="dmc-voucher-wallet__heading"><?php esc_html_e( 'Voucher có thể nhận', 'dmc-voucher' ); ?></h2>
						<div class="dmc-voucher-wallet__grid">
							<?php foreach ( $available as $card ) : ?>
								<?php self::render_card( $card, false ); ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * @param array<string, mixed> $card Voucher card data.
	 * @param bool                 $is_saved Saved state.
	 */
	private static function render_card( $card, $is_saved ) {
		$voucher_id = (int) $card['id'];
		$date_end   = get_post_meta( $voucher_id, '_dmc_voucher_date_end', true );
		?>
		<div class="dmc-voucher-wallet__card" data-voucher-id="<?php echo esc_attr( (string) $voucher_id ); ?>">
			<?php
			DMC_Voucher_Card::render(
				$card,
				[
					'variant'     => 'wallet',
					'tag'         => 'div',
					'is_saved'    => $is_saved,
					'show_action' => false,
					'date_end'    => $date_end,
				]
			);
			?>
			<div class="dmc-voucher-wallet__card-actions">
				<button type="button" class="dmc-btn dmc-btn--outline dmc-voucher-wallet__copy" data-code="<?php echo esc_attr( $card['code'] ); ?>">
					<?php esc_html_e( 'Sao chép mã', 'dmc-voucher' ); ?>
				</button>
				<?php if ( $is_saved ) : ?>
					<button type="button" class="dmc-btn dmc-btn--outline dmc-voucher-wallet__remove" data-voucher-id="<?php echo esc_attr( (string) $voucher_id ); ?>">
						<?php esc_html_e( 'Xóa', 'dmc-voucher' ); ?>
					</button>
				<?php else : ?>
					<button type="button" class="dmc-btn dmc-btn--primary dmc-voucher-save-btn" data-voucher-id="<?php echo esc_attr( (string) $voucher_id ); ?>">
						<?php esc_html_e( 'Lưu ngay', 'dmc-voucher' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

/**
 * Render voucher wallet on My Account.
 */
function dmc_voucher_render_account_page() {
	if ( class_exists( 'DMC_Voucher_Account' ) ) {
		DMC_Voucher_Account::render_page();
	}
}
