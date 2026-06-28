<?php
/**
 * Checkout — page content.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<header class="dmc-checkout-head">
	<h1 class="dmc-checkout-head__title"><?php echo esc_html( dmc_checkout_page_title() ); ?></h1>
</header>

<div class="dmc-checkout-content">
	<?php
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
	?>
</div>
