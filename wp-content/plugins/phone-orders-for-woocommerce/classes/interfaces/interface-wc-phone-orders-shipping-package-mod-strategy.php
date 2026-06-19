<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

interface WC_Phone_Order_Shipping_Package_Mod_Strategy_Interface {
	/**
	 * @param array $cart_data
	 *
	 * @return null
	 */
	public function process_custom_shipping( $cart_data );
}