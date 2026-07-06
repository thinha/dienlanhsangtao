<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Phone_Orders_Tools_Page extends WC_Phone_Orders_Admin_Abstract_Page
{
    public $title;
    public $priority = 50;
    protected $tab_name = 'tools';

    public function __construct()
    {
        parent::__construct();
        $this->title = __('Tools', 'phone-orders-for-woocommerce');
    }

    public function enqueue_scripts()
    {
        parent::enqueue_scripts();
    }

    public function action()
    {
    }

    public function render()
    {
        $this->tab_data = array(
            'buttonLabel' => __('Get report', 'phone-orders-for-woocommerce'),
            'tabName'     => $this->tab_name,
            'noteLabel'   => __(
                'If you have problems with the plugin, you should submit a <a href="https://algolplus.freshdesk.com/support/tickets/new" target="_blank">new support request</a> and attach the generated report to it.',
                'phone-orders-for-woocommerce'
            ),
        );

        ?>

        <tab-tools v-bind="<?php
        echo esc_attr(json_encode($this->tab_data)) ?>"></tab-tools>
        <?php
    }

    protected function ajax_get_report($request)
    {
        add_action('shutdown', array($this, 'save_report'), PHP_INT_MAX);

        return $this->wpo_send_json_success();
    }

    public function save_report()
    {
        $report = array(
            'active_theme'             => $this->get_theme(),
            'active_plugins'           => $this->get_plugins(),
            'woocommerce_hooks'        => $this->get_wc_hooks(),
            'woocommerce_tax_settings' => $this->get_wc_taxes(),
            'phone_orders_settings'    => $this->option_handler->get_all_options(),
        );
        set_transient('wpo_report', $report);
    }

    private function get_plugins()
    {
        // Ensure get_plugins function is loaded.
        if ( ! function_exists('get_plugins')) {
            include ABSPATH . '/wp-admin/includes/plugin.php';
        }

        $plugins             = get_plugins();
        $active_plugins_keys = get_option('active_plugins', array());
        $active_plugins      = array();

        foreach ($plugins as $k => $v) {
            // Take care of formatting the data how we want it.
            $formatted         = array();
            $formatted['name'] = wp_strip_all_tags($v['Name']);
            if (isset($v['Version'])) {
                $formatted['version'] = wp_strip_all_tags($v['Version']);
            }
            if (isset($v['Author'])) {
                $formatted['author'] = wp_strip_all_tags($v['Author']);
            }
            if (isset($v['Network'])) {
                $formatted['network'] = wp_strip_all_tags($v['Network']);
            }
            if (isset($v['PluginURI'])) {
                $formatted['plugin_uri'] = wp_strip_all_tags($v['PluginURI']);
            }
            if (in_array($k, $active_plugins_keys)) {
                // Remove active plugins from list so we can show active and inactive separately.
                unset($plugins[$k]);
                $active_plugins[$k] = $formatted;
            } else {
                $plugins[$k] = $formatted;
            }
        }

        return $active_plugins;
    }

    protected function get_theme()
    {
        $current_theme = wp_get_theme();

        return array(
            'name'        => $current_theme->get("Name"),
            'version'     => $current_theme->get("Version"),
            'child_theme' => $current_theme->get_stylesheet() !== $current_theme->get_template(),
            'wc_support'  => current_theme_supports('woocommerce'), // nothing we can do with this for now :(
        );
    }

    private function get_wc_hooks()
    {
        global $wp_filter;
        $filters = array();
        foreach ($wp_filter as $hookName => $hookObj) {
            /**
             * @var WP_Hook $hookObj
             */
            if (preg_match('#^woocommerce_#', $hookName)) {
                $filters[$hookName] = array();

                foreach ($hookObj->callbacks as $priority => $callbacks) {
                    $filters[$hookName][$priority] = array();

                    foreach ($callbacks as $idx => $callback_details) {
                        $classname  = $this->fetch_class_name_from_callback($callback_details['function']);
                        $methodname = $this->fetch_method_name_from_callback($callback_details['function']);

                        if (is_null($methodname) && is_null($classname)) {
                            continue;
                        }

                        $filters[$hookName][$priority][] = ! is_null(
                            $classname
                        ) ? $classname . '::' . $methodname : $methodname;
                    }
                }
            }
        }

        return $filters;
    }

    private function fetch_class_name_from_callback($callback)
    {
        $classname = null;
        if (is_array($callback)) {
            if (isset($callback[0])) {
                if (is_string($callback[0])) {
                    $classname = $callback[0];
                } elseif (is_object($callback[0])) {
                    $classname = get_class($callback[0]);
                }
            }
        }

        return $classname;
    }

    private function fetch_method_name_from_callback($callback)
    {
        $methodName = null;
        if (is_array($callback)) {
            if (isset($callback[1])) {
                $methodName .= $callback[1];
            }
        } elseif (is_string($callback)) {
            $methodName = $callback;
        }

        return $methodName;
    }

    public function get_wc_taxes()
    {
        $wc_customer = new WC_Customer(get_current_user_id());

        $rates = array();
        $slugs = WC_Tax::get_tax_class_slugs();
        foreach (
            array_merge(array('standard' => ''),
                array_combine(array_values($slugs), array_values($slugs))) as $key => $tax_class_slug
        ) {
            $rates[$key] = WC_Tax::get_rates_for_tax_class($tax_class_slug);
        }

        return array(
            'woocommerce_calc_taxes'            => wc_tax_enabled(),
            'woocommerce_ship_to_countries'     => wc_shipping_enabled(),
            'woocommerce_prices_include_tax'    => wc_prices_include_tax(),
            'woocommerce_enable_coupons'        => wc_coupons_enabled(),
            'woocommerce_tax_round_at_subtotal' => get_option('woocommerce_tax_round_at_subtotal'),
            'tax_rates'                         => $rates,
            'customer_tax_rates'                => WC_Tax::get_rates('', $wc_customer),
            'base_tax_rates'                    => WC_Tax::get_base_tax_rates(''),
        );
    }

    protected function ajax_download_report($request)
    {
        if (!is_super_admin(get_current_user_id())) {
            wp_die();
        }

        global $wp_filesystem;

        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        $data = get_transient('wpo_report');
        $tmp_dir = ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : get_temp_dir();
        $filepath = tempnam($tmp_dir, 'wpo');

        $file_content = json_encode($data, JSON_PRETTY_PRINT);
        if (!$wp_filesystem->put_contents($filepath, $file_content, FS_CHMOD_FILE)) {
            wp_die(esc_html__('Error writing file', 'phone-orders-for-woocommerce'));
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-type: application/json');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '.json' . '"');
        header('Content-Length: ' . filesize($filepath));

        //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $wp_filesystem->get_contents($filepath);

        $wp_filesystem->delete($filepath);

        wp_die();
    }
}
