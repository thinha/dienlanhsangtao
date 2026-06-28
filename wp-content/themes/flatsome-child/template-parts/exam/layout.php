<?php
/**
 * Exam — page layout wrapper.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dmc-homepage dmc-exam">
	<?php get_template_part( 'template-parts/homepage/drawer' ); ?>
	<?php get_template_part( 'template-parts/product-list/search-toolbar' ); ?>

	<main class="dmc-exam-main">
		<div class="container">
			<?php get_template_part( 'template-parts/exam/content' ); ?>
		</div>
	</main>

	<?php get_template_part( 'template-parts/commons/footer' ); ?>
	<?php get_template_part( 'template-parts/homepage/mobile-bar' ); ?>
</div>
