<?php

if ( ! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class WC_Phone_Woocs_Compatibility
{
    protected $currency;
    protected $prevCurrency;

    public function __construct($currency = '')
    {
        $this->currency = $currency;
        if (self::isActive()) {
            if ($this->currency) {
                global $WOOCS;
                $this->prevCurrency = $WOOCS->current_currency;
                add_filter('wpo_prepare_item', function ($item) {
                    global $WOOCS;

                    $item['item_cost'] = $WOOCS->woocs_back_convert_price($item['item_cost']);
                    $WOOCS->set_currency($this->currency);

                    return $item;
                }, 10);

                add_filter('wpo_update_cart_item_cost', function ($itemCost, $item) {
                    global $WOOCS;

                    if (empty($item['adp'])) {
                        $itemCost = $WOOCS->woocs_convert_price($item['item_cost']);
                    }

                    return $itemCost;
                }, 10, 2);

                add_filter('woocommerce_package_rates', function ($rates, $package) {
                    global $WOOCS;

                    $currency                = $WOOCS->current_currency;
                    $WOOCS->current_currency = $this->prevCurrency;
                    foreach ($rates as $rate) {
                        $rate->cost = $WOOCS->woocs_back_convert_price($rate->cost);
                    }
                    $WOOCS->current_currency = $currency;

                    return $rates;
                }, 10, 2);
            }

            add_filter('wpo_currency_code_options', function ($currencyOptions) {
                global $WOOCS;

                $woocsCurrencies = array_keys($WOOCS->get_currencies());
                $currencyOptions = array_filter($currencyOptions, function ($code) use ($woocsCurrencies) {
                    return in_array($code, $woocsCurrencies);
                }, ARRAY_FILTER_USE_KEY);

                //prevent using only current currency symbol
                remove_filter('woocommerce_currency_symbol', array($WOOCS, 'woocommerce_currency_symbol'), 9999);

                return $currencyOptions;
            });

            add_action('wpo_before_init_order', function () {
                global $WOOCS;

                remove_filter('woocommerce_currency_symbol', array($WOOCS, 'woocommerce_currency_symbol'), 9999);

                $this->resetGlobalCurrency();
            });
        }
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    public function resetGlobalCurrency()
    {
        if (self::isActive()) {
            global $WOOCS;

            $WOOCS->reset_currency();
        }
    }

    public static function isActive()
    {
        return defined('WOOCS_VERSION');
    }
}
