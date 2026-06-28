<?php
/**
 * Homepage layout — sections driven by ACF config.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dmc-homepage">
	<?php get_template_part( 'template-parts/homepage/drawer' ); ?>
	<?php get_template_part( 'template-parts/commons/header' ); ?>

	<main>
		<div class="container">
			<?php
			$hero_enabled = function_exists( 'dmc_tmp_hp_hero_enabled' )
				? dmc_tmp_hp_hero_enabled()
				: true;
			if ( $hero_enabled ) :
				get_template_part( 'template-parts/homepage/hero' );
			endif;
			?>

			<?php if ( dmc_homepage_is_enabled( 'hp_categories_enable', true ) ) : ?>
				<?php get_template_part( 'template-parts/homepage/categories' ); ?>
			<?php endif; ?>

			<?php do_action( 'dmc_homepage_after_categories' ); ?>

			<?php if ( dmc_homepage_get_flash_config()['enable'] ) : ?>
				<?php get_template_part( 'template-parts/homepage/flash-sale' ); ?>
			<?php endif; ?>

			<?php if ( dmc_homepage_is_enabled( 'hp_vouchers_enable', true ) ) : ?>
				<?php get_template_part( 'template-parts/homepage/vouchers' ); ?>
			<?php endif; ?>

			<?php
			foreach ( dmc_homepage_get_product_sections() as $index => $section ) {
				dmc_homepage_render_product_section( $section, $index );
			}
			?>

			<?php
			$widesale_enabled = function_exists( 'dmc_tmp_hp_widesale_enabled' )
				? dmc_tmp_hp_widesale_enabled()
				: dmc_homepage_is_enabled( 'hp_widesale_enable', true );
			$brands_enabled   = function_exists( 'dmc_tmp_hp_brands_enabled' ) && dmc_tmp_hp_brands_enabled();
			if ( $widesale_enabled || $brands_enabled ) :
				get_template_part( 'template-parts/homepage/wide-sale' );
			endif;
			?>

			<?php if ( dmc_homepage_is_enabled( 'hp_services_enable', true ) ) : ?>
				<?php get_template_part( 'template-parts/homepage/services' ); ?>
			<?php endif; ?>
		</div>
	</main>

	<?php get_template_part( 'template-parts/commons/footer' ); ?>
	<?php get_template_part( 'template-parts/homepage/mobile-bar' ); ?>
</div>
