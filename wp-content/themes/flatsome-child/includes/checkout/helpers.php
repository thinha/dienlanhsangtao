<?php
/**
 * Checkout — helpers.
 *
 * @package Flatsome_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether current request uses custom checkout layout.
 */
function dmc_is_checkout_layout() {
	return class_exists( 'WooCommerce' ) && is_checkout();
}

/**
 * Whether current request is the order received (thank you) page.
 */
function dmc_is_order_received_page() {
	if ( ! dmc_is_checkout_layout() ) {
		return false;
	}

	if ( function_exists( 'is_order_received_page' ) ) {
		return is_order_received_page();
	}

	return is_wc_endpoint_url( 'order-received' );
}

/**
 * Breadcrumb items for checkout page.
 *
 * @return array<int, array{label: string, url: string}>
 */
function dmc_checkout_breadcrumb_items() {
	$items = [
		[
			'label' => __( 'Trang chủ', 'flatsome-child' ),
			'url'   => home_url( '/' ),
		],
	];

	if ( class_exists( 'WooCommerce' ) ) {
		$cart_url = wc_get_cart_url();
		if ( $cart_url ) {
			$items[] = [
				'label' => __( 'Giỏ hàng', 'flatsome-child' ),
				'url'   => $cart_url,
			];
		}

		if ( dmc_is_order_received_page() ) {
			$checkout_url = wc_get_checkout_url();
			if ( $checkout_url ) {
				$items[] = [
					'label' => __( 'Thanh toán', 'flatsome-child' ),
					'url'   => $checkout_url,
				];
			}
		}
	}

	$items[] = [
		'label' => dmc_is_order_received_page()
			? __( 'Đặt hàng thành công', 'flatsome-child' )
			: __( 'Thanh toán', 'flatsome-child' ),
		'url'   => '',
	];

	return $items;
}

/**
 * Subtitle for order received page.
 */
function dmc_checkout_received_subtitle() {
	return __( 'Đơn hàng của bạn đã được ghi nhận. Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.', 'flatsome-child' );
}

/**
 * Action links shown on order received page.
 *
 * @param WC_Order $order Order.
 * @return array<int, array{label: string, url: string, class: string}>
 */
function dmc_checkout_received_actions( $order ) {
	$actions = [
		[
			'label' => __( 'Tiếp tục mua sắm', 'flatsome-child' ),
			'url'   => wc_get_page_permalink( 'shop' ) ?: home_url( '/' ),
			'class' => 'dmc-btn dmc-btn--primary',
		],
	];

	if ( is_user_logged_in() && $order instanceof WC_Order ) {
		$view_url = $order->get_view_order_url();
		if ( $view_url ) {
			$actions[] = [
				'label' => __( 'Xem đơn hàng', 'flatsome-child' ),
				'url'   => $view_url,
				'class' => 'dmc-btn dmc-btn--outline',
			];
		}
	}

	return $actions;
}

/**
 * Page title for checkout.
 */
function dmc_checkout_page_title() {
	if ( dmc_is_order_received_page() ) {
		return __( 'Đặt hàng thành công', 'flatsome-child' );
	}

	return __( 'Thanh toán', 'flatsome-child' );
}
