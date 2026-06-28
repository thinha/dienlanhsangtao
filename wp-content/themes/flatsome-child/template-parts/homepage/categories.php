<?php
/**
 * Homepage — category icons row.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$categories = dmc_homepage_get_categories( 9 );
$shop_url   = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : '#';

$fallback = [
	[ 'icon' => 'tivi', 'label' => 'Tivi' ],
	[ 'icon' => 'tu-lanh', 'label' => 'Tủ lạnh' ],
	[ 'icon' => 'may-giat', 'label' => 'Máy giặt' ],
	[ 'icon' => 'dieu-hoa', 'label' => 'Điều hòa' ],
	[ 'icon' => 'gia-dung', 'label' => 'Gia dụng' ],
	[ 'icon' => 'dien-thoai', 'label' => 'Điện thoại' ],
	[ 'icon' => 'laptop', 'label' => 'Laptop' ],
	[ 'icon' => 'am-thanh', 'label' => 'Âm thanh' ],
	[ 'icon' => 'phu-kien', 'label' => 'Phụ kiện' ],
];
?>
<section class="card">
	<div class="category-row">
		<?php if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) : ?>
			<?php foreach ( $categories as $cat ) : ?>
				<a class="category" href="<?php echo esc_url( get_term_link( $cat ) ); ?>">
					<div class="category-icon"><?php echo dmc_homepage_term_icon_html( $cat ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php echo esc_html( $cat->name ); ?>
				</a>
			<?php endforeach; ?>
			<a class="category" href="<?php echo esc_url( $shop_url ); ?>">
				<div class="category-icon"><?php echo dmc_icon( 'grid', [ 'size' => 22, 'variant' => 'blue', 'class' => 'dmc-icon--category' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php esc_html_e( 'Xem thêm', 'flatsome-child' ); ?>
			</a>
		<?php else : ?>
			<?php foreach ( $fallback as $item ) : ?>
				<a class="category" href="<?php echo esc_url( $shop_url ); ?>">
					<div class="category-icon"><?php echo dmc_icon( $item['icon'], [ 'size' => 22, 'variant' => 'blue', 'class' => 'dmc-icon--category' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php echo esc_html( $item['label'] ); ?>
				</a>
			<?php endforeach; ?>
			<a class="category" href="<?php echo esc_url( $shop_url ); ?>">
				<div class="category-icon"><?php echo dmc_icon( 'grid', [ 'size' => 22, 'variant' => 'blue', 'class' => 'dmc-icon--category' ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php esc_html_e( 'Xem thêm', 'flatsome-child' ); ?>
			</a>
		<?php endif; ?>
	</div>
</section>
