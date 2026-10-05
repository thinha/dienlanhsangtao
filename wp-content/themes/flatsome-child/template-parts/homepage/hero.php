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
						<?php $slide_type = ( $slide['type'] ?? 'image' ) === 'video' ? 'video' : 'image'; ?>
						<div class="swiper-slide<?php echo 'video' === $slide_type ? ' hero-slide--video' : ''; ?>">
							<?php if ( 'video' === $slide_type ) : ?>
								<?php
								$video_link = ! empty( $slide['url'] ) && '#' !== $slide['url'];
								$mime       = ! empty( $slide['mime'] ) ? $slide['mime'] : 'video/mp4';
								?>
								<div class="hero-slide__video-wrap">
									<video
										class="hero-slide__video"
										muted
										playsinline
										preload="metadata"
										disablepictureinpicture
										<?php echo ! empty( $slide['poster'] ) ? 'poster="' . esc_url( $slide['poster'] ) . '"' : ''; ?>
									>
										<source src="<?php echo esc_url( $slide['src'] ); ?>" type="<?php echo esc_attr( $mime ); ?>">
									</video>
									<?php if ( $video_link ) : ?>
										<a href="<?php echo esc_url( $slide['url'] ); ?>" class="hero-slide__link hero-slide__link--overlay">
											<span class="screen-reader-text"><?php esc_html_e( 'Xem chi tiết', 'flatsome-child' ); ?></span>
										</a>
									<?php endif; ?>
									<button type="button" class="hero-slide__sound is-muted" aria-label="<?php esc_attr_e( 'Bật tiếng', 'flatsome-child' ); ?>">
										<svg class="is-on" viewBox="0 0 24 24" aria-hidden="true">
											<path fill="currentColor" d="M4 9h3.2L12 5.2v13.6L7.2 15H4V9zm9.2.4v5.2a2.6 2.6 0 0 0 0-5.2zm0-2.5a5 5 0 0 1 0 10.2v-2.1a2.9 2.9 0 0 0 0-6V6.9z"/>
										</svg>
										<svg class="is-off" viewBox="0 0 24 24" aria-hidden="true">
											<path fill="currentColor" d="M4 9h3.2L12 5.2v13.6L7.2 15H4V9zm12.1 3 2.5-2.5-1.4-1.4-2.5 2.5-2.5-2.5-1.4 1.4 2.5 2.5-2.5 2.5 1.4 1.4 2.5-2.5 2.5 2.5 1.4-1.4-2.5-2.5z"/>
										</svg>
									</button>
								</div>
							<?php else : ?>
								<a href="<?php echo esc_url( $slide['url'] ); ?>" class="hero-slide__link">
									<img src="<?php echo esc_url( $slide['src'] ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>" width="1200" height="400" loading="lazy">
								</a>
							<?php endif; ?>
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
