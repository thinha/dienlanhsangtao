<?php
/**
 * Homepage — configurable product section (grid / flash / swiper).
 *
 * @package Flatsome_Child
 * @var array        $args['config']   Section config.
 * @var WC_Product[] $args['products'] Products.
 * @var string       $args['layout']   grid|flash|swiper.
 * @var int          $args['index']    Section index.
 * @var string       $args['more_url'] View all URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$config   = $args['config'] ?? [];
$products = $args['products'] ?? [];
$layout   = $args['layout'] ?? 'grid';
$index    = (int) ( $args['index'] ?? 0 );
$title     = $config['title'] ?? '';
$more_url  = $args['more_url'] ?? '#';
$more_text = trim( (string) ( $config['more_text'] ?? 'Xem tất cả ›' ) ) ?: 'Xem tất cả ›';
$show_more = ! empty( $config['show_more'] );

$decor = function_exists( 'dmc_tmp_product_section_decor_attrs' )
	? dmc_tmp_product_section_decor_attrs( $config )
	: [ 'class' => '', 'style' => '' ];
$decor_class = $decor['class'] ? ' ' . esc_attr( $decor['class'] ) : '';
$decor_style = $decor['style'] ? ' style="' . esc_attr( $decor['style'] ) . '"' : '';

if ( empty( $products ) || ! $title ) {
	return;
}

if ( 'showcase' === $layout ) :
	?>
	<section class="card dmc-product-section dmc-product-section--showcase<?php echo $decor_class; ?>"<?php echo $decor_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="dmc-showcase">
			<div class="dmc-showcase__head">
				<h2 class="dmc-showcase__title"><?php echo esc_html( $title ); ?></h2>
				<?php if ( $show_more ) : ?>
					<a href="<?php echo esc_url( $more_url ); ?>" class="more"><?php echo esc_html( $more_text ); ?></a>
				<?php endif; ?>
			</div>
			<div class="dmc-showcase__grid">
				<?php foreach ( $products as $product ) : ?>
					<?php dmc_homepage_render_product_card( $product ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
	<?php
	return;
endif;

if ( 'flash' === $layout ) :
	?>
	<section class="flash-section dmc-product-flash-block<?php echo $decor_class; ?>"<?php echo $decor_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="flash-top">
			<div class="flash-brand">⚡ <?php echo esc_html( $title ); ?></div>
			<?php if ( $show_more ) : ?>
				<a href="<?php echo esc_url( $more_url ); ?>" class="flash-all"><?php echo esc_html( $more_text ); ?></a>
			<?php endif; ?>
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
	<?php
	return;
endif;

$swiper_id = 'dmcProductSwiper' . $index;
$slides_per_view = max( 2, min( 8, (int) ( $config['slides_per_view'] ?? 4 ) ) );
$swiper_autoplay = ! empty( $config['swiper_autoplay'] );
$swiper_autoplay_delay = max( 2, min( 15, (int) ( $config['swiper_autoplay_delay'] ?? 4 ) ) );
?>
<section class="card dmc-product-section dmc-product-section--<?php echo esc_attr( $layout ); ?><?php echo $decor_class; ?>"<?php echo $decor_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="section-head">
		<h2><?php echo esc_html( $title ); ?></h2>
		<?php if ( $show_more ) : ?>
			<a href="<?php echo esc_url( $more_url ); ?>" class="more"><?php echo esc_html( $more_text ); ?></a>
		<?php endif; ?>
	</div>

	<?php if ( 'swiper' === $layout ) : ?>
		<div class="dmc-swiper-wrap">
			<button type="button" class="dmc-swiper-nav dmc-swiper-prev" aria-label="<?php esc_attr_e( 'Trước', 'flatsome-child' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="M15 6l-6 6 6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
			<div
				class="swiper dmc-product-swiper"
				id="<?php echo esc_attr( $swiper_id ); ?>"
				data-slides-per-view="<?php echo esc_attr( (string) $slides_per_view ); ?>"
				data-autoplay="<?php echo esc_attr( $swiper_autoplay ? '1' : '0' ); ?>"
				data-autoplay-delay="<?php echo esc_attr( (string) $swiper_autoplay_delay ); ?>"
			>
				<div class="swiper-wrapper">
					<?php foreach ( $products as $product ) : ?>
						<div class="swiper-slide">
							<?php dmc_homepage_render_product_card( $product ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
			<button type="button" class="dmc-swiper-nav dmc-swiper-next" aria-label="<?php esc_attr_e( 'Sau', 'flatsome-child' ); ?>">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
					<path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		</div>
	<?php else : ?>
		<div class="product-row">
			<?php foreach ( $products as $product ) : ?>
				<?php dmc_homepage_render_product_card( $product ); ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
