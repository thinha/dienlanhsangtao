<?php
/**
 * Homepage — product grid section.
 *
 * @package Flatsome_Child
 * @var string $args['title']     Section title.
 * @var string $args['more_url']  View all link.
 * @var array  $args['products']  WC_Product[].
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$title    = $args['title'] ?? '';
$more_url = $args['more_url'] ?? '#';
$products = $args['products'] ?? [];
?>
<section class="card">
	<div class="section-head">
		<h2><?php echo esc_html( $title ); ?></h2>
		<a href="<?php echo esc_url( $more_url ); ?>" class="more"><?php esc_html_e( 'Xem tất cả ›', 'flatsome-child' ); ?></a>
	</div>
	<div class="product-row">
		<?php
		if ( ! empty( $products ) ) :
			foreach ( $products as $product ) :
				dmc_homepage_render_product_card( $product );
			endforeach;
		endif;
		?>
	</div>
</section>
