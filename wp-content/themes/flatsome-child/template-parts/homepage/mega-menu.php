<?php
/**
 * Homepage — XANH-style mega menu panel (full-width dropdown).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mega_groups = dmc_homepage_mega_menu_data();
?>
<div class="mega-menu" id="dmcMegaMenu" aria-hidden="true">
	<div class="container">
		<div class="mega-layout">
			<aside class="mega-sidebar">
				<ul class="mega-sidebar__list">
					<?php foreach ( $mega_groups as $index => $group ) : ?>
						<li class="mega-sidebar__item<?php echo 0 === $index ? ' is-active' : ''; ?>" data-mega-index="<?php echo esc_attr( $index ); ?>">
							<a href="<?php echo esc_url( $group['url'] ); ?>" class="mega-sidebar__link">
								<?php echo esc_html( $group['label'] ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</aside>

			<div class="mega-panels">
				<?php foreach ( $mega_groups as $index => $group ) : ?>
					<div class="mega-panel<?php echo 0 === $index ? ' is-active' : ''; ?>" data-mega-index="<?php echo esc_attr( $index ); ?>">
						<h3 class="mega-panel__title"><?php echo esc_html( mb_strtoupper( $group['label'], 'UTF-8' ) ); ?></h3>
						<?php if ( ! empty( $group['brands'] ) ) : ?>
							<div class="mega-subgrid">
								<a href="<?php echo esc_url( $group['url'] ); ?>" class="mega-subitem">
									<span class="mega-subitem__icon"><?php echo dmc_icon( 'package', [ 'size' => 28, 'variant' => 'blue', 'class' => 'dmc-icon--mega' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span class="mega-subitem__label"><?php esc_html_e( 'Xem tất cả', 'flatsome-child' ); ?></span>
								</a>
								<?php foreach ( $group['brands'] as $brand ) : ?>
									<a href="<?php echo esc_url( $brand['url'] ); ?>" class="mega-subitem">
										<span class="mega-subitem__icon"><?php echo $brand['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<span class="mega-subitem__label"><?php echo esc_html( $brand['label'] ); ?></span>
									</a>
								<?php endforeach; ?>
							</div>
						<?php elseif ( ! empty( $group['children'] ) ) : ?>
							<div class="mega-subgrid">
								<?php foreach ( $group['children'] as $child ) : ?>
									<a href="<?php echo esc_url( $child['url'] ); ?>" class="mega-subitem">
										<span class="mega-subitem__icon"><?php echo $child['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<span class="mega-subitem__label"><?php echo esc_html( $child['label'] ); ?></span>
									</a>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<div class="mega-subgrid mega-subgrid--single">
								<a href="<?php echo esc_url( $group['url'] ); ?>" class="mega-subitem">
									<span class="mega-subitem__icon"><?php echo dmc_icon( 'package', [ 'size' => 28, 'variant' => 'blue', 'class' => 'dmc-icon--mega' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span class="mega-subitem__label"><?php esc_html_e( 'Xem tất cả', 'flatsome-child' ); ?></span>
								</a>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>
