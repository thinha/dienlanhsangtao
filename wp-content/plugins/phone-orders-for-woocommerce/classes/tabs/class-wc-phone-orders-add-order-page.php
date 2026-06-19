<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Phone_Orders_Add_Order_Page extends WC_Phone_Orders_Admin_Abstract_Page {
	public $title;
	public $priority = 10;
	protected $tab_name = 'add-order';

	protected $meta_key_private_note;
	protected $meta_key_order_creator;

	/**
	 * @var WC_Phone_Orders_Custom_Products_Controller
	 */
	protected $custom_prod_control;

	private $subscription_plugin_enabled;

	const ORDER_STATUS_COMPLETED = 'completed';

	public function __construct() {
		parent::__construct();
		$this->title = __( 'Add order', 'phone-orders-for-woocommerce' );
		$this->meta_key_private_note = WC_Phone_Orders_Loader::$meta_key_private_note;
		$this->meta_key_order_creator = WC_Phone_Orders_Loader::$meta_key_order_creator;
		if ( class_exists( "WC_Phone_Orders_Custom_Products_Controller_Pro" ) ) {
			$this->custom_prod_control = new WC_Phone_Orders_Custom_Products_Controller_Pro();
		} else {
			$this->custom_prod_control = new WC_Phone_Orders_Custom_Products_Controller();
		}
	}

	public function enqueue_scripts() {
            parent::enqueue_scripts();
	}

	public function action() {
	}

	public function render() {

		$buttons_labels = array(
			'cancelLabel' => __( 'Cancel', 'phone-orders-for-woocommerce' ),
			'applyLabel'  => __( 'Apply', 'phone-orders-for-woocommerce' ),
			'removeLabel' => __( 'Remove', 'phone-orders-for-woocommerce' ),
			'saveLabel'   => __( 'Save', 'phone-orders-for-woocommerce' ),
		);

		$this->tab_data = array(
                    'addCouponModalSettings'     => array(
	                    'addCouponLabel'         => __( 'Add Coupon', 'phone-orders-for-woocommerce' ),
	                    'typeToSearchLabel'      => _x( 'Type to search', 'search coupons', 'phone-orders-for-woocommerce' ),
	                    'noResultLabel'          => __( 'Oops! No elements found. Consider changing the search query.', 'phone-orders-for-woocommerce' ),
	                    'multiSelectSearchDelay' => $this->multiselect_search_delay,
	                    'noOptionsTitle'         => __( 'List is empty.', 'phone-orders-for-woocommerce' ),
                    ),
                    'addCustomItemModalSettings' => array(
	                    'addCustomItemLabel' => __( 'Create custom product', 'phone-orders-for-woocommerce' ),
	                    'skuNameLabel'       => __( 'SKU', 'phone-orders-for-woocommerce' ),
	                    'taxClassLabel'      => __( 'Tax class', 'phone-orders-for-woocommerce' ),
	                    'itemTaxClasses'     => $this->make_tax_classes(),
	                    'lineItemNameLabel'  => __( 'Line item name', 'phone-orders-for-woocommerce' ),
	                    'pricePerItemLabel'  => __( 'Price per item', 'phone-orders-for-woocommerce' ),
	                    'quantityLabel'      => __( 'Quantity', 'phone-orders-for-woocommerce' ),
	                    'noOptionsTitle'     => __( 'List is empty.', 'phone-orders-for-woocommerce' ),
	                    'selectOptionPlaceholder' => __( 'Select option', 'phone-orders-for-woocommerce' ),
	                    'categoryLabel'      => __( 'Category', 'phone-orders-for-woocommerce' ),
	                    'weightLabel'	 => __( 'Weight', 'phone-orders-for-woocommerce' ),
	                    'lengthLabel'	 => __( 'Length', 'phone-orders-for-woocommerce' ),
	                    'widthLabel'	 => __( 'Width', 'phone-orders-for-woocommerce' ),
	                    'heightLabel'	 => __( 'Height', 'phone-orders-for-woocommerce' ),
	                    'createWoocommerceProductLabel' => __( 'Create product', 'phone-orders-for-woocommerce' ),
                    ),
                    'addCustomerModalSettings'   => array(
	                    'fieldsToShow'                 => $this->make_customer_fields_to_show_visibility( $this->make_customer_fields_to_show() ),
	                    'saveCustomerLabel'            => __( 'Save customer', 'phone-orders-for-woocommerce' ),
	                    'addCustomerLabel'             => __( 'New customer', 'phone-orders-for-woocommerce' ),
	                    'selectPlaceholder'            => __( 'Select option', 'phone-orders-for-woocommerce' ),
	                    'autocompleteInputPlaceholder' => __( 'Input your address', 'phone-orders-for-woocommerce' ),
	                    'initCustomAutocompleteFunction'	=> apply_filters('wpo_custom_init_autocomplete_js_function', null),
			    'initCustomAutocompletePlaceholder' => apply_filters('wpo_custom_init_autocomplete_placeholder', __('Input your address', 'phone-orders-for-woocommerce')),
	                    'autocompleteInvalidMessage'	 => __( 'Please, enter valid Places API key at tab Settings', 'phone-orders-for-woocommerce' ),
	                    'rolesList'				 => $this->make_roles_list(),
	                    'fillAllFieldsLabel'		 => __( 'Please fill out all required fields!', 'phone-orders-for-woocommerce'),
			    'customerAddressAdditionalKeys'	 => $this->customer_address_additional_keys,
	                    'noOptionsTitle'			 => __( 'List is empty.', 'phone-orders-for-woocommerce' ),
	                    'customGoogleAutocompleteJsCallback' => apply_filters('wpo_custom_google_autocomplete_js_callback_function', 'wpo_prepare_selected_address'),
	                    'selectExistingOrdersPlaceholder'	 => __( 'Load customer details from order', 'phone-orders-for-woocommerce' ),
	                    'noResultLabel'			 => __( 'Oops! No elements found. Consider changing the search query.', 'phone-orders-for-woocommerce' ),
			    'multiSelectSearchDelay'		 => $this->multiselect_search_delay,
			    'selectedOrderMessage'		 => __( 'Copied from order', 'phone-orders-for-woocommerce' ),
                    ),
                    'addDiscountModalSettings'   => array(
                        'addDiscountLabel'	    => __('Add discount', 'phone-orders-for-woocommerce'),
                        'discountType'		    => 'fixed_cart', // discount type by default
                        'discountValue'		    => 10, // discount value by default
			'discountWithTaxLabel'	    => __('with tax', 'phone-orders-for-woocommerce'),
			'discountWithoutTaxLabel'   => __('without tax', 'phone-orders-for-woocommerce'),
                    ),
                    'addFeeModalSettings'        => array(
                        'addFeeLabel'		    => __('Add Fee', 'phone-orders-for-woocommerce'),
                        'feeNameLabel'		    => __('Fee name', 'phone-orders-for-woocommerce'),
                        'feeAmountLabel'	    => __('Fee amount', 'phone-orders-for-woocommerce'),
			'feeAmountWithTaxLabel'	    => __('with tax', 'phone-orders-for-woocommerce'),
			'feeAmountWithoutTaxLabel'  => __('without tax', 'phone-orders-for-woocommerce'),
                    ),
                    'shippingModalSettings'      => array(
                        'shippingMethodLabel'             => __('Shipping method', 'phone-orders-for-woocommerce'),
                        'noShippingMethodsAvailableLabel' => __('No shipping methods available', 'phone-orders-for-woocommerce'),
                        'shippingMethods'                 => $this->get_shipping_rates(),
                        'selectedShippingMethod'          => '',
                        'removeLabel'                     => __( 'Reset', 'phone-orders-for-woocommerce' ),
                        'packageContentsLabel'            => __( 'Package contents', 'phone-orders-for-woocommerce' ),
                    ),
                    'editAddressModalSettings'   => array(
                        'editAddressLabel'             => __('Edit address', 'phone-orders-for-woocommerce'),
                        'saveAddressLabel'             => __('Save address', 'phone-orders-for-woocommerce'),
                        'addressType'                  => '',
                        'customerId'                   => 0,
                        'selectPlaceholder'            => __('Select option', 'phone-orders-for-woocommerce'),
                        'autocompleteInputPlaceholder' => __('Input your address', 'phone-orders-for-woocommerce'),
			'initCustomAutocompleteFunction'    => apply_filters('wpo_custom_init_autocomplete_js_function', null),
			'initCustomAutocompletePlaceholder' => apply_filters('wpo_custom_init_autocomplete_placeholder', __('Input your address', 'phone-orders-for-woocommerce')),
                        'autocompleteInvalidMessage'	     => __('Please, enter valid Places API key at tab Settings', 'phone-orders-for-woocommerce'),
                        'fillAllFieldsLabel'		     => __( 'Please fill out all required fields!', 'phone-orders-for-woocommerce'),
                        'customerAddressAdditionalKeys'	     => $this->customer_address_additional_keys,
                        'noOptionsTitle'		     => __( 'List is empty.', 'phone-orders-for-woocommerce' ),
                        'personalFieldsOrder'		     => apply_filters('wpo_edit_address_modal_personal_fields_order', array('email', 'role', 'first_name', 'last_name', 'company', 'phone')),
                        'addressFieldsOrder'		     => apply_filters('wpo_edit_address_modal_address_fields_order', array('country', 'address_1', 'address_2', 'city', 'state', 'postcode')),
                        'countFieldsInRow'		     => apply_filters('wpo_edit_address_modal_count_fields_in_row', 2),
			'customGoogleAutocompleteJsCallback' => apply_filters('wpo_custom_google_autocomplete_js_callback_function', 'wpo_prepare_selected_address'),
			'copyFromBillingAddressLabel'	     => __('Copy from billing address', 'phone-orders-for-woocommerce'),
			'rolesList'			     => $this->make_roles_list(),
                    ),
                    'configureProductModalSettings'   => array(
						'configureProductLabel'	=> __('Configure product', 'phone-orders-for-woocommerce'),
						'addProductsFromShopLabel' => __('Add products to the cart', 'phone-orders-for-woocommerce'),
						'configureProductNote'	=> __('Select the options, add the item to the cart and close this window. Please note that any special prices for the customer will be applied after adding the product to the order and closing this window.', 'phone-orders-for-woocommerce'),
						'addProductsFromShopNote'	=> __('Select products and add them to the cart and close this window. Please note that any special prices for the customer will be applied after adding the product to the order and closing this window.', 'phone-orders-for-woocommerce'),
						'shopPermalink' => get_permalink(wc_get_page_id('shop')),
			),
                    'orderHistoryCustomerModalSettings'   => array(
			'tabName'				  => 'add-order',
                        'orderHistoryCustomerLabel'		  => __('Order history for', 'phone-orders-for-woocommerce'),
                        'orderHistoryCustomerTableEmptyText'	  => __('There are no records to show', 'phone-orders-for-woocommerce'),
                        'orderHistoryCustomerTableSummaryLabels'  => array(
			    'no_transactions'	=> __('Total Orders', 'phone-orders-for-woocommerce'),
			    'total_paid'	=> __('Total Paid', 'phone-orders-for-woocommerce'),
			    'total'		=> __('Total', 'phone-orders-for-woocommerce'),
			),
                        'orderHistoryCustomerTableHeaders'  => apply_filters( 'wpo_order_history_customer_table_headers', array(
			    array(
				'key'   => 'order_number',
				'label' => __('Order number', 'phone-orders-for-woocommerce'),
			    ),
			    array(
				'key'   => 'date',
				'label' => __('Date', 'phone-orders-for-woocommerce'),
			    ),
			    array(
				'key'   => 'items',
				'label' => __('Items', 'phone-orders-for-woocommerce'),
			    ),
			    array(
				'key'   => 'status',
				'label' => __('Status', 'phone-orders-for-woocommerce'),
			    ),
			    array(
				'key'   => 'payment_type',
				'label' => __('Payment Type', 'phone-orders-for-woocommerce'),
			    ),
			    array(
				'key'   => 'total',
				'label' => __('Total Amount', 'phone-orders-for-woocommerce'),
				'escape' => false,
			    ),
			)),
		    ),
                    'deletedItemLabel'           => __('Product does not exist', 'phone-orders-for-woocommerce'),
                    'outOfStockItemLabel'        => __('Product is out of stock', 'phone-orders-for-woocommerce'),
		    'orderSidebarCustomHtml'	 => apply_filters('wpo_order_sidebar_custom_html', ''),
                );

        array_walk( $this->tab_data, function ( &$item, $key ) use ( $buttons_labels ) {
			if ( is_array( $item ) ) {
				$item = array_merge( $buttons_labels, $item, array( 'tabName' => $this->tab_name ) );
			}
		} );

                $option_handler = $this->option_handler;

                //var_dump($this->get_shipping_rates());die;
        $wc_settings_url = admin_url( 'admin.php?page=wc-settings' );
		$wc_settings_url_html = "<a target='_blank' href=\"{$wc_settings_url}\">" . __( 'Please, enable coupons to use discounts.', 'phone-orders-for-woocommerce' ) . '</a>';

		$order_statuses_list = array();
        foreach ( wc_get_order_statuses() as $i => $status ) {
            $order_statuses_list[] = array(
                'value' => $i,
                'title' => $status,
            );
        }
		$order_statuses_list[] = array(
			'value' => 'draft',
			'title' => __( 'Draft', 'phone-orders-for-woocommerce' ),
		);

		$subscription_payment_interval_options = array();

		if ( function_exists('wcs_get_subscription_period_interval_strings') ) {
		    foreach (wcs_get_subscription_period_interval_strings() as $i => $v) {
			$subscription_payment_interval_options[] = array(
			    'value' => $i,
			    'title' => $v,
			);
		    }
		}

		$subscription_payment_period_options = array();

		if ( function_exists('wcs_get_subscription_period_strings') ) {
		    foreach (wcs_get_subscription_period_strings() as $i => $v) {
			$subscription_payment_period_options[] = array(
			    'value' => $i,
			    'title' => $v,
			);
		    }
		}

		$tab_data = array(
			'findOrCreateCustomerSettings' => array(
				'title'                           => __( 'Find or create a customer', 'phone-orders-for-woocommerce' ),
				'titleOnlyFind'                   => __( 'Find a customer', 'phone-orders-for-woocommerce' ),
				'createNewCustomerLabel'          => __( 'New customer', 'phone-orders-for-woocommerce' ),
				'billingDetailsLabel'             => __( 'Billing Details', 'phone-orders-for-woocommerce' ),
				'shipDifferentLabel'              => __( 'Ship to a different address?', 'phone-orders-for-woocommerce' ),
				'shipDetailsLabel'                => __( 'Shipping Details', 'phone-orders-for-woocommerce' ),
				'emptyBillingAddressMessage'      => __( 'No billing address was provided.', 'phone-orders-for-woocommerce' ),
				'emptyShippingAddressMessage'     => __( 'No shipping address was provided.', 'phone-orders-for-woocommerce' ),
				'billingAddressAsShippingMessage' => __( 'Same as shipping address.', 'phone-orders-for-woocommerce' ),
				'tabName'                         => 'add-order',
				'defaultCustomer'                 => $this->get_customer_by_id( $option_handler->get_option( 'default_customer_id' ) ),
				'isProVersion'                    => WC_Phone_Orders_Loader::is_pro_version(),
				'proFeaturesSettings'             => array(
					'needExtraFeaturesTitle' => __( 'Need extra features?', 'phone-orders-for-woocommerce' ),
					'buyProVersionTitle'     => __( 'Buy Pro version', 'phone-orders-for-woocommerce' ),
				),
				'requiredFieldsForPopUp'          => $this->make_edit_address_fields_to_show(),
				'selectCustomerPlaceholder'       => __( 'Guest', 'phone-orders-for-woocommerce' ),
				'noResultLabel'                   => __( 'Oops! No elements found. Consider changing the search query.', 'phone-orders-for-woocommerce' ),
				'profileUrlTitle'                 => __( 'Profile &rarr;', 'phone-orders-for-woocommerce' ),
				'otherOrderUrlTitle'              => __( 'View other orders &rarr;', 'phone-orders-for-woocommerce' ),
				'customerAddressAdditionalKeys'   => $this->customer_address_additional_keys,
				'multiSelectSearchDelay'          => $this->multiselect_search_delay,
				'noOptionsTitle'                  => __( 'List is empty.', 'phone-orders-for-woocommerce' ),
				'orderHistoryCustomerLinkTitle'	  => __( 'Order History &rarr;', 'phone-orders-for-woocommerce' ),
				'orderHistoryCustomerSummaryNoTransactionsTitle'    => __( 'Orders', 'phone-orders-for-woocommerce' ),
				'disableCustomerSearch'		  => apply_filters('wpo_disable_customer_search', false),
				'customerEmptyMessage'		  => __( 'Please, select/create customer', 'phone-orders-for-woocommerce' ),
			),
			'orderDetailsSettings'         => array(
				'title'                           => apply_filters( 'wpo_order_details_container_header', '<h2><span>' . __( 'Order details', 'phone-orders-for-woocommerce' ) . '</span></h2>' ),
				'addProductButtonTitle'				 => __('Create custom product', 'phone-orders-for-woocommerce'),
				'findProductsSelectPlaceholder'			 => apply_filters('wpo_find_products_select_placeholder', __('Find products...', 'phone-orders-for-woocommerce')),
				'findProductsSelectButtonAddToOrderLabel'	 => __('Add to Order', 'phone-orders-for-woocommerce'),
				'findProductsSelectButtonConfigureLabel'	 => __('Configure product', 'phone-orders-for-woocommerce'),
				'productsTableItemColumnTitle'			 => __('Item', 'phone-orders-for-woocommerce'),
				'productsTableCostColumnTitle'			 => __('Cost', 'phone-orders-for-woocommerce'),
				'productsTableQtyColumnTitle'			 => __('Qty', 'phone-orders-for-woocommerce'),
				'productsTableTotalColumnTitle'			 => __('Total', 'phone-orders-for-woocommerce'),
				'subtotalLabel'					 => __('Subtotal', 'phone-orders-for-woocommerce'),
				'addCouponLabel'				 => __('Add coupon', 'phone-orders-for-woocommerce'),
				'addDiscountLabel'				 => __('Add discount', 'phone-orders-for-woocommerce'),
				'feeNameLabel'				     => __('Fee', 'phone-orders-for-woocommerce'),
				'discountLabel'					 => __('Discount', 'phone-orders-for-woocommerce'),
				'addShippingLabel'				 => __('Add shipping', 'phone-orders-for-woocommerce'),
				'shippingLabel'					 => __('Shipping', 'phone-orders-for-woocommerce'),
				'recalculateButtonLabel'			 => __('Recalculate', 'phone-orders-for-woocommerce'),
				'taxLabel'					 => __('Taxes', 'phone-orders-for-woocommerce'),
				'orderTotalLabel'				 => __('Order Total', 'phone-orders-for-woocommerce'),
				'createOrderButtonLabel'			 => apply_filters('wpo_create_order_button_label', __('Create order', 'phone-orders-for-woocommerce')),
				'viewOrderButtonLabel'				 => __('View order', 'phone-orders-for-woocommerce'),
				'sendOrderButtonLabel'				 => __('Send invoice', 'phone-orders-for-woocommerce'),
				'createNewOrderLabel'				 => __('Create new order', 'phone-orders-for-woocommerce'),
				'payOrderNeedProVersionMessage'			 => __('Want to pay order as customer?', 'phone-orders-for-woocommerce'),
				'buyProVersionMessage'				 => __('Buy Pro version', 'phone-orders-for-woocommerce'),
				'tabName'					 => 'add-order',
				'isProVersion'					 => WC_Phone_Orders_Loader::is_pro_version(),
				'logRowID'					 => uniqid(),
				'noResultLabel'					 => __('Oops! No elements found. Consider changing the search query.', 'phone-orders-for-woocommerce'),
				'productItemLabels'               => array(
					'deleteProductItemButtonTooltipText' => __( 'Delete item', 'phone-orders-for-woocommerce' ),
					'skuLabel'                           => __( 'SKU', 'phone-orders-for-woocommerce' ),
					'productStockMessage'                => __( 'Only %s items can be purchased', 'phone-orders-for-woocommerce' ),
					'variationIDLabel'                   => __( 'Variation ID', 'phone-orders-for-woocommerce' ),
					'productsTableCostColumnTitle'	     => __('Cost', 'phone-orders-for-woocommerce'),
					'productsTableQtyColumnTitle'	     => __('Qty', 'phone-orders-for-woocommerce'),
					'productCustomMetaFieldsLabels'      => array(
						'editMetaLabel'                => __( 'Edit meta', 'phone-orders-for-woocommerce' ),
						'productCustomMetaFieldLabels' => array(
							'chooseOptionLabel'          => __( 'Choose meta field', 'phone-orders-for-woocommerce' ),
							'addMetaLabel'               => __( 'Add meta', 'phone-orders-for-woocommerce' ),
							'customMetaValuePlaceholder' => __( 'Custom meta field value', 'phone-orders-for-woocommerce' ),
							'customMetaKeyPlaceholder'   => __( 'Custom meta field key', 'phone-orders-for-woocommerce' ),
							'cancelEditMetaLabel'        => __( 'Collapse edit meta', 'phone-orders-for-woocommerce' ),
						),
					),
					'productMissingAttributeLabels'      => array(
						'chooseOptionLabel' => __( 'Choose an option', 'phone-orders-for-woocommerce' ),
					),
				),
				'couponsEnabled'                  => wc_coupons_enabled(),
				'activateCouponsLabel'            => $wc_settings_url_html,
				'chooseMissingAttributeLabel'     => __( 'Please, choose all attributes.', 'phone-orders-for-woocommerce' ),
				'manualDiscountLabel'             => __( 'Manual Discount', 'phone-orders-for-woocommerce' ),
				'copyCartButtonLabel'             => __( 'Copy url to populate cart', 'phone-orders-for-woocommerce' ),
				'copyCopiedCartButtonLabel'       => __( 'Url has been copied to clipboard', 'phone-orders-for-woocommerce' ),
				'duplicateOrderLabel'             => __( 'Duplicate order', 'phone-orders-for-woocommerce' ),
				'fillAllFieldsLabel'              => __( 'Please fill out all required fields!', 'phone-orders-for-woocommerce'),
				'multiSelectSearchDelay'          => apply_filters('wpo_order_details_find_products_timeout_ms', $this->multiselect_search_delay),
				'noOptionsTitle'                  => __( 'List is empty.', 'phone-orders-for-woocommerce' ),
				'disableProductSearch'		     => apply_filters('wpo_disable_product_search', false),
				'useConfigureProductActionAsDefault' => apply_filters('wpo_use_configure_product_action_as_default', false),
				'clearCartAfterCreateOrder'	     => apply_filters('wpo_clear_cart_after_create_order', false),
				'customerProvidedNoteLabel'		=> __('Customer provided note', 'phone-orders-for-woocommerce'),
				'customerProvidedNotePlaceholder'	=> __('Add a note', 'phone-orders-for-woocommerce'),
				'customerPrivateNoteLabel'		=> __('Private note', 'phone-orders-for-woocommerce'),
				'customerPrivateNotePlaceholder'	=> __('Add a note', 'phone-orders-for-woocommerce'),
				'openViewOrderInSameTab'		=> apply_filters('wpo_order_details_open_view_order_in_same_tab', false),
				'packageLabel'		            => __('Package', 'phone-orders-for-woocommerce'),
				'itemsLabel'		            => __('items', 'phone-orders-for-woocommerce'),
				'itemLabel'		                => __('item', 'phone-orders-for-woocommerce'),
				'removeLabel'		            => __('Remove', 'phone-orders-for-woocommerce'),
				'addLabel'		                => __('Add', 'phone-orders-for-woocommerce'),
				'multipleSelectedProductsCountLabel'		=> __('Selected', 'phone-orders-for-woocommerce'),
				'addMultipleSelectedProductsLabel'		=> __('Add to cart', 'phone-orders-for-woocommerce'),
				'cancelMultipleSelectedProductsLabel'		=> __('Cancel', 'phone-orders-for-woocommerce'),
				'browseProductsMultipleSelectedProductsLabel'	=> __('Advanced search', 'phone-orders-for-woocommerce'),
				'shippingGrantedByCoupon'		                => __('granted by coupon', 'phone-orders-for-woocommerce'),
				'copyCheckoutLinkTitle'		                => __('Copy to clipboard', 'phone-orders-for-woocommerce'),
				'columnDiscountTitle'		                => __('Discount', 'phone-orders-for-woocommerce'),
				'barcodeModeAlertMessage'			=> __('Barcode mode is enabled! "Find products" working only after click Enter key', 'phone-orders-for-woocommerce'),
				'productSubscriptionOptions'		        => array(
				    'paymentLabel'		=> __('Billing Schedule', 'phone-orders-for-woocommerce'),
				    'paymentPeriodOptions'	=> $subscription_payment_period_options,
				    'paymentIntervalOptions'	=> $subscription_payment_interval_options,
				    'nextPaymentLabel'		=> __('Next Payment', 'phone-orders-for-woocommerce'),
				    'timezoneOffset'            => (int) get_option( 'gmt_offset' ),
				    'hourPlaceholder'           => _x( 'h', 'hour placeholder', 'phone-orders-for-woocommerce' ),
				    'minutePlaceholder'         => _x( 'm', 'minute placeholder', 'phone-orders-for-woocommerce' ),
				    'chooseOptionBillingIntervalPlaceholder'	=> __('Choose interval', 'phone-orders-for-woocommerce'),
				    'chooseOptionBillingPeriodPlaceholder'	=> __('Choose period', 'phone-orders-for-woocommerce'),
				    'signUpFeeLabel'				=> __('Sign-up Fee', 'phone-orders-for-woocommerce'),
				),
			),
			'orderDateSettings'            => array(
				'title'                    => apply_filters( 'wpo_order_date_container_header', __( 'Order date', 'phone-orders-for-woocommerce' ) ),
				'currentDateTimeTimestamp' => current_time( 'timestamp' ),
				'timeZoneOffset'           => (int) get_option( 'gmt_offset' ),
				'hourPlaceholder'          => _x( 'h', 'hour placeholder', 'phone-orders-for-woocommerce' ),
				'minutePlaceholder'        => _x( 'm', 'minute placeholder', 'phone-orders-for-woocommerce' ),
			),
			'orderStatusSettings' => array(
				'title'             => apply_filters( 'wpo_order_status_container_header', __( 'Order status', 'phone-orders-for-woocommerce' ) ),
				'orderStatusesList' => apply_filters( 'wpo_order_status_list_statuses', $order_statuses_list),
				'noOptionsTitle'    => __( 'List is empty.', 'phone-orders-for-woocommerce' ),
			),
			'orderPaymentMethod' => array(
				'title'                    => apply_filters( 'wpo_order_payment_method_container_header', __( 'Payment method', 'phone-orders-for-woocommerce' ) ),
				'initialPaymentGateways'   => $this->updater->make_order_payment_methods_list(),
				'noOptionsTitle'           => __( 'List is empty.', 'phone-orders-for-woocommerce' ),
			),
		);

		?>

                <tab-add-order v-bind="<?php echo esc_attr( json_encode( $this->tab_data ) ) ?>">
                    <?php do_action( 'wpo_find_order' ) ?>
                    <find-or-create-customer slot="find-or-create-customer" v-bind="<?php echo esc_attr(json_encode($tab_data['findOrCreateCustomerSettings'])) ?>">
                        <?php do_action( 'wpo_after_customer_details' ) ?>
                    </find-or-create-customer>
                    <order-details slot="order-details" v-bind="<?php echo esc_attr(json_encode($tab_data['orderDetailsSettings'])) ?>">
                        <?php do_action( 'wpo_before_search_items_field' ) ?>
                        <?php do_action( 'wpo_after_order_items' ) ?>
			<?php do_action('wpo_order_footer_left_side') ?>
                        <?php do_action( 'wpo_add_fee' ); ?>
                        <?php do_action("wpo_footer_buttons") ?>
                    </order-details>
                    <order-date slot="order-date" v-bind="<?php echo esc_attr(json_encode($tab_data['orderDateSettings'])) ?>" ></order-date>
                    <order-status slot="order-status" v-bind="<?php echo esc_attr(json_encode($tab_data['orderStatusSettings'])) ?>" ></order-status>
                    <order-payment-method slot="order-payment-method" v-bind="<?php echo esc_attr(json_encode($tab_data['orderPaymentMethod'])) ?>" ></order-payment-method>
                </tab-add-order>
		<?php
	}

	private function make_edit_address_fields_to_show() {
	    $fields = array(
		    'email' => array(
			    'label' => __( 'E-mail', 'phone-orders-for-woocommerce' ),
			    'value' => '',
		    ),
		    'first_name' => array(
			    'label' => __( 'First name', 'phone-orders-for-woocommerce' ),
			    'value' => '',
		    ),
		    'last_name' => array(
			    'label' => __( 'Last name', 'phone-orders-for-woocommerce' ),
			    'value' => '',
		    ),
        );

	    $fields = array_merge( $fields, $this->make_customer_fields_to_show()['billingAddress']['fields'] );

        return apply_filters('wpo_customer_edit_address_fields_to_show', $fields);
    }

	private function make_customer_fields_to_show_visibility( $fields ) {
		foreach ( $fields as &$container ) {
			foreach ( $container['fields'] as $field_name => &$field ) {
				$field['visibility'] = isset($field['visibility']) ? $field['visibility'] : true;
			}
		}

		return $fields;
	}

	protected function make_customer_fields_to_show() {

	    $fields = array(
            'common' => array(
                'label' => __( 'Common', 'phone-orders-for-woocommerce' ),
                'fields' => array(
                    'first_name' => array(
                        'label' => __( 'First name', 'phone-orders-for-woocommerce' ),
                        'value' => '',
                    ),
                    'last_name' => array(
	                    'label' => __( 'Last name', 'phone-orders-for-woocommerce' ),
	                    'value' => '',
                    ),
                    'email' => array(
	                    'label' => __( 'E-mail', 'phone-orders-for-woocommerce' ),
	                    'value' => '',
                    ),
                ),
            ),
            'billingAddress' => array(
	            'label' => __( 'Billing address', 'phone-orders-for-woocommerce' ),
	            'fields' => array(
		            'company' => array(
			            'label' => __( 'Company', 'phone-orders-for-woocommerce' ),
			            'value' => '',
		            ),
		            'phone' => array(
			            'label' => __( 'Phone', 'phone-orders-for-woocommerce' ),
			            'value' => '',
		            ),
		            'country' => array(
			            'label' => __( 'Country', 'phone-orders-for-woocommerce' ),
			            'value' => $this->option_handler->get_option( 'default_country' ),
		            ),
		            'address_1' => array(
			            'label' => __( 'Address1', 'phone-orders-for-woocommerce' ),
			            'value' => '',
		            ),
		            'address_2' => array(
			            'label' => __( 'Address2', 'phone-orders-for-woocommerce' ),
			            'value' => '',
		            ),
		            'city' => array(
			            'label' => __( 'City', 'phone-orders-for-woocommerce' ),
			            'value' => $this->option_handler->get_option( 'default_city' ),
		            ),
		            'state' => array(
			            'label' => __( 'State/County', 'phone-orders-for-woocommerce' ),
			            'value' => $this->option_handler->get_option( 'default_state' ),
		            ),
		            'postcode' => array(
			            'label' => __( 'Postcode', 'phone-orders-for-woocommerce' ),
			            'value' => $this->option_handler->get_option( 'default_postcode' ),
		            ),
                )
            ),
        );

		$fields['billingAddress']['fields'] = array_merge( $fields['billingAddress']['fields'], $this->customer_address_additional_keys );

	    return $fields;
    }

	protected function ajax_load_items( $request ) {

		do_action('wpo_before_load_items', $request);

		if ( ! isset( $request['ids'] ) || ! is_array( $request['ids'] ) ) {
			return $this->wpo_send_json_success( array(
				'items' => array(),
			) );
		}

		$qty = isset( $request['qty'] ) ? $request['qty'] : 1;

		$old_user_id = false;
		if ( ! empty ( $request['customer_id'] ) ) {
			$customer_id = $request['customer_id'];

			$update_customer_result = $this->update_customer( $customer_id, array() );

			if ( $customer_id AND apply_filters( 'wpo_must_switch_cart_user', $this->option_handler->get_option( 'switch_customer_while_calc_cart' ) ) ) {
				$old_user_id = get_current_user_id();
				wp_set_current_user( $customer_id );
			}
			do_action( 'wdp_after_switch_customer_while_calc' );
		}

		$ids = array();

		foreach ($request['ids'] as $id) {
		    $product = wc_get_product($id);
		    if ($product->is_type( 'grouped' )) {
			$ids = array_merge($ids, $product->get_children());
		    } else {
			$ids[] = $id;
		    }
		}

		$result = array(
			'items' => $this->get_formatted_product_items_by_ids( $ids, $qty ),
		);

		foreach($result['items'] as &$item) {
			$product = wc_get_product($item['variation_id']);
			$item['formatted_variation_data'] = isset($item['variation_data']) ? static::get_formatted_variation_data($item['variation_data'], $product) : array();
		}

		//switch back to admin
		if ( $old_user_id ) {
			wp_set_current_user( $old_user_id );
		}

		return $this->wpo_send_json_success( $result );
	}

	public static function get_formatted_variation_data($variation_data, $_product) {
		if ( ! is_array( $variation_data ) ) {
			return array();
		}

		foreach($variation_data as $attr_key => $attr_value) {
			unset($variation_data[$attr_key]);
			if($attr_value === "") {
				continue;
			}
			$attr = wc_attribute_label(str_replace('attribute_', '',$attr_key), $_product);

                        $value = $_product->get_attribute(str_replace('attribute_', '',$attr_key));

                        if ($value === "") {
                            continue;
                        }

			$variation_data[$attr] = $value;
		}
		return $variation_data;
	}

	protected function get_formatted_product_items_by_ids( array $ids = array(), $quantity = 1 ) {
		return $this->updater->get_formatted_product_items_by_ids( $ids, $quantity );
	}

	protected function get_order_item_html( $item ) {
		$option_handler = $this->option_handler;

		$item_id  = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
		$_product = wc_get_product( $item_id );

		if ( $_product->get_parent_id() ) {
			$product_link = admin_url( 'post.php?post=' . absint( $_product->get_parent_id() ) . '&action=edit' );
		} else {
			$product_link = admin_url( 'post.php?post=' . absint( $item_id ) . '&action=edit' );
		}

		$thumbnail = $_product ? apply_filters( 'woocommerce_admin_order_item_thumbnail',
			$_product->get_image( 'thumbnail', array( 'title' => '' ), false ), $item_id, $item ) : '';
		$tax_data  = empty( $legacy_order ) && $this->is_tax_enabled() ? maybe_unserialize( isset( $item['line_tax_data'] ) ? $item['line_tax_data'] : '' ) : false;
//		$item_total    = ( isset( $item['line_total'] ) ) ? esc_attr( wc_format_localized_price( $item['line_total'] ) ) : '';
//		$item_subtotal = ( isset( $item['line_subtotal'] ) ) ? esc_attr( wc_format_localized_price( $item['line_subtotal'] ) ) : '';

		$item['variation_data'] = $item['variation_id'] ? $_product->get_variation_attributes() : '';
		$item['in_stock']       = $_product->is_on_backorder() ? null : $_product->get_stock_quantity();
		$item['name']           = $_product->get_name();

		if ( $this->is_subscription( $item_id ) ) {
			$item['item_cost']          = wc_format_decimal( $item['item_cost'],
				$option_handler->get_option( 'item_price_precision' ) );
		}

		ob_start();
		include( WC_Phone_Orders_Tabs_Helper::get_views_path() . 'html/html-order-item.php' );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	/**
	 * @param array $data
	 *
	 * @return WC_Product_Simple
	 */
	protected function create_item( $data ) {
		$product = $this->custom_prod_control->create_custom_product();
		$this->set_created_item_props( $product, $data );
		$product->save();

		do_action( 'wpo_create_custom_product', $product->get_id(), $product );

		return $product;
	}

	/**
	 * @param WC_Product_Simple $product
	 * @param array $data
	 */
	protected function set_created_item_props( &$product, $data ) {
		if ( ! $catalog_visibility = $this->option_handler->get_option( 'product_visibility' ) ) {
			$catalog_visibility = 'visible';
		}

		if ( ! $featured = $this->option_handler->get_option( 'is_featured' ) ) {
			$featured = 'no';
		}

		$product->set_props( array(
			'name'               => $data['name'],
			'sku'                => $data['sku'],
			'price'              => $data['price'],
			'regular_price'      => $data['price'],
			'catalog_visibility' => $catalog_visibility,
			'featured'           => $featured,
		) );
	}

	protected function ajax_create_item( $request ) {
		$custom_prod_control = $this->custom_prod_control;

		if( $this->option_handler->get_option( 'disable_adding_products' ) ) {
			return $this->wpo_send_json_error( __( 'Can not create new product', 'phone-orders-for-woocommerce' ) );
		}

		if ( ! empty( $request['data'] ) ) {
			$product = $this->create_item( $request['data'] );
			$product = apply_filters( "wpo_load_custom_product", $product, $request );

			$quantity = ! empty( $request['data']['quantity'] ) ? $request['data']['quantity'] : 1;
        } else {
			return $this->wpo_send_json_error( __( 'Missing data', 'phone-orders-for-woocommerce' ) );
		}

		$item = $this->get_item_by_product( $product, array( 'quantity' => $quantity ));

		if ( $custom_prod_control->is_custom_product( $product ) ) {
			$custom_prod_control->store_product_in_cart_item( $item, $product );
		}

		return $this->wpo_send_json_success( array(
			'item' =>  $item,
			'url'  => get_edit_post_link( $item['product_id'], false ),
		) );
	}

	/**
	 * @param WC_Product|WC_Product_Variation $product
	 * @param array $item_data
	 *
	 * @return array
	 */
	protected function get_item_by_product( $product, array $item_data = array() ) {
		return $this->updater->get_item_by_product( $product, $item_data );
	}

	protected function ajax_create_customer( $request ) {
		$user_id = $this->create_customer( $request );
		if ( is_wp_error($user_id) ) {
			$error_data = array('message' => $user_id->get_error_message(),
			'code' => $user_id->get_error_code());
			return $this->wpo_send_json_error( $error_data );
        }

		return $this->wpo_send_json_success( array(
			'id' => $user_id,
		) );
	}

	protected function create_customer( $request ) {

		parse_str( $request['data'], $data );
		parse_str( $request['order_customer'], $order_customer );

		// $user_name = $data['first_name'] . ' ' . $data['last_name'] . rand(1, 1000);

		$fake_email = false;

		if (
			( ! isset( $data['email'] ) || $data['email'] === '' )
			&&
			( $this->option_handler->get_option( 'newcustomer_hide_email' ) || !in_array('email', $this->option_handler->get_option( 'newcustomer_required_fields' )) ) ) {
			$fake_email = sanitize_email( trim( $data['first_name'] ) . trim( $data['last_name'] ) . '@test.com' );
			if ( ! $fake_email AND ! empty( $data['phone'] ) ) {
				$fake_email = sanitize_email( "Tel." . trim( $data['phone'] ) . '@test.com' );
			}
			if ( ! $fake_email ) { //use date
				$fake_email = sanitize_email( current_time("Ymd") . '@test.com' );
			}
			$fake_email    = apply_filters( "wpo_make_fake_email", $fake_email, $data );
			$data['email'] = $fake_email;
			// don't pass it to Wordpress!
			add_filter( 'pre_user_email', function () {
				return "";
			} );
		}

		$data     = apply_filters( 'wpo_before_create_customer', $data );
		$username = isset( $data['username'] ) ? $data['username'] : "";
		$password = isset( $data['password'] ) ? $data['password'] : "";

		if (!$password) {
			add_filter( "option_woocommerce_registration_generate_password", function(){ return 'yes'; } );
		}

		if (!$username) {
			add_filter( "option_woocommerce_registration_generate_username", function(){ return 'yes'; } );
		}

		$user_id  = wc_create_new_customer( $data['email'], $username, $password );
		do_action( 'wpo_after_create_customer', $user_id, $data );
		if ( is_wp_error( $user_id ) ) {
		    return $user_id;
		}

		if ( $fake_email ) {
			$data['email'] = '';
		}

		update_user_meta( $user_id, 'first_name', $data['first_name'] );
		update_user_meta( $user_id, 'last_name', $data['last_name'] );

		$billing_fields = array(
			'email',
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'postcode',
			'country',
			'state',
			'phone',
		);

		$billing_fields = array_merge($billing_fields, array_keys( $this->customer_address_additional_keys ));

		foreach ( $billing_fields as $field ) {

			update_user_meta( $user_id, 'billing_' . $field, $data[ $field ] );

			$shipping_field = 'shipping_' . $field;

			if (isset($order_customer[$shipping_field])) {
			    update_user_meta( $user_id, $shipping_field, $order_customer[$shipping_field] );
			} else {
			    if ( $field != "phone" AND $field != "email" ) {
				update_user_meta( $user_id, $shipping_field, $data[ $field ] );
			    }
			}

		}

		if (isset($order_customer['is_vat_exempt'])) {
		    $tax_exempt = wc_string_to_bool($order_customer['is_vat_exempt']) ? 'yes' : 'no';
		    update_user_meta( $user_id, 'is_vat_exempt', $tax_exempt );
		}

		return $user_id;
	}

	protected function ajax_update_customer( $request ) {

		//parse_str( $request['data'], $data );

		$customer_data = ( isset( $request['customer_data'] ) && is_array( $request['customer_data'] ) ) ? $request['customer_data'] : array();
		$id = isset( $customer_data['id'] ) ? $customer_data['id'] : '0';

		$data = $this->get_updated_customer( $id, $customer_data, $request );
		if ( $data instanceof WC_Data_Exception ) {
			return $this->wpo_send_json_error( array('message' => $data->getMessage(), 'code' => $data->getErrorCode()) );
		} else {
		    return $this->wpo_send_json_success( $data );
		}
	}

	protected function update_customer( $id, $customer_data ) {
		return $this->updater->update_customer( $id, $customer_data );
	}

	protected function ajax_get_formatted_address( $request ) {
		$customer_data = isset( $request['data'] ) ? json_decode(stripslashes($request['data']), true) : array();

		$result        = $this->get_formatted_address( $customer_data );

		return $this->wpo_send_json_success( $result );
	}

	protected function get_formatted_address( $customer_data ) {
		$address         = array(
			'first_name' => isset( $customer_data['billing_first_name'] ) ? $customer_data['billing_first_name'] : '',
			'last_name'  => isset( $customer_data['billing_last_name'] ) ? $customer_data['billing_last_name'] : '',
			'company'    => isset( $customer_data['billing_company'] ) ? $customer_data['billing_company'] : '',
			'address_1'  => isset( $customer_data['billing_address_1'] ) ? $customer_data['billing_address_1'] : '',
			'address_2'  => isset( $customer_data['billing_address_2'] ) ? $customer_data['billing_address_2'] : '',
			'city'       => isset( $customer_data['billing_city'] ) ? $customer_data['billing_city'] : '',
			'state'      => isset( $customer_data['billing_state'] ) ? $customer_data['billing_state'] : '',
			'postcode'   => isset( $customer_data['billing_postcode'] ) ? $customer_data['billing_postcode'] : '',
			'country'    => isset( $customer_data['billing_country'] ) ? $customer_data['billing_country'] : '',
		);

		$billing_data = array();
		$shipping_data = array();

		foreach ( $customer_data as $key => $data ) {
			if ( preg_match( '"^(billing_)(.+)"', $key, $matches ) === 1 ) {
				$billing_data[ $matches[2] ] = $data;
			} elseif ( preg_match( '"^(shipping_)(.+)"', $key, $matches ) === 1 ) {
				$shipping_data[ $matches[2] ] = $data;
			}
		}


		$billing_address = WC()->countries->get_formatted_address( apply_filters('wpo_customer_formatted_address', $billing_data, $customer_data, 'billing') );
		$shipping_address = WC()->countries->get_formatted_address( apply_filters('wpo_customer_formatted_address', $shipping_data, $customer_data, 'shipping') );

		return array(
			'formatted_billing_address' => $billing_address,
			'formatted_shipping_address' => $shipping_address,
		);
    }

	protected function ajax_get_shipping_rates( $data ) {
		$result = $this->update_cart( $data['cart'] );
		if ( $result instanceof WC_Data_Exception ) {
			return $this->wpo_send_json_error( $result->getMessage() );
		}
		$order_id = $this->get_frontend_order_id( $data );
		WC_Phone_Orders_Tabs_Helper::add_log( $data['log_row_id'], $result, $order_id );
		if ( isset( $data['cart']['order_id'] ) AND $data['cart']['order_id'] ) {
			WC()->cart->empty_cart();
		}
		return $this->wpo_send_json_success( $result['shipping'] );
	}

	public function update_cart( $data ) {
		return $this->updater->process( $data );
	}

	protected function get_cart_total() {
		$value = '<strong>' . WC()->cart->get_total() . '</strong> ';

		// If prices are tax inclusive, show taxes here
//		if ( wc_tax_enabled() && WC()->cart->tax_display_cart == 'incl' ) {
//			$tax_string_array = array();
//
//			if ( get_option( 'woocommerce_tax_total_display' ) == 'itemized' ) {
//				foreach ( WC()->cart->get_tax_totals() as $code => $tax ) {
//					$tax_string_array[] = sprintf( '%s %s', $tax->formatted_amount, $tax->label );
//				}
//			} else {
//				$tax_string_array[] = sprintf( '%s %s', wc_price( WC()->cart->get_taxes_total( true, true ) ),
//					WC()->countries->tax_or_vat() );
//			}
//
//			if ( ! empty( $tax_string_array ) ) {
//				$taxable_address = WC()->customer->get_taxable_address();
//				$estimated_text  = WC()->customer->is_customer_outside_base() && ! WC()->customer->has_calculated_shipping()
//					? sprintf( ' ' . __( 'estimated for %s', 'woocommerce' ),
//						WC()->countries->estimated_for_prefix( $taxable_address[0] ) . WC()->countries->countries[ $taxable_address[0] ] )
//					: '';
//				$value           .= '<small class="includes_tax">' . sprintf( __( '(includes %s)', 'woocommerce' ),
//						implode( ', ', $tax_string_array ) . $estimated_text ) . '</small>';
//			}
//		}

		return apply_filters( 'woocommerce_cart_totals_order_total_html', $value );
	}

	protected function get_shipping_rates() {
		WC()->shipping->load_shipping_methods();
		$packages              = WC()->shipping->get_packages();
		$shipping_rates_result = array();

               // var_dump(WC()->shipping);die;

		foreach ( $packages as $package ) {
			if ( isset( $package['rates'] ) ) {
               //var_dump($package['rates']);die;
				$shipping_rates = array_map( function ( $rate ) {
					$fields = array(
						'id'             => $rate->get_id(),
						'label'          => $rate->get_label(),
						'cost'           => $rate->get_cost(),
						'full_cost'      => $rate->get_cost() + $rate->get_shipping_tax(),
					);

					return $fields;
				}, $package['rates'] );

				$shipping_rates_result = array_merge( $shipping_rates_result, $shipping_rates );
			}
		}

		return array_values( $shipping_rates_result );
	}

	// Sees if the customer has entered enough data to calc the shipping yet.

	protected function ajax_create_order( $data ) {
		$result = $this->create_order( $data );
		if ( is_array( $result ) && isset( $result['success'] ) && $result['success'] == false ) {
			return $result;
		}
		$order_id = $result;

		if ( ! $order_id ) {
//			return $this->wpo_send_json_error( __( 'Recalculate cart changed cart items', 'phone-orders-for-woocommerce' ) );
			return $this->wpo_send_json_error();
		}

		$order       = wc_get_order( $order_id );
		$payment_url = $order->get_checkout_payment_url();

		$message = sprintf( __( 'Order #%s created', 'phone-orders-for-woocommerce' ), $order->get_order_number() );

		$result = array(
                    'order_id'		 => $order_id,
                    'order_number'	 => $order->get_order_number(),
                    'message'		 => $message,
                    'payment_url'	 => $payment_url,
                    'is_completed'	 => $order->get_status() === self::ORDER_STATUS_COMPLETED,
		    'allow_refund_order' => $this->get_allow_refund_order($order),
                );

                return $this->wpo_send_json_success( $result );
	}

	protected function create_order( $data, $set_status = true ) {
		$option_handler = $this->option_handler;
		$cart = $data['cart'];
		add_filter( 'woocommerce_checkout_customer_id', function ( $user_id ) use ( $cart ) {
			return ! empty( $cart['customer']['id'] ) ? $cart['customer']['id'] : 0;
		} );

		$this->disable_email_notifications($_enabled = true);

		//refresh cart
		$result = $this->update_cart( $cart );
		if ( $result instanceof WC_Data_Exception ) {
			return $this->wpo_send_json_error( $result->getMessage() );
		}
		if ( count( $result['deleted_items'] ) ) {
			return false;
		}

		if ( WC()->cart->needs_shipping() && ! $this->option_handler->get_option( "allow_to_create_orders_without_shipping" ) ) {
            $chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );

            foreach ( WC()->shipping()->get_packages() as $i => $package ) {
                if ( ! isset( $chosen_shipping_methods[ $i ], $package['rates'][ $chosen_shipping_methods[ $i ] ] ) ) {
	                return $this->wpo_send_json_error(  __( 'No shipping method has been selected.', 'phone-orders-for-woocommerce' ) );
                }
            }
		}

		// checkout needs customer fields!
		$use_shipping_address = (isset( $cart['customer']['ship_different_address'] ) AND 'true' == $cart['customer']['ship_different_address']);
		$checkout_data        = array();
		foreach ( $cart['customer'] as $key => $value ) {
			if ( stripos( $key, 'billing_' ) !== false ) {
				$checkout_data[ $key ] = $value;
				if ( ! $use_shipping_address ) // use billing details as delivery address
				{
					$checkout_data[ str_replace( 'billing_', 'shipping_', $key ) ] = $value;
				}
			} elseif ( $use_shipping_address AND stripos( $key, 'shipping_' ) !== false ) {
				$checkout_data[ $key ] = $value;
			}
		}

		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'action_woocommerce_checkout_create_order_line_item' ), 10, 4 );
		add_action( 'woocommerce_checkout_create_order_shipping_item', array( $this, 'action_woocommerce_checkout_create_order_shipping_item' ), 10, 4 );

		//remap incoming note
		$checkout_data['order_comments'] = ! empty( $cart['customer_note'] ) ? $cart['customer_note'] : "";
		$checkout_data['payment_method'] = ! empty( $cart['payment_method'] ) ? $cart['payment_method'] : $option_handler->get_option( 'order_payment_method' );
		$checkout                        = new WC_Checkout();


		// external plugins can filter orders by creator
		// this is the only way to add meta data with WC_Checkout
		$addOrderCreatorMeta = function ( $order, $data ) {
			$order->add_meta_data( $this->meta_key_order_creator, get_current_user_id(), true );
		};
		add_action( 'woocommerce_checkout_create_order', $addOrderCreatorMeta, 10, 2 );
		$order_id = $checkout->create_order( $checkout_data );
		remove_action( 'woocommerce_checkout_create_order', $addOrderCreatorMeta, 10 );

		remove_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'action_woocommerce_checkout_create_order_line_item' ) );
		remove_action( 'woocommerce_checkout_create_order_shipping_item', array( $this, 'action_woocommerce_checkout_create_order_shipping_item' ) );

        WC_Phone_Orders_Tabs_Helper::add_log( $data['log_row_id'], $result, $order_id );

		$order    = wc_get_order( $order_id );
		if ( is_wp_error( $order_id ) ) {
			return $this->wpo_send_json_error( $order_id->get_error_message() );
		}

		$order->add_order_note( __( 'Order created with Phone Order.', 'phone-orders-for-woocommerce' ), false, true );

		do_action( 'woocommerce_checkout_order_processed', $order_id, $checkout_data, $order );
		do_action( 'woocommerce_checkout_phone_order_processed', $order_id, $checkout_data, $order );
		$order    = wc_get_order( $order_id );

		if ( isset( $cart['private_note'] ) and ! empty( $cart['private_note'] ) ) {
			$order->add_order_note( $cart['private_note'], false, true );
			if ( ! add_post_meta( $order_id, $this->meta_key_private_note, $cart['private_note'], true ) ) {
				update_post_meta( $order_id, $this->meta_key_private_note, $cart['private_note'] );
			}
		}

		$created_date_time = ! empty( $data['created_date_time'] ) ? (int) $data['created_date_time']: null;
		if ( is_integer( $created_date_time ) ) {
			$order->set_date_created( $data['created_date_time'] );
			$order->save();
        }

		WC()->cart->empty_cart();

		// set status ?
		if ( ! empty( $data['order_status'] ) && $set_status ) {
			$new_status = $data['order_status'];

			if ( $new_status AND $new_status != get_post_status( $order_id ) ) {

				$_new_status = wc_is_order_status( 'wc-' . get_post_status( $order_id ) ) ? 'wc-' . $new_status : $new_status;

				$order->set_status($_new_status);

				$order->save();
			}
		}

		// set status ?
		/*if ( $this->option_handler->get_option( 'order_status' ) ) {
			$order->update_status( $this->option_handler->get_option( 'order_status' ) );
		}*/

		if ($order->get_status() === self::ORDER_STATUS_COMPLETED) {
			do_action( 'woocommerce_payment_complete', $order->get_id() );
		}

		if ( ! empty( $cart['discount'] ) ) {
			$discount = $cart['discount'];
			if ( empty( $discount['type'] ) ) {
				$discount['type'] = 'fixed_cart';
			}
			$manual_cart_discount_code = strtolower( $this->option_handler->get_option( 'manual_coupon_title' ) );
			$result                    = array(
				'code'   => $manual_cart_discount_code,
				'type'   => $discount['type'],
				'amount' => $discount['amount'],
				'id'     => - 1,
			);
			if ( ! add_post_meta( $order_id, $option_handler->get_option( 'manual_coupon_title' ), $result, true ) ) {
				update_post_meta( $order_id, $option_handler->get_option( 'manual_coupon_title' ), $result );
			}
		}


		do_action( 'wpo_order_created', wc_get_order( $order_id ), $data['cart'] );

		$this->disable_email_notifications($_enabled = false);

		return $order_id;
	}

    public function action_woocommerce_checkout_create_order_line_item($item, $cart_item_key, $values, $order) {

            if ( ! isset($values['data']->custom_meta_fields) ) {
                return;
            }

            foreach ($values['data']->custom_meta_fields as $meta) {
                $item->update_meta_data($meta['meta_key'], $meta['meta_value']);
            }

        }

	/**
	 * @param WC_Order_Item_Shipping $item
	 * @param integer $package_key
	 * @param array $package
	 * @param WC_Order $order
	 */
	public function action_woocommerce_checkout_create_order_shipping_item( $item, $package_key, $package, $order ) {
		if ( ! isset( $package[ WC_Phone_Orders_Cart_Shipping_Processor::PACKAGE_HASH_KEY ] ) ) {
			return;
		}

		$hash = $package[ WC_Phone_Orders_Cart_Shipping_Processor::PACKAGE_HASH_KEY ];

		$item->add_meta_data( WC_Phone_Orders_Cart_Shipping_Processor::ORDER_SHIPPING_ITEM_HASH_KEY, $hash );
	}

    protected function ajax_create_order_email_invoice( $data ) {
		$order_id = $data['order_id'];

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return $this->wpo_send_json_error( __( 'Order not found', 'phone-orders-for-woocommerce' ) );
		}

		$email = $order->get_billing_email();

		if ( empty( $email ) ) {
			$user_info = get_userdata( $data['cart']['customer']['id'] );
			$email     = $user_info->user_email;
		}

		if ( ! is_email( $email ) ) {
			return $this->wpo_send_json_error( __( 'A valid email address is required', 'phone-orders-for-woocommerce' ) );
		}

		try {
			// replaced WC()->mailer()->customer_invoice( $order ); with full version
			// copied from  class-wc-meta-box-order-actions.php
			do_action( 'woocommerce_before_resend_order_emails', $order, 'customer_invoice' );

			// Send the customer invoice email.
			WC()->payment_gateways();
			WC()->shipping();
			WC()->mailer()->customer_invoice( $order );

			// Note the event.
			$order->add_order_note( __( 'Invoice manually sent to customer from Phone Order.', 'woocommerce' ), false, true );

			do_action( 'woocommerce_after_resend_order_email', $order, 'customer_invoice' );
		} catch ( phpmailerException $e ) {
			return $this->wpo_send_json_error( __( 'There was an error sending the email', 'phone-orders-for-woocommerce' ) );
		}

		$result = array(
			'order_id' => $order_id,
			'email'    => $email,
			'message'  => sprintf( __( 'Invoice for order #%s has been sent to %s', 'phone-orders-for-woocommerce' ),
				$order_id, $email ),
		);

		return $this->wpo_send_json_success( $result );
	}

	protected function ajax_recalculate( $data ) {
                $result = $this->update_cart( $data['cart'] );

                if ( $result instanceof WC_Data_Exception ) {
                    return $this->wpo_send_json_error( $result->getMessage() );
                }

                $order_id = $this->get_frontend_order_id( $data );

                WC_Phone_Orders_Tabs_Helper::add_log( $data['log_row_id'], $result, $order_id );

                if ($result['shipping'] && isset($result['shipping']['total_html'])) {
                    unset($result['shipping']['total_html']);
                }

		WC()->cart->empty_cart();

		return $this->wpo_send_json_success( $result );
	}

	protected function get_frontend_order_id( $data ) {
		$order_id = 0;
		foreach ( array( 'order_id', 'drafted_order_id', 'edit_order_id' ) as $item ) {
			if ( isset( $data['cart'][ $item ] ) ) {
				$order_id = $data['cart'][ $item ];
				break;
			}
		}
		return $order_id;
	}

	protected function ajax_get_coupons_list( $data ) {
		$exclude        = isset( $_GET['exclude'] ) ? $_GET['exclude'] : array();
		$exclude_titles = array_filter( array_map( function ( $current ) {
			$current = json_decode( stripslashes( $current ), true );

			return ! empty( $current['title'] ) ? $current['title'] : false;
		}, $exclude ) );

		$term = isset($data['term']) ? $data['term'] : '';
		$customer_id = isset($data['customer_id']) ? $data['customer_id'] : '';

		$args    = array(
			'posts_per_page' => - 1,
			'orderby'        => 'title',
			'order'          => 'asc',
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			's'              => $term,
		);
		$coupons = get_posts( apply_filters("wpo_get_coupons_list_args", $args,$customer_id)  );

		$result = array();

		// add row with search term
        // enable to add not existing coupon with typed title
		if ( $term && ! in_array( $term, $exclude_titles ) ) {
			$result[] = array(
				'title' => $term,
			);
		}

		foreach ( $coupons as $coupon ) {
			$coupon_name = $coupon->post_title;

			if ( in_array( $coupon_name, $exclude_titles ) ) {
				continue;
			}

			$coupon_meta = get_post_meta($coupon->ID);
			$currency = get_woocommerce_currency_symbol();
			if($coupon_meta['discount_type'][0] === 'percent') {
				$coupon_name .= ' - ' . $coupon_meta['coupon_amount'][0] . '%';
			}
			else {
				$coupon_name .= ' - ' . $currency . $coupon_meta['coupon_amount'][0];
			}

			$result[] = array(
				'title' 		=> $coupon_name,
			);
		}

		die( json_encode( $result ) );
	}

	protected function ajax_search_products_and_variations( $data ) {

		do_action('wpo_before_search_products_and_variations', $data);

		if ( ! $data ) {
			wp_send_json( null );
		}
		if ( ! empty( $data['exclude'] ) ) {
			$exclude = json_decode( stripslashes( $data['exclude'] ), true );
		} else {
			$exclude = array();
		}

		$customer_id = isset($data['customer_id']) ? $data['customer_id'] : 0;

		$old_user_id = false;

		if ( $customer_id AND apply_filters( 'wpo_must_switch_cart_user', $this->option_handler->get_option( 'switch_customer_while_calc_cart' ) ) ) {
			$update_customer_result = $this->update_customer( $customer_id, array() );

		    $old_user_id = get_current_user_id();
		    wp_set_current_user( $customer_id );
		    do_action( 'wdp_after_switch_customer_while_calc' );
		}

		$term = isset( $data['term'] ) ? trim( $data['term'] ) : '';

		$additional_query_args = $this->create_additional_query_args( $data );
		if( $this->option_handler->get_option( 'show_long_attribute_names' ) )
			add_filter("woocommerce_product_variation_title_include_attributes", "__return_true");
		$products = $this->search_products_and_variations( $term, $exclude, $additional_query_args );

		$result = array();
		$delimiter = apply_filters( 'wpo_autocomplete_product_fields_delimiter', '|' );
		$hide_image = $this->option_handler->get_option( 'autocomplete_product_hide_image' );

		foreach ( $products as $product_id => $product) {
			/**
			 * @var WC_Product $product
			 */
			$product_parent = isset( $products[ $product->get_parent_id() ] ) ? $products[ $product->get_parent_id() ] : wc_get_product( $product->get_parent_id() );
			if ( $product_parent && $product_parent->is_type( "simple" ) ) {
				continue;
			}

			$image_url = "";

			if( ! $hide_image ) {
			    $image_url = $this->get_thumbnail_src_by_product($product);
			}

			$result[] = apply_filters( 'wpo_search_products_result_item', array(
				'product_id'		=> $product_id,
				'title'			=> $this->format_row_product( $product, $delimiter ),
				'sort'			=> ( $term == $product_id ) ? "" : $product->get_name(),
				'img'			=> $image_url,
				'permalink'		=> get_permalink( $product_id ),
				'product_link'		=> admin_url( 'post.php?post=' . absint( $product_id ) . '&action=edit' ),
				'product'		=> $product,
				'parent_id'		=> $product->get_parent_id(),
				'menu_order'		=> $product->get_menu_order(),
				'add_to_exclude'	=> !$product->is_type( "grouped" ),
			), $product_id, $product );
		}

		if ( has_filter( 'wpo_search_products_and_variations_results' ) ) {
			$result = apply_filters( 'wpo_search_products_and_variations_results', $result, $term );
		} else {
			usort( $result, function ( $a, $b ) {
				// sort products with same defined parent by menu order
				if ( $a['parent_id'] !== 0 && $b['parent_id'] !== 0 && $a['parent_id'] === $b['parent_id'] ) {
					return $a['menu_order'] - $b['menu_order'];
				}

				// move product with parent below
				// independent product moves up
				if ( ( $a['parent_id'] === 0 ) XOR ( $b['parent_id'] === 0 ) ) {
					return $a['parent_id'] - $b['parent_id'];
				}

				return strcmp( $a['sort'], $b['sort'] );
			} );
		}

		//switch back to admin
		if ( $old_user_id ) {
		    wp_set_current_user( $old_user_id );
		}

		wp_send_json( $result );
	}

	protected function get_thumbnail_src_by_product( $product ) {
		return $this->updater->get_thumbnail_src_by_product( $product );
	}

	public function OR_where_search_by_sku( $like ) {
		global $wpdb;
		$where = $wpdb->prepare(" OR ({$wpdb->posts}.ID IN ( SELECT post_id FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.meta_key = '_sku' AND {$wpdb->postmeta}.meta_value LIKE %s ))", $like);
		return $where;
	}

	public function AND_where_search_term_in_title( $where, $query ) {
		return $where . $this->parse_search( $query, true );
	}

	public function AND_where_search_term_in_excerpt_or_content( $where, $query ) {
		return $where . $this->parse_search( $query, false );
	}

	/**
     * Method copied from @see WP_Query::parse_search
     * Differences marked with comments
     *
	 * @param WP_Query $query
	 * @param boolean $is_on_title_search
	 *
	 * @return string
	 * @throws ReflectionException if the class or method does not exist.
	 */
	protected function parse_search( $query, $is_on_title_search ) {
		global $wpdb;

		$search_by_sku = $this->option_handler->get_option( 'search_by_sku' ) && ! $this->option_handler->get_option( 'verbose_search' );

		/**
		 * Here is the block of the difference from original
         * Create variable $q
		 */
		$q = $query->query_vars;
		/**
		 * End of the block
		 */
		$search = '';

		// added slashes screw with quote grouping when done early, so done later
		$q['search'] = stripslashes( $q['search'] );
		if ( empty( $_GET['s'] ) && $query->is_main_query() ) {
			$q['search'] = urldecode( $q['search'] );
		}
		// there are no line breaks in <input /> fields
		$q['search']                  = str_replace( array( "\r", "\n" ), '', $q['search'] );
		$q['search_terms_count'] = 1;
		if ( ! empty( $q['sentence'] ) ) {
			$q['search_terms'] = array( $q['s'] );
		} else {
			if ( preg_match_all( '/".*?("|$)|((?<=[\t ",+])|^)[^\t ",+]+/', $q['search'], $matches ) ) {
				$q['search_terms_count'] = count( $matches[0] );

				/**
				 * Here is the block of the difference from original
                 * We have to use ReflectionMethod to call "protected" method
				 */
				$method = new ReflectionMethod( $query, 'parse_search_terms' );
				$method->setAccessible( true );
				$q['search_terms'] = $method->invoke( $query, $matches[0] );
				/**
				 * End of the block
				 */

				// if the search string has only short terms or stopwords, or is 10+ terms long, match it as sentence
				if ( empty( $q['search_terms'] ) || count( $q['search_terms'] ) > 9 ) {
					$q['search_terms'] = array( $q['s'] );
				}
			} else {
				$q['search_terms'] = array( $q['s'] );
			}
		}

		$n                         = ! empty( $q['exact'] ) ? '' : '%';
		$searchand                 = '';
		$q['search_orderby_title'] = array();

		/**
		 * Filters the prefix that indicates that a search term should be excluded from results.
		 *
		 * @since 4.7.0
		 *
		 * @param string $exclusion_prefix The prefix. Default '-'. Returning
		 *                                 an empty value disables exclusions.
		 */
		$exclusion_prefix = apply_filters( 'wp_query_search_exclusion_prefix', '-' );

		foreach ( $q['search_terms'] as $term ) {
			// If there is an $exclusion_prefix, terms prefixed with it should be excluded.
			$exclude = $exclusion_prefix && ( $exclusion_prefix === substr( $term, 0, 1 ) );
			if ( $exclude ) {
				$like_op  = 'NOT LIKE';
				$andor_op = 'AND';
				$term     = substr( $term, 1 );
			} else {
				$like_op  = 'LIKE';
				$andor_op = 'OR';
			}

			if ( $n && ! $exclude ) {
				$like                        = '%' . $wpdb->esc_like( $term ) . '%';
				$q['search_orderby_title'][] = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", $like );
			}

			$like      = $n . $wpdb->esc_like( $term ) . $n;

			/**
			 * Here is the block of the difference from original
			 */
			if ( $is_on_title_search ) {

				$concat   = $wpdb->prepare( "{$wpdb->posts}.post_title $like_op %s", $like );

				if ($search_by_sku) {
				    $concat .= $this->OR_where_search_by_sku($like);
				}

				$search   .= "{$searchand}($concat)";
			} else {
				$concat = "{$wpdb->posts}.post_title, {$wpdb->posts}.post_excerpt, {$wpdb->posts}.post_content";

				$concat .= ", IFNULL((SELECT meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND {$wpdb->postmeta}.meta_key = '_variation_description'), '')";

				if ( $this->option_handler->get_option( 'verbose_search' ) ) {
					// TODO test with > 10k products
					$concat .= ", IFNULL((SELECT CONCAT(post_title,post_content,post_excerpt) FROM {$wpdb->posts} as tmp_posts_parent WHERE {$wpdb->posts}.post_parent = tmp_posts_parent.ID), '')";
				}

				if ( $this->option_handler->get_option( 'search_by_sku' ) ) {
					$concat .= ", IFNULL((SELECT meta_value FROM {$wpdb->postmeta} WHERE {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id AND {$wpdb->postmeta}.meta_key = '_sku'), '')";
				}

				$search .= $wpdb->prepare( "{$searchand}(CONCAT($concat) $like_op %s )", $like );
			}
			/**
			 * End of the block
			 */

			$searchand = ' AND ';
		}

		if ( ! empty( $search ) ) {
			$search = " AND ({$search}) ";
			if ( ! is_user_logged_in() ) {
				$search .= " AND ({$wpdb->posts}.post_password = '') ";
			}
		}

		return $search;
	}


	public function get_products( $term, $query_args, $is_on_title_search ) {

		if ( $is_on_title_search ) {
			add_filter( 'posts_where', array( $this, 'AND_where_search_term_in_title' ), 10, 2 );
		} else {
			add_filter( 'posts_where', array( $this, 'AND_where_search_term_in_excerpt_or_content' ), 10, 2 );
		}

		$query_args['search']        = $term;
		$query_args['OR_sku_filter'] = $term;
		$products_ids                = wc_get_products( $query_args );

		if ( $is_on_title_search ) {
			remove_filter( 'posts_where', array( $this, 'AND_where_search_term_in_title' ), 10 );
		} else {
			remove_filter( 'posts_where', array( $this, 'AND_where_search_term_in_excerpt_or_content' ), 10 );
		}

		return $products_ids;
	}

	protected function search_products_and_variations( $term, $exclude, $additional_query_args = array() ) {
		$limit = $this->option_handler->get_option( 'number_of_products_to_show' );

		$query_args = array(
			'type'   => apply_filters( "wpo_search_product_types", array( 'simple', 'variable','variation','subscription', 'grouped' ) ),
			'exclude' => $exclude,
			'return' => 'ids',
			'orderby' => 'title',
			'order'  => 'ASC',
			'limit'  => apply_filters( "wpo_search_product_limit", -1, $limit ),
		);
		// filter by category/tags ?
		$query_args = array_merge( $query_args, $additional_query_args );
		if ( $products_ids = apply_filters( "wpo_custom_product_search", array(),$query_args,$term) ) {
			; // do nothing,  just use  custom results
        }
		elseif ( isset( $term ) AND $term ) { // keyword?
			$products_ids = $this->get_products( $term, $query_args, true );

			if ( count( $products_ids ) < $limit ) {
				$query_args['limit'] = apply_filters( "wpo_search_product_limit", -1, $limit - count( $products_ids ) );
				$products_ids = array_merge( $products_ids, $this->get_products( $term, $query_args, false ) );
			}
		} else { // just category/tag  ?
			$products_ids = wc_get_products( $query_args );
		}

		//exact product by id ? add at top!
		if( preg_match('#^\d+$#',$term) AND ($product = wc_get_product($term)) ) {
			array_unshift( $products_ids, (int)$term );
			$products_ids = array_unique( $products_ids );
		}

		$selected_products = array();
		foreach ( $products_ids as $index => $product_id ) {
			if( in_array( $product_id, $exclude ) )
				continue;
			$product = wc_get_product( $product_id );
			if ( $product ) {
				if( $product->is_type( 'variable' ) ) {
					$variations = $product->get_children();
					// add childrens only IF we don't have exact varition in results already!
					if( !array_intersect($products_ids,$variations ) ) {
						foreach($variations  as $variation_id) {
							if( in_array( $variation_id, $exclude ) )
								continue;
							$variation = wc_get_product( $variation_id );
							if( $this->is_valid_product($variation) ) {
								$selected_products[$variation_id] = $variation;
								if ( count($selected_products) >= $limit )
									break;
							}
						}//end foreach variations
					}
				}
				elseif ( $this->is_valid_product($product) ) // add simple product or exact variation
					$selected_products[$product_id] = $product;
			}
			if ( count($selected_products) >= $limit )
				break;
		}
		return $selected_products;
	}

	protected function is_valid_product( $product ) {
		return apply_filters('wpo_is_valid_product', $this->get_is_valid_product( $product ), $product, $this->option_handler->get_all_options());
	}

	protected function get_is_valid_product( $product ) {
		$zero_prices    = array( false, "", "0", "0.00" );
		$option_handler = $this->option_handler;

		if ( in_array( $product->get_status(), array("trash","draft") ) || $product->is_type( 'variation' ) && in_array( wc_get_product( $product->get_parent_id() )->get_status(), array("trash","draft") ) ) {
			return false;
		}

		if ( in_array( $product->get_price(),
				$zero_prices ) AND $option_handler->get_option( 'hide_products_with_no_price' ) ) {
			return false;
		}

		if ( ! $product->is_in_stock() AND ! $option_handler->get_option( 'sale_backorder_product' ) ) {
			return false;
		}

		return true;
	}

	protected function create_additional_query_args( $params ) {
		return array();
	}

	protected function ajax_generate_log_row_id() {
	    return $this->wpo_send_json_success(array('log_row_id' => uniqid()));
        }

    protected function is_tax_enabled() {
	    return $this->updater->is_tax_enabled();
    }

    protected function ajax_init_order( $request ) {

        $custom_fields_option  = $this->option_handler->get_option('order_custom_fields');
        $custom_fields_options = array();

	    if ( $custom_fields_option ) {
		    if ( method_exists( $this, 'extract_field_from_option' ) ) {
			    $custom_fields_options = $this->extract_field_from_option( $custom_fields_option );
		    }
	    }

	$customer_id = isset( $request['customer_id'] ) ? $request['customer_id'] : $this->option_handler->get_option('default_customer_id');

	$default_customer = $this->get_customer_by_id($customer_id);

	do_action('wpo_before_init_order', $request, $default_customer);

	$old_user_id = false;
	if ( $customer_id ) {

		if ( apply_filters( 'wpo_must_switch_cart_user', $this->option_handler->get_option( 'switch_customer_while_calc_cart' ) ) ) {
			$old_user_id = get_current_user_id();
			wp_set_current_user( $customer_id );
		}
		do_action( 'wdp_after_switch_customer_while_calc' );
	}

        $state = array(
            'default_customer'                  => $default_customer,
            'default_items'                     => $this->get_formatted_product_items_by_ids($this->option_handler->get_option('item_default_selected')),
            'default_order_custom_field_values' => apply_filters('wpo_init_order_default_custom_fields_values', $custom_fields_options),
            'log_row_id' => uniqid(),
            'default_order_status' => $this->option_handler->get_option('order_status'),
            'payment_gateways'	     => $this->updater->make_order_payment_methods_list(),
            'default_payment_method' => $this->option_handler->get_option('order_payment_method'),
            'wc_price_settings'      => array(
	            'currency'           => get_woocommerce_currency(),
	            'currency_symbol'    => get_woocommerce_currency_symbol(),
	            'decimal_separator'  => wc_get_price_decimal_separator(),
	            'thousand_separator' => wc_get_price_thousand_separator(),
	            'decimals'           => wc_get_price_decimals(),
	            'price_format'       => get_woocommerce_price_format(),
            ),
	    'wc_tax_settings'      => array(
		'prices_include_tax' => wc_prices_include_tax(),
	    ),
	    'wc_measurements_settings' => array(
		'show_weight_unit'	=> wc_product_weight_enabled(),
		'weight_unit'		=> get_option( 'woocommerce_weight_unit' ),
		'show_dimension_unit'   => wc_product_dimensions_enabled(),
		'dimension_unit'	=> get_option( 'woocommerce_dimension_unit' ),
	    ),
        );

	    //switch back to admin
	    if ( $old_user_id ) {
		    wp_set_current_user( $old_user_id );
	    }

	$state = apply_filters('wpo_init_order_get_data', $state, $customer_id);

	$customer       = $state['default_customer'];
	$customer_id	= isset($customer['id']) ? $customer['id'] : '0';
	$customer_data	= is_array( $customer ) ? $customer : array();

	$updated_customer_data = $this->get_updated_customer($customer_id, $customer_data, $request);

	if ( $updated_customer_data instanceof WC_Data_Exception ) {
	    return $this->wpo_send_json_error( $updated_customer_data->getMessage() );
	}

	$state['default_customer']  = $updated_customer_data['customer'];

	$cart				    = $request['cart'];
	$cart['customer']		    = $state['default_customer'];
	$cart['items']			    = $state['default_items'];
	$cart['wc_price_settings']	    = $state['wc_price_settings'];
	$cart['wc_tax_settings']	    = $state['wc_tax_settings'];
	$cart['wc_measurements_settings']   = $state['wc_measurements_settings'];

	$payment_method = isset($updated_customer_data['customer_last_order_payment_method']) ? $updated_customer_data['customer_last_order_payment_method'] : $state['default_payment_method'];

	$cart['payment_method'] = $payment_method;

	//to fix unsupportable payment
	if ( ! in_array( $cart['payment_method'], array_map(function($v) { return $v['value']; }, $state['payment_gateways']) ) ) {
	    $cart['payment_method'] = '';
	}

	$result = $this->get_calculated_cart( $cart );

	if ( $result instanceof WC_Data_Exception ) {
	    return $this->wpo_send_json_error( $result->getMessage() );
	}

        wp_send_json( array( 'state' => $state, 'cart' => $result ));
    }

	protected function is_readonly_product_price( $product_id, $cart_item_data ) {
		return $this->updater->is_readonly_product_price( $product_id, $cart_item_data );
	}

	protected function is_readonly_product_qty( $cart_item_data ) {
		return $this->updater->is_readonly_product_qty( $cart_item_data );
	}

	protected function is_subscription( $product_id ) {
		return $this->updater->is_subscription( $product_id );
	}

    protected function disable_email_notifications($enabled = false) {

	if ( ! $this->option_handler->get_option( 'disable_order_emails' ) ) {
	    return;
	}

	$notification_emails_types = apply_filters("wpo_disable_order_emails", array(
	    'new_order',
	    'failed_order',
	    'cancelled_order',
	    'customer_on_hold_order',
	    'customer_refunded_order',
	    'customer_completed_order',
	    'customer_processing_order',
	) );

	if ($enabled) {
	    foreach ($notification_emails_types as $type) {
		add_filter( 'woocommerce_email_enabled_' . $type, array($this, 'disable_email_notification'), 10, 2 );
	    }
	    return;
	}

	foreach ($notification_emails_types as $type) {
	    remove_filter( 'woocommerce_email_enabled_' . $type, array($this, 'disable_email_notification'), 10, 2 );
	}

    }

    public function disable_email_notification($enabled, $object) {
	return false;
    }

    protected function get_allow_refund_order($order) {
	return 0 < $order->get_total() - $order->get_total_refunded() || 0 < absint( $order->get_item_count() - $order->get_item_count_refunded() );
    }

    protected function ajax_wpo_rated() {

	if ( ! current_user_can( WC_Phone_Orders_Loader::$cap_manage_phone_orders ) ) {
	    die;
	}

	update_option( 'phone-orders-for-woocommerce-rated', 1 );
    }

}