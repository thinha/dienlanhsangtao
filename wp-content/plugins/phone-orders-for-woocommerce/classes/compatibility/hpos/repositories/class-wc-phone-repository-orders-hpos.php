<?php
// class loaded in ADMIN areay only

if ( ! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly


class WC_Phone_Repository_Orders_HPOS
{

    public function get_customer_other_order_url($customer_id)
    {
        $args = array(
            'status'         => 'all',
            'page'           => 'wc-orders',
            '_customer_user' => $customer_id,
        );

        return $customer_id ? add_query_arg($args, admin_url('admin.php')) : "";
    }
}

