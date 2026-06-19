<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Phone_Orders_Settings_Page extends WC_Phone_Orders_Admin_Abstract_Page {
	public $title;
	public $priority = 20;
	protected $tab_name = 'settings';

	protected $updater;

	public function __construct() {
		parent::__construct();
		$this->title = __( 'Settings', 'phone-orders-for-woocommerce' );
		$this->updater = new WC_Phone_Orders_Cart_Updater( $this->option_handler );
	}

	protected function make_order_statuses_list() {
		$order_statuses_list = array();

		foreach ( wc_get_order_statuses() as $i => $status ) {
			$order_statuses_list[] = array(
				'value' => $i,
				'title' => $status,
			);
		}

		return $order_statuses_list;
	}

	public function action() {
		/*if ( ! empty( $_POST ) ) {
			//cache ?
			$types = array('customers','products','orders','coupons');
			foreach($types as $type) {
				if( !isset($_POST['cache_' . $type . '_timeout'])  OR !$_POST['cache_' . $type . '_timeout'] ) {
					$_POST['cache_' . $type . '_session_key'] = 'no-cache';
				} else {
					if( $_POST['cache_' . $type . '_session_key'] == 'no-cache'  OR $_POST['cache_' . $type . '_reset'])
						$_POST['cache_' . $type . '_session_key'] = $this->generate_session_key();
				}
			}
			// update
			$this->option_handler->set_options( $_POST );
			wp_redirect( $_SERVER['HTTP_REFERER'] );
		}*/
	}

	public function render() {
		$this->tab_data = array(
			'submitButtonTitle'           => __( 'Save Changes', 'phone-orders-for-woocommerce' ),
			'requestSuccessResultMessage' => __( 'Settings have been updated', 'phone-orders-for-woocommerce' ),
			'requestErrorResultMessage'   => __( 'Settings have not been updated', 'phone-orders-for-woocommerce' ),
			'tabName'                     => 'settings',
			'isProVersion'                => WC_Phone_Orders_Loader::is_pro_version(),
			'needMoreSettings'            => array(
				'content' => sprintf(
					'<b>%s</b> <a href="https://algolplus.com/plugins/downloads/phone-orders-woocommerce-pro/" target=_blank> %s </a>', __( 'Need more settings?', 'phone-orders-for-woocommerce' ), __( 'Buy Pro version', 'phone-orders-for-woocommerce' )
				),
			),
			'baseSettings'                => array(
				'commonSettings'     => array(
					'title'                          => __( "Common", 'phone-orders-for-woocommerce' ),
					'autoRecalculateLabel'           => __( 'Automatically update Shipping/Taxes/Totals', 'phone-orders-for-woocommerce' ),
					'orderPaymentMethodLabel'        => __( 'Set payment method for created order', 'phone-orders-for-woocommerce' ),
					'orderStatusLabel'               => __( 'Set status for created order', 'phone-orders-for-woocommerce' ),

					'googleMapAPIKeyLabel'           => __( 'Google Map API Key', 'phone-orders-for-woocommerce' ),
					'validateMapAPIKeyLabel'         => __( 'Check', 'phone-orders-for-woocommerce' ),
					'validatedMapAPIKeySuccessTitle' => __( 'API Key is valid', 'phone-orders-for-woocommerce' ),
					'validatedMapAPIKeyErrorTitle'   => __( 'API Key is invalid', 'phone-orders-for-woocommerce' ),
					'googleMapAPIKeyLinkLabel'       => __( 'How to get api key', 'phone-orders-for-woocommerce' ),
					'switchCustomerInCartLabel'      => __( 'Switch customer during cart calculations', 'phone-orders-for-woocommerce' ),
					'switchCustomerInCartLabelTip'   => __( 'required by some pricing plugins', 'phone-orders-for-woocommerce' ),
					'disableOrderEmailsLabel'	     => __( "Don't send order emails", 'phone-orders-for-woocommerce' ),
					'noOptionsTitle'                 => __( 'List is empty.', 'phone-orders-for-woocommerce' ),

					'autoRecalculate'                => $this->option_handler->get_option( 'auto_recalculate' ),
					'orderPaymentMethod'             => $this->option_handler->get_option( 'order_payment_method' ),
					'orderStatus'                    => $this->option_handler->get_option( 'order_status' ),

					'googleMapAPIKey'                => $this->option_handler->get_option( 'google_map_api_key' ),
					'switchCustomerInCart'           => $this->option_handler->get_option( 'switch_customer_while_calc_cart' ),

					'orderPaymentMethodsList'        => $this->updater->make_order_payment_methods_list(),
					'orderStatusesList'              => $this->make_order_statuses_list(),
					'disableOrderEmails'		 => $this->option_handler->get_option( 'disable_order_emails' ),

					'addressValidationServiceAPIKeyLabel' => __( 'Address Validation Service API Key (USPS Username)', 'phone-orders-for-woocommerce' ),
					'addressValidationServiceAPIKey'      => $this->option_handler->get_option( 'address_validation_service_api_key' ),

					'addressValidationServiceUSPSLabel' => __( 'USPS', 'phone-orders-for-woocommerce' ),
					'addressValidationService'	    => $this->option_handler->get_option( 'address_validation_service' ),
				),
				'interfaceSettings'     => array(
					'title'                          => __( "Interface", 'phone-orders-for-woocommerce' ),
					'logShowRecordsDaysLabel'        => __( 'Show records for last X days in log', 'phone-orders-for-woocommerce' ),
					'dontClosePopupClickOutsideLabel' => __( "Don't close popup on click outside", 'phone-orders-for-woocommerce' ),
					'collapseWpMenuLabel'            => __( "Collapse WordPress menu", 'phone-orders-for-woocommerce' ),


					'logShowRecordsDays'             => $this->option_handler->get_option( 'log_show_records_days' ),
					'dontClosePopupClickOutside'	 => $this->option_handler->get_option( 'dont_close_popup_click_outside' ),
					'collapseWpMenu'	             => $this->option_handler->get_option( 'collapse_wp_menu' ),

				),
				'layoutSettings'     => array(
					'title'                          => __( "Layout", 'phone-orders-for-woocommerce' ),
					'showOrderDateTimeLabel'         => __( 'Show order date/time', 'phone-orders-for-woocommerce' ),
					'showOrderStatusLabel'		 => __( 'Show order status', 'phone-orders-for-woocommerce' ),
					'showPaymentMethodsLabel'   	 => __( 'Show payment method', 'phone-orders-for-woocommerce' ),
					'orderFieldsPositionLabel'		 => __( 'Order fields position', 'phone-orders-for-woocommerce'),
					'orderFieldsPositionAboveCustomerDetailsLabel' => __( 'above customer details', 'phone-orders-for-woocommerce'),
					'orderFieldsPositionBelowCustomerDetailsLabel' => __( 'below customer details', 'phone-orders-for-woocommerce'),

					'showOrderDateTime'		=> $this->option_handler->get_option( 'show_order_date_time' ),
					'showOrderStatus'		=> $this->option_handler->get_option( 'show_order_status' ),
					'showPaymentMethods'		=> $this->option_handler->get_option( 'show_payment_methods' ),
					'orderFieldsPosition'		=> $this->option_handler->get_option( 'order_fields_position'),
				),
				'cartItemsSettings'     => array(
					'title'                          => __( "Cart Items", 'phone-orders-for-woocommerce' ),
					'showCartLinkLabel'              => __( 'Show button "Copy url to populate cart"', 'phone-orders-for-woocommerce' ),
					'showCartLinkNote'               => __( 'warning : this feature is not compatible with discounts', 'phone-orders-for-woocommerce' ),
					'scrollableCartContentsLabel'	 => __( 'Scrollable cart contents', 'phone-orders-for-woocommerce'),
					'allowToInputFractionalQtyLabel' => __( 'Allow to input fractional qty', 'phone-orders-for-woocommerce' ),

					'showCartLink'                   => $this->option_handler->get_option( 'show_cart_link' ),
					'scrollableCartContents'	 => $this->option_handler->get_option( 'scrollable_cart_contents'),
					'allowToInputFractionalQty'	 => $this->option_handler->get_option( 'allow_to_input_fractional_qty' ),
					'showColumnDiscountLabel'	 => __( 'Show column "Discount"', 'phone-orders-for-woocommerce'),
					'showColumnDiscount'		 => $this->option_handler->get_option( 'show_column_discount'),
				),
				'woocommerceSettings'     => array(
					'title'                          => __( "WooCommerce", 'phone-orders-for-woocommerce' ),
					'showIconInOrdersListLabel'	 => __( 'Show icon for phone orders in orders list', 'phone-orders-for-woocommerce' ),
					'showIconInOrdersList'		 => $this->option_handler->get_option( 'show_icon_in_orders_list' ),
				),
				'taxSettings' => array(
					'title'                       => __( "Tax", 'phone-orders-for-woocommerce' ),
					'showTaxTotalsLabel'          => __( 'Show detailed taxes', 'phone-orders-for-woocommerce' ),
					'hideTaxLineProductItemLabel' => __( "Hide tax line for item", 'phone-orders-for-woocommerce' ),
					'showTaxTotals'               => $this->option_handler->get_option( 'show_tax_totals' ),
					'hideTaxLineProductItem'      => $this->option_handler->get_option( 'hide_tax_line_product_item' ),
				),
				'couponsSettings'    => array(
					'title'                             => __( "Coupons", 'phone-orders-for-woocommerce' ),
					'cacheCouponSearchResultHoursLabel' => __( 'Caching search results', 'phone-orders-for-woocommerce' ),
					'hoursLabel'                        => __( "hours", 'phone-orders-for-woocommerce' ),
					'cacheCouponsDisableButtonLabel'    => __( "Disable cache", 'phone-orders-for-woocommerce' ),
					'cacheCouponsResetButtonLabel'      => __( "Reset cache", 'phone-orders-for-woocommerce' ),

					'cacheCouponsSessionKey'            => $this->option_handler->get_option( 'cache_coupons_session_key' ),
					'cacheCouponsTimeout'               => (int) $this->option_handler->get_option( 'cache_coupons_timeout' ),
				),
				'referencesSettings' => array(
					'title'                             => __( "References", 'phone-orders-for-woocommerce' ),
					'cacheReferencesHoursLabel'         => __( 'Caching locations/categories/tags', 'phone-orders-for-woocommerce' ),
					'hoursLabel'                        => __( "hours", 'phone-orders-for-woocommerce' ),
					'cacheReferencesDisableButtonLabel' => __( "Disable cache", 'phone-orders-for-woocommerce' ),
					'cacheReferencesResetButtonLabel'   => __( "Reset cache", 'phone-orders-for-woocommerce' ),

					'cacheReferencesSessionKey'         => $this->option_handler->get_option( 'cache_references_session_key' ),
					'cacheReferencesTimeout'            => (int) $this->option_handler->get_option( 'cache_references_timeout' ),
				),
				'shippingSettings' => array(
					'title'                             => __( "Shipping", 'phone-orders-for-woocommerce' ),
					'allowEditShippingCostLabel'        => __( 'Allow to edit shipping cost', 'phone-orders-for-woocommerce' ),

					'allowEditShippingCost'             => $this->option_handler->get_option( 'allow_edit_shipping_cost' ),
					'allowEditShippingTitleLabel'       => __( 'Allow to edit shipping title', 'phone-orders-for-woocommerce' ),

					'allowEditShippingTitle'            => $this->option_handler->get_option( 'allow_edit_shipping_title' ),
					'orderDefaultShippingMethodLabel'   => __( 'Default shipping method', 'phone-orders-for-woocommerce' ),
					'orderShippingZonesList'	    => $this->make_order_shipping_zones_list(),
					'orderDefaultZonesShippingMethod'   => $this->option_handler->get_option( 'order_default_zones_shipping_method' ),

					'allowToCreateOrdersWithoutShippingLabel' => __( 'Allow to create orders without shipping', 'phone-orders-for-woocommerce' ),
					'allowToCreateOrdersWithoutShipping'      => $this->option_handler->get_option( 'allow_to_create_orders_without_shipping' ),
				),
			),
		);
                ?>
		<tab-settings v-bind="<?php echo esc_attr( json_encode($this->tab_data) ) ?>">
                    <base-settings slot="base-settings">
			<common-settings
			    v-bind="<?php echo esc_attr( json_encode($this->tab_data['baseSettings']['commonSettings']) ) ?>"
			    slot="common-settings"
			>
			</common-settings>

            <interface-settings
                    v-bind="<?php echo esc_attr( json_encode($this->tab_data['baseSettings']['interfaceSettings']) ) ?>"
                    slot="interface-settings"
            >
	            <?php do_action( 'wpo_add_interface_settings' ) ?>
            </interface-settings>

            <woocommerce-settings
                    v-bind="<?php echo esc_attr( json_encode($this->tab_data['baseSettings']['woocommerceSettings']) ) ?>"
                    slot="woocommerce-settings"
            >
	            <?php do_action( 'wpo_add_woocommerce_settings' ) ?>
            </woocommerce-settings>

            <tax-settings
                    v-bind="<?php echo esc_attr( json_encode($this->tab_data['baseSettings']['taxSettings']) ) ?>"
                    slot="tax-settings"
            >
                <?php do_action( 'wpo_add_tax_settings' ) ?>
            </tax-settings>

            <layout-settings
                    v-bind="<?php echo esc_attr( json_encode($this->tab_data['baseSettings']['layoutSettings']) ) ?>"
                    slot="layout-settings"
            >
                <?php do_action( 'wpo_add_layout_settings' ) ?>
            </layout-settings>

            <coupons-settings
			    v-bind="<?php echo esc_attr( json_encode($this->tab_data['baseSettings']['couponsSettings']) ) ?>"
			    slot="coupons-settings"
			>
	            <?php do_action( 'wpo_add_coupons_settings' ); ?>
            </coupons-settings>
			<references-settings
			    v-bind="<?php echo esc_attr( json_encode($this->tab_data['baseSettings']['referencesSettings']) ) ?>"
			    slot="references-settings"
			></references-settings>
            <shipping-settings
                    v-bind="<?php echo esc_attr( json_encode($this->tab_data['baseSettings']['shippingSettings']) ) ?>"
                    slot="shipping-settings"
            >
                <?php do_action( 'wpo_add_shipping_settings' ); ?>
            </shipping-settings>

            <cart-items-settings
                    v-bind="<?php echo esc_attr( json_encode($this->tab_data['baseSettings']['cartItemsSettings']) ) ?>"
                    slot="cart-items-settings"
            >
                <?php do_action( 'wpo_add_cart_items_settings' ); ?>
            </cart-items-settings>

		    </base-settings>
		    <?php do_action( 'wpo_add_settings' ) ?>
                </tab-settings>
		<?php
	}

	private function generate_session_key() {
		return md5( time(). mt_rand(1,100000) );
	}

        protected function ajax_save_settings($request) {

            $types    = array('customers', 'products', 'orders', 'coupons', 'references' );
            $settings = isset($request['settings']) ? json_decode(stripslashes($request['settings']), true) : array();

            foreach($types as $type) {
                if( !isset($settings['cache_' . $type . '_timeout'])  OR !$settings['cache_' . $type . '_timeout'] ) {
                        $settings['cache_' . $type . '_session_key'] = 'no-cache';
                } else {
                        if( $settings['cache_' . $type . '_session_key'] == 'no-cache'  OR $settings['cache_' . $type . '_reset'])
                                $settings['cache_' . $type . '_session_key'] = $this->generate_session_key();
                }
            }

	    $settings = apply_filters('wpo_ajax_save_settings', $settings);

            // update
            $this->option_handler->set_options( $settings );

            return $this->wpo_send_json_success(array(
                'settings' => $this->option_handler->get_all_options(),
            ));
        }
}