<?php
/**
 * Front page template — Điện Máy homepage layout.
 *
 * @package Flatsome_Child
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'dmc-homepage-page' ); ?>>
<?php wp_body_open(); ?>

<?php get_template_part( 'template-parts/homepage/layout' ); ?>

<?php wp_footer(); ?>
</body>
</html>
