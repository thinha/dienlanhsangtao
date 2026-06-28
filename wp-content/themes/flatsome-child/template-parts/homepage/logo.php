<?php
/**
 * Homepage — site logo (Flatsome Customizer → Logo & Site Identity).
 *
 * @package Flatsome_Child
 * @var array  $args Optional overrides.
 * @var string $args['class']     Extra CSS class on wrapper.
 * @var bool   $args['mobile']    Force mobile logo only (drawer).
 * @var bool   $args['mini']      Compact size for drawer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo     = dmc_homepage_get_logo();
$home_url = home_url( '/' );
$class    = trim( 'logo ' . ( $args['class'] ?? '' ) );
$is_mini  = ! empty( $args['mini'] );
$force_mobile = ! empty( $args['mobile'] );
$has_mobile   = $logo['mobile_url'] && $logo['mobile_url'] !== $logo['url'];

$img_style = '';
if ( ! empty( $logo['max_width'] ) ) {
	$img_style = ' style="max-width:' . (int) $logo['max_width'] . 'px"';
}
?>
<a class="<?php echo esc_attr( $class ); ?><?php echo $is_mini ? ' logo--mini' : ''; ?><?php echo $has_mobile ? ' logo--has-mobile' : ''; ?>" href="<?php echo esc_url( $home_url ); ?>">
	<?php if ( $logo['has_image'] ) : ?>
		<?php if ( $force_mobile ) : ?>
			<img
				class="logo-img"
				src="<?php echo esc_url( $logo['mobile_url'] ?: $logo['url'] ); ?>"
				alt="<?php echo esc_attr( $logo['alt'] ); ?>"
				height="<?php echo $is_mini ? '38' : '48'; ?>"
				loading="eager"
				decoding="async"
				<?php echo $img_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
		<?php else : ?>
			<img
				class="logo-img logo-img--desktop"
				src="<?php echo esc_url( $logo['url'] ); ?>"
				alt="<?php echo esc_attr( $logo['alt'] ); ?>"
				height="48"
				loading="eager"
				decoding="async"
				<?php echo $img_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			>
			<?php if ( $has_mobile ) : ?>
				<img
					class="logo-img logo-img--mobile"
					src="<?php echo esc_url( $logo['mobile_url'] ); ?>"
					alt="<?php echo esc_attr( $logo['alt'] ); ?>"
					height="35"
					loading="eager"
					decoding="async"
					<?php echo $img_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				>
			<?php endif; ?>
		<?php endif; ?>
	<?php else : ?>
		<span class="logo-mark">✦</span>
	<?php endif; ?>
</a>
