<?php
/**
 * Loop Price
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/price.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;

if ( '' === $product->get_price() ) {
	$price = apply_filters( 'woocommerce_empty_price_html', '', $product );
} elseif ( $product->is_on_sale() ) {
	$price = custom_wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
} else {
	$price = wc_price( wc_get_price_to_display( $product ) ) . $product->get_price_suffix();
}

$price_html = apply_filters( 'woocommerce_get_price_html', $price, $product );

?>

<?php if ( $product->get_price_html() ) : ?>
	<span class="price"><?php echo $price_html; ?></span>
	<?php $promotion = get_field('promotion'); ?>
	<?php if( $promotion ) : ?>
		<span class="promotion"><?= $promotion; ?></span>
	<?php else: ?>
		<!-- <span class="promotion">Khách Đăng Ký  Online <br> Giảm Giá 200.000 đ </span> -->
	<?php endif; ?>
<?php endif; ?>
