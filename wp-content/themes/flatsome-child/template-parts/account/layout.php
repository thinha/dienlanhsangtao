<?php
/**
 * My Account — page layout wrapper.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="dmc-homepage dmc-account">
	<?php get_template_part( 'template-parts/homepage/drawer' ); ?>
	<?php get_template_part( 'template-parts/commons/header' ); ?>

	<main class="dmc-account-main">
		<div class="container">
			<?php if ( ! is_user_logged_in() ) : ?>
				<?php get_template_part( 'template-parts/account/login' ); ?>
			<?php else : ?>
				<div class="dmc-account-layout">
					<?php dmc_account_render_sidebar(); ?>
					<div class="dmc-account-content">
						<?php wc_print_notices(); ?>
						<?php get_template_part( 'template-parts/account/content' ); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</main>

	<?php get_template_part( 'template-parts/commons/footer' ); ?>
	<?php get_template_part( 'template-parts/homepage/mobile-bar' ); ?>
</div>
