<?php
/**
 * Product list / detail — breadcrumb (Shopee-style).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$items = dmc_pl_breadcrumb_items();
if ( empty( $items ) ) {
	return;
}
?>
<nav class="pl-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb', 'flatsome-child' ); ?>">
	<div class="container">
		<ol class="pl-breadcrumb__list">
			<?php foreach ( $items as $index => $item ) : ?>
				<li class="pl-breadcrumb__item">
					<?php if ( ! empty( $item['url'] ) && $index < count( $items ) - 1 ) : ?>
						<a href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['label'] ); ?></a>
					<?php else : ?>
						<span class="pl-breadcrumb__current" title="<?php echo esc_attr( $item['label'] ); ?>">
							<?php echo esc_html( $item['label'] ); ?>
						</span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</nav>
