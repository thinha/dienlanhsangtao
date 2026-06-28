<?php
/**
 * Product list — sort toolbar.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query;

$current_sort = dmc_pl_get_sort();
$sort_options = dmc_pl_sort_options();
$total        = $wp_query->found_posts;
$paged        = max( 1, (int) get_query_var( 'paged' ) );
$per_page     = (int) get_query_var( 'posts_per_page' );
$from         = $total ? ( ( $paged - 1 ) * $per_page ) + 1 : 0;
$to           = min( $total, $paged * $per_page );
?>
<div class="pl-sortbar">
	<div class="pl-sortbar__label"><?php esc_html_e( 'Sắp xếp theo', 'flatsome-child' ); ?></div>
	<div class="pl-sortbar__tabs">
		<?php foreach ( $sort_options as $key => $label ) : ?>
			<a
				class="pl-sortbar__tab<?php echo $current_sort === $key ? ' is-active' : ''; ?>"
				href="<?php echo esc_url( dmc_pl_sort_url( $key ) ); ?>"
			><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>
	<?php if ( $total ) : ?>
		<div class="pl-sortbar__meta desktop-only">
			<?php
			printf(
				/* translators: 1: from, 2: to, 3: total */
				esc_html__( '%1$d–%2$d / %3$d sản phẩm', 'flatsome-child' ),
				(int) $from,
				(int) $to,
				(int) $total
			);
			?>
		</div>
	<?php endif; ?>
</div>
