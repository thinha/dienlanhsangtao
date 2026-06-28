<?php
/*
Template name: WooCommerce - My Account
Custom account layout — login for members + wishlist.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'dmc-account-page' ); ?>>
<?php wp_body_open(); ?>

<?php get_template_part( 'template-parts/account/layout' ); ?>

<?php wp_footer(); ?>
</body>
</html>
