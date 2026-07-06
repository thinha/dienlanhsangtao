<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

class WC_Phone_Orders_Loader
{

    private static $slug = 'phone-orders-for-woocommerce';
    public static $log_table_name;
    var $activation_notice_option = 'phone-orders-for-woocommerce-activation-notice-shown';
    public $version = '3.1.0';
    private $wpo_version_option = 'phone-orders-for-woocommerce-version';

    public static $meta_key_private_note = '_wpo_private_note';
    public static $meta_key_order_creator = '_wpo_order_creator';
    public static $meta_key_order_item_discount = '_wpo_item_discount';

    public static $cap_manage_phone_orders = "manage_woocommerce_phone_orders";

    public function __construct()
    {
        foreach (glob(WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/interfaces/interface-*.php') as $filename) {
            include_once $filename;
        }

        include_once WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/tabs/helpers/class-wc-phone-orders-shipping-package-mod-strategy.php';
        include_once WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/tabs/helpers/class-wc-phone-orders-shipping-rate-mod.php';
        include_once WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/tabs/helpers/class-wc-phone-orders-cart-shipping-processor.php';
        include_once WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/tabs/helpers/class-wc-phone-orders-custom-products-controller.php';

        include_once WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/class-wc-phone-orders-cart-updater.php';
        include_once WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/class-wc-phone-orders-pricing-3-cmp.php';
        include_once WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/class-wc-phone-orders-pricing-4-cmp.php';

        include_once WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/repositories/class-wc-phone-repository-orders.php';
        include_once WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/compatibility/class-wc-phone-woocs-compatibility.php';

        add_action('woocommerce_init', function () {
            include_once WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/class-wc-cart-partial-totals.php';
        }, 10);

        add_action('woocommerce_loaded', function () {
            if (version_compare(WC_VERSION, '7.1', '>=') && wc_get_container()->get(
                    CustomOrdersTableController::class
                )->custom_orders_table_usage_is_enabled()) {
                include_once WC_PHONE_ORDERS_PLUGIN_PATH . 'classes/compatibility/hpos/repositories/class-wc-phone-repository-orders-hpos.php';
            }
        });

        if (is_admin()) {
            //for shipping method
            add_action('woocommerce_shipping_init', function ($methods) {
                include_once 'class-wc-phone-shipping-method.php';
            });

            add_filter('woocommerce_shipping_methods', function ($methods) {
                //phpcs:ignore  WordPress.Security.NonceVerification.Recommended
                if (isset( $_REQUEST['action'] )) $action = sanitize_key( wp_unslash( $_REQUEST['action'] ) ); else $action = '';

                if(
                    //phpcs:ignore  WordPress.Security.NonceVerification.Recommended
                   wp_doing_ajax() AND isset($action) AND ($action=='phone-orders-for-woocommerce' OR strpos($action,'woocommerce_shipping_zone_') !== false )
                    OR !wp_doing_ajax() //wp-admin pages
                )
                    $methods['phone_orders'] = 'WC_Phone_Shipping_Method';

                return $methods;
            });

            add_filter('user_row_actions', function ($actions, $user_object) {
                $actions['new_phone_order'] = "<a  href='" . admin_url(
                        "admin.php?page=phone-orders-for-woocommerce&user_id=" . $user_object->ID
                    ) . "'>" . __('Create Order', 'phone-orders-for-woocommerce') . "</a>";

                return $actions;
            }, 10, 2);

            add_filter('admin_footer_text', array('WC_Phone_Orders_Loader', 'admin_footer_text'));
        }

        add_action('wp_loaded', array($this, 'check_url'), 5);

        add_action('wp_loaded', array($this, 'show_icon_in_orders_list'), 5);

        add_action('init', array($this, 'init_plugin'));

        add_action('admin_init', function () {
            if ( ! get_option($this->activation_notice_option, false)) {
                add_action('admin_notices', array($this, 'display_plugin_activated_message'));
            }
            if ( ! defined('IFRAME_REQUEST') && version_compare(
                    get_option($this->wpo_version_option),
                    $this->version,
                    '<'
                )) {
                self::create_tables();
                $this->update_wpo_version();
            }
        });

        global $wpdb;
        self::$log_table_name = "{$wpdb->prefix}phone_orders_log";

        add_action('wp_loaded', function () {
            if( is_admin() )
                $this->add_billing_phone_email_to_wc_customer_formatted_address();
            $this->add_billing_phone_email_to_wpo_customer_formatted_address();
        });

        add_filter('plugin_action_links_' . WC_PHONE_ORDERS_BASENAME, array($this, 'add_action_links'));

        add_action('before_woocommerce_init', function () {
            if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
                \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                    'custom_order_tables',
                    WC_PHONE_ORDERS_PLUGIN_PATH_FILE,
                    true
                );
            }
        });

        //required for shipping cache
        add_filter("woocommerce_cart_shipping_packages", array($this, "skip_our_fields_in_package_hash"));
    }

    private function update_wpo_version()
    {
        delete_option($this->wpo_version_option);
        add_option($this->wpo_version_option, $this->version);
    }

    public static function load_core()
    {
        do_action('wpo_include_core_classes');
    }

    public static function is_pro_version()
    {
        return defined('WC_PHONE_ORDERS_PRO_VERSION_PATH');
    }

    public function init_plugin()
    {
        //load_plugin_textdomain() isn't used because it imports language from wp-content/languages/plugins/
        self::load_textdomain_from_plugin();
        include_once 'class-wc-phone-orders-settings.php';

        if (is_admin()) {
            self::load_main();
        }
    }

    public static function load_textdomain_from_plugin()
    {
        if (self::is_pro_version()) {
            add_filter('load_textdomain_mofile', function ($mofile, $domain) {
                if ($domain !== self::$slug) {
                    return $mofile;
                }
                $path = WP_PLUGIN_DIR . '/' . trim(basename(dirname(dirname(__FILE__))) . '/languages/', '/');

                return $path . '/' . substr($mofile, strrpos($mofile, '/') + 1);
            }, 10, 2);
        }

        load_plugin_textdomain(self::$slug, false, basename(dirname(dirname(__FILE__))) . '/languages/');
    }

    public static function load_main()
    {
        include_once 'class-wc-phone-orders-main.php';
        new WC_Phone_Orders_Main();
    }

    public function activate()
    {
        global $wp_roles;
        if ($wp_roles) {
            $wp_roles->add_cap('shop_manager', self::$cap_manage_phone_orders);
            $wp_roles->add_cap('administrator', self::$cap_manage_phone_orders);
        }

        self::create_tables();
        //self::add_shipping_methods_for_all_zones();
    }

    private static function create_tables()
    {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $table_name = self::$log_table_name;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name
    (
    ID VARCHAR(20) NOT NULL,
    time_updated DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
    user_id INT,
    user_name VARCHAR(20) DEFAULT '' NOT NULL,
    order_id INT,
    order_number VARCHAR(20) DEFAULT '' NOT NULL,
    customer TEXT,
    customer_id INT,
    items TEXT,
    discount TEXT,
    fees TEXT,
    shipping TEXT,
    total TEXT,
    PRIMARY KEY (ID)
    ) $charset_collate;";

        dbDelta($sql);
    }

    private static function add_shipping_methods_for_all_zones()
    {
        $delivery_zones       = WC_Shipping_Zones::get_zones();
        $new_shipping_methods = array(
            'phone_orders',
        );

        $new_shipping_methods = apply_filters('wpo_get_shipping_methods', $new_shipping_methods);

        foreach ((array)$delivery_zones as $the_zone) {
            $zone             = new WC_Shipping_Zone($the_zone['id']);
            $shipping_methods = array();

            foreach ($zone->get_shipping_methods() as $method) {
                $shipping_methods[] = $method->id;
            }

            foreach ($new_shipping_methods as $method) {
                if ( ! in_array($method, $shipping_methods)) {
                    $zone->add_shipping_method($method);
                }
            }
        }
    }

    public function deactivate()
    {
        global $wp_roles;
        if ($wp_roles) {
            $wp_roles->remove_cap('shop_manager', self::$cap_manage_phone_orders);
            $wp_roles->remove_cap('administrator', self::$cap_manage_phone_orders);
        }

        delete_option($this->activation_notice_option);
    }

    public function display_plugin_activated_message()
    {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php
                echo sprintf(
                // translators: A message that phone orders for WooCommerce are available on this page
                __('Phone Orders For WooCommerce is available <a href="%s">on this page</a>.', 'phone-orders-for-woocommerce'), 'admin.php?page=' . self::$slug); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </p>
        </div>
        <?php
        update_option($this->activation_notice_option, true);
    }

    public function check_url()
    {
        $key = 'wpo_fill_cart';

		//phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! isset($_GET[$key])) {
            return;
        }

        include_once 'class-wc-phone-orders-fill-cart.php';

        //phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.Security.NonceVerification.Recommended
        WC_Phone_Orders_Fill_Cart::fill_cart($_GET[$key]);
        //phpcs:ignore WordPress.Security
        wp_redirect(remove_query_arg(array($key), get_home_url(null, $_SERVER['REQUEST_URI'])));
        exit;
    }

    public static function check_user_capability()
    {
        //detect active capability
        $capability = false;

        if (current_user_can(WC_Phone_Orders_Loader::$cap_manage_phone_orders)) {
            $capability = WC_Phone_Orders_Loader::$cap_manage_phone_orders;
        } elseif (current_user_can('manage_woocommerce')) {
            $capability = 'manage_woocommerce';
        } elseif (current_user_can('edit_shop_orders')) {
            $capability = 'edit_shop_orders';
        }

        return $capability;
    }

    public function show_icon_in_orders_list()
    {
        //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $settings_option_handler = WC_Phone_Orders_Settings::getInstance();

        $show_icon_in_orders_list = $settings_option_handler->get_option('show_icon_in_orders_list');

        if ( ! $show_icon_in_orders_list) {
            return;
        }

        add_action('manage_shop_order_posts_custom_column', array($this, 'manage_orders_custom_column'), 999, 2);
        add_action(
            'manage_woocommerce_page_wc-orders_custom_column',
            array($this, 'manage_orders_custom_column'),
            999,
            2
        );

        // add icons only in orders list
        add_action('current_screen', function () {
            $screen_id = false;
            if (function_exists('get_current_screen')) {
                $screen    = get_current_screen();
                $screen_id = isset($screen, $screen->id) ? $screen->id : '';
            }
            //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if ( ! empty($_REQUEST['screen'])) { // WPCS: input var ok.
                //phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput
                $screen_id = wc_clean(wp_unslash($_REQUEST['screen'])); // WPCS: input var ok, sanitization ok.
            }
            if ('edit-shop_order' == $screen_id || 'woocommerce_page_wc-orders' == $screen_id) {
                add_action('wp_print_scripts', function () {
                    ?>
                    <style>

                        .wc-orders-list__wpo-order-number-icon {
                            margin-left: 3px;
                            vertical-align: middle;
                        }

                        .wc-orders-list__wpo-order-number-icon::after {
                            font-family: 'Woocommerce';
                            content: "\e03b";
                        }

                    </style>
                    <?php
                });
            }
        });
    }

    public static function admin_footer_text($footer_text)
    {
        if ( ! current_user_can(self::$cap_manage_phone_orders)) {
            return $footer_text;
        }

        $current_screen = get_current_screen();

        $pages = array(
            'woocommerce_page_phone-orders-for-woocommerce',
        );

        // Check to make sure we're on a WooCommerce admin page.
        if (isset($current_screen->id) && apply_filters(
                'wpo_display_admin_footer_text',
                in_array($current_screen->id, $pages, true)
            )) {
            // Change the footer text.
            if ( ! get_option('phone-orders-for-woocommerce-rated')) {
                $footer_text = sprintf(
                /* translators: 1: WooCommerce 2:: five stars */
                    __(
                        'If you like %1$s please leave us a %2$s rating. Thank you so much in advance!',
                        'phone-orders-for-woocommerce'
                    ),
                    sprintf(
                        '<strong>%s</strong>',
                        esc_html__('Phone Orders for WooCommerce', 'phone-orders-for-woocommerce')
                    ),
                    '<a href="https://wordpress.org/support/plugin/phone-orders-for-woocommerce/reviews?rate=5#new-post" target="_blank" class="wpo-rating-link" aria-label="' . esc_attr__(
                        'five star',
                        'phone-orders-for-woocommerce'
                    ) . '" data-rated="' . esc_attr__(
                        'Thanks :)',
                        'phone-orders-for-woocommerce'
                    ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
                );

				$handle = 'wpo-admin-footer-text';
				wp_register_script( $handle, '', array(), WC_PHONE_ORDERS_VERSION, true );
				wp_enqueue_script( $handle );
                wp_add_inline_script( $handle,
                    "jQuery( 'a.wpo-rating-link' ).on('click', function() {
						jQuery.post( '" . admin_url('admin-ajax.php') . "', { action: 'phone-orders-for-woocommerce', method: 'wpo_rated', tab: 'add-order' } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});"
                );
            }
        }

        return $footer_text;
    }

    public static function get_shipping_package_mod_strategy()
    {
        return apply_filters("wpo_shipping_package_mod_strategy", new WC_Phone_Order_Shipping_Package_Mod_Strategy());
    }

    public function add_billing_phone_email_to_wc_customer_formatted_address()
    {
        add_filter('woocommerce_formatted_address_replacements', function ($replacements, $args) {
            if ( ! empty($args['wpo_billing_phone'])) {
                $replacements['{wpo_billing_phone}'] = $args['wpo_billing_phone'];
            }

            if ( ! empty($args['wpo_billing_email'])) {
                $replacements['{wpo_billing_email}'] = $args['wpo_billing_email'];
            }

            if ( ! isset($replacements['{wpo_billing_phone}'])) {
                $replacements['{wpo_billing_phone}'] = '';
            }

            if ( ! isset($replacements['{wpo_billing_email}'])) {
                $replacements['{wpo_billing_email}'] = '';
            }

            return $replacements;
        }, 10, 2);

        add_filter('woocommerce_localisation_address_formats', function ($address_formats) {
            $field_name_email         = 'wpo_billing_email';
            $field_name_phone         = 'wpo_billing_phone';
            $modified_address_formats = array();
            foreach ($address_formats as $country => $address_format) {
                $modified_address_formats[$country] = $address_format . "\n{" . $field_name_email . '}' . "\n{" . $field_name_phone . '}';
            }

            return $modified_address_formats;
        });
    }

    public function add_billing_phone_email_to_wpo_customer_formatted_address()
    {
        add_filter('wpo_customer_formatted_address', function ($fields, $customer_data, $type) {
            if (($type == 'billing') && isset($customer_data['billing_phone'])) {
                $fields['wpo_billing_phone'] = $customer_data['billing_phone'] ? sprintf(
                    '<a href="tel:%1$s">%1$s</a>',
                    $customer_data['billing_phone']
                ) : $customer_data['billing_phone'];
            }
            if (($type == 'billing') && isset($customer_data['billing_email'])) {
                $fields['wpo_billing_email'] = $customer_data['billing_email'] ? sprintf(
                    '<a href="mailto:%1$s" target=_blank>%1$s</a>',
                    $customer_data['billing_email']
                ) : $customer_data['billing_email'];
            }

            return $fields;
        }, 50, 3);
    }

    public function add_action_links($links)
    {
        $mylinks = array(
            '<a href="' . admin_url('admin.php?page=phone-orders-for-woocommerce') . '">' . __(
                'Create Order',
                'phone-orders-for-woocommerce'
            ) . '</a>',
            '<a href="https://docs.algolplus.com/category/algol_phone_order/" target="_blank">' . __(
                'Docs',
                'phone-orders-for-woocommerce'
            ) . '</a>',
            '<a href="https://docs.algolplus.com/support/" target="_blank">' . __(
                'Support',
                'phone-orders-for-woocommerce'
            ) . '</a>',
        );

        return array_merge($mylinks, $links);
    }

    public function manage_orders_custom_column($column, $post_id)
    {
        $order = wc_get_order($post_id);
        if ($column === 'order_number' && $order->get_meta(WC_Phone_Orders_Loader::$meta_key_order_creator, true)) {
            echo '<span title="' . esc_attr__(
                    "Phone order",
                    "phone-orders-for-woocommerce"
                ) . '" class="wc-orders-list__wpo-order-number-icon">&nbsp;</span>';
        }
    }

    public function skip_our_fields_in_package_hash($packages)
    {
        $our_fields = [
            "wpo_key",
            "cost_updated_manually",
            "allow_po_discount",
            "wpo_item_cost",
            "removed_custom_meta_fields_keys",
            "adp"
        ];
        foreach ($packages as $idx => $package) {
            foreach ($package['contents'] as $item_key => $item) {
                foreach ($our_fields as $field) {
                    unset($packages[$idx]['contents'][$item_key][$field]);
                }
            }
        }

        return $packages;
    }
}
