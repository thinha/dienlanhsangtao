<?php
/**
 * Cart — page content.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = dmc_cart_breadcrumb_items();
$item_count = dmc_cart_item_count();
?>
<header class="dmc-cart-head">
	<nav class="dmc-cart-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'flatsome-child' ); ?>">
		<ol class="dmc-cart-breadcrumb__list">
			<?php foreach ( $items as $item ) : ?>
				<li class="dmc-cart-breadcrumb__item">
					<?php if ( ! empty( $item['url'] ) ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
					<?php else : ?>
						<span class="dmc-cart-breadcrumb__current"><?php echo esc_html( $item['label'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>

	<div class="dmc-cart-head__title">
		<h1><?php esc_html_e( 'Giỏ hàng', 'flatsome-child' ); ?></h1>
		<?php if ( $item_count > 0 ) : ?>
			<span class="dmc-cart-head__count">
				<?php
				printf(
					/* translators: %d: number of items in cart */
					esc_html( _n( '%d sản phẩm', '%d sản phẩm', $item_count, 'flatsome-child' ) ),
					(int) $item_count
				);
				?>
			</span>
		<?php endif; ?>
	</div>
</header>

<div class="dmc-cart-content">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</div>
