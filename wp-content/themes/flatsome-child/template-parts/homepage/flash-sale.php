<?php
/**
 * Homepage — flash sale section (ACF config).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$config   = dmc_homepage_get_flash_config();
$products = dmc_homepage_get_flash_products();
$shop_url = $config['more_url'] ?: ( class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : '#' );

if ( empty( $products ) ) {
	return;
}
?>
<section class="flash-section" id="dmcSale">
	<div class="flash-top">
		<div class="flash-brand">
			<span class="flash-brand__icon" aria-hidden="true">⚡</span>
			<span class="flash-brand__title"><?php echo esc_html( $config['title'] ); ?></span>
			<?php if ( $config['subtitle'] ) : ?>
				<span class="flash-brand__subtitle"><?php echo esc_html( $config['subtitle'] ); ?></span>
			<?php endif; ?>
		</div>
		<div class="flash-countdown">
			<span class="flash-countdown__label"><?php esc_html_e( 'Kết thúc sau', 'flatsome-child' ); ?></span>
			<span class="flash-countdown__digit" id="dmcCountH">04</span>
			<span class="flash-countdown__sep">:</span>
			<span class="flash-countdown__digit" id="dmcCountM">23</span>
			<span class="flash-countdown__sep">:</span>
			<span class="flash-countdown__digit" id="dmcCountS">59</span>
		</div>
		<a href="<?php echo esc_url( $shop_url ); ?>" class="flash-all"><?php esc_html_e( 'Xem tất cả ›', 'flatsome-child' ); ?></a>
	</div>
	<div class="flash-body">
		<div class="flash-inner">
			<div class="flash-products">
				<?php foreach ( $products as $product ) : ?>
					<?php dmc_homepage_render_flash_card( $product ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</section>
