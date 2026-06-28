<?php
/**
 * WooCommerce single product — custom product detail layout.
 *
 * @package Flatsome_Child
 * @version 8.0.0
 */

defined( 'ABSPATH' ) || exit;

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'dmc-product-list-page dmc-product-detail-page' ); ?>>
<?php wp_body_open(); ?>

<?php while ( have_posts() ) : ?>
	<?php
	the_post();
	get_template_part( 'template-parts/product-detail/layout' );
	?>
<?php endwhile; ?>

<?php wp_footer(); ?>
</body>
</html>
