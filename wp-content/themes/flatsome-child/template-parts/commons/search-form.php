<?php
/**
 * Shared header search form with live suggestions.
 *
 * @package Flatsome_Child
 *
 * @var array $args {
 *     @type string $form_id      Form element ID.
 *     @type string $input_id     Input element ID.
 *     @type string $form_class   Extra form classes.
 *     @type string $submit_label Submit button label.
 *     @type string $search_value Prefilled search value.
 * }
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$args = wp_parse_args(
	$args ?? [],
	[
		'form_id'      => 'dmcSearchForm',
		'input_id'     => 'dmcSearchInput',
		'form_class'   => '',
		'submit_label' => __( 'Tìm kiếm', 'flatsome-child' ),
		'submit_icon'  => '',
		'search_value' => is_search() ? get_search_query() : '',
	]
);

$home_url    = home_url( '/' );
$form_class  = trim( 'search ' . $args['form_class'] );
?>
<div class="search-wrap">
	<form
		class="<?php echo esc_attr( $form_class ); ?>"
		id="<?php echo esc_attr( $args['form_id'] ); ?>"
		action="<?php echo esc_url( $home_url ); ?>"
		method="get"
		role="search"
	>
		<span class="search__lead" aria-hidden="true"><?php echo dmc_icon( 'search', [ 'size' => 16, 'variant' => 'muted' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<input
			id="<?php echo esc_attr( $args['input_id'] ); ?>"
			type="search"
			name="s"
			placeholder="<?php esc_attr_e( 'Bạn cần tìm gì hôm nay?', 'flatsome-child' ); ?>"
			value="<?php echo esc_attr( $args['search_value'] ); ?>"
			autocomplete="off"
		>
		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<input type="hidden" name="post_type" value="product">
		<?php endif; ?>
		<button type="submit"><?php if ( $args['submit_icon'] && function_exists( 'dmc_icon' ) ) : ?><?php echo dmc_icon( $args['submit_icon'], [ 'size' => 18, 'variant' => 'white' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php else : ?><?php echo esc_html( $args['submit_label'] ); ?><?php endif; ?></button>
	</form>
	<div class="search-suggest" hidden aria-live="polite"></div>
</div>
