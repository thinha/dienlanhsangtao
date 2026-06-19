<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Phone_Order_Shipping_Package_Mod_Strategy implements WC_Phone_Order_Shipping_Package_Mod_Strategy_Interface {
	/**
	 * The temporary property which stores prices for custom shipping method with custom price.
	 *
	 * @var array
	 */
	protected $tmp_prices_shipping = array();

	public function process_custom_shipping( $cart_data ) {
		if ( ! isset( $cart_data['shipping']['packages'] ) ) {
			return;
		}

		$prices = array();
		foreach ( $cart_data['shipping']['packages'] as $package ) {
			if ( isset( $package['hash'], $package['chosen_rate']['cost'] ) ) {
				$prices[ $package['hash'] ] = $package['chosen_rate']['cost'];
			}
		}

		$this->tmp_prices_shipping = $prices;

		add_filter( "woocommerce_cart_shipping_packages", array( $this, "set_package_hashes" ), 10, 1 );
	}

	public function set_package_hashes( $packages ) {
		foreach ( $packages as &$package ) {
			$hash_key     = WC_Phone_Orders_Cart_Shipping_Processor::PACKAGE_HASH_KEY;
			$package_hash = WC_Phone_Orders_Cart_Shipping_Processor::calculate_package_hash( $package );
			$package[ $hash_key ] = $package_hash;
		}

		return $packages;
	}
}