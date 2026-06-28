<?php
/**
 * Frontend rendering & assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DMC_Voucher_Frontend {

	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ], 5 );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue' ], 100 );
		add_action( 'woocommerce_single_product_summary', [ __CLASS__, 'render_product_vouchers' ], 25 );
		add_action( 'dmc_product_detail_after_price', [ __CLASS__, 'render_product_vouchers' ] );
		add_filter( 'dmc_voucher_homepage_vouchers', [ __CLASS__, 'get_homepage_vouchers' ] );
	}

	/**
	 * @param WC_Product|null $product Product object.
	 */
	public static function get_product_base_price( $product = null ) {
		if ( ! $product instanceof WC_Product ) {
			global $product;
		}

		if ( ! $product instanceof WC_Product ) {
			return 0.0;
		}

		$sale_price = (float) $product->get_sale_price();
		if ( $sale_price > 0 ) {
			return $sale_price;
		}

		$regular_price = (float) $product->get_regular_price();
		if ( $regular_price > 0 ) {
			return $regular_price;
		}

		return (float) $product->get_price();
	}

	public static function register_assets() {
		wp_register_style(
			'dmc-voucher',
			DMC_VOUCHER_URL . 'assets/css/voucher.css',
			[],
			DMC_VOUCHER_VERSION
		);

		$deps = [ 'jquery' ];
		if ( wp_script_is( 'dmc-product-list', 'registered' ) ) {
			$deps[] = 'dmc-product-list';
		}

		wp_register_script(
			'dmc-voucher',
			DMC_VOUCHER_URL . 'assets/js/voucher.js',
			$deps,
			DMC_VOUCHER_VERSION,
			true
		);
	}

	public static function enqueue() {
		if ( ! is_product() && ! self::is_homepage_context() && ! self::is_account_voucher_context() && ! self::is_cart_context() ) {
			return;
		}

		if ( is_product() && wp_script_is( 'dmc-product-list', 'registered' ) ) {
			wp_scripts()->registered['dmc-voucher']->deps = array_unique(
				array_merge( (array) wp_scripts()->registered['dmc-voucher']->deps, [ 'dmc-product-list' ] )
			);
		}

		wp_enqueue_style( 'dmc-voucher' );
		wp_enqueue_script( 'dmc-voucher' );

		$product_id = is_product() ? get_the_ID() : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;
		$base_price = 0;

		if ( $product ) {
			$base_price = self::get_product_base_price( $product );
		}

		wp_localize_script(
			'dmc-voucher',
			'dmcVoucher',
			[
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'dmc_voucher' ),
				'productId'    => $product_id,
				'basePrice'    => $base_price,
				'appliedCode'  => DMC_Voucher_Session::get_applied_code(),
				'savedVouchers'=> DMC_Voucher_User_Wallet::get_saved_cards(),
				'i18n'         => [
					'apply'       => __( 'Áp dụng', 'dmc-voucher' ),
					'remove'      => __( 'Gỡ bỏ', 'dmc-voucher' ),
					'activePrefix'=> __( 'Đang dùng: ', 'dmc-voucher' ),
					'saved'       => __( 'Đã lưu', 'dmc-voucher' ),
					'save'        => __( 'Lưu ngay', 'dmc-voucher' ),
					'best'        => __( 'Áp dụng voucher tốt nhất', 'dmc-voucher' ),
					'placeholder' => __( 'Nhập mã voucher', 'dmc-voucher' ),
					'voucherRow'  => __( 'Giảm voucher', 'dmc-voucher' ),
				],
			]
		);
	}

	private static function is_homepage_context() {
		return is_front_page() || is_page_template( 'page-templates/homepage.php' );
	}

	private static function is_account_voucher_context() {
		return function_exists( 'dmc_is_account_layout' )
			&& dmc_is_account_layout()
			&& function_exists( 'dmc_account_current_endpoint' )
			&& 'vouchers' === dmc_account_current_endpoint();
	}

	private static function is_cart_context() {
		return function_exists( 'dmc_is_cart_layout' ) && dmc_is_cart_layout();
	}

	/**
	 * Product detail voucher box.
	 */
	public static function render_product_vouchers() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = $product->get_id();
		$base_price = self::get_product_base_price( $product );
		if ( $base_price <= 0 ) {
			return;
		}

		$saved       = DMC_Voucher_User_Wallet::get_saved_cards();
		$applicable  = DMC_Voucher_Engine::get_applicable_vouchers_for_product( $product_id, $base_price, 6 );
		$applied     = DMC_Voucher_Session::get_applied_code();
		$merged      = [];

		foreach ( array_merge( $saved, $applicable ) as $card ) {
			$merged[ $card['id'] ] = $card;
		}

		wp_enqueue_style( 'dmc-voucher' );
		wp_enqueue_script( 'dmc-voucher' );
		?>
		<div class="dmc-voucher-box" id="dmc-voucher-box" data-product-id="<?php echo esc_attr( (string) $product_id ); ?>" data-base-price="<?php echo esc_attr( (string) $base_price ); ?>">
			<div class="dmc-voucher-box__head">
				<span class="dmc-voucher-box__title"><?php esc_html_e( 'Voucher ưu đãi', 'dmc-voucher' ); ?></span>
				<?php if ( ! empty( $merged ) ) : ?>
					<button type="button" class="dmc-voucher-box__best" id="dmc-voucher-apply-best">
						<?php esc_html_e( 'Áp dụng tốt nhất', 'dmc-voucher' ); ?>
					</button>
				<?php endif; ?>
			</div>

			<div class="dmc-voucher-box__form">
				<input type="text" class="dmc-voucher-box__input" id="dmc-voucher-code-input" placeholder="<?php esc_attr_e( 'Nhập mã voucher', 'dmc-voucher' ); ?>" value="<?php echo esc_attr( $applied ); ?>">
				<button type="button" class="dmc-voucher-box__apply" id="dmc-voucher-apply-code"><?php esc_html_e( 'Áp dụng', 'dmc-voucher' ); ?></button>
			</div>

			<?php if ( $applied ) : ?>
				<div class="dmc-voucher-box__active" id="dmc-voucher-active">
					<span><?php echo esc_html( sprintf( __( 'Đang dùng: %s', 'dmc-voucher' ), $applied ) ); ?></span>
					<button type="button" class="dmc-voucher-box__remove" id="dmc-voucher-remove"><?php esc_html_e( 'Bỏ', 'dmc-voucher' ); ?></button>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $merged ) ) : ?>
				<ul class="dmc-voucher-box__list dmc-voucher-box__list--stack">
					<?php foreach ( $merged as $card ) : ?>
						<?php
						$is_saved  = in_array( (int) $card['id'], DMC_Voucher_User_Wallet::get_saved_ids(), true );
						$is_active = $applied === $card['code'];
						?>
						<li class="dmc-voucher-list-item<?php echo $is_active ? ' is-active' : ''; ?>">
							<?php
							DMC_Voucher_Card::render(
								$card,
								[
									'variant'   => 'product',
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

			<p class="dmc-voucher-box__note" id="dmc-voucher-message" hidden></p>
		</div>
		<?php
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_homepage_vouchers() {
		$posts = get_posts(
			[
				'post_type'      => 'voucher',
				'post_status'    => 'publish',
				'posts_per_page' => 8,
				'meta_query'     => [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'   => '_dmc_voucher_show_homepage',
						'value' => '1',
					],
				],
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		$cards = [];
		foreach ( $posts as $post ) {
			if ( ! DMC_Voucher_Engine::is_voucher_active( $post->ID ) ) {
				continue;
			}
			$cards[] = DMC_Voucher_Engine::format_voucher_card( $post->ID );
		}

		return $cards;
	}

	/**
	 * Render homepage vouchers section (called from theme template).
	 *
	 * @param array<string, mixed> $args Template args.
	 */
	public static function render_homepage_section( $args = [] ) {
		$vouchers = apply_filters( 'dmc_voucher_homepage_vouchers', self::get_homepage_vouchers() );
		$more_url = $args['more_url'] ?? '#';

		if ( function_exists( 'dmc_account_vouchers_url' ) && is_user_logged_in() ) {
			$more_url = dmc_account_vouchers_url();
		}

		wp_enqueue_style( 'dmc-voucher' );
		wp_enqueue_script( 'dmc-voucher' );

		if ( wp_script_is( 'swiper', 'registered' ) ) {
			wp_enqueue_style( 'swiper' );
			wp_enqueue_script( 'swiper' );
		}
		?>
		<section class="card dmc-voucher-section">
			<div class="section-head">
				<h2><?php esc_html_e( 'VOUCHER DÀNH CHO BẠN', 'dmc-voucher' ); ?></h2>
				<a href="<?php echo esc_url( $more_url ); ?>" class="more"><?php esc_html_e( 'Xem tất cả', 'dmc-voucher' ); ?> &rsaquo;</a>
			</div>
			<div class="vouchers">
				<?php if ( empty( $vouchers ) ) : ?>
					<p class="dmc-voucher-empty"><?php esc_html_e( 'Chưa có voucher nào.', 'dmc-voucher' ); ?></p>
				<?php else : ?>
					<div class="swiper dmc-voucher-swiper" id="dmc-homepage-vouchers">
						<div class="swiper-wrapper">
							<?php foreach ( $vouchers as $card ) : ?>
								<div class="swiper-slide">
									<?php
									$is_saved = in_array( (int) $card['id'], DMC_Voucher_User_Wallet::get_saved_ids(), true );
									DMC_Voucher_Card::render(
										$card,
										[
											'variant'   => 'homepage',
											'is_saved'  => $is_saved,
											'action'    => 'save',
											'show_code' => false,
										]
									);
									?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}

/**
 * Public API for theme templates.
 *
 * @param array<string, mixed> $args Args.
 */
function dmc_voucher_render_homepage_section( $args = [] ) {
	if ( class_exists( 'DMC_Voucher_Frontend' ) ) {
		DMC_Voucher_Frontend::render_homepage_section( $args );
	}
}

/**
 * Render voucher picker on product detail.
 *
 * @param WC_Product|null $product Product object.
 */
function dmc_voucher_render_product_box( $product = null ) {
	if ( class_exists( 'DMC_Voucher_Frontend' ) ) {
		DMC_Voucher_Frontend::render_product_vouchers();
	}
}
