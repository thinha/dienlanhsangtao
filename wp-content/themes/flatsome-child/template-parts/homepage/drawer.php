<?php
/**
 * Homepage — mobile / tablet category drawer (split tabs + panels).
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mega_groups = dmc_homepage_mega_menu_data();
?>
<div class="drawer-backdrop" id="dmcDrawerBackdrop"></div>
<aside class="drawer drawer--category" id="dmcDrawer" aria-label="<?php esc_attr_e( 'Danh mục sản phẩm', 'flatsome-child' ); ?>">
	<div class="drawer-head">
		<h2 class="drawer-head__title"><?php esc_html_e( 'Danh mục sản phẩm', 'flatsome-child' ); ?></h2>
		<button type="button" class="drawer-close" id="dmcDrawerClose" aria-label="<?php esc_attr_e( 'Đóng menu', 'flatsome-child' ); ?>">✕</button>
	</div>

	<div class="drawer-layout">
		<aside class="drawer-sidebar">
			<ul class="drawer-sidebar__list">
				<?php foreach ( $mega_groups as $index => $group ) : ?>
					<li class="drawer-sidebar__item<?php echo 0 === $index ? ' is-active' : ''; ?>" data-drawer-index="<?php echo esc_attr( $index ); ?>">
						<button type="button" class="drawer-sidebar__btn">
							<span class="drawer-sidebar__label"><?php echo esc_html( $group['label'] ); ?></span>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</aside>

		<div class="drawer-panels">
			<?php foreach ( $mega_groups as $index => $group ) : ?>
				<div class="drawer-panel<?php echo 0 === $index ? ' is-active' : ''; ?>" data-drawer-index="<?php echo esc_attr( $index ); ?>">
					<div class="drawer-panel__head">
						<h3 class="drawer-panel__title"><?php echo esc_html( $group['label'] ); ?></h3>
						<a href="<?php echo esc_url( $group['url'] ); ?>" class="drawer-panel__more"><?php esc_html_e( 'Xem tất cả', 'flatsome-child' ); ?> &rsaquo;</a>
					</div>

					<?php if ( ! empty( $group['brands'] ) ) : ?>
						<div class="drawer-subgrid">
							<?php foreach ( $group['brands'] as $brand ) : ?>
								<a href="<?php echo esc_url( $brand['url'] ); ?>" class="drawer-subitem">
									<span class="drawer-subitem__icon"><?php echo $brand['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span class="drawer-subitem__label"><?php echo esc_html( $brand['label'] ); ?></span>
								</a>
							<?php endforeach; ?>
						</div>
					<?php elseif ( ! empty( $group['children'] ) ) : ?>
						<div class="drawer-subgrid">
							<?php foreach ( $group['children'] as $child ) : ?>
								<a href="<?php echo esc_url( $child['url'] ); ?>" class="drawer-subitem">
									<span class="drawer-subitem__icon"><?php echo $child['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
									<span class="drawer-subitem__label"><?php echo esc_html( $child['label'] ); ?></span>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</aside>
