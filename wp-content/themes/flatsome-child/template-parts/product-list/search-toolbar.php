<?php
/**
 * Product list / detail — sticky search toolbar.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_url    = home_url( '/' );
$cart_url    = class_exists( 'WooCommerce' ) ? wc_get_cart_url() : '#';
$cart_count  = 0;
if ( class_exists( 'WooCommerce' ) && WC()->cart ) {
	$cart_count = WC()->cart->get_cart_contents_count();
}
$search_value = is_search() ? get_search_query() : '';
$show_nav     = ! is_product();
?>
<header class="pl-search-toolbar">
	<div class="pl-search-toolbar__bar">
		<div class="container pl-search-toolbar__inner">
			<div class="pl-search-toolbar__left mobile-only">
				<button type="button" class="menu-btn" id="dmcDrawerOpen" aria-label="<?php esc_attr_e( 'Mở menu', 'flatsome-child' ); ?>"><?php echo dmc_icon( 'menu', [ 'size' => 22, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			</div>
			<?php
			get_template_part( 'template-parts/homepage/logo' );
			?>
			<?php
			get_template_part(
				'template-parts/commons/search-form',
				null,
				[
					'form_id'      => 'dmcPlSearchForm',
					'input_id'     => 'dmcPlSearchInput',
					'form_class'   => 'pl-search-form',
					'submit_icon'  => 'search',
					'search_value' => $search_value,
				]
			);
			?>
			<a class="pl-cart" href="<?php echo esc_url( $cart_url ); ?>" id="dmcPlCartButton" aria-label="<?php esc_attr_e( 'Giỏ hàng', 'flatsome-child' ); ?>">
				<span class="pl-cart__icon"><?php echo dmc_icon( 'cart', [ 'size' => 22, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<i class="badge" id="dmcPlCartCount"><?php echo esc_html( $cart_count ); ?></i>
			</a>
		</div>
	</div>

	<?php if ( $show_nav ) : ?>
		<nav class="pl-nav desktop-only" id="dmcPlMegaZone">
			<div class="container pl-nav__inner">
				<div class="mega-wrap" id="dmcMegaWrap">
					<button type="button" class="nav-trigger" id="dmcMegaTrigger" aria-expanded="false" aria-controls="dmcMegaMenu">
						<span class="nav-trigger__icon"><?php echo dmc_icon( 'menu', [ 'size' => 18, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php esc_html_e( 'Danh mục sản phẩm', 'flatsome-child' ); ?>
					</button>
				</div>
				<div class="nav-list">
					<?php
					$categories = function_exists( 'dmc_homepage_get_categories' ) ? dmc_homepage_get_categories( 8 ) : [];
					if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
						foreach ( $categories as $cat ) :
							?>
							<a href="<?php echo esc_url( get_term_link( $cat ) ); ?>" class="nav-list__link">
								<span class="nav-list__icon"><?php echo dmc_homepage_term_icon_html( $cat, [ 'size' => 18 ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="nav-list__label"><?php echo esc_html( $cat->name ); ?></span>
							</a>
							<?php
						endforeach;
					else :
						?>
						<a href="#"><?php esc_html_e( 'Tivi', 'flatsome-child' ); ?></a>
						<a href="#"><?php esc_html_e( 'Tủ lạnh', 'flatsome-child' ); ?></a>
						<a href="#"><?php esc_html_e( 'Máy giặt', 'flatsome-child' ); ?></a>
						<a href="#"><?php esc_html_e( 'Điều hòa', 'flatsome-child' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			<?php get_template_part( 'template-parts/homepage/mega-menu' ); ?>
		</nav>
	<?php endif; ?>
</header>
