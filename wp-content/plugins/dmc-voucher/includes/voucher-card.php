<?php
/**
 * Shared voucher ticket card markup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_Card {

	/**
	 * Inline ticket icon SVG.
	 */
	public static function icon_svg() {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 9a3 3 0 1 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 1 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M9 9h.01"/><path d="m15 9-6 6"/><path d="M15 15h.01"/></svg>';
	}

	/**
	 * @param array<string, mixed> $card   Voucher card data.
	 * @param array<string, mixed> $args   Render args.
	 */
	public static function render( $card, $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'variant'     => 'homepage',
				'tag'         => 'article',
				'is_active'   => false,
				'is_saved'    => false,
				'show_code'   => true,
				'show_action' => true,
				'action'      => 'save',
				'date_end'    => '',
				'extra_class' => '',
			]
		);

		$voucher_id = (int) ( $card['id'] ?? 0 );
		$code       = (string) ( $card['code'] ?? '' );
		$amount     = (string) ( $card['amount_display'] ?? $card['title'] ?? '' );
		$label      = (string) ( $card['label'] ?? '' );
		$subtitle   = (string) ( $card['subtitle'] ?? '' );
		$is_homepage = 'homepage' === $args['variant'];

		$classes = [
			'dmc-voucher-ticket',
			'dmc-voucher-ticket--' . sanitize_html_class( (string) $args['variant'] ),
		];

		if ( $args['is_active'] ) {
			$classes[] = 'is-active';
		}

		if ( $args['is_saved'] ) {
			$classes[] = 'is-saved';
		}

		if ( $args['extra_class'] ) {
			$classes[] = $args['extra_class'];
		}

		$allowed_tags = [ 'article', 'div', 'li' ];
		$tag          = in_array( (string) $args['tag'], $allowed_tags, true ) ? (string) $args['tag'] : 'article';
		?>
		<<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-voucher-id="<?php echo esc_attr( (string) $voucher_id ); ?>"
			data-voucher-code="<?php echo esc_attr( $code ); ?>"
		>
			<div class="dmc-voucher-ticket__stub">
				<span class="dmc-voucher-ticket__icon"><?php echo self::icon_svg(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			</div>

			<div class="dmc-voucher-ticket__body">
				<?php if ( $is_homepage && $code ) : ?>
					<span class="dmc-voucher-ticket__label"><?php echo esc_html( $code ); ?></span>
				<?php endif; ?>
				<strong class="dmc-voucher-ticket__amount"><?php echo esc_html( $amount ); ?></strong>

				<?php if ( $subtitle ) : ?>
					<small class="dmc-voucher-ticket__condition"><?php echo esc_html( $subtitle ); ?></small>
				<?php endif; ?>

				<?php if ( $args['date_end'] ) : ?>
					<span class="dmc-voucher-ticket__expiry">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: expiry date */
								__( 'HSD: %s', 'dmc-voucher' ),
								wp_date( 'd/m/Y', strtotime( (string) $args['date_end'] ) )
							)
						);
						?>
					</span>
				<?php endif; ?>

				<?php if ( $args['show_code'] || $args['show_action'] ) : ?>
					<div class="dmc-voucher-ticket__footer">
						<?php if ( $args['show_code'] && $code ) : ?>
							<span class="dmc-voucher-ticket__code">
								<?php esc_html_e( 'Mã:', 'dmc-voucher' ); ?>
								<strong><?php echo esc_html( $code ); ?></strong>
							</span>
						<?php endif; ?>

						<?php if ( $args['show_action'] ) : ?>
							<?php self::render_action_button( $card, $args ); ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</<?php echo $tag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
	}

	/**
	 * @param array<string, mixed> $card Voucher card data.
	 * @param array<string, mixed> $args Render args.
	 */
	private static function render_action_button( $card, $args ) {
		$voucher_id = (int) ( $card['id'] ?? 0 );
		$code       = (string) ( $card['code'] ?? '' );

		if ( 'apply' === $args['action'] ) {
			$is_cart   = 'cart' === $args['variant'];
			$label     = ( $is_cart && $args['is_active'] ) ? __( 'Gỡ bỏ', 'dmc-voucher' ) : ( $args['is_active'] ? __( 'Đang dùng', 'dmc-voucher' ) : __( 'Áp dụng', 'dmc-voucher' ) );
			$class     = 'dmc-voucher-ticket__btn dmc-voucher-ticket__btn--apply js-dmc-voucher-product-pick';
			if ( $is_cart ) {
				$class = 'dmc-voucher-ticket__btn dmc-voucher-ticket__btn--apply js-dmc-voucher-cart-pick';
				if ( $args['is_active'] ) {
					$class .= ' dmc-voucher-ticket__btn--remove';
				}
			}
			?>
			<button
				type="button"
				class="<?php echo esc_attr( $class ); ?>"
				data-voucher-id="<?php echo esc_attr( (string) $voucher_id ); ?>"
				data-voucher-code="<?php echo esc_attr( $code ); ?>"
				data-voucher-saved="<?php echo $args['is_saved'] ? '1' : '0'; ?>"
				<?php echo ( ! $is_cart && $args['is_active'] ) ? ' disabled' : ''; ?>
			>
				<?php echo esc_html( $label ); ?>
			</button>
			<?php
			return;
		}

		$is_homepage = 'homepage' === $args['variant'];
		$label       = $args['is_saved']
			? __( 'Đã lưu', 'dmc-voucher' )
			: ( $is_homepage ? __( 'Lấy ngay', 'dmc-voucher' ) : __( 'Lưu ngay', 'dmc-voucher' ) );
		?>
		<button
			type="button"
			class="dmc-voucher-ticket__btn dmc-voucher-ticket__btn--save dmc-voucher-save-btn"
			data-voucher-id="<?php echo esc_attr( (string) $voucher_id ); ?>"
			<?php echo $args['is_saved'] ? ' disabled' : ''; ?>
		>
			<?php echo esc_html( $label ); ?>
		</button>
		<?php
	}
}
