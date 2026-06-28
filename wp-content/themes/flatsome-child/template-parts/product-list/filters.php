<?php
/**
 * Product list — sidebar filters.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$categories   = dmc_pl_filter_categories();
$selected_cat = dmc_pl_selected_categories();
$brands       = dmc_pl_filter_brands();
$selected_br  = dmc_pl_selected_brands();
$price_bounds = dmc_pl_product_price_bounds();
?>
<aside class="pl-filters">
	<div class="pl-filters__head">
		<span class="pl-filters__icon"><?php echo dmc_icon( 'filter', [ 'size' => 20, 'variant' => 'blue' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<h2><?php esc_html_e( 'Bộ lọc tìm kiếm', 'flatsome-child' ); ?></h2>
	</div>

	<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
		<div class="pl-filters__group">
			<h3><?php esc_html_e( 'Theo danh mục', 'flatsome-child' ); ?></h3>
			<ul class="pl-filters__list">
				<?php foreach ( $categories as $cat ) : ?>
					<li>
						<label class="pl-filter-check">
							<input
								type="checkbox"
								<?php checked( in_array( $cat->term_id, $selected_cat, true ) ); ?>
								onchange="window.location.href='<?php echo esc_url( dmc_pl_filter_url( 'filter_cat', $cat->term_id ) ); ?>'"
							>
							<span><?php echo esc_html( $cat->name ); ?></span>
							<small>(<?php echo esc_html( $cat->count ); ?>)</small>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $brands ) && ! is_wp_error( $brands ) ) : ?>
		<div class="pl-filters__group">
			<h3><?php esc_html_e( 'Theo thương hiệu', 'flatsome-child' ); ?></h3>
			<ul class="pl-filters__list">
				<?php foreach ( $brands as $brand ) : ?>
					<li>
						<label class="pl-filter-check">
							<input
								type="checkbox"
								<?php checked( in_array( $brand->term_id, $selected_br, true ) ); ?>
								onchange="window.location.href='<?php echo esc_url( dmc_pl_filter_url( 'filter_brand', $brand->term_id ) ); ?>'"
							>
							<span><?php echo esc_html( $brand->name ); ?></span>
							<small>(<?php echo esc_html( $brand->count ); ?>)</small>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $price_bounds && $price_bounds['min'] < $price_bounds['max'] ) : ?>
		<?php
		$selected_price = dmc_pl_selected_price_range( $price_bounds );
		global $wp;
		if ( '' === get_option( 'permalink_structure' ) ) {
			$form_action = remove_query_arg( [ 'page', 'paged', 'product-page' ], add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
		} else {
			$form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( trailingslashit( $wp->request ) ) );
		}
		?>
		<div class="pl-filters__group">
			<h3><?php esc_html_e( 'Khoảng giá', 'flatsome-child' ); ?></h3>
			<form method="get" action="<?php echo esc_url( $form_action ); ?>" class="pl-price-filter">
				<div class="pl-price-filter__slider" data-step="<?php echo esc_attr( $price_bounds['step'] ); ?>"></div>
				<div class="pl-price-filter__label">
					<span class="pl-price-filter__from"></span>
					<span class="pl-price-filter__sep">&mdash;</span>
					<span class="pl-price-filter__to"></span>
				</div>
				<input
					type="hidden"
					id="pl_min_price"
					name="min_price"
					value="<?php echo esc_attr( $selected_price['min'] ); ?>"
					data-min="<?php echo esc_attr( $price_bounds['min'] ); ?>"
				>
				<input
					type="hidden"
					id="pl_max_price"
					name="max_price"
					value="<?php echo esc_attr( $selected_price['max'] ); ?>"
					data-max="<?php echo esc_attr( $price_bounds['max'] ); ?>"
				>
				<?php echo wc_query_string_form_fields( null, [ 'min_price', 'max_price', 'paged' ], '', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<button type="submit" class="pl-price-filter__btn">
					<?php esc_html_e( 'Lọc giá', 'flatsome-child' ); ?>
				</button>
			</form>
		</div>
	<?php endif; ?>
</aside>
