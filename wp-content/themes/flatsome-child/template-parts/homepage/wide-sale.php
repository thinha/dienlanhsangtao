<?php
/**
 * Homepage — wide sale banner + featured brands.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wide_sale = function_exists( 'dmc_tmp_get_hp_widesale' ) ? dmc_tmp_get_hp_widesale() : null;
$brands    = function_exists( 'dmc_tmp_hp_brands_enabled' ) && dmc_tmp_hp_brands_enabled() && function_exists( 'dmc_tmp_get_hp_brands' )
	? dmc_tmp_get_hp_brands()
	: [];

$widesale_enabled = function_exists( 'dmc_tmp_hp_widesale_enabled' )
	? dmc_tmp_hp_widesale_enabled()
	: true;
$has_banner       = $widesale_enabled && ! empty( $wide_sale['image']['url'] );

if ( ! $has_banner && empty( $brands ) ) {
	return;
}
?>
<section class="wide-sale">
	<?php if ( ! empty( $brands ) ) : ?>
		<?php
		get_template_part(
			'template-parts/homepage/brands',
			null,
			[
				'brands'         => $brands,
				'wrapper_class'  => 'card wide-sale__brands',
			]
		);
		?>
	<?php endif; ?>

	<?php if ( $has_banner ) : ?>
		<img
			src="<?php echo esc_url( $wide_sale['image']['url'] ); ?>"
			alt="<?php echo esc_attr( $wide_sale['image']['alt'] ?: get_bloginfo( 'name' ) ); ?>"
			class="wide-sale__img"
			loading="lazy"
		>
	<?php endif; ?>
</section>
