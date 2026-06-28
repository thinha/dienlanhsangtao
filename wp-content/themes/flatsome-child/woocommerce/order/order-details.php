<?php
/**
 * Order details
 *
 * @package Flatsome_Child
 * @version 10.9.0
 *
 * @var bool $show_downloads Controls whether the downloads table should be rendered.
 */

defined( 'ABSPATH' ) || exit;

$order = wc_get_order( $order_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

if ( ! $order ) {
	return;
}

$order_items        = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$show_purchase_note = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', [ 'completed', 'processing' ] ) );
$downloads          = $order->get_downloadable_items();
$actions            = array_filter(
	wc_get_account_orders_actions( $order ),
	static function ( $key ) {
		return 'view' !== $key;
	},
	ARRAY_FILTER_USE_KEY
);

$show_customer_details = $order->get_user_id() === get_current_user_id();

if ( $show_downloads ) {
	wc_get_template(
		'order/order-downloads.php',
		[
			'downloads'  => $downloads,
			'show_title' => true,
		]
	);
}
?>
<section class="woocommerce-order-details dmc-order-details">
	<?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>

	<h2 class="woocommerce-order-details__title dmc-order-details__title">
		<?php esc_html_e( 'Chi tiết đơn hàng', 'flatsome-child' ); ?>
	</h2>

	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details dmc-order-details__table">

		<thead>
			<tr>
				<th class="woocommerce-table__product-name product-name"><?php esc_html_e( 'Sản phẩm', 'flatsome-child' ); ?></th>
				<th class="woocommerce-table__product-table product-total"><?php esc_html_e( 'Thành tiền', 'flatsome-child' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
			do_action( 'woocommerce_order_details_before_order_table_items', $order );

			foreach ( $order_items as $item_id => $item ) {
				$product = $item->get_product();

				wc_get_template(
					'order/order-details-item.php',
					[
						'order'              => $order,
						'item_id'            => $item_id,
						'item'               => $item,
						'show_purchase_note' => $show_purchase_note,
						'purchase_note'      => $product ? $product->get_purchase_note() : '',
						'product'            => $product,
					]
				);
			}

			do_action( 'woocommerce_order_details_after_order_table_items', $order );
			?>
		</tbody>

		<tfoot>
			<?php foreach ( $order->get_order_item_totals() as $key => $total ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
					<td><?php echo wp_kses_post( $total['value'] ); ?></td>
				</tr>
			<?php endforeach; ?>

			<?php if ( $order->get_customer_note() ) : ?>
				<tr>
					<th><?php esc_html_e( 'Ghi chú', 'flatsome-child' ); ?></th>
					<td>
						<?php
						$customer_note = wc_wptexturize_order_note( $order->get_customer_note() );
						echo wp_kses( nl2br( $customer_note ), [ 'br' => [] ] );
						?>
					</td>
				</tr>
			<?php endif; ?>

			<?php if ( ! empty( $actions ) ) : ?>
				<tr>
					<th class="order-actions--heading"><?php esc_html_e( 'Thao tác', 'flatsome-child' ); ?></th>
					<td>
						<?php
						$wp_button_class = wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '';
						foreach ( $actions as $key => $action ) {
							if ( empty( $action['aria-label'] ) ) {
								$action_aria_label = sprintf(
									/* translators: %1$s Action name, %2$s Order number. */
									__( '%1$s order number %2$s', 'woocommerce' ),
									$action['name'],
									$order->get_order_number()
								);
							} else {
								$action_aria_label = $action['aria-label'];
							}
							echo '<a href="' . esc_url( $action['url'] ) . '" class="dmc-btn dmc-btn--outline woocommerce-button' . esc_attr( $wp_button_class ) . ' button ' . sanitize_html_class( $key ) . ' order-actions-button" aria-label="' . esc_attr( $action_aria_label ) . '">' . esc_html( $action['name'] ) . '</a>';
							unset( $action_aria_label );
						}
						?>
					</td>
				</tr>
			<?php endif; ?>
		</tfoot>
	</table>

	<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
</section>

<?php
do_action( 'woocommerce_after_order_details', $order );

if ( $show_customer_details ) {
	wc_get_template( 'order/order-details-customer.php', [ 'order' => $order ] );
}
