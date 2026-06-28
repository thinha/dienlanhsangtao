<?php
/**
 * Tab menu product section template.
 *
 * @var array<int, array<string, mixed>> $tabs
 * @var int                              $slides_per_view
 * @var bool                             $show_more
 * @var string                           $more_text
 * @var bool                             $swiper_autoplay
 * @var int                              $swiper_autoplay_delay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$active_more_url = dmc_tmp_tab_more_url( $tabs[0] );
?>
<section class="card dmc-tab-menu-product" id="dmcTabMenuProduct">
	<div class="dmc-tab-menu-product__head">
		<nav class="dmc-tab-menu-product__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Danh mục sản phẩm', 'dmc-tab-menu-product' ); ?>">
			<?php foreach ( $tabs as $index => $tab ) : ?>
				<?php
				$icon      = $tab['icon'];
				$icon_html = '';
				if ( $icon ) {
					$icon_html = '<img src="' . esc_url( $icon['url'] ) . '" alt="' . esc_attr( $icon['alt'] ?: $tab['title'] ) . '" class="dmc-tab-icon-img" loading="lazy">';
				} elseif ( ! empty( $tab['category'] ) && function_exists( 'dmc_homepage_term_icon_html' ) ) {
					$icon_html = dmc_homepage_term_icon_html( $tab['category'], 'thumbnail' );
				} else {
					$icon_html = '<span class="dmc-tab-icon-fallback">📦</span>';
				}
				?>
				<button
					type="button"
					class="dmc-tab-menu-product__tab<?php echo 0 === $index ? ' is-active' : ''; ?>"
					role="tab"
					id="dmcTabBtn<?php echo esc_attr( (string) $index ); ?>"
					aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
					aria-controls="dmcTabPanel<?php echo esc_attr( (string) $index ); ?>"
					data-tab-index="<?php echo esc_attr( (string) $index ); ?>"
					data-more-url="<?php echo esc_url( dmc_tmp_tab_more_url( $tab ) ); ?>"
				>
					<span class="dmc-tab-menu-product__tab-icon"><?php echo $icon_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="dmc-tab-menu-product__tab-label"><?php echo esc_html( $tab['title'] ); ?></span>
				</button>
			<?php endforeach; ?>
		</nav>
		<?php if ( $show_more ) : ?>
			<a href="<?php echo esc_url( $active_more_url ); ?>" class="dmc-tab-menu-product__more more" id="dmcTabMenuMore">
				<?php echo esc_html( $more_text ); ?>
			</a>
		<?php endif; ?>
	</div>

	<div class="dmc-tab-menu-product__panels">
		<?php foreach ( $tabs as $index => $tab ) : ?>
			<?php
			$products = dmc_tmp_resolve_tab_products( $tab );
			if ( empty( $products ) ) {
				continue;
			}
			?>
			<div
				class="dmc-tab-menu-product__panel<?php echo 0 === $index ? ' is-active' : ''; ?>"
				id="dmcTabPanel<?php echo esc_attr( (string) $index ); ?>"
				role="tabpanel"
				aria-labelledby="dmcTabBtn<?php echo esc_attr( (string) $index ); ?>"
				<?php echo 0 !== $index ? 'hidden' : ''; ?>
			>
				<div class="dmc-swiper-wrap" data-slides="<?php echo esc_attr( (string) $slides_per_view ); ?>">
					<button type="button" class="dmc-swiper-nav dmc-swiper-prev" aria-label="<?php esc_attr_e( 'Xem sản phẩm trước', 'dmc-tab-menu-product' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</button>
					<div
						class="swiper dmc-tab-product-swiper"
						data-slides-per-view="<?php echo esc_attr( (string) $slides_per_view ); ?>"
						data-autoplay="<?php echo esc_attr( ! empty( $swiper_autoplay ) ? '1' : '0' ); ?>"
						data-autoplay-delay="<?php echo esc_attr( (string) ( $swiper_autoplay_delay ?? 4 ) ); ?>"
					>
						<div class="swiper-wrapper">
							<?php foreach ( $products as $product ) : ?>
								<div class="swiper-slide">
									<?php
									if ( function_exists( 'dmc_homepage_render_product_card' ) ) {
										dmc_homepage_render_product_card( $product );
									}
									?>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					<button type="button" class="dmc-swiper-nav dmc-swiper-next" aria-label="<?php esc_attr_e( 'Xem thêm sản phẩm', 'dmc-tab-menu-product' ); ?>">
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</button>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
