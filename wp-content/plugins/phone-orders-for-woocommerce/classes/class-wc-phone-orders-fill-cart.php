<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


class WC_Phone_Orders_Fill_Cart {

    public static function fill_cart($cart_items) {
        WC()->cart->empty_cart();
		$added_products = array();
        
        $items = explode(",", $cart_items);
        foreach( $items as $item_line) {
			if( preg_match('#^\d+$#',$item_line) ) {
				$qty = 1;
				$product_id = $item_line;
			} elseif( preg_match('#^(\d+\.?\d*)x(\d+)$#',$item_line,$m) ) {
				$qty = $m[1];
				$product_id = $m[2];
			} else{
				continue;
			}	
			self::add_to_cart($product_id, $qty, $added_products );
        }
        if( $added_products )
			wc_add_to_cart_message( $added_products, true );
    }

    protected static function add_to_cart($product_id, $quantity, &$added_products ) {
		// based on WC_AJAX::add_cart 
		$product_id        = apply_filters( 'woocommerce_add_to_cart_product_id', absint( $product_id ) );
		$product           = wc_get_product( $product_id );
		$quantity          = empty( $quantity ) ? 1 : wc_stock_amount( $quantity );
		$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
		$variation_id      = 0;
		$variation         = array();

		if ( !$product ) {
			wc_add_notice( sprintf( __( 'Unknown product &quot;%d&quot;.', 'phone-orders-for-woocommerce'), $product_id), 'error');
			return ;
		}
		if ( !$quantity ) {
			wc_add_notice( sprintf( __( 'Can not add %d &quot;%s&quot; because it is not available.', 'phone-orders-for-woocommerce'), $quantity, $product->get_title()), 'error');
			return ;
		}

		if ( $product && 'variation' === $product->get_type() ) {
			$variation_id = $product_id;
			$product_id   = $product->get_parent_id();
			$variation    = $product->get_variation_attributes();
		}

		if ( $passed_validation ) {
			if( WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation ) )
				$added_products[$product_id] = $quantity;
		}
		//done
    }
}