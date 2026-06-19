<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Phone_Orders_Shipping_Rate_Mod {
	/**
	 * @var float
	 */
	private $cost;

	/**
	 * @var boolean
	 */
	private $enable_cost_mod  = false;
	private $enable_title_mod = false;

	/**
	 * @var string
	 */
	private $label;

	/**
	 * @var boolean
	 */
	private $is_vat_exempt;

	private $rate_id;
	private $package;

	/**
	 * WC_Phone_Orders_Shipping_Rate_Mod constructor.
	 *
	 * @param string $shipping_rate_id
	 * @param array $package
	 */
	public function __construct( $shipping_rate_id, $package ) {
		$this->rate_id = $shipping_rate_id;
		$this->package = $package;

		$this->cost  = floatval( 0 );
		$this->title = "";
	}

	public function install_hooks() {
		add_filter( 'woocommerce_package_rates', array( $this, "hook_process_rates" ), 10, 2 );
	}

	public function remove_hooks() {
		remove_filter( 'woocommerce_package_rates', array( $this, "hook_process_rates" ), 10 );
	}

	/**
	 * @param WC_Shipping_Rate[] $rates
	 * @param array              $package
	 *
	 * @return WC_Shipping_Rate[]
	 */
	public function hook_process_rates( $rates, $package ) {
		if ( ! WC_Phone_Orders_Cart_Shipping_Processor::are_packages_equal( $package, $this->package ) ) {
			return $rates;
		}

		if ( ! isset( $rates[ $this->rate_id ] ) ) {
			return $rates;
		}

		$chosen_rate = &$rates[ $this->rate_id ];

		if ( $this->enable_cost_mod ) {
			if ( ! $this->is_free_shipping( $chosen_rate ) && ! $this->is_custom_price_shipping_method( $chosen_rate ) ) {
				$chosen_rate->set_cost( $this->cost );

				$taxes = array();
				if ( ! $this->is_vat_exempt ) {
					$taxes = WC_Tax::calc_shipping_tax( $this->cost, WC_Tax::get_shipping_tax_rates() );
				}
				$chosen_rate->set_taxes( $taxes );
			} else {
				$this->set_is_enabled_cost_mod( false );
			}
		}

		if ( $this->label ) {
			$chosen_rate->set_label( $this->label );
		}

		return $rates;
	}

	/**
	 * @param string $label
	 */
	public function set_label( $label ) {
		if ( ! empty( $label ) ) {
			$this->label = $label;
		}
	}

	/**
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * @param integer|float|string $cost
	 */
	public function set_cost( $cost ) {
		$this->cost = floatval( $cost );
	}

	/**
	 * @param boolean $enabled
	 */
	public function set_is_enabled_cost_mod( $enabled ) {
		$this->enable_cost_mod = boolval( $enabled );
	}

	public function set_is_enabled_title_mod( $enabled ) {
		$this->enable_title_mod = boolval( $enabled );
	}

	/**
	 * @return boolean
	 */
	public function is_enabled_cost_mod() {
		return $this->enable_cost_mod;
	}

	public function is_enabled_title_mod() {
		return $this->enable_title_mod;
	}

	/**
	 * @return float
	 */
	public function get_cost() {
		return $this->cost;
	}

	/**
	 * @return array
	 */
	public function get_package() {
		return $this->package;
	}

	/**
	 * @param boolean $is_vat_exempt
	 */
	public function set_is_vat_exempt( $is_vat_exempt ) {
		$this->is_vat_exempt = $is_vat_exempt;
	}

	/**
	 * @return string
	 */
	protected function get_shipping_method_id() {
		$chosen_method = explode( ':', $this->rate_id );

		return is_array( $chosen_method ) ? current( $chosen_method ) : null;
	}

	public static function make_active_scheme( $cost ) {
		return array(
			"enabled" => true,
			"cost"   => $cost,
		);
	}

	public static function make_inactive_scheme( $cost ) {
		return array(
			"enabled" => false,
			"cost"   => $cost,
		);
	}

	public static function make_title_active_scheme( $title ) {
		return array(
			"enabled" => true,
			"title"   => $title,
		);
	}

	public static function make_title_inactive_scheme( $title ) {
		return array(
			"enabled" => false,
			"title"   => $title,
		);
	}

	/**
	 * @param $rate WC_Shipping_Rate
	 *
	 * @return bool
	 */
	private function is_custom_price_shipping_method( $rate ) {
		return preg_match( '/^phone_orders_custom_price:\d+$/', $rate->get_id() );
	}

	/**
	 * @param $rate WC_Shipping_Rate
	 *
	 * @return bool
	 */
	private function is_free_shipping( $rate ) {
		return isset( $rate->cost ) ? 0.00 === (float) $rate->cost : false;
	}
}