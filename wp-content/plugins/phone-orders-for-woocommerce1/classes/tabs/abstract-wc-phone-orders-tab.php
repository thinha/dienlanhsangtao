<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

abstract class WC_Phone_Orders_Admin_Abstract_Page
{
    public $title;
    public $priority;
    protected $tab_name;

    protected $option_handler;
    protected $tab_data;
    protected $multiselect_search_delay = 1000;
    protected $updater;
    protected $repository_orders;

    /**
     * Address keys that are not displayed in the pop-up
     * They only work with google`s autocomplete
     *
     * Filling in construct to provide localized name
     *
     * @var array $customer_address_additional_keys
     */
    protected $customer_address_additional_keys = array();

    protected function customer_addition_full_keys()
    {
        $full_keys = array();
        foreach (array_keys($this->customer_address_additional_keys) as $key) {
            $full_keys[] = 'billing_' . $key;
            $full_keys[] = 'shipping_' . $key;
        }

        return apply_filters('wpo_customer_addition_full_keys', $full_keys);
    }

    protected function __construct()
    {
        $this->option_handler = WC_Phone_Orders_Settings::getInstance();

        $this->customer_address_additional_keys = apply_filters('wpo_customer_address_additional_keys', array(
            'lat'           => array(
                'label'      => __('Latitude', 'phone-orders-for-woocommerce'),
                'value'      => '',
                'visibility' => false,
            ),
            'lng'           => array(
                'label'      => __('Longitude', 'phone-orders-for-woocommerce'),
                'value'      => '',
                'visibility' => false,
            ),
            'street_number' => array(
                'label'      => __('Street number', 'phone-orders-for-woocommerce'),
                'value'      => '',
                'visibility' => false,
            ),
        ));

        add_filter('woocommerce_hidden_order_itemmeta', function ($keys) {
            $keys[] = WC_Phone_Orders_Cart_Shipping_Processor::ORDER_SHIPPING_ITEM_HASH_KEY;
            $keys[] = WC_Phone_Orders_Cart_Shipping_Processor::ORDER_SHIPPING_METHOD_ID_KEY;
            $keys[] = "_wpo_item_cost_updated_manually";

            return $keys;
        }, 10, 1);

        $this->updater = new WC_Phone_Orders_Cart_Updater($this->option_handler);

        if (class_exists('WC_Phone_Repository_Orders_HPOS')) {
            $this->repository_orders = new WC_Phone_Repository_Orders_HPOS();
        } else {
            $this->repository_orders = new WC_Phone_Repository_Orders();
        }
    }

    public function enqueue_scripts()
    {
        define('WOOCOMMERCE_CART', 1);
    }

    public function ajax($method, $request)
    {
        if (method_exists($this, $method)) {
            if ( ! empty($request['cart'])) {
                $cart_data = is_array($request['cart']) ? array() : json_decode(
                    stripslashes($request['cart']),
                    JSON_OBJECT_AS_ARRAY
                );
                if (empty($cart_data)) {
                    array_walk_recursive($request['cart'], function (&$item, $key) {
                        if ($item === 'true') {
                            $item = true;
                        }
                        if ($item === 'false') {
                            $item = false;
                        }
                    });
                } else {
                    $request['cart'] = $cart_data;
                }
                $_REQUEST['cart'] = $request['cart'];
            }
            $request = apply_filters("wpo_before_" . $method, $request);
            if ( ! ob_get_level()) {
                ob_start();
            }
            $result = $this->$method($request);
            if (isset($result['success'])) {
                $data = ! empty($result['data']) ? $result['data'] : false;

                $result = array(
                    'success' => (bool)$result['success'],
                    'data'    => $data,
                );

                $buffer = ob_get_clean();
                while (ob_get_level()) {
                    $buffer .= ob_get_clean();
                }
                if ($buffer) {
                    $prefix = __('Unexpected output', 'phone-orders-for-woocommerce') . ": ";
                    $buffer = strlen($buffer) > 200 ? substr($buffer, -200) : $buffer;

                    $result['unexpected_output'] = $prefix . $buffer;
                }

                $encoded_result = json_encode($result);

                if (json_last_error() === JSON_ERROR_NONE) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $encoded_result;
                } else {
                    $error = 'json_encode_error';
                    if (function_exists('json_last_error_msg')) {
                        $error .= ': ' . json_last_error_msg();
                    }

                    echo esc_html($error);
                }
            }
        } else {
            return 'No ajax method';
        }

        die;
    }

    protected function wpo_send_json_success($data = false)
    {
        return array(
            'success' => true,
            'data'    => $data,
        );
    }

    protected function wpo_send_json_error($data = false)
    {
        return array(
            'success' => false,
            'data'    => $data,
        );
    }

    public function action()
    {
    }

    public function render()
    {
    }

    protected function ajax_get_customer($request)
    {
        return $this->wpo_send_json_success(
            $this->get_customer_by_type_and_id($request['id'], $request['type'])
        );
    }

    protected function ajax_get_sessions($request) {
        global $wpdb;

        $table = $wpdb->prefix . 'woocommerce_sessions';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB
        $rows = $wpdb->get_results("SELECT session_key, session_value, session_expiry FROM $table ORDER BY session_expiry DESC LIMIT 100");
        $sessions = [];

        foreach ($rows as $row) {

            $value = maybe_unserialize($row->session_value);
            if (!is_array($value)) {
                continue;
            }

            $customer = $value['customer'] ?? [];

            if (is_string($customer)) {
                $customer = maybe_unserialize($customer);
            }
            if (!is_array($customer)) {
                $customer = [];
            }

            $first_name = $customer['first_name'] ?? '';
            $last_name  = $customer['last_name'] ?? '';
            $email      = $customer['email'] ?? '';

            $full_name = trim("$first_name $last_name");
            if (!$full_name) {
                if($email) {
                    $full_name = $email;
                } else{
                    $full_name = __('Guest', 'phone-orders-for-woocommerce');
                }
            }

            $address_parts = [
                $customer['address_1'] ?? '',
                $customer['address_2'] ?? '',
                $customer['city'] ?? '',
                $customer['state'] ?? '',
                $customer['postcode'] ?? '',
                $customer['country'] ?? '',
            ];
            $address = implode(', ', array_filter($address_parts));
            if (!$address) {
                $address = '';
            }

            $cart = $value['cart'] ?? [];
            if (is_string($cart)) {
                $cart = maybe_unserialize($cart);
            }
            $cart_items = is_array($cart) ? count($cart) : 0;

            $cart_items_list = [];

            foreach ($cart as $cart_key => $cart_item) {

                $product_id = $cart_item['product_id'] ?? 0;
                $qty        = $cart_item['quantity'] ?? 0;

                if (!$product_id || !$qty) {
                    continue;
                }

                $product = wc_get_product($product_id);
                if (!$product) {
                    continue;
                }

                $cart_items_list[] = $qty . ' x ' . $product->get_name();
            }

            if (!empty($customer['date_modified'])) {
                $date_mod = gmdate(wc_date_format(). " " .wc_time_format(), strtotime($customer['date_modified']));
            } else {
                $date_mod = '';
            }

            $customer_id = $customer['id'] ?? 0;

            if ($customer_id > 0) {
                $user_info = get_userdata($customer_id);
                $customer_type = $user_info ? implode(',', $user_info->roles) : '';
            } else {
                $customer_type = 'guest';
            }


            if($cart_items != 0) {
                $sessions[] = [
                    'key'        => $row->session_key,
                    'customer'   => [
                        'id'   => $customer_id,
                        'type' => $customer_type,
                        'name' => $full_name,
                    ],
                    'cart_items' => $cart_items_list,
                    'address'    => $address,
                    'date'       => $date_mod,
                ];
            }

        }

        return $this->wpo_send_json_success([
            'sessions' => $sessions
        ]);
    }

    function add_customer_cart_items_to_current_cart(&$cart,$session_cart){
        $customer_cart = maybe_unserialize($session_cart);
        if( !is_array($customer_cart) )
            return ;

        $add_ids = [];
        foreach($customer_cart as $customer_item){
            $found_item = false;
            foreach($cart['items'] as $cart_item){
                if($customer_item['variation_id'] AND $customer_item['variation_id'] == $cart_item['variation_id']
                    OR
                    !$customer_item['variation_id'] AND $customer_item['product_id'] == $cart_item['product_id']
                ) {
                    $found_item = true;
                    break;
                }
            }
            if(!$found_item) {
                $id = $customer_item['variation_id'] ? $customer_item['variation_id'] : $customer_item['product_id'];
                $add_ids[] = array( 'id'=>$id, 'qty'=>$customer_item['quantity']);
            }
        }

        //add missed
        foreach( $this->updater->get_formatted_product_items_by_ids($add_ids) as $new_item)
            $cart['items'][] = $new_item;
    }

    protected function ajax_get_all_customers($request)
    {
        $users = get_users();
        $user_data = [];

        foreach ($users as $user) {
            $user_id = $user->ID;
            $user_name = trim($user->first_name . ' ' . $user->last_name);
            $user_email = $user->user_email;

            $roles = $user->roles;
            $user_type = !empty($roles) ? $roles[0] : 'customer';

            $billing_address = [
                'billing_company' => get_user_meta($user_id, 'billing_company', true),
                'billing_phone' => get_user_meta($user_id, 'billing_phone', true),
                'billing_address_1' => get_user_meta($user_id, 'billing_address_1', true),
                'billing_address_2' => get_user_meta($user_id, 'billing_address_2', true),
                'billing_city' => get_user_meta($user_id, 'billing_city', true),
                'billing_postcode' => get_user_meta($user_id, 'billing_postcode', true),
                'billing_country' => get_user_meta($user_id, 'billing_country', true),
                'billing_state' => get_user_meta($user_id, 'billing_state', true),
            ];

            $shipping_address = [
                'shipping_company' => get_user_meta($user_id, 'shipping_company', true),
                'shipping_phone' => get_user_meta($user_id, 'shipping_phone', true),
                'shipping_address_1' => get_user_meta($user_id, 'shipping_address_1', true),
                'shipping_address_2' => get_user_meta($user_id, 'shipping_address_2', true),
                'shipping_city' => get_user_meta($user_id, 'shipping_city', true),
                'shipping_postcode' => get_user_meta($user_id, 'shipping_postcode', true),
                'shipping_country' => get_user_meta($user_id, 'shipping_country', true),
                'shipping_state' => get_user_meta($user_id, 'shipping_state', true),
            ];

            $billing_address_full = implode(' ', array_filter($billing_address));
            $shipping_address_full = implode(' ', array_filter($shipping_address));

            $user_data[] = [
                'id' => $user_id,
                'name' => $user_name,
                'email' => $user_email,
                'type' => $user_type,
                'billing_address' => $billing_address_full,
                'shipping_address' => $shipping_address_full,
            ];
        }

        return $this->wpo_send_json_success($user_data);
    }

    protected function get_session_by_key( $session_key, $default_value = [] ) {
        global $wpdb;
        $table = $wpdb->prefix . 'woocommerce_sessions';

        //phpcs:ignore PluginCheck.Security.DirectDB, WordPress.DB.DirectDatabaseQuery
        $value = $wpdb->get_var(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
                "SELECT session_value FROM $table WHERE session_key = %s",
                $session_key
            )
        );

        if ( is_null($value) ) {
            return $default_value;
        }

        $value = maybe_unserialize($value);

        if ( isset($value['cart']) && is_string($value['cart']) ) {
            $value['cart'] = maybe_unserialize($value['cart']);
        }
        if ( isset($value['customer']) && is_string($value['customer']) ) {
            $value['customer'] = maybe_unserialize($value['customer']);
        }

        return $value;
    }


    protected function ajax_set_customer($request)
    {
        $customer = $this->get_customer_by_type_and_id($request['id'], $request['type']);

        $customer_id   = isset($customer['id']) ? $customer['id'] : '0';
        $customer_data = is_array($customer) ? $customer : array();

        $updated_customer_data = $this->get_updated_customer($customer_id, $customer_data, $request);

        if ($updated_customer_data instanceof WC_Data_Exception) {
            return $this->wpo_send_json_error($updated_customer_data->getMessage());
        }

        $updated_customer = $updated_customer_data['customer'];
        $cart             = $request['cart'];
        $cart['customer'] = $updated_customer;

        $payment_method = isset($updated_customer_data['customer_last_order_payment_method']) ? $updated_customer_data['customer_last_order_payment_method'] : $cart['payment_method'];

        if( isset($request['session_key']) ){
            $session = $this->get_session_by_key($request['session_key']);
            if( is_array($session) AND !empty($session['cart']) ) {
                $cart['items'] = [];
                $this->add_customer_cart_items_to_current_cart($cart,$session['cart']);
            }
        }

        $cart['payment_method'] = $payment_method;

        $result = $this->get_calculated_cart($cart);

        if ($result instanceof WC_Data_Exception) {
            return $this->wpo_send_json_error($result->getMessage());
        }

        return $this->wpo_send_json_success(array('customer' => $updated_customer, 'cart' => $result));
    }

    protected function get_customer_by_type_and_id($id, $type = 'customer')
    {
        // to support customers from external systems --  !WC customers or WC orders
        // for example, Bookly
        $custom_result = apply_filters("wpo_get_customer_by_type_and_id", false, $id, $type);
        if ($custom_result) {
            return $custom_result;
        }

        switch ($type) {
            case 'order':
                return $this->get_customer_by_order(wc_get_order($id));
            case 'customer':
                return $this->get_customer_by_id($id);
        }

        return $this->get_customer_by_id($id);
    }

    protected function get_customer_by_id($customer_id)
    {
        $customer = get_user_meta($customer_id);

        if ( ! $customer) {
            return $customer;
        }

        //adjust default first and last names
        if ( ! isset($customer['billing_first_name']) or ! isset($customer['billing_last_name'])) {
            $user_first_name = get_user_meta($customer_id, "first_name", true);
            $user_last_name  = get_user_meta($customer_id, "last_name", true);
        }

        $customer_obj  = new WC_Customer($customer_id);
        $is_vat_exempt = 'yes' === $customer_obj->get_meta('is_vat_exempt');

        $customer_data = array(
            'id' => (string)$customer_id,

            'billing_first_name' => isset($customer['billing_first_name']) ? $customer['billing_first_name'][0] : $user_first_name,
            'billing_last_name'  => isset($customer['billing_last_name']) ? $customer['billing_last_name'][0] : $user_last_name,
            'billing_company'    => isset($customer['billing_company']) ? $customer['billing_company'][0] : '',
            'billing_address_1'  => isset($customer['billing_address_1']) ? $customer['billing_address_1'][0] : '',
            'billing_address_2'  => isset($customer['billing_address_2']) ? $customer['billing_address_2'][0] : '',
            'billing_city'       => isset($customer['billing_city']) ? $customer['billing_city'][0] : '',
            'billing_postcode'   => isset($customer['billing_postcode']) ? $customer['billing_postcode'][0] : '',
            'billing_country'    => isset($customer['billing_country']) ? $customer['billing_country'][0] : '',
            'billing_state'      => isset($customer['billing_state']) ? $customer['billing_state'][0] : '',
            'billing_email'      => isset($customer['billing_email']) ? $customer['billing_email'][0] : '',
            'billing_phone'      => isset($customer['billing_phone']) ? $customer['billing_phone'][0] : '',

            'shipping_first_name' => isset($customer['shipping_first_name']) ? $customer['shipping_first_name'][0] : '',
            'shipping_last_name'  => isset($customer['shipping_last_name']) ? $customer['shipping_last_name'][0] : '',
            'shipping_company'    => isset($customer['shipping_company']) ? $customer['shipping_company'][0] : '',
            'shipping_address_1'  => isset($customer['shipping_address_1']) ? $customer['shipping_address_1'][0] : '',
            'shipping_address_2'  => isset($customer['shipping_address_2']) ? $customer['shipping_address_2'][0] : '',
            'shipping_city'       => isset($customer['shipping_city']) ? $customer['shipping_city'][0] : '',
            'shipping_postcode'   => isset($customer['shipping_postcode']) ? $customer['shipping_postcode'][0] : '',
            'shipping_country'    => isset($customer['shipping_country']) ? $customer['shipping_country'][0] : '',
            'shipping_state'      => isset($customer['shipping_state']) ? $customer['shipping_state'][0] : '',

            'is_vat_exempt' => $is_vat_exempt,

            'other_order_url' => $this->get_customer_other_order_url($customer_id),
            'profile_url'     => $this->get_customer_profile_url($customer_id),

            'disable_checkout_link' => $customer_id ? is_super_admin($customer_id) : false,

            'show_profile_url' => current_user_can('edit_users'),

            'role' => $customer_obj->get_role(),
        );

        foreach ($this->customer_addition_full_keys() as $key) {
            $customer_data[$key] = isset($customer[$key][0]) ? $customer[$key][0] : '';
        }

        $customer_data = apply_filters('wpo_after_get_customer_by_id', $customer_data, $customer_obj);

        return $this->get_customer_by_array_data($customer_data);
    }

    /**
     * @param $order WC_Order
     *
     * @return array|bool
     */
    protected function get_customer_by_order($order)
    {
        if ( ! $order) {
            return false;
        }

        $is_vat_exempt = apply_filters(
            'woocommerce_order_is_vat_exempt',
            'yes' === $order->get_meta('is_vat_exempt'),
            $order
        );

        $customer_id = $order->get_customer_id();

        $customer_obj = new WC_Customer($customer_id);

        $customer_data = array(
            'id' => (string)$customer_id,

            'billing_first_name' => $order->get_billing_first_name(''),
            'billing_last_name'  => $order->get_billing_last_name(''),
            'billing_company'    => $order->get_billing_company(''),
            'billing_address_1'  => $order->get_billing_address_1(''),
            'billing_address_2'  => $order->get_billing_address_2(''),
            'billing_city'       => $order->get_billing_city(''),
            'billing_postcode'   => $order->get_billing_postcode(''),
            'billing_country'    => $order->get_billing_country(''),
            'billing_state'      => $order->get_billing_state(''),
            'billing_email'      => $order->get_billing_email(''),
            'billing_phone'      => $order->get_billing_phone(''),

            'shipping_first_name' => $order->get_shipping_first_name(''),
            'shipping_last_name'  => $order->get_shipping_last_name(''),
            'shipping_company'    => $order->get_shipping_company(''),
            'shipping_address_1'  => $order->get_shipping_address_1(''),
            'shipping_address_2'  => $order->get_shipping_address_2(''),
            'shipping_city'       => $order->get_shipping_city(''),
            'shipping_postcode'   => $order->get_shipping_postcode(''),
            'shipping_country'    => $order->get_shipping_country(''),
            'shipping_state'      => $order->get_shipping_state(''),

            'is_vat_exempt' => $is_vat_exempt,

            'other_order_url' => $this->get_customer_other_order_url($customer_id),
            'profile_url'     => $this->get_customer_profile_url($customer_id),

            'disable_checkout_link' => $customer_id ? is_super_admin($customer_id) : false,

            'show_profile_url' => current_user_can('edit_users'),

            'role' => $customer_obj ? $customer_obj->get_role() : '',
        );

        $customer_data = apply_filters('wpo_after_get_customer_by_order', $customer_data, $customer_obj);

        return $this->get_customer_by_array_data($customer_data);
    }

    protected function get_customer_profile_url($customer_id)
    {
        return $customer_id ? esc_url(add_query_arg('user_id', $customer_id, admin_url('user-edit.php'))) : "";
    }

    protected function get_customer_other_order_url($customer_id)
    {
        return $this->repository_orders->get_customer_other_order_url($customer_id);
    }

    protected function get_customer_by_array_data(array $customer_data = array())
    {
        if ( ! $customer_data) {
            return false;
        }

        if (empty($customer_data['billing_email']) and $customer_data['id']) {
            $customer_data['billing_email'] = get_userdata($customer_data['id'])->user_email;
        }

        $is_shipping_address_empty = true;

        $check_fields = array(
            "first_name",
            "last_name",
            "company",
            "address_1",
            "address_2",
            "city",
            "postcode",
            "country",
            "state",
            "phone",
        );

        foreach ($check_fields as $field) {
            if ( ! empty($customer_data["shipping_" . $field])) {
                $is_shipping_address_empty = false;
            }
        }

        $is_ship_different_address = false;

        if ( ! $is_shipping_address_empty) {
            foreach ($check_fields as $field) {
                if ( ! empty($customer_data["shipping_" . $field]) && $customer_data["shipping_" . $field] !== $customer_data["billing_" . $field]) {
                    $is_ship_different_address = true;
                }
            }
        }

        $customer_data['ship_different_address'] = $is_ship_different_address;

        $customer_data['formatted_billing_address'] = htmlspecialchars_decode(
            WC()->countries->get_formatted_address(apply_filters('wpo_customer_formatted_address', array(
                'first_name' => $customer_data['billing_first_name'],
                'last_name'  => $customer_data['billing_last_name'],
                'company'    => $customer_data['billing_company'],
                'address_1'  => $customer_data['billing_address_1'],
                'address_2'  => $customer_data['billing_address_2'],
                'city'       => $customer_data['billing_city'],
                'state'      => $customer_data['billing_state'],
                'postcode'   => $customer_data['billing_postcode'],
                'country'    => $customer_data['billing_country'],
            ), $customer_data, 'billing'))
        );

        $customer_data['formatted_shipping_address'] = htmlspecialchars_decode(
            WC()->countries->get_formatted_address(apply_filters('wpo_customer_formatted_address', array(
                'first_name' => $customer_data['shipping_first_name'],
                'last_name'  => $customer_data['shipping_last_name'],
                'company'    => $customer_data['shipping_company'],
                'address_1'  => $customer_data['shipping_address_1'],
                'address_2'  => $customer_data['shipping_address_2'],
                'city'       => $customer_data['shipping_city'],
                'state'      => $customer_data['shipping_state'],
                'postcode'   => $customer_data['shipping_postcode'],
                'country'    => $customer_data['shipping_country'],
            ), $customer_data, 'shipping'))
        );

        return apply_filters('wpo_get_customer_by_array_data', $customer_data);
    }

    protected function make_country_list()
    {
        $default_country_list = array(
            array(
                'value' => '',
                'title' => __('No value', 'phone-orders-for-woocommerce'),
            ),
        );
        foreach (WC()->countries->get_allowed_countries() as $code => $name) {
            $default_country_list[] = array(
                'value' => $code,
                'title' => $name,
            );
        }

        return $default_country_list;
    }

    protected function make_states_list()
    {
        $states_list = array();

        foreach (array_filter(WC()->countries->get_states()) as $country_code => $states) {
            $tmp_array = array(
                array(
                    'value' => '',
                    'title' => __('No value', 'phone-orders-for-woocommerce'),
                ),
            );

            foreach ($states as $state_code => $state_name) {
                $tmp_array[] = array(
                    'value' => $state_code,
                    'title' => $state_name,
                );
            }

            $states_list[$country_code] = $tmp_array;
        }

        return $states_list;
    }

    protected function ajax_get_countries_and_states_list($data)
    {
        return $this->wpo_send_json_success(array(
            'countries_list' => $this->make_country_list(),
            'states_list'    => $this->make_states_list(),
        ));
    }

    protected function make_tax_classes()
    {
        $tax_classes = array(
            array(
                'slug'  => "",
                'title' => __('Not taxable', 'phone-orders-for-woocommerce'),
            ),
            array(
                'slug'  => "standard",
                'title' => __('Standard rate', 'phone-orders-for-woocommerce'),
            ),
        );
        foreach (WC_Tax::get_tax_classes() as $tax_class_title) {
            $tax_classes[] = array(
                'slug'  => sanitize_title($tax_class_title),
                'title' => $tax_class_title,
            );
        }

        return $tax_classes;
    }

    protected function format_row_product($product, $delimiter = '|')
    {
        $custom_output = apply_filters('wpo_autocomplete_product_custom_output', false, $product);

        if ($custom_output) {
            return $custom_output;
        }

        $data           = array();
        $data['status'] = $this->get_stock_status_title($product);
        $data['qty']    = $product->get_stock_quantity();
        $data['price']  = $product->get_price_html();
        $data['sku']    = $product->get_sku();
        $data['name']   = rawurldecode($product->get_name());

        $order            = apply_filters(
            'wpo_autocomplete_product_fields',
            array('name', 'sku', 'price', 'status', 'qty')
        );
        $formatted_output = array();
        $option_handler   = $this->option_handler;
        foreach ($order as $field) {
            if ($option_handler->get_option('autocomplete_product_hide_' . $field)) {
                continue;
            }
            $formatted_output[] = $data[$field];
        }

        return join(' ' . $delimiter . ' ', array_filter($formatted_output));
    }

    protected function get_stock_status_title($product)
    {
        $defined_statuses = wc_get_product_stock_status_options();
        $status           = $product->get_stock_status();

        return isset($defined_statuses[$status]) ? $defined_statuses[$status] : $status;
    }

    protected function make_roles_list()
    {
        $role_list = array();
        foreach (get_editable_roles() as $role => $role_data) {
            $role_list[] = array('value' => $role, 'title' => translate_user_role($role_data['name']));
        }

        return $role_list;
    }

    protected function make_languages_list()
    {
        $language_list = array();

        require_once ABSPATH . 'wp-admin/includes/translation-install.php';
        $translations = wp_get_available_translations();

        /*
         * $parsed_args['languages'] should only contain the locales. Find the locale in
         * $translations to get the native name. Fall back to locale.
         */
        foreach (get_available_languages() as $locale) {
            if (isset($translations[$locale])) {
                $translation     = $translations[$locale];
                $language_list[] = array(
                    'value' => $translation['language'],
                    'title' => $translation['native_name'],
                );

                // Remove installed language from available translations.
                unset($translations[$locale]);
            } else {
                $language_list[] = array(
                    'value' => $locale,
                    'title' => $locale,
                );
            }
        }

        $language_list = array_merge(
            array(
                array('value' => 'site-default', 'title' => _x('Site Default', 'default site language', 'phone-orders-for-woocommerce')),
                array('value' => '', 'title' => 'English (United States)')
            ),
            $language_list
        );

        return $language_list;
    }

    protected function make_order_shipping_zones_list()
    {
        $shipping_zones = array();

        $zones = WC_Shipping_Zones::get_zones();

        $zone = new WC_Shipping_Zone(0);

        $zones[] = array(
            'id'               => $zone->get_id(),
            'zone_name'        => $zone->get_zone_name(),
            'shipping_methods' => $zone->get_shipping_methods(),
        );

        foreach ($zones as $zone) {
            $shipping_methods_list = array(
                array(
                    'value' => '',
                    'title' => __('Selected by WooCommerce', 'phone-orders-for-woocommerce'),
                ),
                array(
                    'value' => 'empty',
                    'title' => __('No default shipping method', 'phone-orders-for-woocommerce'),
                ),
            );

            foreach ($zone['shipping_methods'] as $method) {
                $shipping_methods_list[] = array(
                    'value' => sprintf('%s:%s', $method->id, $method->instance_id),
                    'title' => $method->method_title,
                );
            }

            $shipping_zones[] = array(
                'id'               => $zone['id'],
                'title'            => $zone['zone_name'],
                'shipping_methods' => $shipping_methods_list,
            );
        }

        return $shipping_zones;
    }

    protected function get_calculated_cart($cart)
    {
        $result = $this->updater->process($cart);

        if ($result instanceof WC_Data_Exception) {
            return $result;
        }

        if ($result['shipping'] && isset($result['shipping']['total_html'])) {
            unset($result['shipping']['total_html']);
        }

        $this->clear_cart_for_switch_user($cart['customer']['id']);

        return $result;
    }

    protected function get_updated_customer($id, $customer_data, $request)
    {
        $customer_data = wp_unslash($customer_data);

        $customer_data = $this->updater->update_customer($id, $customer_data);
        if ($customer_data instanceof WC_Data_Exception) {
            return $customer_data;
        } else {
            $customer = array();

            foreach (WC()->customer->get_billing() as $key => $value) {
                $customer['billing_' . $key] = $value;
            }

            foreach (WC()->customer->get_shipping() as $key => $value) {
                $customer['shipping_' . $key] = $value;
            }

            $customer['ship_different_address'] = $customer_data['ship_different_address'];

            foreach ($this->customer_addition_full_keys() as $key) {
                $field = $key;
                if ( ! $customer_data['ship_different_address']) { // shipping == billing
                    $field = str_replace('shipping_', 'billing_', $field);
                }
                $customer[$key] = ! empty($customer_data[$field]) ? $customer_data[$field] : '';
            }

            $customer['id']   = $id;
            $customer['role'] = WC()->customer->get_role();

            if (isset($customer_data['locale'])) {
                $locale = sanitize_text_field($customer_data['locale']);
                if ('site-default' === $locale) {
                    $locale = '';
                } elseif ('' === $locale) {
                    $locale = 'en_US';
                } elseif ( ! in_array($locale, get_available_languages(), true)) {
                    $locale = '';
                }
            } else {
                $locale = get_user_meta($id, 'locale', true);
            }

            $customer['locale'] = $locale;

            if ('en_US' === $customer['locale']) {
                $customer['locale'] = '';
            } elseif ('' === $customer['locale'] || ! in_array($customer['locale'], get_available_languages(), true)) {
                $customer['locale'] = 'site-default';
            }

            $customer['formatted_billing_address']  = htmlspecialchars_decode(
                WC()->countries->get_formatted_address(
                    apply_filters('wpo_customer_formatted_address', WC()->customer->get_billing(), $customer, 'billing')
                )
            );
            $customer['formatted_shipping_address'] = htmlspecialchars_decode(
                WC()->countries->get_formatted_address(
                    apply_filters(
                        'wpo_customer_formatted_address',
                        WC()->customer->get_shipping(),
                        $customer,
                        'shipping'
                    )
                )
            );

            $customer['is_vat_exempt'] = WC()->customer->is_vat_exempt();

            $customer['other_order_url'] = $this->get_customer_other_order_url($id);
            $customer['profile_url']     = $this->get_customer_profile_url($id);

            $customer['disable_checkout_link'] = $id ? is_super_admin($id) : false;

            $customer['show_profile_url'] = current_user_can('edit_users');

            $customer = apply_filters('wpo_after_update_customer', $customer, $request);

            $data = array('customer' => $customer);

            return apply_filters('wpo_update_customer_get_data', $data, $id);
        }
    }

    protected function refresh_usps_token($clientID, $clientSecret) {
        $url = 'https://apis.usps.com/oauth2/v3/token';

        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => $clientID,
            'client_secret' => $clientSecret,
            'scope' => 'addresses',
        ];

        $response = wp_remote_post($url, array(
            'body' => $body,
            'headers' => array()
        ));

        $respBody = json_decode($response['body']);

        if ( $response['response']['code'] !== 200 && !isset($respBody->access_token) ) {
            $errorMsg = isset($respBody->error_description) ? __('Unknown error', 'phone-orders-for-woocommerce') : $respBody->error_description;
            return $this->wpo_send_json_error($errorMsg);
        } else {
            $this->option_handler->set_option('address_validation_usps_key', $clientID);
            $this->option_handler->set_option('address_validation_usps_secret', $clientSecret);
            $this->option_handler->set_option('address_validation_usps_token', $respBody->access_token);
            $this->option_handler->set_option('address_validation_usps_token_expires', strval($respBody->issued_at + ($respBody->expires_in * 1000)));
            return $this->wpo_send_json_success('');
        }
    }

    protected function clear_cart_for_switch_user($switched_customer_id)
    {
        if (apply_filters(
                'wpo_must_switch_cart_user',
                $this->option_handler->get_option('switch_customer_while_calc_cart')
            ) &&
            ! empty($switched_customer_id)) {
            $old_user_id = get_current_user_id();
            wp_set_current_user($switched_customer_id);
        }

        WC()->cart->empty_cart();

        if (isset($old_user_id)) {
            wp_set_current_user($old_user_id);
        }
    }
}
