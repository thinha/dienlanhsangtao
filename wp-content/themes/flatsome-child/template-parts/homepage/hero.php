<?php
/**
 * Homepage — hero banner slider (Swiper) & benefits.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slides           = dmc_homepage_get_slides();
$shop_url         = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : '#';
$benefits         = function_exists( 'dmc_tmp_get_hp_benefits' ) ? dmc_tmp_get_hp_benefits() : [];
$benefits_enabled = function_exists( 'dmc_tmp_hp_benefits_enabled' ) ? dmc_tmp_hp_benefits_enabled() : true;
?>
<section class="hero-row">
	<div class="hero-slider">
		<div class="swiper dmc-hero-swiper">
			<div class="swiper-wrapper">
				<?php if ( ! empty( $slides ) ) : ?>
					<?php foreach ( $slides as $slide ) : ?>
						<div class="swiper-slide">
							<a href="<?php echo esc_url( $slide['url'] ); ?>" class="hero-slide__link">
								<img src="<?php echo esc_url( $slide['src'] ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>" width="1200" height="400" loading="lazy">
							</a>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
						<div class="swiper-slide">
							<a href="<?php echo esc_url( $shop_url ); ?>" class="hero-slide__link hero-slide__link--fallback hero-slide__link--<?php echo (int) $i; ?>">
								<span class="screen-reader-text"><?php esc_html_e( 'Banner khuyến mãi', 'flatsome-child' ); ?></span>
							</a>
						</div>
					<?php endfor; ?>
				<?php endif; ?>
			</div>
			<div class="dmc-hero-pagination swiper-pagination"></div>
		</div>
	</div>
	<?php if ( $benefits_enabled && ! empty( $benefits ) ) : ?>
		<aside class="benefits">
			<?php foreach ( $benefits as $benefit ) : ?>
				<div class="benefit">
					<div class="benefit__icon">
						<?php
						if ( function_exists( 'dmc_tmp_render_benefit_icon' ) ) {
							dmc_tmp_render_benefit_icon( $benefit );
						}
						?>
					</div>
					<div>
						<b><?php echo esc_html( $benefit['title'] ); ?></b>
						<?php if ( ! empty( $benefit['subtitle'] ) ) : ?>
							<span><?php echo esc_html( $benefit['subtitle'] ); ?></span>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</aside>
	<?php endif; ?>
</section>
