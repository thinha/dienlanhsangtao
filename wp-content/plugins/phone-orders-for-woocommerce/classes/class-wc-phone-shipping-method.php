<?php
// class loaded in ADMIN areay only 

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class WC_Phone_Shipping_Method extends WC_Shipping_Method {
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'phone_orders';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Free Shipping [Phone Orders]', 'phone-orders-for-woocommerce' );
		$this->method_description = __( 'Free Shipping in admin area only, for Phone Orders ',
			'phone-orders-for-woocommerce' );
		$this->supports           = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);
		$this->init();
	}

	function init() {
		// Load the settings API
		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option( 'title' );
		$this->cost  = 0;

		// Actions
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	function init_form_fields() {
		$this->instance_form_fields = array(
			'title' => array(
				'title'       => __( 'Title', 'phone-orders-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Title to be displayed', 'phone-orders-for-woocommerce' ),
				'default'     => __( 'Free Shipping [Phone Orders]', 'phone-orders-for-woocommerce' ),
			),
		);
	}

	public function calculate_shipping( $package = array() ) {
		$this->add_rate( array(
			'label'   => $this->title,
			'package' => $package,
			'cost'    => $this->cost,
		) );
	}
}
 
