<?php
/**
 * Site header & navigation (shared).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name   = get_bloginfo( 'name' );
$home_url    = home_url( '/' );
$account_url = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url();
$wishlist_url = ( class_exists( 'WooCommerce' ) && is_user_logged_in() && function_exists( 'dmc_account_wishlist_url' ) )
	? dmc_account_wishlist_url()
	: $account_url;
$cart_url    = class_exists( 'WooCommerce' ) ? wc_get_cart_url() : '#';
$cart_count  = 0;
$wishlist_count = 0;
if ( class_exists( 'WooCommerce' ) && WC()->cart ) {
	$cart_count = WC()->cart->get_cart_contents_count();
}
if ( is_user_logged_in() && function_exists( 'dmc_wishlist_count' ) ) {
	$wishlist_count = dmc_wishlist_count();
}
$current_user = wp_get_current_user();
$categories  = dmc_homepage_get_categories( 10 );
$hotline     = function_exists( 'dmc_tmp_get_company_hotline_display' )
	? dmc_tmp_get_company_hotline_display()
	: dmc_web_option( 'web_hotline', '1900 2323 88' );
$hotline_hrs = function_exists( 'dmc_tmp_get_company_hotline_hours' )
	? dmc_tmp_get_company_hotline_hours()
	: dmc_web_option( 'web_hotline_hours', '8:00 - 21:00' );
$hotline_tel = function_exists( 'dmc_tmp_get_company_hotline' )
	? dmc_tmp_get_company_hotline()
	: preg_replace( '/\s+/', '', (string) dmc_web_option( 'web_hotline', '19002628' ) );
?>
<header class="site-header">
	<div class="topline desktop-only">
		<div class="container inner">
			<div class="left">
				<span class="topline__item"><?php echo dmc_icon( 'phone', [ 'size' => 14, 'variant' => 'topline' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo esc_html( sprintf( __( 'Hotline: %1$s (%2$s)', 'flatsome-child' ), $hotline, $hotline_hrs ) ); ?></span>
				<span class="topline__item"><?php echo dmc_icon( 'headset', [ 'size' => 14, 'variant' => 'topline' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Hỗ trợ khách hàng', 'flatsome-child' ); ?></span>
			</div>
			<div class="right">
				<span class="topline__item"><?php echo dmc_icon( 'orders', [ 'size' => 14, 'variant' => 'topline' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Theo dõi đơn hàng', 'flatsome-child' ); ?></span>
				<span class="topline__item"><?php echo dmc_icon( 'store', [ 'size' => 14, 'variant' => 'topline' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Tìm cửa hàng', 'flatsome-child' ); ?></span>
				<b class="delivery-pill"><?php echo dmc_icon( 'delivery-truck', [ 'size' => 16, 'variant' => 'pill' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'MUA ONLINE - GIAO SIÊU TỐC 2H', 'flatsome-child' ); ?><?php echo dmc_icon( 'chevron-right', [ 'size' => 14, 'variant' => 'pill' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></b>
			</div>
		</div>
	</div>

	<div class="header">
		<div class="container inner">
			<div class="header-brand">
				<div class="mobile-head-left">
					<button type="button" class="menu-btn" id="dmcDrawerOpen" aria-label="<?php esc_attr_e( 'Mở menu', 'flatsome-child' ); ?>"><?php echo dmc_icon( 'menu', [ 'size' => 22, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				</div>
				<?php
				get_template_part( 'template-parts/homepage/logo' );
				?>
			</div>
			<?php
			get_template_part(
				'template-parts/commons/search-form',
				null,
				[
					'submit_icon' => 'search',
				]
			);
			?>
			<div class="actions">
				<a class="action action--account" href="<?php echo esc_url( $account_url ); ?>">
					<span class="ico"><?php echo dmc_icon( 'user', [ 'size' => 22, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span>
						<?php esc_html_e( 'Tài khoản', 'flatsome-child' ); ?><br>
						<b>
							<?php
							if ( is_user_logged_in() ) {
								echo esc_html( $current_user->display_name ?: __( 'Thành viên', 'flatsome-child' ) );
							} else {
								esc_html_e( 'Đăng nhập', 'flatsome-child' );
							}
							?>
						</b>
					</span>
				</a>
				<a class="action action--wishlist" href="<?php echo esc_url( $wishlist_url ); ?>">
					<span class="ico"><?php echo dmc_icon( 'heart', [ 'size' => 22, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php if ( $wishlist_count > 0 ) : ?><i class="badge" data-dmc-wishlist-count="<?php echo esc_attr( $wishlist_count ); ?>"><?php echo esc_html( $wishlist_count ); ?></i><?php endif; ?></span>
					<span><?php esc_html_e( 'Yêu thích', 'flatsome-child' ); ?></span>
				</a>
				<a class="action action--cart" href="<?php echo esc_url( $cart_url ); ?>" id="dmcCartButton" aria-label="<?php esc_attr_e( 'Giỏ hàng', 'flatsome-child' ); ?>">
					<span class="ico"><?php echo dmc_icon( 'cart', [ 'size' => 22, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><i class="badge" id="dmcCartCount"><?php echo esc_html( $cart_count ); ?></i></span>
					<span><?php esc_html_e( 'Giỏ hàng', 'flatsome-child' ); ?></span>
				</a>
				<a class="action action--chat" href="<?php echo esc_url( 'tel:' . $hotline_tel ); ?>" aria-label="<?php esc_attr_e( 'Tư vấn', 'flatsome-child' ); ?>">
					<span class="ico"><?php echo dmc_icon( 'chat', [ 'size' => 22, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</a>
			</div>
		</div>
	</div>

	<?php get_template_part( 'template-parts/commons/header-benefits' ); ?>

	<nav class="nav desktop-only" id="dmcMegaZone">
		<div class="container inner">
			<div class="mega-wrap" id="dmcMegaWrap">
				<button type="button" class="nav-trigger" id="dmcMegaTrigger" aria-expanded="false" aria-controls="dmcMegaMenu">
					<span class="nav-trigger__icon"><?php echo dmc_icon( 'menu', [ 'size' => 18, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<?php esc_html_e( 'Danh mục sản phẩm', 'flatsome-child' ); ?>
				</button>
			</div>
			<div class="nav-list">
				<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
					<?php foreach ( $categories as $cat ) : ?>
						<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="nav-list__link">
							<span class="nav-list__icon"><?php echo dmc_homepage_term_icon_html( $cat, [ 'size' => 18 ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="nav-list__label"><?php echo esc_html( $cat->name ); ?></span>
						</a>
					<?php endforeach; ?>
				<?php else : ?>
					<a href="#"><?php esc_html_e( 'Tivi', 'flatsome-child' ); ?></a>
					<a href="#"><?php esc_html_e( 'Tủ lạnh', 'flatsome-child' ); ?></a>
					<a href="#"><?php esc_html_e( 'Máy giặt', 'flatsome-child' ); ?></a>
					<a href="#"><?php esc_html_e( 'Điều hòa', 'flatsome-child' ); ?></a>
					<a href="#"><?php esc_html_e( 'Gia dụng', 'flatsome-child' ); ?></a>
					<a href="#"><?php esc_html_e( 'Khuyến mãi', 'flatsome-child' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
		<?php get_template_part( 'template-parts/homepage/mega-menu' ); ?>
	</nav>
</header>
