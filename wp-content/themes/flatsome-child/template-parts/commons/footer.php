<?php
/**
 * Site footer (shared) — content from widget areas.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name     = get_bloginfo( 'name' );
$year          = gmdate( 'Y' );
$footer_cols   = dmc_footer_column_sidebars();
$has_columns   = false;

foreach ( $footer_cols as $sidebar_id ) {
	if ( is_active_sidebar( $sidebar_id ) ) {
		$has_columns = true;
		break;
	}
}
?>
<footer>
	<div class="container">
		<?php if ( $has_columns ) : ?>
			<div class="footer-grid">
				<?php
				foreach ( $footer_cols as $sidebar_id ) {
					if ( is_active_sidebar( $sidebar_id ) ) {
						dynamic_sidebar( $sidebar_id );
					}
				}
				?>
			</div>
		<?php endif; ?>

		<?php if ( is_active_sidebar( 'dmc-footer-copyright' ) ) : ?>
			<div class="copyright">
				<?php dynamic_sidebar( 'dmc-footer-copyright' ); ?>
			</div>
		<?php else : ?>
			<div class="copyright">
				© <?php echo esc_html( $year ); ?> <?php echo esc_html( $site_name ); ?>. <?php esc_html_e( 'All rights reserved.', 'flatsome-child' ); ?>
			</div>
		<?php endif; ?>
	</div>
</footer>
