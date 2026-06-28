<?php

if ( ! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Phone_Orders_Pricing_3_Cmp
{
    /**
     * @var \ADP\ProVersion\Includes\Cart\Structures\Cart | \ADP\BaseVersion\Includes\Cart\Structures\Cart | null
     */
    protected $cart;

    /**
     * @var \ADP\BaseVersion\Includes\Context
     */
    protected $context;

    public function __construct()
    {
        if ($this->is_pricing_active()) {
            $this->context = new \ADP\BaseVersion\Includes\Context();
        }
    }

    /**
     * @return \ADP\BaseVersion\Includes\Context
     */
    public function get_context()
    {
        return $this->context;
    }

    public function install_hook_to_catch_cart()
    {
        add_action('wdp_before_apply_to_wc_cart', array($this, 'hook_catch_selectable_gifts'), 10, 3);
    }

    /**
     * @param \ADP\ProVersion\Includes\Cart\CartProcessor $cartProcessor
     * @param \WC_Cart $wcCart
     * @param \ADP\ProVersion\Includes\Cart\Structures\Cart $cart
     */
    public function hook_catch_selectable_gifts($cartProcessor, $wcCart, $cart)
    {
        $this->cart = $cart;
    }

    /**
     * @return bool
     */
    public function is_pricing_active()
    {
        return defined("WC_ADP_VERSION");
    }

    public function is_pro_version()
    {
        return class_exists("\ADP\ProVersion\Includes\Loader");
    }

    /**
     * @return array int[string] Key is a hash of the gift and value is available quantity.
     */
    public function get_list_of_selectable_gifts()
    {
        $cart = $this->cart;

        if ( ! $cart) {
            return array();
        }

        if ( ! class_exists("\ADP\ProVersion\Includes\Cart\Structures\Cart")) {
            return array();
        }

        if ( ! ($cart instanceof \ADP\ProVersion\Includes\Cart\Structures\Cart)) {
            return array();
        }

        $result = array();

        /** @var \ADP\ProVersion\Includes\Cart\Structures\Cart $cart */
        foreach ($cart->getListOfFreeCartItemChoices() as $hash => $freeCartItemChoices) {
            if ($freeCartItemChoices->getRequiredQty() <= 0) {
                continue;
            }

            $result[$hash] = $freeCartItemChoices->getRequiredQty();
        }

        return $result;
    }

    /**
     * @param \ADP\ProVersion\Includes\Cart\Structures\Cart $cart
     * @param string $hash
     *
     * @return WP_Post[]
     */
    public function get_list_of_products_available_for_gift_hash($hash)
    {
        $cart = $this->cart;

        if ( ! $cart) {
            return array();
        }

        if ( ! class_exists("\ADP\ProVersion\Includes\Cart\Structures\Cart")) {
            return array();
        }

        if ( ! ($cart instanceof \ADP\ProVersion\Includes\Cart\Structures\Cart)) {
            return array();
        }

        $listOfFreeCartItemChoices = $cart->getListOfFreeCartItemChoices();
        if ( ! isset($listOfFreeCartItemChoices[$hash])) {
            return array();
        }

        $choices = $listOfFreeCartItemChoices[$hash];
        if ($choices->getRequiredQty() <= 0) {
            return array();
        }

        $productGiftSuitability = new \ADP\ProVersion\Includes\Cart\FreeCartItemChoicesSuitability();
        $query_vars             = $productGiftSuitability->getMatchedProductsGlobalQueryArgs(
            $choices,
            array('post_type' => array('product', 'product_variation'), 'posts_per_page' => -1)
        );
        $wp_query               = new WP_Query();

        return $wp_query->query($query_vars);
    }

    /**
     * @param int $productId
     * @param float $qty
     * @param string $giftHash
     * @param array $variation_data
     */
    public function gift_the_product($productId, $qty, $giftHash, $variation_data = array())
    {
        $cart = $this->cart;

        if ( ! $cart) {
            return;
        }

        if ( ! class_exists("\ADP\ProVersion\Includes\Cart\Structures\Cart")) {
            return;
        }

        if ( ! ($cart instanceof \ADP\ProVersion\Includes\Cart\Structures\Cart)) {
            return;
        }

        $selectableGifts = $this->cart->getListOfFreeCartItemChoices();
        if ( ! isset($selectableGifts[$giftHash])) {
            return;
        }

        $product = wc_get_product($productId);

        $gift = $selectableGifts[$giftHash];

        /** @var \ADP\ProVersion\Includes\Cart\Structures\CartCustomer $customer */
        $customer       = $this->cart->getContext()->getCustomer();
        $giftSelections = $customer->getGiftsSelections($giftHash);

        $qty = min($gift->getRequiredQty(), $qty);

        if (count($variation_data) === 0 && $product instanceof WC_Product_Variation) {
            $variation_data = $product->get_variation_attributes();
        }

        $giftSelections->change(
            $productId,
            $qty,
            $product->get_parent_id(),
            $variation_data,
            array()
        );

        $wcSessionFacade = $this->cart->getContext()->getSession();
        if ($wcSessionFacade->isValid()) {
            $wcSessionFacade->fetchPropsFromCustomer($customer);
            $wcSessionFacade->push();
        }
    }

    /**
     * @param int $product_id
     * @param float $qty
     * @param int $variation_id
     * @param array $variation
     * @param string $gift_hash
     */
    public function remove_selected_gift($product_id, $qty, $variation_id, $variation, $gift_hash)
    {
        $cart = $this->cart;

        if ( ! $cart) {
            return;
        }

        $customer = $this->cart->getContext()->getCustomer();

        $parentId   = $variation_id ? $product_id : 0;
        $product_id = $variation_id ? $variation_id : $product_id;

        $customer->getGiftsSelections($gift_hash)->change(
            $product_id,
            (-1) * $qty,
            $parentId,
            $variation,
            array()
        );

        $wcSessionFacade = $this->cart->getContext()->getSession();
        if ($wcSessionFacade->isValid()) {
            $wcSessionFacade->fetchPropsFromCustomer($customer);
            $wcSessionFacade->push();
        }
    }

    /**
     * @param string $associated_gift_hash
     * @param string $free_cart_item_hash
     * @param float $qty
     */
    public function remove_gift($associated_gift_hash, $free_cart_item_hash, $qty)
    {
        $cart = $this->cart;

        if ( ! $cart) {
            return;
        }

        $customer         = $this->cart->getContext()->getCustomer();
        $removedFreeItems = $customer->getRemovedFreeItems($associated_gift_hash);
        $removedFreeItems->add($free_cart_item_hash, $qty);

        $wcSessionFacade = $this->cart->getContext()->getSession();
        if ($wcSessionFacade->isValid()) {
            $wcSessionFacade->fetchPropsFromCustomer($customer);
            $wcSessionFacade->push();
        }
    }

    public function clear_gift_selections()
    {
        /** @var \ADP\ProVersion\Includes\External\FreeItemsController $freeItemsController */
        $freeItemsController = \ADP\Factory::get('External_FreeItemsController', $this->context);
        $freeItemsController->onCreateOrder();
    }

    public function restore_deleted_items($gift_hash)
    {
        $cart = $this->cart;

        if ( ! $cart) {
            return;
        }

        $customer         = $cart->getContext()->getCustomer();
        $removedFreeItems = $customer->getRemovedFreeItems($gift_hash);
        $removedFreeItems->purge();

        $wcSessionFacade = $cart->getContext()->getSession();
        if ($wcSessionFacade->isValid()) {
            $wcSessionFacade->fetchPropsFromCustomer($customer);
            $wcSessionFacade->push();
        }
    }

    public function set_variation_missing_attributes_for_gifts($item)
    {
        $missing_variation_attributes = isset($item['missing_variation_attributes']) && is_array(
            $item['missing_variation_attributes']
        ) ? $item['missing_variation_attributes'] : array();

        if ( ! $missing_variation_attributes) {
            return;
        }

        $cart = $this->cart;

        if ( ! $cart) {
            return;
        }

        if ( ! class_exists("\ADP\ProVersion\Includes\Cart\Structures\Cart")) {
            return;
        }

        if ( ! ($cart instanceof \ADP\ProVersion\Includes\Cart\Structures\Cart)) {
            return;
        }

        $giftHash = isset($item['adp']['gift_hash']) ? $item['adp']['gift_hash'] : null;
        if ( ! $giftHash) {
            return;
        }

        $selectableGifts = $this->cart->getListOfFreeCartItemChoices();
        if ( ! isset($selectableGifts[$giftHash])) {
            return;
        }

        $productId     = ! empty ($item['variation_id']) ? (int)$item['variation_id'] : (int)$item['product_id'];
        $variationData = ! empty($item['variation_data']) ? $item['variation_data'] : array();
        $product       = wc_get_product($productId);

        /** @var \ADP\ProVersion\Includes\Cart\Structures\CartCustomer $customer */
        $customer       = $this->cart->getContext()->getCustomer();
        $giftSelections = $customer->getGiftsSelections($giftHash);

        if (count($variationData) === 0 && $product instanceof WC_Product_Variation) {
            $variationData = $product->get_variation_attributes();
        }

        $qty = $giftSelections->get(
            $productId,
            $product->get_parent_id(),
            $variationData,
            array()
        );

        $giftSelections->update(
            $productId,
            0,
            $product->get_parent_id(),
            $variationData,
            array()
        );

        foreach ($missing_variation_attributes as $attribute) {
            $slug = $attribute['key'];

            if (empty($item['variation_data'][$slug])) {
                $variationData['attribute_' . $slug] = $attribute['value'];
            }
        }

        $giftSelections->change(
            $productId,
            $qty,
            $product->get_parent_id(),
            $variationData,
            array()
        );

        $wcSessionFacade = $this->cart->getContext()->getSession();
        if ($wcSessionFacade->isValid()) {
            $wcSessionFacade->fetchPropsFromCustomer($customer);
            $wcSessionFacade->push();
        }
    }

    public function correct_free_items_qty($item)
    {
        $cart = $this->cart;

        if ( ! $cart) {
            return;
        }

        if ( ! class_exists("\ADP\ProVersion\Includes\Cart\Structures\Cart")) {
            return;
        }

        if ( ! ($cart instanceof \ADP\ProVersion\Includes\Cart\Structures\Cart)) {
            return;
        }

        $giftHash = isset($item['adp']['gift_hash']) ? $item['adp']['gift_hash'] : null;
        if ( ! $giftHash) {
            return;
        }

        $selectableGifts = $this->cart->getListOfFreeCartItemChoices();
        if ( ! isset($selectableGifts[$giftHash])) {
            return;
        }

        $productId     = ! empty ($item['variation_id']) ? (int)$item['variation_id'] : (int)$item['product_id'];
        $variationData = ! empty($item['variation_data']) ? $item['variation_data'] : array();
        $qty           = isset($item['qty']) ? $item['qty'] : 0;

        if ( ! $qty) {
            return;
        }

        $product = wc_get_product($productId);

        $gift = $selectableGifts[$giftHash];

        /** @var \ADP\ProVersion\Includes\Cart\Structures\CartCustomer $customer */
        $customer       = $this->cart->getContext()->getCustomer();
        $giftSelections = $customer->getGiftsSelections($giftHash);

        $qtyGifted = $giftSelections->get(
            $productId,
            $product->get_parent_id(),
            $variationData,
            array()
        );

        $qty = min($gift->getRequiredQty(), $qty - $qtyGifted);

        if (count($variationData) === 0 && $product instanceof WC_Product_Variation) {
            $variationData = $product->get_variation_attributes();
        }

        $giftSelections->change(
            $productId,
            $qty,
            $product->get_parent_id(),
            $variationData,
            array()
        );

        $wcSessionFacade = $this->cart->getContext()->getSession();
        if ($wcSessionFacade->isValid()) {
            $wcSessionFacade->fetchPropsFromCustomer($customer);
            $wcSessionFacade->push();
        }
    }

}
