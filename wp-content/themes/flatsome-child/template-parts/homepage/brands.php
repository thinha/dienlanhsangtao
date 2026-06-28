<?php
/**
 * Homepage — featured brands row (reusable partial).
 *
 * @package Flatsome_Child
 *
 * @var array<int, array{url:string,alt:string,link:string,name:string}>|null $brands
 * @var string|null                                                          $title
 * @var string|null                                                          $wrapper_class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( isset( $args ) && is_array( $args ) ) {
	if ( isset( $args['brands'] ) ) {
		$brands = $args['brands'];
	}
	if ( isset( $args['title'] ) ) {
		$title = $args['title'];
	}
	if ( isset( $args['wrapper_class'] ) ) {
		$wrapper_class = $args['wrapper_class'];
	}
}

if ( ! isset( $brands ) ) {
	$brands = function_exists( 'dmc_tmp_get_hp_brands' )
		? dmc_tmp_get_hp_brands()
		: dmc_homepage_get_featured_brands();
}

if ( empty( $brands ) ) {
	return;
}

$config         = function_exists( 'dmc_tmp_get_hp_brands_config' ) ? dmc_tmp_get_hp_brands_config() : [];
$title          = isset( $title ) ? $title : ( $config['title'] ?? __( 'THƯƠNG HIỆU NỔI BẬT', 'flatsome-child' ) );
$wrapper_class  = isset( $wrapper_class ) ? $wrapper_class : 'card';
?>
<div class="<?php echo esc_attr( $wrapper_class ); ?>">
	<div class="section-head">
		<h2><?php echo esc_html( $title ); ?></h2>
	</div>
	<div class="brand-row">
		<?php foreach ( $brands as $brand ) : ?>
			<?php
			$tag        = ! empty( $brand['link'] ) ? 'a' : 'div';
			$attrs      = ! empty( $brand['link'] ) ? ' href="' . esc_url( $brand['link'] ) . '"' : '';
			$has_image  = ! empty( $brand['url'] );
			$item_class = 'brand' . ( $has_image ? ' brand--image' : '' );
			?>
			<<?php echo esc_html( $tag ); ?> class="<?php echo esc_attr( $item_class ); ?>"<?php echo $attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<?php if ( $has_image ) : ?>
					<img
						class="brand__img"
						src="<?php echo esc_url( $brand['url'] ); ?>"
						alt="<?php echo esc_attr( $brand['alt'] ?? $brand['name'] ?? '' ); ?>"
						width="120"
						height="43"
						loading="lazy"
						decoding="async"
					>
				<?php else : ?>
					<?php echo esc_html( $brand['name'] ?? $brand['alt'] ?? '' ); ?>
				<?php endif; ?>
			</<?php echo esc_html( $tag ); ?>>
		<?php endforeach; ?>
	</div>
</div>
