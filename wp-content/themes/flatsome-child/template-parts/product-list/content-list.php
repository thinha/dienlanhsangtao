<?php
/**
 * Product list — main content.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$archive_columns = function_exists( 'dmc_tmp_get_archive_columns' ) ? dmc_tmp_get_archive_columns() : 3;
?>
<main class="pl-main">
	<div class="container">
		<?php get_template_part( 'template-parts/commons/product-breadcrumb' ); ?>

		<h1 class="pl-page-title"><?php echo esc_html( dmc_pl_list_title() ); ?></h1>

		<div class="pl-layout">
			<?php get_template_part( 'template-parts/product-list/filters' ); ?>

			<div class="pl-content">
				<?php get_template_part( 'template-parts/product-list/sort-bar' ); ?>

				<?php if ( woocommerce_product_loop() ) : ?>
					<div class="pl-grid" style="--pl-grid-cols: <?php echo esc_attr( (string) $archive_columns ); ?>;">
						<?php
						while ( have_posts() ) :
							the_post();
							$product = wc_get_product( get_the_ID() );
							if ( $product ) {
								dmc_pl_render_product_card( $product );
							}
						endwhile;
						?>
					</div>

					<div class="pl-pagination">
						<?php
						echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							[
								'total'   => $GLOBALS['wp_query']->max_num_pages,
								'current' => max( 1, get_query_var( 'paged' ) ),
								'prev_text' => '‹',
								'next_text' => '›',
							]
						);
						?>
					</div>
				<?php else : ?>
					<div class="pl-empty">
						<p><?php esc_html_e( 'Không tìm thấy sản phẩm phù hợp.', 'flatsome-child' ); ?></p>
						<a class="pl-empty__link" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
							<?php esc_html_e( 'Xem tất cả sản phẩm', 'flatsome-child' ); ?>
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</main>
