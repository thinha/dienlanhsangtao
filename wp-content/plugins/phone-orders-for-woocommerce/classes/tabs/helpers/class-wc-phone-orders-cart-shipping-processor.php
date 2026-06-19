<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Phone_Orders_Cart_Shipping_Processor {
	const METHOD_SELECTED_BY_WC = '';
	const METHOD_NOT_SELECTED = 'empty';

	const PACKAGE_HASH_KEY = "wpo_package_hash";
	const ORDER_SHIPPING_ITEM_HASH_KEY = "wpo_package_hash";

	/**
	 * @var WC_Phone_Orders_Settings
	 */
	protected $option_handler;

	protected $chosen_methods = array();

	protected $shipping_package_mod_strategy;

	/**
	 * @var WC_Phone_Orders_Shipping_Rate_Mod[]
	 */
	protected $shipping_rate_mods;

	/**
	 * WC_Phone_Orders_Cart_Shipping_Processor constructor.
	 *
	 * @param WC_Phone_Orders_Settings $options
	 */
	public function __construct( $options ) {
		$this->option_handler = $options;
		$this->shipping_package_mod_strategy = WC_Phone_Orders_Loader::get_shipping_package_mod_strategy();
	}

	protected static function get_rate_mod_class_name() {
		return "WC_Phone_Orders_Shipping_Rate_Mod";
	}

	public static function purge_packages_from_session() {
		foreach ( WC()->shipping()->get_packages() as $index => $value ) {
			WC()->session->set( 'shipping_for_package_' . $index, '' );
		}
	}

	/**
	 * @param WC_Order                 $order
	 * @param WC_Phone_Orders_Settings $option_handler
	 * @param boolean                  $is_edit
	 *
	 * @return array|null
	 */
	public static function make_shipping_from_order( $order, $option_handler, $is_edit = false ) {
		$packages = array();

		foreach ( $order->get_shipping_methods() as $item_shipping ) {
			if ( method_exists( $item_shipping, "get_instance_id" ) ) {
				//since WC 3.4
				$shipping_id = $item_shipping->get_method_id() . ':' . $item_shipping->get_instance_id();
			} else {
				$shipping_id = $item_shipping->get_method_id();
			}

			$hash = $item_shipping->get_meta( self::ORDER_SHIPPING_ITEM_HASH_KEY, true );

			if ( ! $option_handler->get_option( 'set_current_price_shipping_in_copied_order' ) || $is_edit ) {
				$custom_price = call_user_func( array( self::get_rate_mod_class_name(), "make_active_scheme" ), floatval( $item_shipping->get_total() ) );
			} else {
				$custom_price = call_user_func( array( self::get_rate_mod_class_name(), "make_inactive_scheme" ), floatval( $item_shipping->get_total() ) );
			}

			$packages[] = array(
				'hash'         => apply_filters( "wpo_package_hash_from_order", $hash, $item_shipping, $order ),
				'chosen_rate'  => array(
					"id"        => $shipping_id,
					"label"     => $is_edit ? $item_shipping->get_name() : null,
					'cost'      => floatval( $item_shipping->get_total() ),
					'tax'       => floatval( $item_shipping->get_total_tax() ),
					'full_cost' => floatval( $item_shipping->get_total() ) + floatval( $item_shipping->get_total_tax() ),
				),
				'rates'        => array(),

				'custom_price' => $custom_price,
			);
		}

		return array(
			'packages' => $packages,
		);
	}

	/**
	 * @param array $cart_data
	 * @param array $package
	 *
	 * @return array
	 */
	protected static function fetch_package_from_posted_cart_data( $cart_data, $package ) {
		$hash = self::calculate_package_hash( $package );

		if ( isset( $cart_data['shipping']['packages'] ) && is_array( $cart_data['shipping']['packages'] ) ) {
			foreach ( $cart_data['shipping']['packages'] as $shipping_package ) {
				if ( ! isset( $shipping_package['hash'] ) ) {
					continue;
				}

				/**
				 We got a problem with shipping during loading order.
				 It is not possible to calculate hashes of the packages, because cart does not exist.

				 After this commit, we store package hash in WC_Order_Item_Shipping meta data, so it is possible to find selected method.
				 But what to do with orders created before?
				 For orders with more than one package, we use filter "wpo_package_hash_from_order", otherwise "hash" equals "",
				 it is needed for orders created earlier to work as before.

				 * @see WC_Phone_Orders_Cart_Shipping_Processor::make_shipping_from_order($order)
				 */
				if ( "" === $shipping_package['hash'] ) {
					return $shipping_package;
				}

				if ( $hash === $shipping_package['hash'] ) {
					return $shipping_package;
				}
			}
		}

		return null;
	}

	/**
	 * @param array $package
	 *
	 * @return string|null
	 */
	public static function calculate_package_hash( $package ) {
		$third_party_calc_hash = apply_filters( 'wpo_calculate_package_hash', null, $package );
		if ( isset( $third_party_calc_hash ) ) {
			return $third_party_calc_hash;
		}

		if ( ! isset( $package['contents'] ) ) {
			return null;
		}

		$contents = array();
		foreach ( $package['contents'] as $cart_item ) {
			if ( ! isset( $cart_item['product_id'], $cart_item['variation_id'], $cart_item['variation'] ) ) {
				return null;
			}

			$contents[] = array( $cart_item['product_id'], $cart_item['variation_id'], $cart_item['variation'] );
		}

		return md5( json_encode( $contents ) );
	}

	/**
	 * This is the main method.
	 * Be advised, use the priority described below (starts with the most important)
	 *      Method selected by user at frontend -> Default method for zone (from settings) -> WooCommerce method
	 *
	 *
	 * @param array   $wc_chosen_shipping_methods Same as WC()->session->get( 'chosen_shipping_methods' )
	 * @param array   $shipping_packages          Same as WC()->cart->get_shipping_packages()
	 * @param array   $cart_data                  Frontend data
	 * @param boolean $customer_is_vat_exempt     Same as WC()->customer->is_vat_exempt()
	 */
	public function prepare_shipping( $wc_chosen_shipping_methods, $shipping_packages, $cart_data, $customer_is_vat_exempt ) {
		$chosen_methods = array();

		foreach ( $shipping_packages as $package_key => $package ) {
			$chains = array(
				array( array( $this, "get_selected_method" ), array( $package, $cart_data ) ),
				array(
					array( $this, "get_chosen_shipping_method_for_package" ),
					array( $package_key, $package, $wc_chosen_shipping_methods, $cart_data )
				),
				array(
					array( $this, "get_calculated_method_by_wc" ),
					array( $package_key, $wc_chosen_shipping_methods )
				),
			);

			$chosen_method = null;
			foreach ( $chains as $link ) {
				$chosen_method = call_user_func_array( $link[0], $link[1] );
				if ( isset( $chosen_method ) ) {
					break;
				}
			}

			$chosen_methods[ $package_key ] = $chosen_method;

			$package_hash      = self::calculate_package_hash( $package );

			$shipping_rate_mod_class_name = self::get_rate_mod_class_name();
			$shipping_rate_mod = new $shipping_rate_mod_class_name( $chosen_method, $package );
			/**
			 * @var WC_Phone_Orders_Shipping_Rate_Mod $shipping_rate_mod
			 */
			$shipping_rate_mod->set_is_vat_exempt( $customer_is_vat_exempt );
			self::apply_posted_cart_data( $shipping_rate_mod, $cart_data );
			$shipping_rate_mod->install_hooks();

			$this->shipping_rate_mods[ $package_hash ] = $shipping_rate_mod;
		}

		$this->chosen_methods = $chosen_methods;
	}


	/**
	 * @param WC_Phone_Orders_Shipping_Rate_Mod $shipping_rate_mod
	 * @param array $cart_data
	 */
	protected static function apply_posted_cart_data( &$shipping_rate_mod, $cart_data ) {
		$cart_data_package = self::fetch_package_from_posted_cart_data( $cart_data, $shipping_rate_mod->get_package() );

		$custom_price = isset( $cart_data_package['custom_price'] ) ? $cart_data_package['custom_price'] : array();
		if ( isset( $custom_price['cost'], $custom_price['enabled'] ) ) {
			$enabled = boolval( $custom_price['enabled'] );
			$cost    = $custom_price['cost'];

			$shipping_rate_mod->set_is_enabled_cost_mod( $enabled );
			if ( $enabled ) {
				$shipping_rate_mod->set_cost( $cost );
			}
		}

		$label = isset( $cart_data_package['chosen_rate']['label'] ) ? $cart_data_package['chosen_rate']['label'] : null;
		$shipping_rate_mod->set_label( $label );

		$custom_title = isset( $cart_data_package['custom_title'] ) ? $cart_data_package['custom_title'] : array();
		if ( isset( $custom_title['title'], $custom_title['enabled'] ) ) {
			$enabled = boolval( $custom_title['enabled'] );
			$title	 = $custom_title['title'];

			$shipping_rate_mod->set_is_enabled_title_mod( $enabled );
			if ( $enabled ) {
				$shipping_rate_mod->set_label( $title );
			}
		}
	}

	/**
	 * @return array
	 */
	public function get_chosen_methods() {
		return $this->chosen_methods;
	}

	/**
	 * @param array $package
	 * @param array $cart_data
	 *
	 * @return string|null
	 */
	protected function get_selected_method( $package, $cart_data ) {
		$shipping_package = self::fetch_package_from_posted_cart_data( $cart_data, $package );
		if ( ! isset( $shipping_package ) ) {
			return null;
		}

		return isset( $shipping_package['chosen_rate']['id'] ) ? $shipping_package['chosen_rate']['id'] : null;
	}

	/**
	 * @param integer $package_key
	 * @param array   $package
	 * @param array   $wc_chosen_shipping_methods
	 * @param array   $cart_data
	 *
	 * @return string|null
	 */
	protected function get_chosen_shipping_method_for_package( $package_key, $package, $wc_chosen_shipping_methods, $cart_data ) {
		$shipping_zone = self::get_shipping_zone_from_package( $package );
		$zone_id       = isset( $shipping_zone ) ? $shipping_zone->get_id() : null;

		$default_zones_shipping_method = $this->option_handler->get_option( 'order_default_zones_shipping_method' );

		$default_shipping_method_id = isset( $default_zones_shipping_method[ $zone_id ] ) ? $default_zones_shipping_method[ $zone_id ] : null;

		$customer_id = isset($cart_data['customer']['id']) ? $cart_data['customer']['id'] : 0;

		$default_shipping_method_id = apply_filters('wpo_get_default_shipping_method_id', $default_shipping_method_id, $customer_id);

		if ( isset( $default_shipping_method_id ) ) {
			if ( $default_shipping_method_id === $this::METHOD_SELECTED_BY_WC ) {
				return $this->get_calculated_method_by_wc( $package_key, $wc_chosen_shipping_methods );
			} elseif ( $default_shipping_method_id === $this::METHOD_NOT_SELECTED ) {
				/**
				 * @see WC_Phone_Orders_Cart_Shipping_Processor::enable_preventing_to_select_method_for_certain_packages
				 */
				return $default_shipping_method_id;
			}
		}

		return $default_shipping_method_id;
	}

	public static function enable_preventing_to_select_method_for_certain_packages() {
		add_filter( "woocommerce_shipping_chosen_method", function ( $default, $rates, $chosen_method ) {
			/**
			 * check if FALSE too!
			 * because of
			 * $chosen_method  = isset( $chosen_methods[ $key ] ) ? $chosen_methods[ $key ] : false;
			 *
			 * @see wc_get_chosen_shipping_method_for_package()
			 * @see wc_get_default_shipping_method_for_package()
			 */

			if ( isset( $chosen_method ) && ( $chosen_method === self::METHOD_NOT_SELECTED || $chosen_method === false ) ) {
				return false;
			}

			return $default;
		}, 10, 3 );
	}

	/**
	 * @param integer $package_key
	 * @param array   $wc_chosen_shipping_methods
	 *
	 * @return string|null
	 */
	protected function get_calculated_method_by_wc( $package_key, $wc_chosen_shipping_methods ) {
		return isset( $wc_chosen_shipping_methods[ $package_key ] ) ? $wc_chosen_shipping_methods[ $package_key ] : null;
	}

	/**
	 * @param array $package
	 *
	 * @return WC_Shipping_Zone|null
	 */
	protected static function get_shipping_zone_from_package( $package ) {
		return function_exists( "wc_get_shipping_zone" ) ? wc_get_shipping_zone( $package ) : null;
	}

	public static function are_packages_equal( $a_package, $b_package ) {
		$a_hash = self::calculate_package_hash( $a_package );
		$b_hash = self::calculate_package_hash( $b_package );

		return isset( $a_hash, $b_hash ) && $a_hash === $b_hash;
	}

	public function process_custom_shipping( $cart_data ) {
		$this->shipping_package_mod_strategy->process_custom_shipping( $cart_data );
	}

	/**
	 * @param array $package
	 *
	 * @return array
	 */
	public function get_custom_price_data_for_package( $package ) {
		$data         = call_user_func( array( self::get_rate_mod_class_name(), "make_inactive_scheme" ), floatval( 0 ) );
		$package_hash = self::calculate_package_hash( $package );

		if ( isset( $this->shipping_rate_mods[ $package_hash ] ) ) {
			$shipping_rate_mod = $this->shipping_rate_mods[ $package_hash ];
			if ( $shipping_rate_mod->is_enabled_cost_mod() ) {
				$data = $shipping_rate_mod::make_active_scheme( $shipping_rate_mod->get_cost() );
			} else {
				$data = $shipping_rate_mod::make_inactive_scheme( $shipping_rate_mod->get_cost() );
			}
		}

		return $data;
	}

	public function get_custom_title_data_for_package( $package ) {
		$data         = call_user_func( array( self::get_rate_mod_class_name(), "make_title_inactive_scheme" ), '' );
		$package_hash = self::calculate_package_hash( $package );

		if ( isset( $this->shipping_rate_mods[ $package_hash ] ) ) {
			$shipping_rate_mod = $this->shipping_rate_mods[ $package_hash ];
			if ( $shipping_rate_mod->is_enabled_title_mod() ) {
				$data = $shipping_rate_mod::make_title_active_scheme( $shipping_rate_mod->get_label() );
			} else {
				$data = $shipping_rate_mod::make_title_inactive_scheme( $shipping_rate_mod->get_label() );
			}
		}

		return $data;
	}
}