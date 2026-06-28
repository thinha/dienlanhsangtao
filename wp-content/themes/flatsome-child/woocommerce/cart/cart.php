<?php
/**
 * Cart Page
 *
 * @package Flatsome_Child
 * @version 8.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>
<div class="dmc-cart-layout woocommerce">
	<div class="dmc-cart-items-col">
		<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
			<?php do_action( 'woocommerce_before_cart_table' ); ?>

			<div class="dmc-cart-items shop_table shop_table_responsive cart woocommerce-cart-form__contents">
				<div class="dmc-cart-items__head desktop-only">
					<span class="dmc-cart-items__col dmc-cart-items__col--product"><?php esc_html_e( 'Sản phẩm', 'flatsome-child' ); ?></span>
					<span class="dmc-cart-items__col dmc-cart-items__col--price"><?php esc_html_e( 'Đơn giá', 'flatsome-child' ); ?></span>
					<span class="dmc-cart-items__col dmc-cart-items__col--qty"><?php esc_html_e( 'Số lượng', 'flatsome-child' ); ?></span>
					<span class="dmc-cart-items__col dmc-cart-items__col--subtotal"><?php esc_html_e( 'Thành tiền', 'flatsome-child' ); ?></span>
				</div>

				<?php do_action( 'woocommerce_before_cart_contents' ); ?>

				<?php
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
					$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

					if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
						continue;
					}

					$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
					?>
					<div class="dmc-cart-item woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
						<div class="dmc-cart-item__product">
							<div class="dmc-cart-item__remove product-remove">
								<?php
								echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									'woocommerce_cart_item_remove_link',
									sprintf(
										'<a href="%s" class="remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
										esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
										esc_html__( 'Xóa sản phẩm', 'flatsome-child' ),
										esc_attr( $product_id ),
										esc_attr( $_product->get_sku() )
									),
									$cart_item_key
								);
								?>
							</div>

							<div class="dmc-cart-item__thumb product-thumbnail">
								<?php
								$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'woocommerce_thumbnail' ), $cart_item, $cart_item_key );

								if ( ! $product_permalink ) {
									echo $thumbnail; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								} else {
									printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								}
								?>
							</div>

							<div class="dmc-cart-item__info product-name" data-title="<?php esc_attr_e( 'Sản phẩm', 'flatsome-child' ); ?>">
								<?php
								if ( ! $product_permalink ) {
									echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) );
								} else {
									echo wp_kses_post(
										apply_filters(
											'woocommerce_cart_item_name',
											sprintf( '<a class="dmc-cart-item__name" href="%s">%s</a>', esc_url( $product_permalink ), $_product->get_name() ),
											$cart_item,
											$cart_item_key
										)
									);
								}

								do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );

								echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

								if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
									echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) );
								}
								?>

								<div class="dmc-cart-item__mobile-meta mobile-only">
									<span class="dmc-cart-item__mobile-price">
										<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</span>
								</div>
							</div>
						</div>

						<div class="dmc-cart-item__price product-price desktop-only" data-title="<?php esc_attr_e( 'Đơn giá', 'flatsome-child' ); ?>">
							<?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<div class="dmc-cart-item__qty product-quantity" data-title="<?php esc_attr_e( 'Số lượng', 'flatsome-child' ); ?>">
							<?php
							if ( $_product->is_sold_individually() ) {
								$product_quantity = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
							} else {
								$product_quantity = woocommerce_quantity_input(
									[
										'input_name'   => "cart[{$cart_item_key}][qty]",
										'input_value'  => $cart_item['quantity'],
										'max_value'    => $_product->get_max_purchase_quantity(),
										'min_value'    => '0',
										'product_name' => $_product->get_name(),
									],
									$_product,
									false
								);
							}

							echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							?>
						</div>

						<div class="dmc-cart-item__subtotal product-subtotal" data-title="<?php esc_attr_e( 'Thành tiền', 'flatsome-child' ); ?>">
							<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
					<?php
				}
				?>

				<?php do_action( 'woocommerce_cart_contents' ); ?>
			</div>

			<div class="dmc-cart-form-actions actions clear">
				<?php do_action( 'woocommerce_cart_actions' ); ?>

				<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
			</div>

			<?php do_action( 'woocommerce_after_cart_table' ); ?>
		</form>
	</div>

	<?php do_action( 'woocommerce_before_cart_collaterals' ); ?>

	<aside class="dmc-cart-sidebar cart-collaterals">
		<div class="dmc-cart-sidebar__inner cart-sidebar">
			<?php do_action( 'dmc_cart_voucher_box' ); ?>
			<?php do_action( 'dmc_cart_shipping_box' ); ?>
			<?php do_action( 'woocommerce_cart_collaterals' ); ?>
		</div>
	</aside>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
