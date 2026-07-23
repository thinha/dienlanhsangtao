<?php
/**
 * About page — layout wrapper.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dmc-homepage dmc-about">
	<?php get_template_part( 'template-parts/homepage/drawer' ); ?>
	<?php get_template_part( 'template-parts/commons/header' ); ?>

	<main class="dmc-about-main">
		<?php get_template_part( 'template-parts/about/content' ); ?>
	</main>

	<?php get_template_part( 'template-parts/commons/footer' ); ?>
	<?php get_template_part( 'template-parts/homepage/mobile-bar' ); ?>
</div>
