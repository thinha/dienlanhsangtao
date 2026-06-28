<?php
/**
 * My Account — helpers & wishlist.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether current request is the custom account layout.
 */
function dmc_is_account_layout() {
	return class_exists( 'WooCommerce' ) && is_account_page();
}

/**
 * Wishlist product IDs for a user (YITH or custom meta).
 *
 * @param int $user_id User ID.
 * @return int[]
 */
function dmc_wishlist_get_ids( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();

	if ( ! $user_id ) {
		return [];
	}

	if ( class_exists( 'YITH_WCWL' ) && function_exists( 'yith_wcwl_get_products' ) ) {
		$products = yith_wcwl_get_products(
			[
				'user_id' => $user_id,
				'is_default' => true,
			]
		);

		if ( is_array( $products ) ) {
			return array_values(
				array_filter(
					array_map(
						static function ( $item ) {
							if ( is_object( $item ) && isset( $item->prod_id ) ) {
								return (int) $item->prod_id;
							}
							if ( is_array( $item ) && isset( $item['prod_id'] ) ) {
								return (int) $item['prod_id'];
							}
							return 0;
						},
						$products
					)
				)
			);
		}
	}

	$ids = get_user_meta( $user_id, '_dmc_wishlist', true );

	return is_array( $ids ) ? array_values( array_unique( array_map( 'intval', $ids ) ) ) : [];
}

/**
 * @param int $product_id Product ID.
 * @param int $user_id    User ID.
 */
function dmc_wishlist_has( $product_id, $user_id = 0 ) {
	$product_id = (int) $product_id;
	return in_array( $product_id, dmc_wishlist_get_ids( $user_id ), true );
}

/**
 * @param int $user_id User ID.
 */
function dmc_wishlist_count( $user_id = 0 ) {
	return count( dmc_wishlist_get_ids( $user_id ) );
}

/**
 * @param int $product_id Product ID.
 * @param int $user_id    User ID.
 * @return bool
 */
function dmc_wishlist_add( $product_id, $user_id = 0 ) {
	$product_id = (int) $product_id;
	$user_id    = $user_id ? (int) $user_id : get_current_user_id();

	if ( ! $user_id || ! $product_id || ! wc_get_product( $product_id ) ) {
		return false;
	}

	if ( class_exists( 'YITH_WCWL' ) && function_exists( 'YITH_WCWL' ) ) {
		YITH_WCWL()->add( $product_id );
		return true;
	}

	$ids   = dmc_wishlist_get_ids( $user_id );
	$ids[] = $product_id;
	update_user_meta( $user_id, '_dmc_wishlist', array_values( array_unique( $ids ) ) );

	return true;
}

/**
 * @param int $product_id Product ID.
 * @param int $user_id    User ID.
 * @return bool
 */
function dmc_wishlist_remove( $product_id, $user_id = 0 ) {
	$product_id = (int) $product_id;
	$user_id    = $user_id ? (int) $user_id : get_current_user_id();

	if ( ! $user_id || ! $product_id ) {
		return false;
	}

	if ( class_exists( 'YITH_WCWL' ) && function_exists( 'YITH_WCWL' ) ) {
		YITH_WCWL()->remove( $product_id );
		return true;
	}

	$ids = array_diff( dmc_wishlist_get_ids( $user_id ), [ $product_id ] );
	update_user_meta( $user_id, '_dmc_wishlist', array_values( $ids ) );

	return true;
}

/**
 * Toggle wishlist item for logged-in user.
 *
 * @param int $product_id Product ID.
 * @return array{added:bool,count:int}
 */
function dmc_wishlist_toggle( $product_id ) {
	$product_id = (int) $product_id;
	$added      = false;

	if ( dmc_wishlist_has( $product_id ) ) {
		dmc_wishlist_remove( $product_id );
	} else {
		dmc_wishlist_add( $product_id );
		$added = true;
	}

	return [
		'added' => $added,
		'count' => dmc_wishlist_count(),
	];
}

/**
 * Account wishlist URL.
 */
function dmc_account_wishlist_url() {
	return wc_get_account_endpoint_url( 'wishlist' );
}

/**
 * Account vouchers URL.
 */
function dmc_account_vouchers_url() {
	return wc_get_account_endpoint_url( 'vouchers' );
}

/**
 * Saved voucher count for account badge.
 */
function dmc_account_voucher_count() {
	if ( ! is_user_logged_in() || ! class_exists( 'DMC_Voucher_Account' ) ) {
		return 0;
	}

	return DMC_Voucher_Account::saved_count();
}

/**
 * Vietnamese account menu labels + wishlist tab.
 *
 * @param array<string,string> $items Menu items.
 * @return array<string,string>
 */
function dmc_account_menu_items( $items ) {
	$labels = [
		'dashboard'       => __( 'Tổng quan', 'flatsome-child' ),
		'orders'          => __( 'Đơn hàng', 'flatsome-child' ),
		'wishlist'        => __( 'Yêu thích', 'flatsome-child' ),
		'vouchers'        => __( 'Voucher', 'flatsome-child' ),
		'downloads'       => __( 'Tải xuống', 'flatsome-child' ),
		'edit-address'    => __( 'Địa chỉ', 'flatsome-child' ),
		'payment-methods' => __( 'Phương thức thanh toán', 'flatsome-child' ),
		'edit-account'    => __( 'Thông tin tài khoản', 'flatsome-child' ),
		'customer-logout' => __( 'Đăng xuất', 'flatsome-child' ),
	];

	$ordered = [
		'dashboard'    => $labels['dashboard'],
		'orders'       => $labels['orders'],
		'wishlist'     => $labels['wishlist'],
		'vouchers'     => $labels['vouchers'],
		'edit-address' => $labels['edit-address'],
		'edit-account' => $labels['edit-account'],
	];

	foreach ( $items as $key => $label ) {
		if ( isset( $labels[ $key ] ) && ! isset( $ordered[ $key ] ) ) {
			$ordered[ $key ] = $labels[ $key ];
		}
	}

	if ( isset( $items['customer-logout'] ) ) {
		$ordered['customer-logout'] = $labels['customer-logout'];
	}

	unset( $ordered['downloads'], $ordered['payment-methods'] );

	return $ordered;
}

/**
 * Current account endpoint slug.
 */
function dmc_account_current_endpoint() {
	if ( function_exists( 'WC' ) && WC()->query && method_exists( WC()->query, 'get_current_endpoint' ) ) {
		$endpoint = WC()->query->get_current_endpoint();
		return $endpoint ? $endpoint : 'dashboard';
	}

	global $wp;

	if ( function_exists( 'WC' ) && WC()->query ) {
		foreach ( WC()->query->get_query_vars() as $key => $value ) {
			if ( isset( $wp->query_vars[ $key ] ) ) {
				return $key;
			}
		}
	}

	return 'dashboard';
}

/**
 * Render account sidebar navigation.
 */
function dmc_account_render_sidebar() {
	$items    = wc_get_account_menu_items();
	$current  = dmc_account_current_endpoint();
	$user     = wp_get_current_user();
	$wishlist = dmc_wishlist_count();
	$vouchers = dmc_account_voucher_count();
	?>
	<aside class="dmc-account-sidebar">
		<div class="dmc-account-user">
			<?php echo get_avatar( $user->ID, 72 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div class="dmc-account-user__meta">
				<strong><?php echo esc_html( $user->display_name ); ?></strong>
				<span><?php echo esc_html( $user->user_email ); ?></span>
			</div>
		</div>

		<nav class="dmc-account-nav" aria-label="<?php esc_attr_e( 'Tài khoản', 'flatsome-child' ); ?>">
			<ul>
				<?php foreach ( $items as $endpoint => $label ) : ?>
					<?php
					$classes = wc_get_account_menu_item_classes( $endpoint );
					$url     = wc_get_account_endpoint_url( $endpoint );
					$active  = ( $endpoint === $current ) || ( 'dashboard' === $endpoint && 'dashboard' === $current );
					?>
					<li class="<?php echo esc_attr( $classes . ( $active ? ' is-active' : '' ) ); ?>">
						<a href="<?php echo esc_url( $url ); ?>">
							<span class="dmc-account-nav__label"><?php echo esc_html( $label ); ?></span>
							<?php if ( 'wishlist' === $endpoint && $wishlist > 0 ) : ?>
								<span class="dmc-account-nav__badge"><?php echo esc_html( $wishlist ); ?></span>
							<?php endif; ?>
							<?php if ( 'vouchers' === $endpoint && $vouchers > 0 ) : ?>
								<span class="dmc-account-nav__badge"><?php echo esc_html( $vouchers ); ?></span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>
	</aside>
	<?php
}

/**
 * Render wishlist grid on account page.
 */
function dmc_account_render_wishlist() {
	$ids = dmc_wishlist_get_ids();

	if ( empty( $ids ) ) {
		?>
		<div class="dmc-account-empty">
			<p><?php esc_html_e( 'Danh sách yêu thích của bạn đang trống.', 'flatsome-child' ); ?></p>
			<a class="dmc-btn dmc-btn--primary" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php esc_html_e( 'Khám phá sản phẩm', 'flatsome-child' ); ?>
			</a>
		</div>
		<?php
		return;
	}

	$query = new WP_Query(
		[
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post__in'       => $ids,
			'orderby'        => 'post__in',
		]
	);
	?>
	<div class="dmc-wishlist-grid">
		<?php
		while ( $query->have_posts() ) :
			$query->the_post();
			$product = wc_get_product( get_the_ID() );
			if ( ! $product ) {
				continue;
			}
			?>
			<article class="dmc-wishlist-item">
				<button
					type="button"
					class="dmc-wishlist-remove js-dmc-wishlist-toggle"
					data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
					aria-label="<?php esc_attr_e( 'Xóa khỏi yêu thích', 'flatsome-child' ); ?>"
				>×</button>
				<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="dmc-wishlist-item__image">
					<?php echo $product->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<a href="<?php echo esc_url( $product->get_permalink() ); ?>" class="dmc-wishlist-item__name">
					<?php echo esc_html( $product->get_name() ); ?>
				</a>
				<div class="dmc-wishlist-item__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
				<a class="dmc-btn dmc-btn--outline" href="<?php echo esc_url( $product->add_to_cart_url() ); ?>">
					<?php esc_html_e( 'Thêm vào giỏ', 'flatsome-child' ); ?>
				</a>
			</article>
			<?php
		endwhile;
		wp_reset_postdata();
		?>
	</div>
	<?php
}
