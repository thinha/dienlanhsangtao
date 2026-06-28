<?php
/**
 * Checkout — page layout wrapper.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dmc-homepage dmc-checkout">
	<?php get_template_part( 'template-parts/homepage/drawer' ); ?>
	<?php get_template_part( 'template-parts/commons/header' ); ?>

	<main class="dmc-checkout-main">
		<div class="container">
			<?php wc_print_notices(); ?>
			<?php get_template_part( 'template-parts/checkout/content' ); ?>
		</div>
	</main>

	<?php get_template_part( 'template-parts/commons/footer' ); ?>
	<?php get_template_part( 'template-parts/homepage/mobile-bar' ); ?>
</div>
