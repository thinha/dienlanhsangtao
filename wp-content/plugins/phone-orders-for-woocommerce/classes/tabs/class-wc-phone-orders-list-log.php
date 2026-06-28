<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if ( ! class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
require_once(ABSPATH . 'wp-admin/includes/template.php');
if ( ! class_exists('WP_Screen')) {
    require_once(ABSPATH . 'wp-admin/includes/screen.php');
}

class WC_Phone_Orders_List_Log extends WP_List_Table
{

    private $log_table_name;

    public $order_column;
    public $direction;

    public function __construct()
    {
        parent::__construct(array(
            'singular' => __('item', 'phone-orders-for-woocommerce'),
            'plural'   => __('items', 'phone-orders-for-woocommerce'),
            'ajax'     => true,
        ));
        global $wpdb;
        $this->log_table_name = "{$wpdb->prefix}phone_orders_log";
    }

    /**
     * Output the report
     */
    public function output_report()
    {
        $this->prepare_items();

        echo '<form method="post" id="wc-phone-orders-log">';

        $this->search_box(__('Search', 'phone-orders-for-woocommerce'), 'search');
        $this->display();

        echo '</form>';
    }

    /**
     * get_columns function.
     */
    public function get_columns()
    {
        $columns = array(
            'time_updated' => __('Time', 'phone-orders-for-woocommerce'),
            'user_name'    => __('Username', 'phone-orders-for-woocommerce'),
            'order_number' => __('Order number', 'phone-orders-for-woocommerce'),
            'customer'     => __('Customer', 'phone-orders-for-woocommerce'),
            'items'        => __('Items', 'phone-orders-for-woocommerce'),
            'discount'     => __('Discount', 'phone-orders-for-woocommerce'),
            'fees'         => __('Fees', 'phone-orders-for-woocommerce'),
            'shipping'     => __('Shipping', 'phone-orders-for-woocommerce'),
            'total'        => __('Totals', 'phone-orders-for-woocommerce'),
        );

        return $columns;
    }

    public function prepare_items()
    {
        global $wpdb;

        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $nonce = isset($_REQUEST['nonce']) ? sanitize_text_field(wp_unslash($_REQUEST['nonce'])) : "";
        if ( ! wp_verify_nonce($nonce, 'phone-orders-for-woocommerce')) {
            wp_die(0);
        }
        $columns  = $this->get_columns();
        $hidden   = array('');
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $current_page = absint($this->get_pagenum());
        $per_page     = 10;

        $where = '';
        $limit = '';

        if ( ! empty($_REQUEST['s'])) {
            $s     = '%' . esc_sql(sanitize_text_field(wp_unslash($_REQUEST['s']))) . '%';
            $where .= 'WHERE ';
            foreach ($columns as $column_name => $localized_name) {
                $where .= sprintf("%s LIKE '%s' OR ", $column_name, $s);
            }
            $where = substr($where, 0, -4);
        }

        //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB
        $r   = $wpdb->get_results("SELECT * FROM {$this->log_table_name} $where", ARRAY_A);

        $amount_rows = 0;
        $data        = array();

        foreach ($r as &$d_row) {
            $item = $d_row;

            $this->update_order_number($item);
            $this->update_user_name($item);
            $this->update_customer($item);
            $data[$amount_rows] = $item;

            $amount_rows++;
        }


        if ( ! isset($_REQUEST['orderby'])) {
            $_REQUEST['orderby'] = 'time_updated';
            $_REQUEST['order']   = 'desc';
        }

        $this->order_column = $order = sanitize_text_field(wp_unslash($_REQUEST['orderby']));
        $this->direction    = isset($_REQUEST['order']) ? sanitize_text_field(wp_unslash($_REQUEST['order'])) : '';


        if ($this->direction == 'asc') {
            $ustrcmp = function ($a, $b) use ($order) {
                $a_defined = ! isset($a[$order]) ? '' : $a[$order];
                $b_defined = ! isset($b[$order]) ? '' : $b[$order];

                return strcmp($a_defined, $b_defined);
            };
        } else {
            $ustrcmp = function ($a, $b) use ($order) {
                $a_defined = ! isset($a[$order]) ? '' : $a[$order];
                $b_defined = ! isset($b[$order]) ? '' : $b[$order];

                return strcmp($b_defined, $a_defined);
            };
        }
        usort($data, $ustrcmp);

        if (isset($_REQUEST['paged'])) {
            $start_offset = (absint(wp_unslash($_REQUEST['paged'])) - 1) * $per_page;
        } else {
            $start_offset = 0;
        }
        $data = array_slice($data, $start_offset, $per_page);

        $this->items = $data;
        /**
         * Pagination
         */
        $this->set_pagination_args(array(
            'total_items' => $amount_rows,
            'per_page'    => $per_page,
            'total_pages' => ceil($amount_rows / $per_page),
        ));
    }

    private function update_order_number(&$item)
    {
        $order_id = $item['order_id'];
        $order    = wc_get_order($order_id);
        if ( ! $order) {
            return '';
        }

        $item['order_number'] = '<a href="' . $order->get_edit_order_url(
            ) . '" target="_blank">' . $item['order_number'] . '</a>';

        $option_handler = WC_Phone_Orders_Settings::getInstance();
        if (WC_Phone_Orders_Loader::is_pro_version() &&
            ! in_array(
                'wc-' . $order->get_status(),
                $option_handler->get_option('dont_allow_edit_order_have_status_list')
            )) {
            $item['order_number'] .= '<br><a href="' . add_query_arg(array(
                    'page'          => WC_Phone_Orders_Main::$slug,
                    'edit_order_id' => $order->get_id(),
                ), admin_url('admin.php')) . '" target="_blank">' . __(
                                         'Edit Phone Order',
                                         'phone-orders-for-woocommerce'
                                     ) . '</a>';
        }
    }

    function update_user_name(&$item)
    {
        $user_id = $item['user_id'];
        if ( ! $user_id or ! current_user_can('list_users') or ! current_user_can('edit_user', $user_id)) {
            return $this->column_default($item, 'user_name');
        }

        $user_url         = add_query_arg(array('user_id' => $user_id,), admin_url('user-edit.php'));
        $item['username'] = '<a href="' . $user_url . '" target="_blank">' . $item['user_name'] . '</a>';
    }

    function update_customer(&$item)
    {
        $customer_id = $item['customer_id'];

        $customer_login_html = '';
        if ($customer_id and current_user_can('list_users') and current_user_can(
                                                                    'edit_user',
                                                                    $customer_id
                                                                ) and get_userdata($customer_id)) {
            $customer_url        = add_query_arg(array('user_id' => $customer_id,), admin_url('user-edit.php'));
            $customer_login_html = __(
                                       'Customer login',
                                       'phone-orders-for-woocommerce'
                                   ) . " : " . '<a href="' . $customer_url . '" target="_blank">' . get_userdata(
                                       $customer_id
                                   )->user_login . '</a><br />';
        };
        $item['customer'] = $customer_login_html . $item['customer'];
    }

    function column_default($item, $column_name)
    {
        return isset ($item[$column_name]) ? $item[$column_name] : '';
    }

    public function get_sortable_columns()
    {
        $columns = array(
            'time_updated' => array('time_updated', false),
            'user_name'    => array('user_name', false),
            'order_number' => array('order_number', false),
            'customer'     => array('customer_id', false),
            'shipping'     => array('shipping', false),
            'total'        => array('total', false),
        );

        return $columns;
    }

}
