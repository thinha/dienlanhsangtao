<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class WC_Phone_Orders_Cart_Updater
{
    /**
     * @var WC_Phone_Orders_Settings
     */
    protected $option_handler;

    /**
     * @var bool
     */
    protected $subscription_plugin_enabled = false;

    /**
     * @var WC_Phone_Orders_Custom_Products_Controller|WC_Phone_Orders_Custom_Products_Controller_Pro
     */
    protected $custom_prod_control;

    /**
     * @var WC_Phone_Orders_Pricing_3_Cmp|WC_Phone_Orders_Pricing_4_Cmp
     */
    protected $pricing_cmp;

    protected $repository_orders;

    /**
     * @var WC_Phone_Woocs_Compatibility
     */
    protected $woocs_cmp;

    /**
     * WC_Phone_Orders_Cart_Updater constructor.
     *
     * @param WC_Phone_Orders_Settings $option_handler
     */
    public function __construct($option_handler)
    {
        $this->option_handler = $option_handler;


        if (did_action('wp_loaded')) {
            $this->subscription_plugin_enabled = self::subscriptions_is_enabled();
        } else {
            add_action('wp_loaded', function () {
                $this->subscription_plugin_enabled = self::subscriptions_is_enabled();
            });
        }

        if (class_exists("WC_Phone_Orders_Custom_Products_Controller_Pro")) {
            $this->custom_prod_control = new WC_Phone_Orders_Custom_Products_Controller_Pro();
        } else {
            $this->custom_prod_control = new WC_Phone_Orders_Custom_Products_Controller();
        }

        if ( ! defined("WC_ADP_VERSION") or version_compare(WC_ADP_VERSION, "4.0.0", "<")) {
            $this->pricing_cmp = new WC_Phone_Orders_Pricing_3_Cmp();
        } else {
            $this->pricing_cmp = new WC_Phone_Orders_Pricing_4_Cmp();
        }
        if ($this->pricing_cmp->is_pricing_active()) {
            $this->pricing_cmp->install_hook_to_catch_cart();
        }

        if (class_exists('WC_Phone_Repository_Orders_HPOS')) {
            $this->repository_orders = new WC_Phone_Repository_Orders_HPOS();
        } else {
            $this->repository_orders = new WC_Phone_Repository_Orders();
        }
    }

    protected static function subscriptions_is_enabled()
    {
        return class_exists('WC_Subscriptions') && class_exists('WC_Subscriptions_Product');
    }

    /**
     * @param array $cartItem
     *
     * @return float
     */
    protected function calculateFullLineSubtotalTax($cartItem)
    {
        if ( ! empty(WC()->customer) && WC()->customer->get_is_vat_exempt() && apply_filters(
                'woocommerce_adjust_non_base_location_prices',
                true
            )) {
            $product         = $cartItem['data'];
            $baseTaxRates    = WC_Tax::get_base_tax_rates($product->get_tax_class('unfiltered'));
            $baseTaxes       = WC_Tax::calc_tax(floatval($product->get_price('edit')), $baseTaxRates, true);
            $lineSubtotalTax = array_sum($baseTaxes) * $cartItem['quantity'];
        } elseif (isset($cartItem['line_tax_data']['subtotal'])) {
            $lineSubtotalTax = floatval(array_sum($cartItem['line_tax_data']['subtotal']));
        } elseif (isset($cartItem['line_subtotal_tax'])) {
            $lineSubtotalTax = floatval($cartItem['line_subtotal_tax']);
        } else {
            $lineSubtotalTax = floatval(0);
        }

        return $lineSubtotalTax;
    }

    public function process($cart_data, $force_not_selected_shipping = false)
    {
        $cart_data = apply_filters("wpo_process_cart_data_prepare", $cart_data);

        if ( ! defined('WOOCOMMERCE_CART')) {
            define('WOOCOMMERCE_CART', 1);
        }
        if ( ! defined('WOOCOMMERCE_CHECKOUT')) {
            define('WOOCOMMERCE_CHECKOUT', 1);
        }

        $cart_data = wp_parse_args($cart_data, array(
            'customer' => array(),
            'items'    => array(),
            'coupons'  => array(),
            // 'taxes'    => array(),
            'discount' => null,
            'shipping' => null,
        ));

        $old_user_id = false;
        // customer
        if ( ! empty ($cart_data['customer'])) {
            $customer_data = $cart_data['customer'];

            $id                     = isset($customer_data['id']) ? $customer_data['id'] : 0;
            $update_customer_result = $this->update_customer($id, $customer_data);
            if ($update_customer_result instanceof WC_Data_Exception) {
                return $update_customer_result;
            }
            if (apply_filters(
                'wpo_must_switch_cart_user',
                $this->option_handler->get_option('switch_customer_while_calc_cart')
            )) {
                $old_user_id = get_current_user_id();
                wp_set_current_user($id);
            }
            do_action('wdp_after_switch_customer_while_calc');
        } else {
            WC()->customer->set_calculated_shipping(true);//required since 3.5!
        }

        WC()->cart->empty_cart();

        if (apply_filters(
            "wpo_clear_shipping_selection",
            $this->option_handler->get_option('select_optimal_shipping')
        )) {
            $items_for_hash = array_map(function ($a) {
                    return array( (float)$a['qty'], (int)$a['product_id'], (int)$a['variation_id'] );
                },
                $cart_data['items']
            );
            $cart_items_hash = md5(json_encode( $items_for_hash ));
            //this option applied only if cart items changed
            if( $cart_items_hash != WC()->session->get("wpo_select_optimal_shipping_hash","") )
                unset($cart_data['shipping']['packages']);
            WC()->session->set("wpo_select_optimal_shipping_hash", $cart_items_hash);
        }

        $initial_shipping_methods = WC()->session->get('chosen_shipping_methods');
        WC()->shipping()->reset_shipping();
        wc_clear_notices(); // suppress front-end messages
        // Suppress total recalculation until finished.
        remove_action('woocommerce_add_to_cart', array(WC()->cart, 'calculate_totals'), 20);

        $dont_apply_rules = false;
        if ( ! empty($cart_data['dont_apply_pricing_rules']) || $this->option_handler->get_option(
                'dont_apply_pricing_rules'
            )) {
            add_filter('adp_rules_suppression', '__return_true');
            $dont_apply_rules = true;
        }

        add_filter( 'wc_avatax_cart_needs_calculation', "__return_true");//for Avatax
        do_action("wpo_before_update_cart", $cart_data);

        if (isset($cart_data['adp']['add_gifts_to_cart'])) {
            foreach ($cart_data['adp']['add_gifts_to_cart'] as $gift_data) {
                $gift_prod_id   = isset($gift_data['id']) ? (int)$gift_data['id'] : null;
                $qty            = isset($gift_data['qty']) ? (float)$gift_data['qty'] : null;
                $gift_hash      = isset($gift_data['gift_hash']) ? (string)$gift_data['gift_hash'] : null;
                $variation_data = isset($gift_data['data']['variation_data']) ? $gift_data['data']['variation_data'] : array();

                if ($gift_prod_id === null || $qty === null || $gift_hash === null) {
                    continue;
                }

                $this->pricing_cmp->gift_the_product($gift_prod_id, $qty, $gift_hash, $variation_data);
            }
        }

        $deleted_cart_items = array();

        //ignore stock status??
        if ($this->option_handler->get_option('sale_backorder_product')) {
            add_filter('woocommerce_product_is_in_stock', '__return_true');
            add_filter('woocommerce_product_backorders_allowed', '__return_true');
        }

        $already_reduced_product_ids = array();
        foreach ($cart_data['items'] as $item) {
            if (isset($item['reduced_stock'])) {
                $already_reduced_product_ids[] = ! empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
            }
        }

        add_filter('woocommerce_product_is_in_stock', function ($status, $product) use ($already_reduced_product_ids) {
            if (in_array($product->get_id(), $already_reduced_product_ids)) {
                $status = true;
            }

            return $status;
        }, 10, 2);

        add_filter(
            'woocommerce_product_backorders_allowed',
            function ($status, $product_id, $product) use ($already_reduced_product_ids) {
                if (in_array($product_id, $already_reduced_product_ids)) {
                    $status = true;
                }

                return $status;
            },
            10,
            3
        );

        $used_order_item_ids = array();

        $reflectionClass    = new ReflectionClass(WC()->cart);
        $reflectionProperty = $reflectionClass->getProperty('session');
        $reflectionProperty->setAccessible(true);

        $wc_cart_session = $reflectionProperty->getValue(WC()->cart);

        remove_action('woocommerce_add_to_cart', array($wc_cart_session, 'maybe_set_cart_cookies'));

        $this->woocs_cmp = new WC_Phone_Woocs_Compatibility(
            isset($cart_data['order_currency']['code']) ? $cart_data['order_currency']['code'] : ''
        );


        $order = null;
        if(!empty($cart_data['edit_order_id'])) {
            $order = wc_get_order($cart_data['edit_order_id']);
        }
        $is_edit_order = ! is_null($order);

        // items
        $cart_item_key___original_item = array();
        foreach ($cart_data['items'] as $item) {
            if ( ! empty($item['wpo_skip_item'])) {
                continue;
            }

            if ($this->custom_prod_control->is_custom_product($item['product_id'])) {
                $product = $this->custom_prod_control->restore_product_from_cart(WC()->cart, $item);
            } elseif (empty($item['variation_id'])) { // required field for checkout
                $item['variation_data'] = array();
                $product                = wc_get_product($item['product_id']);
            } else {
                if ( ! isset($item['variation_data']) or ! count($item['variation_data'])) {
                    $item['variation_data'] = isset($item['variation']) ? $item['variation'] : array();
                }

                $missing_variation_attributes = isset($item['missing_variation_attributes']) && is_array(
                    $item['missing_variation_attributes']
                ) ? $item['missing_variation_attributes'] : array();

                foreach ($missing_variation_attributes as $attribute) {
                    $slug = $attribute['key'];

                    if (empty($item['variation_data'][$slug])) {
                        $item['variation_data']['attribute_' . $slug] = $attribute['value'];
                    }
                }

                $product = wc_get_product($item['variation_id']);
            }

            $item_custom_meta_fields = isset($item['custom_meta_fields']) && is_array(
                $item['custom_meta_fields']
            ) ? $item['custom_meta_fields'] : array();

            $item['custom_meta_fields'] = $item_custom_meta_fields;

            $item = apply_filters("wpo_prepare_item", $item, $product);

            if ( ! $product or -1 == $item['product_id']) {
                continue;
            }

            if ('' === $product->get_regular_price() and ! $this->option_handler->get_option(
                    'hide_products_with_no_price'
                )) {
                $product->set_price('0');
                $product->set_regular_price('0');
                $product->save();
//				$item['item_cost'] = 0;
            }

            $quantity = isset($item['qty']) ? $item['qty'] : 0;
            $quantity = floatval($quantity);
            if (
                ! $this->option_handler->get_option('allow_to_input_fractional_qty')
                && ! apply_filters("wpo_allow_fractional_qty_for_product", false, $product)
            ) {
                $quantity = (int)round($quantity);
            }

//			if ( $item['qty'] < 1 ) {
//				$error                                     = __( 'Incorrect quantity value',
//					'phone-orders-for-woocommerce' );
//				$deleted_cart_items[] = array(
//                                    'id'   => $item['product_id'],
//                                    'name' => isset( $item['name'] ) ? $item['name'] : $product->get_name(),
//                                );
//				WC()->session->set( 'wc_notices', array() );
//				continue;
//			}

            $cart_item_meta                                    = defined(
                'WC_ADP_VERSION'
            ) ? array() : array('rand' => wp_rand());
            $cart_item_meta['wpo_key']                         = isset($item['key']) ? $item['key'] : '';
            $cart_item_meta['cost_updated_manually']           = isset($item['cost_updated_manually']) ? $item['cost_updated_manually'] : false;
            $cart_item_meta['allow_po_discount']               = isset($item['allow_po_discount']) ? $item['allow_po_discount'] : true;
            $cart_item_meta['wpo_item_cost']                   = isset($item['item_cost']) ? $item['item_cost'] : null;
            $cart_item_meta['custom_meta_fields']              = $item['custom_meta_fields'];
            $cart_item_meta['removed_custom_meta_fields_keys'] = isset($item['removed_custom_meta_fields_keys']) ? $item['removed_custom_meta_fields_keys'] : array();

            if ( ! empty($item['wpo_item_discount'])) {
                $cart_item_meta['wpo_item_discount'] = $item['wpo_item_discount'];
            }

            if ( ! empty($item['adp'])) {
                $cart_item_meta['adp'] = $item['adp'];
            }

            if ( ! empty($item['reduced_stock'])) {
                $cart_item_meta['reduced_stock'] = $item['reduced_stock'];
            }

            $cart_item_meta['wpo_item_cost'] = isset($item['item_cost']) ? $item['item_cost'] : null;

            $order_item_id = isset($item['order_item_id']) ? $item['order_item_id'] : false;
            if ($order_item_id && ! in_array($order_item_id, $used_order_item_ids)) {
                $cart_item_meta['order_item_id'] = isset($item['order_item_id']) ? $item['order_item_id'] : false;
                $used_order_item_ids[]           = $order_item_id;
            }

            if (isset($item['wscsd_start_date'])) {
                $cart_item_meta['wscsd_start_date'] = $item['wscsd_start_date'];
            }

            $cart_item_meta = apply_filters(
                'wpo_update_cart_cart_item_meta',
                $cart_item_meta,
                $item,
                $cart_data['items']
            );

            if ($this->custom_prod_control->is_custom_product($product)) {
                $cart_item_key = $this->custom_prod_control->add_to_cart(
                    WC()->cart,
                    $product,
                    $quantity,
                    $cart_item_meta
                );
            } else {
                try {
                    $cart_item_key = WC()->cart->add_to_cart(
                        $item['product_id'],
                        $quantity,
                        $item['variation_id'],
                        $item['variable_data']['selected_attributes'] ?? $item['variation_data'],
                        $cart_item_meta
                    );
                } catch (Exception $e) {
                    $cart_item_key = false;
                }
            }
            if ($cart_item_key and $this->option_handler->get_option(
                    'allow_to_rename_cart_items'
                ) && ! empty($item['custom_name'])) {
                WC()->cart->get_cart()[$cart_item_key]['data']->set_name($item['custom_name']);
            }

            if ($cart_item_key) {
                if (apply_filters(
                    'wpo_update_cart_set_cart_item_data_price',
                    wc_format_decimal((string)$item['item_cost'], wc_get_price_decimals()) != wc_format_decimal(
                        (string)$product->get_price(),
                        wc_get_price_decimals()
                    ),
                    $item,
                    $cart_item_key,
                    WC()->cart
                )) {
                    if( !empty($cart_item_meta['cost_updated_manually']) OR !$dont_apply_rules ) {
                            WC()->cart->get_cart()[$cart_item_key]['data']->set_price(
                                apply_filters(
                                    'wpo_update_cart_set_cart_item_data_set_price',
                                    $item['item_cost'],
                                    $item,
                                    $cart_item_key,
                                    WC()->cart
                                )
                            );
                    } else { // $dont_apply_rules and !cost_updated_manually
                            if ( $is_edit_order AND isset($item['order_item_id']) AND
                                !$this->option_handler->get_option('set_current_price_when_edit_order')) {
                                $found_order_item = false;
                                foreach ($order->get_items() as $order_item) {
                                    if ($order_item->get_id() == $item['order_item_id']) {
                                        $net_unit_price = $order_item->get_subtotal() / $order_item->get_quantity();
                                        if (wc_prices_include_tax()) {
                                            $base_tax_rates        = WC_Tax::get_base_tax_rates($product->get_tax_class('unfiltered'));
                                            $base_taxes            = WC_Tax::calc_tax($net_unit_price, $base_tax_rates, false);
                                            $order_item_unit_price = $net_unit_price + array_sum($base_taxes);
                                        } else {
                                            $order_item_unit_price = $net_unit_price;
                                        }
                                        WC()->cart->cart_contents[$cart_item_key]['data']->set_price($order_item_unit_price);
                                        $found_order_item = true;
                                        break;
                                    }
                                }
                                if(!$found_order_item)
                                    WC()->cart->cart_contents[$cart_item_key]['data']->set_price($product->get_price());
                            } else {
                                WC()->cart->cart_contents[$cart_item_key]['data']->set_price($product->get_price());
                            }
                    }
                }
                $cart_item_key___original_item[$cart_item_key] = $item;
//				WC()->cart->cart_contents[ $cart_item_key ] = apply_filters( 'wdp_after_cart_item_add', WC()->cart->cart_contents[ $cart_item_key ], $item );;
            } else {
                $deleted_cart_items[] = array(
                    'id'           => $item['product_id'],
                    'variation_id' => isset($item['variation_id']) ? $item['variation_id'] : null,
                    'name'         => $item['name'],
                    'key'          => ! empty($item['key']) ? $item['key'] : false,
                );

                WC()->session->set('wc_notices', array());
            }
        }
        $wc_cart_session->maybe_set_cart_cookies();

        // replacement of WC()->cart->calculate_totals() which avoids shipping calculation
//         (new WcCartPartialTotals(WC()->cart))->calculateTotalsWithoutShipping();
        WC()->cart->calculate_totals(); // we MUST call it :(
        if (WC()->session->get('chosen_shipping_methods') === null) {
            WC()->session->set('chosen_shipping_methods', $initial_shipping_methods);
        }

        if ( ! wc_prices_include_tax()) {
            foreach (WC()->cart->get_cart() as $cart_item_key => $item) {
                if (isset($cart_item_key___original_item[$cart_item_key])) {
                    $cart_item_key___original_item[$cart_item_key]['line_subtotal_after_add_to_cart']          = wc_format_decimal(
                        $item['line_subtotal']
                    );
                    $cart_item_key___original_item[$cart_item_key]['line_subtotal_with_tax_after_add_to_cart'] = wc_format_decimal(
                        $item['line_subtotal'] + $this->calculateFullLineSubtotalTax($item)
                    );
                }
            }
        }

        foreach ($cart_data['items'] as $item) {
            $this->pricing_cmp->set_variation_missing_attributes_for_gifts($item);
            $this->pricing_cmp->correct_free_items_qty($item);
        }

        if (isset($cart_data['adp']['remove_gifts_from_cart'])) {
            foreach ($cart_data['adp']['remove_gifts_from_cart'] as $remove_gift_data) {
                $product_id          = isset($remove_gift_data['product_id']) ? (int)$remove_gift_data['product_id'] : null;
                $qty                 = isset($remove_gift_data['qty']) ? (float)$remove_gift_data['qty'] : null;
                $variation_id        = isset($remove_gift_data['variation_id']) ? (int)$remove_gift_data['variation_id'] : null;
                $variation           = isset($remove_gift_data['variation']) ? (array)$remove_gift_data['variation'] : null;
                $gift_hash           = isset($remove_gift_data['gift_hash']) ? (string)$remove_gift_data['gift_hash'] : null;
                $selected            = isset($remove_gift_data['selected']) ? (bool)$remove_gift_data['selected'] : null;
                $free_cart_item_hash = isset($remove_gift_data['free_cart_item_hash']) ? (string)$remove_gift_data['free_cart_item_hash'] : null;

                if (
                    $product_id === null
                    || $qty === null
                    || $variation_id === null
                    || $variation === null
                    || $gift_hash === null
                    || $selected === null
                    || $free_cart_item_hash === null
                ) {
                    continue;
                }

                if ($selected) {
                    $this->pricing_cmp->remove_selected_gift($product_id, $qty, $variation_id, $variation, $gift_hash);
                } else {
                    $this->pricing_cmp->remove_gift($gift_hash, $free_cart_item_hash, $qty);
                }
            }
        }

        if (isset($cart_data['adp']['restore_gifts_cart'])) {
            foreach ($cart_data['adp']['restore_gifts_cart'] as $gift_data) {
                $gift_hash = isset($gift_data['gift_hash']) ? (string)$gift_data['gift_hash'] : null;

                if ($gift_hash === null) {
                    continue;
                }

                $this->pricing_cmp->restore_deleted_items($gift_hash);
            }
        }

        $cart_item_key___original_item = apply_filters('wpo_cart_original_items', $cart_item_key___original_item);

        //fee
        if (isset($cart_data['fee']) && is_array($cart_data['fee'])) {
            $fees_data         = apply_filters('wpo_cart_fees', $cart_data['fee'], $cart_data);
            $tax_class         = $this->option_handler->get_option('fee_tax_class');
            $cart_data_fee_ids = isset($cart_data['fee_ids']) ? $cart_data['fee_ids'] : array();
            $fee_ids           = array();
            $fee_cart_data     = array();
            add_action(
                'woocommerce_cart_calculate_fees',
                function () use ($fees_data, $tax_class, $cart_data_fee_ids, &$fee_ids, &$fee_cart_data) {
                    foreach ($fees_data as $index => $fee_data) {
                        $fee_data['name'] = preg_replace('#\(.*\)$#', '', $fee_data['name']); //clean up name
                        if ( ! isset($fee_data['type'])) {
                            $fee_data['type'] = 'fixed';
                        }
                        $fee_data['type'] = apply_filters('wpo_cart_fee_type', $fee_data['type'], $fee_data);
                        $id               = isset($fee_data['id']) ? $fee_data['id'] : sanitize_title(
                            $fee_data['name']
                        );
                        if (isset($fee_data['add_manually']) && $fee_data['add_manually'] || in_array(
                                $id,
                                $cart_data_fee_ids
                            )) {
                            $fixed_amount = $tax_class && wc_prices_include_tax(
                            ) ? (isset($fee_data['original_amount']) ? $fee_data['original_amount'] : $fee_data['amount']) - WC_Tax::get_tax_total(
                                    WC_Tax::calc_tax(
                                        (isset($fee_data['original_amount']) ? $fee_data['original_amount'] : $fee_data['amount']),
                                        WC_Tax::get_rates(
                                            WC_Tax::format_tax_rate_class($tax_class),
                                            WC()->cart->get_customer()
                                        ),
                                        true
                                    )
                                ) : (isset($fee_data['original_amount']) ? $fee_data['original_amount'] : $fee_data['amount']);
                            $perc_amount  = (float)$fee_data['original_amount'] / 100 * (wc_prices_include_tax() ? WC(
                                )->cart->subtotal : WC()->cart->subtotal_ex_tax);
                            WC()->cart->fees_api()->add_fee(array(
                                'id'        => $id,
                                'type'      => $fee_data['type'],
                                'name'      => $fee_data['name'] . ($fee_data['type'] == 'percent' ? " (" . $fee_data['original_amount'] . '%)' : ""),
                                'amount'    => ($fee_data['type'] == 'percent') ? $perc_amount : $fixed_amount,
                                'taxable'   => (bool)$tax_class,
                                'tax_class' => $tax_class
                            ));
                            $fee_ids[$id]       = $id;
                            $fee_cart_data[$id] = $fee_data;
                        }
                    }
                }
            );
        }

        $chosen_payment_method = ! empty($cart_data['payment_method']) ? $cart_data['payment_method'] : '';
        WC()->session->set('chosen_payment_method', $chosen_payment_method);
        $chosen_payment_method = WC()->session->get('chosen_payment_method');

        //new cart ready
        do_action('woocommerce_cart_loaded_from_session', WC()->cart);
        do_action('wdp_force_process_wc_cart', WC()->cart);

        do_action('wpo_gift_card_process_cards', $cart_data);

        $cart_actions              = isset($cart_data['actions']) ? $cart_data['actions'] : array();
        $at_least_one_coupon_added = false;
        $coupons_added             = [];
        foreach ($cart_actions as $action) {
            if (isset($action['action']) && $action['action'] === 'add_coupon') {
                $at_least_one_coupon_added = true;
                if ($coupons_name = isset($action['coupon']) ? $action['coupon'] : "") {
                    $coupons_added[] = $coupons_name;
                }
            }
        }

        $edit_order_coupons = array();

        if ( ! empty($cart_data['edit_order_id'])) {
            $edit_order = wc_get_order($cart_data['edit_order_id']);

            $_coupons = method_exists($edit_order, 'get_coupon_codes') ? $edit_order->get_coupon_codes(
            ) : $edit_order->get_used_coupons();

            foreach ($_coupons as $index => $value) {
                $edit_order_coupons[] = $value;
            }

            add_filter('woocommerce_coupon_validate_expiry_date', function ($valid, $coupon) use ($edit_order_coupons) {
                if (in_array($coupon->get_code(), $edit_order_coupons)) {
                    return false;
                }

                return $valid;
            }, 10, 2);

            add_filter('woocommerce_coupon_get_usage_limit', function ($value, $coupon) use ($edit_order_coupons) {
                if (in_array($coupon->get_code(), $edit_order_coupons)) {
                    return '';
                }

                return $value;
            }, 10, 2);

            add_filter(
                'woocommerce_coupon_validate_user_usage_limit',
                function ($valid, $user_id, $coupon) use ($edit_order_coupons) {
                    if (in_array($coupon->get_code(), $edit_order_coupons)) {
                        return false;
                    }

                    return $valid;
                },
                10,
                3
            );
        }

        // coupons

        //apply cart coupons
        foreach ($cart_data['coupons'] as $item) {
            $code = isset($item['code']) ? $item['code'] : (isset($item['title']) ? $item['title'] : false);
            if( !in_array($code, WC()->cart->get_applied_coupons()) ) // coupon can be aded already by another plugin!
                WC()->cart->add_discount($code);
            $coupon = new WC_Coupon(wc_format_coupon_code($code));
            if ($coupon->get_free_shipping() && $at_least_one_coupon_added && in_array($code, $coupons_added, true)) {
                unset($cart_data['shipping']['packages']);
                $packages                = WC()->shipping()->get_packages();
                $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

                foreach ($packages as $i => $package) {
                    $chosen_shipping_methods[$i] = 'free_shipping';
                }

                WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);
            }
        }
        $coupon_errors = wc_get_notices('error');

        // "Manual discount" is another coupon
        $manual_cart_discount_code = strtolower($this->option_handler->get_option('manual_coupon_title'));
        if ( ! empty($cart_data['discount']) && ($manual_cart_discount_code || $cart_data['discount']['name'])) {
            $discount           = $cart_data['discount'];
            $discount['amount'] = abs($discount['amount']);
            if ( ! empty($discount['name'])) {
                $manual_cart_discount_code = $discount['name'];
            }
            if (empty($discount['type'])) {
                $discount['type'] = 'fixed_cart';
            }
            add_filter(
                'woocommerce_coupon_get_amount',
                function ($_discount, $coupon) use ($discount, $manual_cart_discount_code) {
                    if ($coupon->get_code() != wc_format_coupon_code($manual_cart_discount_code)) {
                        return $_discount;
                    }

                    return $discount['amount'];
                },
                PHP_INT_MAX,
                2
            );
            //create new coupon via action
            add_action(
                'woocommerce_get_shop_coupon_data',
                function ($manual, $coupon) use ($discount, $manual_cart_discount_code) {
                    if ($coupon != wc_format_coupon_code($manual_cart_discount_code)) {
                        return $manual;
                    }

                    // fake coupon here
                    return array(
                        'amount'        => $discount['amount'],
                        'discount_type' => $discount['type'],
                        'id'            => -1,
                        'date_created'  => current_time('timestamp')
                    );
                },
                10,
                2
            );
            //Apply if "individual_use" coupons added
            add_filter(
                'woocommerce_apply_with_individual_use_coupon',
                function($apply, $the_coupon, $coupon, $applied_coupons ) use ($manual_cart_discount_code) {
                    if($the_coupon->get_code() == $manual_cart_discount_code)
                        $apply = true;
                    return $apply;
                },
                10,
                4
            );

            WC()->cart->add_discount($manual_cart_discount_code);
        }

        $shipping_proc = new WC_Phone_Orders_Cart_Shipping_Processor($this->option_handler);
        $shipping_proc::enable_preventing_to_select_method_for_certain_packages();

        $shipping_proc->prepare_shipping(
            WC()->session->get('chosen_shipping_methods'),
            WC()->cart->get_shipping_packages(),
            $cart_data,
            WC()->customer->is_vat_exempt()
        );

        $shipping_proc->process_custom_shipping($cart_data);

        $default_chosen_shipping_methods = $chosen_shipping_methods = $shipping_proc->get_chosen_methods();

        WC()->session->set('chosen_shipping_methods', $chosen_shipping_methods);
        $shipping_proc::purge_packages_from_session();

        $chosen_shipping_methods = WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();

        $manual_discount_value          = 0;
        $manual_discount_value_with_tax = 0;
        $applied_coupons                = array();
        $coupon_amounts                 = WC()->cart->get_coupon_discount_totals();
        $coupon_amounts_taxes           = WC()->cart->get_coupon_discount_tax_totals();

        foreach ($coupon_amounts as $coupon_code => $amount) {
            if ($coupon_code != wc_format_coupon_code($manual_cart_discount_code)) {
                $coupon            = new WC_Coupon($coupon_code);
                $code              = $coupon->get_code() ? $coupon->get_code() : $coupon_code;
                $title             = wp_strip_all_tags(
                    apply_filters(
                        'woocommerce_cart_totals_coupon_label',
                        $code,
                        $coupon
                    )
                ); // apply WC filter
                $applied_coupons[] = array(
                    'title'      => $title,
                    'code'       => $code,
                    'amount'     => $amount,
                    'tax_amount' => $amount + $coupon_amounts_taxes[$coupon_code],
                );
            } else {
                $manual_discount_value          = $amount;
                $manual_discount_value_with_tax = $amount + (isset($coupon_amounts_taxes[$coupon_code]) ? $coupon_amounts_taxes[$coupon_code] : 0);
            }
        }

        do_action('wpo_apply_fees_from_wc_cart', WC()->cart);

        $fees         = array();
        $applied_fees = array();

        foreach (WC()->cart->get_fees() as $fee_id => $fee_data) {
            $fees[$fee_data->name]['amount']          = wc_price($fee_data->amount);
            $fees[$fee_data->name]['amount_with_tax'] = wc_price($fee_data->amount + $fee_data->tax);

            $applied_fees[] = array(
                'id'              => $fee_data->id,
                'name'            => $fee_data->name,
                'type'            => $fee_data->type,
                'amount'          => (float)$fee_data->amount,
                'amount_with_tax' => (float)($fee_data->amount + $fee_data->tax),
                'original_amount' => isset($fee_cart_data[$fee_data->id]) ? $fee_cart_data[$fee_data->id]['original_amount'] : $fee_data->amount,
            );
        }


        $items               = array();
        $subtotal            = 0;
        $subtotal_with_tax   = WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();
        $used_order_item_ids = array();

        $list_of_selectable_gifts = array();
        if ($this->pricing_cmp->is_pricing_active()) {
            $list_of_selectable_gifts = $this->pricing_cmp->get_list_of_selectable_gifts();
        }

        $list_of_choose_gifts_option = array();

        foreach ($list_of_selectable_gifts as $hash => $qty) {
            $list_of_choose_gifts_option[] = array(
                'gift_hash'    => $hash,
                'qty'          => $qty,
                'text'         => sprintf(
                    // translators: Ability to add a certain number of free products to the cart
                    __('You can add %d products for free to the cart.', 'phone-orders-for-woocommerce'),
                    $qty
                ),
                'button_label' => __("Choose your gift", 'phone-orders-for-woocommerce')
            );
        }

        foreach (WC()->cart->get_cart() as $cart_key => $item) {
            $product_id = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];

            if ($this->custom_prod_control->is_custom_product($product_id)) {
                $product = $this->custom_prod_control->restore_product_from_cart(WC()->cart, $item);
            } else {
                $product = wc_get_product($product_id);
            }
            $item['qty']               = $item['quantity'];
            $item['sold_individually'] = $product->is_sold_individually();
            $item['is_readonly_price'] = $this->is_readonly_product_price($product_id, $item);
            $item['is_readonly_qty']   = $this->is_readonly_product_qty($item);
            $item['wpo_cart_item_key'] = $cart_key;
            $item['is_allow_delete']   = $this->is_allow_delete_product($item);
            if (isset($cart_item_key___original_item[$cart_key]) && apply_filters(
                    'wpo_update_cart_use_original_item',
                    true,
                    $cart_key
                )) {
                $item['item_cost'] = wc_format_decimal($cart_item_key___original_item[$cart_key]['item_cost']);
                if (isset($cart_item_key___original_item[$cart_key]['custom_name'])) {
                    $item['custom_name'] = $cart_item_key___original_item[$cart_key]['custom_name'];
                }
            } else {
                    $item['item_cost'] = $item['data']->get_price();
                /*
                if (wc_prices_include_tax()) {
                    $item['item_cost'] = wc_format_decimal(
                        ($item['line_subtotal'] + $this->calculateFullLineSubtotalTax($item)) / $item['qty']
                    );
                } else {
                    $item['item_cost'] = wc_format_decimal($item['line_subtotal'] / $item['qty']);
                }
                */

                $item['custom_name'] = $item['data']->get_name();
            }

            //final fix if NO tax at all
            if (!wc_tax_enabled() )
                $item['item_cost'] = wc_format_decimal($item['line_subtotal'] / $item['qty']);
            elseif($this->option_handler->get_option('hide_tax_line_product_item')) { // OR taxes hidden
                if (wc_prices_include_tax()) {
                    $item['item_cost'] = wc_format_decimal(($item['line_subtotal'] + $this->calculateFullLineSubtotalTax($item)) / $item['qty'] );
                } else {
                    $item['item_cost'] = wc_format_decimal($item['line_subtotal'] / $item['qty']);
                }
            }

            $item['item_cost'] = apply_filters('wpo_update_cart_item_cost', $item['item_cost'], $item);

            // price before pricing plugin was applied
            // will show price as wc_format_sale_price($item['original_price'], $item['price']) without wc_price()
            $item['original_price'] = $this->get_original_price($item);

            $order_item_id = ! empty($item['order_item_id']) ? $item['order_item_id'] : false;
            if ($order_item_id) {
                if (in_array($order_item_id, $used_order_item_ids)) {
                    unset(WC()->cart->cart_contents[$cart_key]['order_item_id']);
                    unset($item['order_item_id']);
                    $order_item_id = false;
                } else {
                    $used_order_item_ids[] = $order_item_id;
                }
            }


            $item['weight'] = $item['data']->has_weight() ? (float)$item['data']->get_weight() : '';

//			if ( ! empty( $cart_item_key___original_item[ $cart_key ]['key'] ) ) {
//				$item['key'] = $cart_item_key___original_item[ $cart_key ]['key'];
//            } else {

            if ($this->custom_prod_control->is_custom_product($product)) {
                $loaded_products = array(
                    $this->get_item_by_product($product, array(
                        'quantity' => $item['qty'],
                    ))
                );
            } else {
                $loaded_products = $this->get_formatted_product_items_by_ids(
                    array(
                        array(
                            'id'            => $product_id,
                            'qty'           => $item['qty'],
                            'reduced_stock' => isset($item['reduced_stock']) ? $item['reduced_stock'] : null
                        )
                    )
                );
            }

            if ( ! empty($loaded_products)) {
                $item['loaded_product']                  = reset($loaded_products);
                $item['loaded_product']['wpo_skip_item'] = apply_filters(
                    'wpo_skip_add_to_cart_item',
                    ! empty($item['wpo_skip_item']),
                    $item
                );

                $item['loaded_product']['wpo_child_item'] = apply_filters('wpo_is_child_cart_item', false, $item);
                $item['loaded_product']['children']       = apply_filters('wpo_children_cart_item', null, $item);

                $item['loaded_product']['wpo_hide_item_price'] = apply_filters(
                    'wpo_hide_cart_item_price',
                    ! empty($item['wpo_hide_item_price']),
                    $item
                );

                $key                                          = uniqid($item['item_cost']);
                $item['key']                                  = $key;
                $item['loaded_product']['key']                = $key;
                $item['loaded_product']['item_cost']          = $item['item_cost'];
                $item['loaded_product']['custom_meta_fields'] = ! empty($item['custom_meta_fields']) ? $item['custom_meta_fields'] : array();
                $item['loaded_product']['variation_data']     = $item['variation'];

                $item['loaded_product']['formatted_variation_data'] = static::get_formatted_variation_data(
                    $item['loaded_product']['variation_data'],
                    $product
                );

                $item['loaded_product']['variable_data'] = static::get_formatted_variable_data($product);
                if ($product->is_type('variation') && $this->option_handler->get_option('show_only_variable_product')) {
                    $item['loaded_product']['variable_data'] = static::get_formatted_variable_data(
                        wc_get_product($product->get_parent_id()),
                        $product,
                        $item['variation']
                    );
                }
                $item['loaded_product']['wpo_cart_item_key']               = $cart_key;
                $item['loaded_product']['cost_updated_manually']           = ! empty($item['cost_updated_manually']) ? $item['cost_updated_manually'] : false;
                $item['loaded_product']['allow_po_discount']               = isset($item['allow_po_discount']) ? $item['allow_po_discount'] : true;
                $item['loaded_product']['calc_line_subtotal']              = apply_filters(
                    'wpo_product_calc_line_subtotal',
                    ! $this->is_subscription($product_id),
                    $item
                );
                $item['loaded_product']['product_price_html']              = $item['data']->get_price_html();
                $item['loaded_product']['custom_name']                     = ! empty($item['custom_name']) ? $item['custom_name'] : '';
                $item['loaded_product']['removed_custom_meta_fields_keys'] = ! empty($item['removed_custom_meta_fields_keys']) ? $item['removed_custom_meta_fields_keys'] : array();
                $item['loaded_product']['wscsd_start_date']                = isset($item['wscsd_start_date']) ? $item['wscsd_start_date'] : null;
                if ($order_item_id) {
                    $item['loaded_product']['order_item_id'] = $order_item_id;
                }

                if ( ! empty ($item['wpo_item_discount'])) {
                    $item['loaded_product']['wpo_item_discount'] = $item['wpo_item_discount'];
                }

                $custom_prod_control = $this->custom_prod_control;
                if (defined(
                        get_class($custom_prod_control) . "::CART_ITEM_KEY"
                    ) && isset($item[$custom_prod_control::CART_ITEM_KEY])) {
                    $item['loaded_product'][$custom_prod_control::CART_ITEM_KEY] = $item[$custom_prod_control::CART_ITEM_KEY];
                }

                if ( ! empty ($item['adp'])) {
                    $item['loaded_product']['adp'] = $item['adp'];
                }


                $item['loaded_product']['weight'] = $item['weight'];

                $item['loaded_product']['readonly_custom_meta_fields_html'] = ! $this->option_handler->get_option(
                    'show_only_variable_product'
                ) ?
                    wc_get_formatted_cart_item_data($item) : '';

                $item['loaded_product'] = apply_filters(
                    'wpo_update_cart_loaded_product',
                    $item['loaded_product'],
                    $item
                );

                if ( ! empty($item['loaded_product']['missing_variation_attributes'])) {
                    foreach ($item['loaded_product']['missing_variation_attributes'] as &$attribute) {
                        if (isset($item['variation']['attribute_' . $attribute['key']])) {
                            $attribute['value'] = $item['variation']['attribute_' . $attribute['key']];
                        }
                    }
                }
            }
//            }

            if ($this->is_tax_enabled()) {
                if ( ! wc_prices_include_tax()) {
                    if (isset($cart_item_key___original_item[$cart_key])) {
                        $item['line_subtotal_after_add_to_cart']          = wc_format_decimal(
                            $cart_item_key___original_item[$cart_key]['line_subtotal_after_add_to_cart']
                        );
                        $item['line_subtotal_with_tax_after_add_to_cart'] = wc_format_decimal(
                            $cart_item_key___original_item[$cart_key]['line_subtotal_with_tax_after_add_to_cart']
                        );
                    } else {
                        $item['line_subtotal_after_add_to_cart']          = wc_format_decimal($item['line_subtotal']);
                        $item['line_subtotal_with_tax_after_add_to_cart'] = wc_format_decimal(
                            $item['line_subtotal'] + $this->calculateFullLineSubtotalTax($item)
                        );
                    }
                }

                $item['item_cost_with_tax']  =   wc_format_decimal(($item['line_subtotal'] + $item['line_subtotal_tax'])/ $item['qty']);
                $item['item_cost_with_tax_original'] = $this->adpGetOriginalPriceOfCartItem($cart_key);
                $item['line_total_with_tax']         = $item['line_subtotal'] + $item['line_subtotal_tax'];
            }

            $item = apply_filters('wpo_update_cart_item', $item);

            $items[] = $this->recursive_replace_nan($item);
            if ($this->is_tax_enabled() and $item['line_tax']) {
                if ( ! wc_prices_include_tax()) {
                    $subtotal += $item['line_subtotal_after_add_to_cart'];
                } else {
                    $subtotal += $item['line_subtotal'];
                }
            } else {
                $subtotal += $item['line_subtotal'];
            }
        }

        do_action('wpo_cart_updated_with_user');
        //disable default shipping method before final calculations, if necessary
        if ( empty($cart_data['shipping']['packages']) or
             isset($cart_data['shipping']['packages'][0]) and ($cart_data['shipping']['packages'][0]['chosen_rate'] === null)) {
            if ($default_chosen_shipping_methods[0] == WC_Phone_Orders_Cart_Shipping_Processor::METHOD_NOT_SELECTED
                OR $force_not_selected_shipping) { // load existing order without shipping!
                WC()->session->set('chosen_shipping_methods', [WC_Phone_Orders_Cart_Shipping_Processor::METHOD_NOT_SELECTED]);
                add_filter("woocommerce_shipping_chosen_method", function ($default, $rates, $chosen_method) {
                    return false;
                }, 1000, 3);
            }
        }

        $chosen_shipping_methods = WC()->cart->calculate_shipping();
        WC()->cart->calculate_totals();

        $reflectionClass    = new ReflectionClass(WC()->cart);
        $reflectionProperty = $reflectionClass->getProperty('shipping_methods');
        $reflectionProperty->setAccessible(true);

        $chosen_shipping_methods = $reflectionProperty->getValue(WC()->cart);

        $discount        = 0;
        $discountWithTax = 0;
        foreach ($items as $item) {
            if ( empty( $item['cost_updated_manually'] ) AND !empty($item['item_cost_with_tax_original']) ) {
                $discount        += ($item['data']->get_regular_price() - $item['data']->get_price()) * $item['qty'];
                $discountWithTax += ($item['item_cost_with_tax_original'] - $item['item_cost_with_tax']) * $item['qty'];
            }elseif(!empty($item['wpo_item_discount']) ) {
                $item_discount = ($item['wpo_item_discount']['original_price'] - $item['wpo_item_discount']['discounted_price']) * $item['qty'];
                $item_tax_rate = $item['line_subtotal'] ? $item['line_subtotal_tax']/$item['line_subtotal'] : 0;
                if ( wc_prices_include_tax() ) {
                    $discount        += $item_discount / (1 + $item_tax_rate);
                    $discountWithTax += $item_discount;
                } else {
                    $discount        += $item_discount;
                    $discountWithTax += $item_discount * (1 +$item_tax_rate);
                }
            }
        }

        $results = array(
            'subtotal'                 => apply_filters('wpo_cart_updated_subtotal', $subtotal, WC()->cart),
            'subtotal_with_tax'        => $subtotal_with_tax,
            'taxes'                    => WC()->cart->get_taxes_total(),
            'total'                    => (float)WC()->cart->get_total('edit'),
            'total_ex_tax'             => max(0, WC()->cart->get_total('edit') - WC()->cart->get_total_tax()),
            'total_custom_html'        => apply_filters(
                'wpo_cart_updated_total_custom_html',
                '',
                $cart_data,
                WC()->cart
            ),
            'discount'                 => WC()->cart->get_discount_total() + $discount,
            'discount_with_tax'        => WC()->cart->get_discount_total() + WC()->cart->get_discount_tax(
                ) + $discountWithTax,
            'discount_amount'          => $manual_discount_value,
            'discount_amount_with_tax' => $manual_discount_value_with_tax,
            'items'                    => $items,
            'shipping'                 => $this->get_shipping_packages($chosen_shipping_methods, $shipping_proc),
            'deleted_items'            => $deleted_cart_items,
            'applied_coupons'          => $applied_coupons,
            'applied_fees'             => $applied_fees,
            'payment_gateways'         => $this->make_order_payment_methods_list(false),
            'payment_method'           => $chosen_payment_method,
            'tax_totals'               => $this->get_tax_totals(),
            'fees'                     => $fees, // only for logging
            'fee_ids'                  => isset($fee_ids) ? $fee_ids : array(),
            'wc_price_settings'        => array(
                'currency'           => get_woocommerce_currency(),
                'currency_symbol'    => get_woocommerce_currency_symbol(),
                'decimal_separator'  => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'decimals'           => wc_get_price_decimals(),
                'price_format'       => get_woocommerce_price_format(),
            ),
            'wc_tax_settings'          => array(
                'prices_include_tax' => wc_prices_include_tax(),
            ),
            'additional_data'          => apply_filters('wpo_cart_updated_additional_data', array(), $cart_data),
            'wc_measurements_settings' => array(
                'show_weight_unit'    => wc_product_weight_enabled(),
                'weight_unit'         => get_option('woocommerce_weight_unit'),
                'show_dimension_unit' => wc_product_dimensions_enabled(),
                'dimension_unit'      => get_option('woocommerce_dimension_unit'),
            ),
            'gift_card'                => array(
                'enabled' => apply_filters('wpo_gift_card_enabled', false),
                'cards'   => apply_filters('wpo_gift_card_cards', array()),
                'errors'  => apply_filters('wpo_gift_card_errors', array()),
            ),
            'account_funds'=> array(
                'enabled' => apply_filters('wpo_account_funds_enabled', false),
                'amount'  => apply_filters('wpo_account_funds_amount', $cart_data["customer"]["id"]),
            ),
            'adp'                      => array(
                'list_of_choose_gifts_option' => $list_of_choose_gifts_option,
                'add_gifts_to_cart'           => array(),
                'remove_gifts_from_cart'      => array(),
                'removed_gifts_from_cart'     => ! empty($cart_data['adp']['remove_gifts_from_cart']) ? array_filter(
                    $cart_data['adp']['remove_gifts_from_cart'],
                    function ($item) {
                        return empty($item['selected']);
                    }
                ) : array(),
                'restore_gifts_cart'          => array(),
            ),
            'coupon_errors'            => $coupon_errors,
            'weight_total'             => round((float)WC()->cart->get_cart_contents_weight(), 3),
        );

        $results = apply_filters('wpo_cart_updated_result', $results, $cart_data, WC()->cart);
        //switch back to admin at the end!
        if ($old_user_id) {
            wp_set_current_user($old_user_id);
        }

        do_action('wpo_cart_updated');

        return $results;
    }

    protected function adpGetOriginalPriceOfCartItem($cart_key)
    {
        $item = isset(WC()->cart->cart_contents[$cart_key]) ? WC()->cart->cart_contents[$cart_key] : null;

        if ( ! $item) {
            return null;
        }

        $costUpdatedManually = ! empty($item['cost_updated_manually']) ? $item['cost_updated_manually'] : false;
        if ($costUpdatedManually) {
            return null;
        }

        $oldPrice = null;
        if (function_exists("adp_context")) {
            $context = adp_context();
            $facade  = new \ADP\BaseVersion\Includes\WC\WcCartItemFacade($item, $cart_key);
            if ($context->getOption('regular_price_for_striked_price')) {
                $oldPrice = $facade->getRegularPriceWithoutTax() + $facade->getRegularPriceTax();
            } else {
                $oldPrice = $facade->getOriginalPriceWithoutTax() + $facade->getOriginalPriceTax();
            }

            $newPrice = ($facade->getSubtotal() + $facade->getExactSubtotalTax()) / $facade->getQty();

            $oldPriceRounded = round($oldPrice, wc_get_price_decimals());
            $newPriceRounded = round($newPrice, wc_get_price_decimals());

            if ($oldPriceRounded <= $newPriceRounded) {
                $oldPrice = null;
            }
        }

        return $oldPrice;
    }

    public function update_customer($id, $customer_data)
    {
        if (isset($customer_data['ship_different_address'])) {
            // string 'false' to boolean false, otherwise boolean true
            $customer_data['ship_different_address'] = ! ($customer_data['ship_different_address'] === 'false' || $customer_data['ship_different_address'] === false);
        } else {
            $customer_data['ship_different_address'] = false;
        }
        // missed state/country ?
        $this->try_set_default_state_country($customer_data, 'billing');
        if ($customer_data['ship_different_address']) {
            $this->try_set_default_state_country($customer_data, 'shipping');
        }

        if ($id) {
            try {
                WC()->customer = new WC_Customer($id);
            } catch (Exception $e) {
                WC()->customer = new WC_Customer();
            }
        } else {
            WC()->customer = new WC_Customer();
        }
        $cart_customer = WC()->customer;

        //to fix calc tax based on shipping
        if ( ! isset ($customer_data['shipping_country'])) {
            $customer_data['shipping_country'] = '';
            $customer_data['shipping_state']   = '';
        }

        try {
            $exclude = array('ship_different_address');
            foreach ($customer_data as $field => $value) {
                if (in_array($field, $exclude)) {
                    continue;
                }

                $method = 'set_' . $field;

                if ( ! $customer_data['ship_different_address']) { // shipping == billing
                    $field = str_replace('shipping_', 'billing_', $field);
                }

                if (method_exists($cart_customer, $method)) {
                    $cart_customer->$method($customer_data[$field]);
                }
            }
        } catch (WC_Data_Exception $e) {
            return $e;
        }

        // fix shipping not applied to totals after WC 3.5 release
        WC()->customer->set_calculated_shipping(true);

        $cart_customer->apply_changes();

        do_action("wpo_set_cart_customer", $cart_customer, $id, $customer_data);

        return $customer_data;
    }

    protected function try_set_default_state_country(&$customer_data, $type)
    {
        if (empty($customer_data[$type . '_country'])) {
            $location                          = wc_get_customer_default_location();
            $customer_data[$type . '_state']   = $location['state'];
            $customer_data[$type . '_country'] = $location['country'];
        }
    }

    public function is_subscription($product_id)
    {
        return apply_filters(
            "wdp_is_subscription_product",
            $this->subscription_plugin_enabled && WC_Subscriptions_Product::is_subscription($product_id),
            $product_id
        );
    }

    public function is_readonly_product_price($product_id, $cart_item_data)
    {
        return apply_filters(
            'wpo_cart_item_is_price_readonly',
            $this->option_handler->get_option('is_readonly_price'),
            $cart_item_data
        );
    }

    public function is_readonly_product_qty($cart_item_data)
    {
        return apply_filters('wpo_cart_item_is_qty_readonly', false, $cart_item_data);
    }

    public function is_allow_delete_product($cart_item_data)
    {
        return apply_filters('wpo_cart_item_is_allow_delete', true, $cart_item_data);
    }

    protected function get_original_price($cart_item)
    {
        $price = apply_filters('wpo_set_original_price_after_calculation', false, $cart_item);

        return is_numeric(
                   $price
               ) && isset($cart_item['item_cost']) && (float)$price !== (float)$cart_item['item_cost'] ? (float)$price : false;
    }

    public function get_formatted_product_items_by_ids(array $ids = array())
    {
        if ($this->option_handler->get_option('show_long_attribute_names')) {
            add_filter("woocommerce_product_variation_title_include_attributes", "__return_true");
        }

        $items = array();

        $item_default_custom_meta_fields_option = $this->option_handler->get_option(
            'default_list_item_custom_meta_fields'
        );
        $item_custom_meta_fields                = array();

        if ($item_default_custom_meta_fields_option) {
            foreach (preg_split("/((\r?\n)|(\r\n?))/", $item_default_custom_meta_fields_option) as $line) {
                $line = explode('|', $line);
                if (count($line) > 1) {
                    $item_custom_meta_fields[] = array(
                        'id'         => '',
                        'meta_key'   => $line[0], //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                        'meta_value' => $line[1], //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                    );
                }
            }
        }

        foreach ($ids as $item) {
            $items[] = $this->get_item_by_product(wc_get_product($item['id']), array(
                'quantity'           => $item['qty'],
                'custom_meta_fields' => $item_custom_meta_fields,
                'item_cost'          => isset($item['item_cost']) ? $item['item_cost'] : '',
                'reduced_stock'      => isset($item['reduced_stock']) ? $item['reduced_stock'] : null,
            ));
        }

        return $items;
    }

    /**
     * @param WC_Product|WC_Product_Variation $product
     * @param array $item_data
     *
     * @return array
     */
    public function get_item_by_product($product, array $item_data = array())
    {
        $product = apply_filters('wpo_product_before_get_item', $product, $item_data);

        $item_id = $product->get_id();

        $qty = isset ($item_data['quantity']) && is_numeric($item_data['quantity']) ? $item_data['quantity'] : 1;
        $qty = floatval($qty);
        if (
            ! $this->option_handler->get_option('allow_to_input_fractional_qty')
            && ! apply_filters("wpo_allow_fractional_qty_for_product", false, $product)
        ) {
            $qty = round($qty);
        }

        if (isset($item_data['item_cost']) and is_numeric($item_data['item_cost'])) {
            $price_excluding_tax = $item_data['item_cost'];
        } else {
            /**
             * Action wpo_get_item_by_product_default_price_context
             *
             * Sometimes we need to get this price without hooks!
             * E.g. With dynamic pricing the price is already calculated with hooks in filter "wpo_product_before_get_item"
             */
            $context =  apply_filters('wpo_get_item_by_product_default_price_context', 'view', $product, $item_data);
            $price_excluding_tax = (float)$product->get_price($context);
             if( $product->is_on_sale($context) )
                 $price_excluding_tax = $product->get_sale_price($context);
        }

        $item_meta_data = array();
        if ($product->is_type('variation')) {
            $variation_id = $item_id;
            $product_id   = $product->get_parent_id();

            if ( ! empty($item_data['meta_data']) && is_array($item_data['meta_data'])) {
                foreach ($item_data['meta_data'] as $meta_datum) {
                    /**
                     * @var WC_Meta_Data $meta_datum
                     */
                    $meta                         = $meta_datum->get_data();
                    $item_meta_data[$meta['key']] = $meta['value'];
                }
            }
        } else {
            $variation_id = '';
            $product_id   = $item_id;
        }

        $product_attributes = array();
        if ($variation_id) {
            $product_attributes = $product->get_variation_attributes();
            foreach ($product_attributes as $key => $value) {
                $metaKey = str_replace('attribute_', '', $key);
                if ($value === '' && isset($item_meta_data[$metaKey])) {
                    $product_attributes[$key] = $item_meta_data[$metaKey];
                }
            }
        }

        $is_subscribed_item = $this->is_subscription($item_id);

        $item_cost = (float)$price_excluding_tax;

        $is_readonly_price = $this->is_readonly_product_price($item_id, $item_data);

        $post_id = $product->get_parent_id() ? $product->get_parent_id() : $item_id;

        $missing_variation_attributes = array();
        if ($variation_id && ! $this->option_handler->get_option('show_only_variable_product')) {
            $attributes = $product->get_attributes();
            foreach ($attributes as $attribute => $value) {
                if ( ! $value) {
                    $value_label         = array();
                    $parent              = wc_get_product($post_id);
                    $variable_attributes = $parent->get_attributes();
                    if ( ! empty($variable_attributes[$attribute])) {
                        $variable_attribute = $variable_attributes[$attribute];

                        if ($variable_attribute->is_taxonomy()) {
                            $values = wc_get_product_terms($product_id, $attribute);
                            /** @var WP_Term[] $values */
                            foreach ($values as $tmp_term) {
                                $value_label[] = array(
                                    'value' => $tmp_term->slug,
                                    'label' => $tmp_term->name,
                                );
                            }
                        } else {
                            $values = $variable_attribute->get_options();
                            foreach ($values as $tmp_value) {
                                $value_label[] = array(
                                    'value' => $tmp_value,
                                    'label' => $tmp_value,
                                );
                            }
                        }
                    }


                    $missing_variation_attributes[] = array(
                        'key'    => $attribute,
                        'label'  => wc_attribute_label($attribute, $product),
                        'value'  => ! empty($item_meta_data[$attribute]) ? $item_meta_data[$attribute] : (empty($value_label) ? "" : current(
                            $value_label
                        )['value']),//fix woocommerce 4.5 variation check
                        'values' => $value_label,
                    );
                }
            }
        }

        $default_qty_step = $this->option_handler->get_option('allow_to_input_fractional_qty') || apply_filters(
            "wpo_allow_fractional_qty_for_product",
            false,
            $product
        ) ? '0.01' : '1';

        if ( ! $product->managing_stock()) { //we know only status for the product
            $in_stock = null;
            if (isset($item_data['reduced_stock']) and $product->get_stock_status() == "outofstock") {
                $in_stock = $item_data['reduced_stock'];
            }
        } else { // manage stock qty
            if (isset($item_data['reduced_stock'])) //edit existing order ?
            {
                $in_stock = $product->get_stock_quantity() > 0 ? $product->get_stock_quantity(
                    ) + $item_data['reduced_stock'] : $item_data['reduced_stock'];
            } else {
                $in_stock = $product->backorders_allowed() ? null : $product->get_stock_quantity();
            }
        }

        if ( ! is_null($in_stock) && $in_stock > 0 && 1 > $in_stock && $qty == 1) {
            $qty = $in_stock;
        }

        $permalink    = $product->get_permalink();
        $product_link = admin_url('post.php?post=' . absint($post_id) . '&action=edit');

        if ($this->custom_prod_control->is_custom_product($product)) {
            $permalink    = "";
            $product_link = "";
        }

        $qty_step = apply_filters('woocommerce_quantity_input_step', $default_qty_step, $product);

        if ( ! is_numeric($qty_step)) {
            $qty_step = $default_qty_step;
        }

        $min_qty = apply_filters('woocommerce_quantity_input_min', $default_qty_step, $product);

        if ( ! is_numeric($min_qty)) {
            $min_qty = $default_qty_step;
        }

        $args = apply_filters('woocommerce_quantity_input_args', [
            'input_value' => $min_qty,
            'max_value'   => $in_stock,
            'min_value'   => $min_qty,
            'step'        => $qty_step,
        ], $product);

        return apply_filters('wpo_get_item_by_product', array(
            'product_id'                      => $product_id,
            'item_cost'                       => wc_format_decimal($item_cost, wc_get_price_decimals()),
            'product_price_html'              => $product->get_price_html(),
            'variation_id'                    => $variation_id,
            'variation_data'                  => $product_attributes,
            'variable_data'                   => static::get_formatted_variable_data($product),
            'custom_meta_fields'              => isset($item_data['custom_meta_fields']) ? $item_data['custom_meta_fields'] : array(),
            'missing_variation_attributes'    => $variation_id ? $missing_variation_attributes : '',
            'name'                            => $product->get_name(),
            'qty'                             => max($qty, $args["input_value"]),
            'type'                            => 'line_item',
            'in_stock'                        => min($args["max_value"], $in_stock),
            'decimals'                        => wc_get_price_decimals(),
            'qty_step'                        => $args["step"],
            'min_qty'                         => $args["min_value"],
            'is_enabled_tax'                  => $this->is_tax_enabled(),
            'is_price_included_tax'           => wc_prices_include_tax(),
            'sku'                             => esc_html($product->get_sku()),
            'thumbnail'                       => $this->get_thumbnail_src_by_product($product),
            'product_link'                    => $product_link,
            'permalink'                       => $permalink,
            'is_subscribed'                   => $is_subscribed_item,
            'is_readonly_price'               => $is_readonly_price,
            'is_readonly_qty'                 => $this->is_readonly_product_qty($item_data),
            'is_allow_delete'                 => $this->is_allow_delete_product($item_data),
            'line_total_with_tax'             => null,
            'item_cost_with_tax'              => null,
            'sold_individually'               => $product->is_sold_individually(),
            'key'                             => uniqid(),
            'extra_col_value'                 => apply_filters('wpo_product_extra_col_value', '', $product, WC()->cart),
            'calc_line_subtotal'              => apply_filters(
                'wpo_product_calc_line_subtotal',
                ! $is_subscribed_item,
                $item_data
            ),
            'cost_updated_manually'           => isset($item_data['item_cost']) && $item_data['item_cost'] !== '',
            'allow_po_discount'               => true,
            'description'                     => $product->get_description(),
            'reduced_stock'                   => isset($item_data['reduced_stock']) ? $item_data['reduced_stock'] : null,
            'removed_custom_meta_fields_keys' => isset($item_data['removed_custom_meta_fields_keys']) ? $item_data['removed_custom_meta_fields_keys'] : array(),
            'wscsd_start_date'                => isset($item_data['wscsd_start_date']) ? $item_data['wscsd_start_date'] : null,
        ), $item_data, $product);
    }

    private function recursive_replace_nan($a)
    {
        foreach ($a as $key => $item) {
            $new_item = $item;

            if ( ! is_array($item) && ! is_object($item)) {
                if ( ! is_string($item) && ! is_null($item) && is_nan($item)) {
                    $new_item = "NAN";
                }
            } else {
                $new_item = $this->recursive_replace_nan($item);
            }

            if (is_array($a)) {
                $a[$key] = $new_item;
            } elseif (is_object($a)) {
                $a->$key = $new_item;
            }
        }

        return $a;
    }

    /**
     * @return bool
     */
    public function is_tax_enabled()
    {
        return wc_tax_enabled() && ! WC()->customer->get_is_vat_exempt();
    }

    /**
     * @return bool
     */
    protected function is_free_shipping_coupon_applied()
    {
        foreach (WC()->cart->get_applied_coupons() as $coupon_code) {
            $coupon = new WC_Coupon($coupon_code);
            if ($coupon->get_free_shipping()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $chosen_shipping_methods
     * @param WC_Phone_Orders_Cart_Shipping_Processor $shipping_proc
     *
     * @return array
     */
    protected function get_shipping_packages($chosen_shipping_methods, $shipping_proc)
    {
        $settings_handler = WC_Phone_Orders_Settings::getInstance();
        WC()->shipping()->load_shipping_methods();
        $shipping               = array();
        $shipping['total_html'] = WC()->cart->get_cart_shipping_total();  // only for logging

        $shipping['is_free_shipping_coupon_applied'] = $this->is_free_shipping_coupon_applied();

        foreach (WC()->shipping()->get_packages() as $package_key => $package) {
            if (empty($package['contents'])) {
                continue;
            }

            $contents = array();
            foreach ($package['contents'] as $cart_item) {
                $product = $cart_item['data'];
                /**
                 * @var $product WC_Product
                 */
                $contents[] = array(
                    'title'    => $product->get_title(),
                    'quantity' => $cart_item['quantity'],
                );
            }

            $hash = $shipping_proc::calculate_package_hash($package);
            if ( ! isset($hash)) {
                continue;
            }

            $shipping_rates = array();
            if (isset($package['rates'])) {
                $shipping_rates = array_values(
                    array_filter(
                        array_map(function ($rate) use ($settings_handler) {
                            /**
                             * @var WC_Shipping_Rate $rate
                             */
                            if ($settings_handler->get_option('show_shipping_methods') === 'inline' &&
                                class_exists("WC_Phone_Shipping_Method_Custom_Price") &&
                                $rate->get_method_id() === WC_Phone_Shipping_Method_Custom_Price::ID) {
                                return null;
                            } else {
                                return array(
                                    'id'        => $rate->get_id(),
                                    'label'     => $rate->get_label(),
                                    'cost'      => floatval($rate->get_cost()),
                                    'tax'       => floatval($rate->get_shipping_tax()),
                                    'full_cost' => floatval($rate->get_cost()) + floatval($rate->get_shipping_tax()),
                                );
                            }
                        }, $package['rates'])
                    )
                );
            }

            $chosen_rate = isset($chosen_shipping_methods[$package_key]) ? $chosen_shipping_methods[$package_key] : null;

            if (isset($chosen_rate)) {
                /**
                 * @var WC_Shipping_Rate $chosen_rate
                 */
                $chosen_rate = array(
                    'id'        => $chosen_rate->get_id(),
                    'label'     => $chosen_rate->get_label(),
                    'cost'      => floatval($chosen_rate->get_cost()),
                    'tax'       => floatval($chosen_rate->get_shipping_tax()),
                    'full_cost' => floatval($chosen_rate->get_cost()) + floatval($chosen_rate->get_shipping_tax()),
                );
            }

            $shipping['packages'][] = array(
                'hash'         => $hash,
                'chosen_rate'  => $chosen_rate,
                'contents'     => $contents,
                'rates'        => $shipping_rates,
                'custom_price' => $shipping_proc->get_custom_price_data_for_package($package),
                'custom_title' => $shipping_proc->get_custom_title_data_for_package($package),
            );
        }

        return $shipping;
    }

    protected function get_tax_totals()
    {
        $taxes      = WC()->cart->get_taxes();
        $tax_totals = array();

        foreach ($taxes as $key => $tax) {
            $code = WC_Tax::get_rate_code($key);

            if ($code || apply_filters('woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated') === $key) {
                if ( ! isset($tax_totals[$code])) {
                    $tax_totals[$code]         = new stdClass();
                    $tax_totals[$code]->amount = 0;
                }
                $tax_totals[$code]->tax_rate_id       = $key;
                $tax_totals[$code]->is_compound       = WC_Tax::is_compound($key);
                $tax_totals[$code]->label             = WC_Tax::get_rate_label($key);
                $tax_totals[$code]->formatted_percent = WC_Tax::get_rate_percent($key);
                $tax_totals[$code]->amount            += wc_round_tax_total($tax);
            }
        }

        if (apply_filters('woocommerce_cart_hide_zero_taxes', true)) {
            $amounts    = array_filter(wp_list_pluck($tax_totals, 'amount'));
            $tax_totals = array_intersect_key($tax_totals, $amounts);
        }

        return $tax_totals;
    }

    /**
     * @param WC_Product $product
     *
     * @return string
     */
    public function get_thumbnail_src_by_product($product)
    {
        $src = '';

        if (preg_match('/src\=["|\'](.*?)["|\']/i', $product->get_image(), $matches)) {
            $src = $matches[1];
        }

        return $src;
    }

    public function make_cart_payment_methods_list($force_shipping = true)
    {
        if ($force_shipping) {
            add_filter("woocommerce_cart_needs_shipping", "__return_true", 111);
        }

        $order_payment_methods_list = array(
            array(
                'value' => '',
                'title' => __('No value', 'phone-orders-for-woocommerce'),
            ),
        );
        /*
         * Store and load $wc_queued_js global variable to prevent print js code from
         * WC_Shipping_Free_Shipping->get_instance_form_fields() every time program calls
         * WC_Shipping_Free_Shipping->get_admin_options_html()
         * */
        global $wc_queued_js;
        $wc_queued_js_temp = $wc_queued_js;
        foreach (WC()->payment_gateways()->get_available_payment_gateways() as $s => $method) {
            /** @var WC_Payment_Gateway $method */
            if ($method->enabled === 'yes') {
                $order_payment_methods_list[] = array(
                    'value' => $s,
                    'title' => $method->title ?: $method->method_title,
                );
            }
        }
        $wc_queued_js = $wc_queued_js_temp;

        if ($force_shipping) {
            remove_filter("woocommerce_cart_needs_shipping", "__return_true", 111);
        }

        return $order_payment_methods_list;
    }

    public function make_order_payment_methods_list($force_shipping = true)
    {
        if ($force_shipping) {
            add_filter("woocommerce_cart_needs_shipping", "__return_true", 111);
        }

        $order_payment_methods_list = array(
            array(
                'value' => '',
                'title' => __('No value', 'phone-orders-for-woocommerce'),
            ),
        );
        /*
         * Store and load $wc_queued_js global variable to prevent print js code from
         * WC_Shipping_Free_Shipping->get_instance_form_fields() every time program calls
         * WC_Shipping_Free_Shipping->get_admin_options_html()
         * */
        global $wc_queued_js;
        $wc_queued_js_temp = $wc_queued_js;
        foreach (WC()->payment_gateways()->payment_gateways() as $s => $method) {
            /** @var WC_Payment_Gateway $method */
            if ($method->enabled === 'yes') {
                $order_payment_methods_list[] = array(
                    'value' => $s,
                    'title' => $method->title ?: $method->method_title,
                );
            }
        }
        $wc_queued_js = $wc_queued_js_temp;

        if ($force_shipping) {
            remove_filter("woocommerce_cart_needs_shipping", "__return_true", 111);
        }

        return apply_filters("wpo_make_order_payment_methods_list", $order_payment_methods_list);
    }

    public static function get_formatted_variable_data($_product, $variation = null, $variationData = null)
    {
        if ( ! $_product->is_type('variable')) {
            return null;
        }

        $optionHandler = WC_Phone_Orders_Settings::getInstance();

        $selectedAttributes = [];
        if ($variationData) {
            foreach ($variationData as $key => $slug) {
                $taxonomy = str_replace('attribute_', '', $key);
                $term     = get_term_by('slug', $slug, $taxonomy);
                if ($term) {
                    $selectedAttributes[$key] = $term->name;
                } else {
                    $selectedAttributes[$key] = $slug;
                }
            }
        } elseif ($variation) {
            $attributes = $variation->get_attributes();
            $data_array = array();

            if ($attributes) {
                foreach ($attributes as $meta_key => $meta) {
                    $display_key              = 'attribute_' . $meta_key;
                    $display_value            = $variation->get_attribute($meta_key);
                    $data_array[$display_key] = $display_value;
                }
            }

            $selectedAttributes = $data_array;
        }

        $variations = [];
        foreach ($_product->get_available_variations() as $data) {
            $variationProduct = wc_get_product($data['variation_id']);
            if (( ! $optionHandler->get_option('sale_backorder_product') && ! $variationProduct->is_in_stock()) ||
                ($optionHandler->get_option('hide_products_with_no_price') && $variationProduct->get_regular_price(
                    ) === '') ||
                ( ! $optionHandler->get_option('sell_disable_variation') && ! $variationProduct->is_purchasable())) {
                continue;
            }
            $attributes = $variationProduct->get_attributes();
            $data_array = array();

            if ($attributes) {
                foreach ($attributes as $meta_key => $meta) {
                    $display_key              = 'attribute_' . $meta_key;
                    $display_value            = $variationProduct->get_attribute($meta_key);
                    $data_array[$display_key] = $display_value;
                }
            }
            $variations[] = [
                'price'        => $variationProduct->get_price(),
                'attributes'   => $data_array,
                'sku'          => $variationProduct->get_sku(),
                'variation_id' => $variationProduct->get_id()
            ];
        }

        $attributes      = [];
        $attributeLabels = [];
        foreach ($_product->get_attributes() as $taxonomy => $attribute) {
            if ($attribute->is_taxonomy()) {
                foreach ($attribute->get_terms() as $term) {
                    $attributes['attribute_' . $taxonomy][] = $term->name;
                }
            } else {
                foreach ($attribute->get_options() as $option) {
                    $attributes['attribute_' . $taxonomy][] = $option;
                }
            }
            $attributeLabels['attribute_' . $taxonomy] = wc_attribute_label($taxonomy, $_product);
        }

        return [
            'variations'          => $variations,
            'attributes'          => $attributes,
            'selected_attributes' => $selectedAttributes,
            'attribute_labels'    => $attributeLabels
        ];
    }

    public static function get_formatted_variation_data($variation_data, $_product)
    {
        if ( ! is_array($variation_data)) {
            return array();
        }

        foreach ($variation_data as $attr_key => $attr_value) {
            unset($variation_data[$attr_key]);
            if ($attr_value === "") {
                continue;
            }
            $attr = wc_attribute_label(str_replace('attribute_', '', $attr_key), $_product);

            $value = $_product->get_attribute(str_replace('attribute_', '', $attr_key));

            if ($value === "") {
                continue;
            }

            $variation_data[$attr] = $value;
        }

        return $variation_data;
    }
}
