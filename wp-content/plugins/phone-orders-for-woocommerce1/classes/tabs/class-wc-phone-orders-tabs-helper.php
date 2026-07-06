<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Phone_Orders_Tabs_Helper
{

    public static function get_tabs()
    {
        include_once self::get_classes_path() . 'abstract-wc-phone-orders-tab.php';
        foreach (glob(self::get_classes_path() . 'class-*.php') as $filename) {
            include_once $filename;
        }

        $tabs = array(
            'add-order' => new WC_Phone_Orders_Add_Order_Page(),
            'log-page'  => new WC_Phone_Orders_Log_Page(),
        );

        if (is_super_admin()) {
            $tabs['settings'] = new WC_Phone_Orders_Settings_Page();
            $tabs['help']     = new WC_Phone_Orders_Help_Page();
            $tabs['tools']    = new WC_Phone_Orders_Tools_Page();
        }

        $tabs = apply_filters('wpo_admin_tabs', $tabs);

        uasort($tabs, function ($tab1, $tab2) {
            $priority1 = (int)isset($tab1->priority) ? $tab1->priority : 1000;
            $priority2 = (int)isset($tab2->priority) ? $tab2->priority : 1000;

            if ($priority1 <= $priority2) {
                return -1;
            } else {
                return 1;
            }
        });

        return $tabs;
    }

    public static function get_classes_path()
    {
        return WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/tabs/';
    }

    public static function get_current_tab_name()
    {
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return isset($_REQUEST['tab']) ? sanitize_text_field(wp_unslash($_REQUEST['tab'])) : self::get_default_tab_name();
    }

    public static function get_default_tab_name()
    {
        return 'add-order';
    }

    public static function get_views_path()
    {
        return WC_PHONE_ORDERS_PLUGIN_PATH . 'views/';
    }

    public static function add_log($id, $result, $order_id = 0)
    {
        global $wpdb;
        if ( ! $id) {
            return false;
        }
        $option_handler = WC_Phone_Orders_Settings::getInstance();

        $time = current_time('mysql');

        $user     = wp_get_current_user();
        $username = $user->user_login;
        $user_id  = $user->ID;

        $order        = wc_get_order($order_id);
        $order_number = ! $order ? $order_id : $order->get_order_number();

        $customer    = WC()->cart->get_customer();
        $customer_id = $customer->get_id();

        $customer = self::create_row_customer_column($result);
        $items    = self::create_row_items_column($result);
        $discount = self::create_row_discount_column($result);
        $fees     = self::create_row_fees_column($result);
        $shipping = self::create_row_shipping_method_column($result);
        $total    = self::create_row_totals_column($result);

        $table_name = esc_sql(WC_Phone_Orders_Loader::$log_table_name);

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
        $count = $wpdb->query($wpdb->prepare("SELECT ID FROM $table_name WHERE ID=%s", array($id)));
        if ($count) {
            if ($order_id) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $r = $wpdb->query(
                    $wpdb->prepare(
                        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        "UPDATE $table_name SET time_updated=%s, user_id=%s, user_name=%s, order_id=%s, order_number=%s, customer=%s, customer_id=%s, items=%s, discount=%s, fees=%s, shipping=%s, total=%s WHERE ID=%s"
                        , array(
                        $time,
                        $user_id,
                        $username,
                        $order_id,
                        $order_number,
                        $customer,
                        $customer_id,
                        $items,
                        $discount,
                        $fees,
                        $shipping,
                        $total,
                        $id,
                    )
                    )
                );
            } else {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $r = $wpdb->query(
                    $wpdb->prepare(
                        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        "UPDATE $table_name SET time_updated=%s, user_id=%s, user_name=%s, customer=%s, customer_id=%s, items=%s, discount=%s, fees=%s, shipping=%s, total=%s WHERE ID=%s"
                        , array(
                        $time,
                        $user_id,
                        $username,
                        $customer,
                        $customer_id,
                        $items,
                        $discount,
                        $fees,
                        $shipping,
                        $total,
                        $id,
                    )
                    )
                );
            }
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $r = $wpdb->query(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "INSERT IGNORE INTO $table_name (ID, time_updated, user_id, user_name, order_id, order_number, customer,customer_id, items, discount, fees, shipping, total ) VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s )"
                    , array(
                    $id,
                    $time,
                    $user_id,
                    $username,
                    $order_id,
                    $order_number,
                    $customer,
                    $customer_id,
                    $items,
                    $discount,
                    $fees,
                    $shipping,
                    $total,
                )
                )
            );

            $show_days = $option_handler->get_option('log_show_records_days');
            if ($show_days) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $r = $wpdb->query(
                    $wpdb->prepare(
                        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        "DELETE FROM $table_name WHERE DATEDIFF( %s, time_updated ) >= %d"
                        , array(current_time('mysql'), (int)$show_days)
                    )
                );
            }
        }
    }

    private static function create_column_row()
    {
        $args = func_get_args();
        if (count($args) < 2) {
            return false;
        }

        $label = $args[0];
        unset($args[0]);

        $value = '';
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $value .= join(' ', $arg);
            } elseif (is_string($arg) or is_numeric($arg)) {
                $value .= $arg . ' ';
            }
        }
        if ( ! $value) {
            return false;
        }
        $value = trim($value);

        return $label . " : " . $value . "<br />";
    }

    private static function create_row_customer_column($result)
    {
        $row              = '';
        $billing_address  = join(", ", array_filter(array(
            WC()->customer->get_billing_first_name() . " " .
            WC()->customer->get_billing_last_name(),
            WC()->customer->get_billing_address_1(),
            WC()->customer->get_billing_address_2(),
            WC()->customer->get_billing_city(),
            WC()->customer->get_billing_postcode(),
            WC()->customer->get_billing_state(),
            WC()->customer->get_billing_country(),
        )));
        $shipping_address = join(", ", array_filter(array(
            WC()->customer->get_shipping_first_name() . " " .
            WC()->customer->get_shipping_last_name(),
            WC()->customer->get_shipping_address_1(),
            WC()->customer->get_shipping_address_2(),
            WC()->customer->get_shipping_city(),
            WC()->customer->get_shipping_postcode(),
            WC()->customer->get_shipping_state(),
            WC()->customer->get_shipping_country(),
        )));

        $row .= self::create_column_row(
            __('Billing address', 'phone-orders-for-woocommerce'),
            $billing_address
        );

        $billing_email = WC()->customer->get_billing_email();
        $row           .= self::create_column_row(
            __('Email', 'phone-orders-for-woocommerce'),
            "<a href='mailto:$billing_email' target=_blank>$billing_email</a>"
        );

        $billing_phone = WC()->customer->get_billing_phone();
        $row           .= self::create_column_row(
            __('Phone', 'phone-orders-for-woocommerce'),
            "<a href='tel:$billing_phone'>$billing_phone</a>"
        );

        if ($shipping_address and $billing_address !== $shipping_address) {
            $row .= self::create_column_row(
                __('Shipping address', 'phone-orders-for-woocommerce'),
                $shipping_address
            );
        }

        if (isset ($result['customer_note']) && $result['customer_note']) {
            $row .= self::create_column_row(
                __('Customer note', 'phone-orders-for-woocommerce'),
                $result['customer_note']
            );
        }
        if (isset($result['private_note']) && $result['private_note']) {
            $row .= self::create_column_row(
                __('Private note', 'phone-orders-for-woocommerce'),
                $result['private_note']
            );
        }

        return $row;
    }

    private static function create_row_items_column($result)
    {
        $row   = '';
        $index = 0;

        if ( ! isset ($result['items']) and ! is_array($result['items'])) {
            return $row;
        }

        foreach ($result['items'] as $item) {
            if (isset ($item['data'])) {
                $index++;
                $row .= self::create_column_row(
                    $index,
                    $item['data']->get_name() . ', ' . wc_price($item['item_cost']) . ' x ' . $item['qty']
                );
            }
        }

        return $row;
    }

    private static function create_row_discount_column($result)
    {
        $row = '';

        if (isset ($result['applied_coupons']) && count($result['applied_coupons'])) {
            $coupons = array();
            foreach ($result['applied_coupons'] as $coupon) {
                $coupons[] = $coupon['title'];
            }
            $row .= self::create_column_row(
                __('Coupons applied', 'phone-orders-for-woocommerce'),
                join(", ", $coupons)
            );
        }

        if (isset ($result['discount_amount']) && $result['discount_amount']) {
            $row .= self::create_column_row(
                __('Manual discount', 'phone-orders-for-woocommerce'),
                wc_price($result['discount_amount'])
            );
        }

        return $row;
    }

    private static function create_row_fees_column($result)
    {
        $row = '';
        if (isset ($result['fees']) && count($result['fees'])) {
            foreach ($result['fees'] as $fee_name => $fee_amounts) {
                $row .= self::create_column_row($fee_name, $fee_amounts['amount_with_tax']);
            }
        }

        return $row;
    }

    private static function create_row_shipping_method_column($result)
    {
        $row = '';
        if ( ! empty($result['shipping']['packages'])) {
            foreach ($result['shipping']['packages'] as $package_key => $package) {
                if (isset($package['chosen_rate'])) {
                    $total = $package['chosen_rate']['label'] . " : " . self::format_shipping_total(
                            $package['chosen_rate']['cost'],
                            $package['chosen_rate']['tax']
                        );
                } else {
                    $total = __('Not selected!', 'phone-orders-for-woocommerce');
                }

                $row .= sprintf(
                    "%s %s (%s)<br />",
                    __("Package ", 'phone-orders-for-woocommerce'),
                    $package_key + 1,
                    $total
                );
            }
        }

        return $row;
    }

    /**
     * @param float $cost
     * @param float $tax
     *
     * @return string
     */
    private static function format_shipping_total($cost, $tax)
    {
        //phpcs:ignore WordPress.WP.I18n.TextDomainMismatch
        $result = __('Free!', 'woocommerce');

        if (0 < $cost) {
            if (WC()->cart->display_prices_including_tax()) {
                $result = wc_price($cost + $tax);

                if ($cost > 0 && ! wc_prices_include_tax()) {
                    $result .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
                }
            } else {
                $result = wc_price($cost);

                if ($cost > 0 && wc_prices_include_tax()) {
                    $result .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
                }
            }
        }

        return $result;
    }

    private static function create_row_totals_column($result)
    {
        ob_start();
        wc_cart_totals_order_total_html();

        return ob_get_clean();
    }

}
