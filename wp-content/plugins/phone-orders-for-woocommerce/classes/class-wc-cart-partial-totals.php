<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WcCartPartialTotals extends WC_Cart
{
    /**
     * @var WC_Cart
     */
    protected $parentCart;

    /**
     * @var bool
     */
    protected $enableShippingCalculation = true;

    public function __construct(WC_Cart $wcCart)
    {
        $this->parentCart = $wcCart;

        $this->cart_contents         = $wcCart->cart_contents;
        $this->removed_cart_contents = $wcCart->removed_cart_contents;
        $this->applied_coupons       = $wcCart->applied_coupons;
        $this->shipping_methods      = $wcCart->shipping_methods;
        $this->totals                = $wcCart->totals;
        $this->session               = $wcCart->session;
        $this->fees_api              = $wcCart->fees_api;
    }

    /**
     * @param bool $enableShippingCalculation
     */
    public function setEnableShippingCalculation(bool $enableShippingCalculation)
    {
        $this->enableShippingCalculation = $enableShippingCalculation;
    }

    public function calculate_shipping()
    {
        if ($this->enableShippingCalculation) {
            parent::calculate_shipping();
        } else {
            $this->shipping_methods = [];
        }

        return $this->shipping_methods;
    }

    public function calculate_totals()
    {
        parent::calculate_totals();
        $this->commitToParent();
    }

    public function calculateTotalsWithoutShipping()
    {
        $this->setEnableShippingCalculation(false);
        parent::calculate_totals();
        $this->commitToParent();
    }

    protected function commitToParent()
    {
        $this->parentCart->cart_contents         = $this->cart_contents;
        $this->parentCart->removed_cart_contents = $this->removed_cart_contents;
        $this->parentCart->applied_coupons       = $this->applied_coupons;
        $this->parentCart->shipping_methods      = $this->shipping_methods;
        $this->parentCart->totals                = $this->totals;
        $this->parentCart->session               = $this->session;
        $this->parentCart->fees_api              = $this->fees_api;
    }
}