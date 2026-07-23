<?php
/**
 * Template Name: Giới thiệu
 * Template Post Type: page
 *
 * Trang giới thiệu doanh nghiệp — Điện Lạnh Sáng Tạo.
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
<body <?php body_class( 'dmc-about-page' ); ?>>
<?php wp_body_open(); ?>

<?php get_template_part( 'template-parts/about/layout' ); ?>

<?php wp_footer(); ?>
</body>
</html>
