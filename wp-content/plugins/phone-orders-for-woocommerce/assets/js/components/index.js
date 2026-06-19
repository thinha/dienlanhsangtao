var components = {
    TabAddOrder: require( './tabs/add_order.vue' ),
    FindOrCreateCustomer: require( './tabs/tab-add-order/find_or_create_customer.vue' ),
    OrderDate: require( './tabs/tab-add-order/order_date.vue' ),
    OrderStatus: require( './tabs/tab-add-order/order_status.vue' ),
    OrderPaymentMethod: require( './tabs/tab-add-order/order_payment_method.vue' ),
    TabSettings: require( './tabs/settings.vue' ),
    TabLog: require( './tabs/log.vue' ),
    TabHelp: require( './tabs/help.vue' ),
    OrderDetails: require( './tabs/tab-add-order/order_details.vue' ),
    BaseSettings: require( './tabs/tab-settings/settings.vue'),
    CommonSettings: require( './tabs/tab-settings/common_settings.vue'),
    InterfaceSettings: require( './tabs/tab-settings/interface_settings.vue'),
    WoocommerceSettings: require( './tabs/tab-settings/woocommerce_settings.vue'),
    TaxSettings: require( './tabs/tab-settings/tax_settings.vue'),
    LayoutSettings: require( './tabs/tab-settings/layout_settings.vue'),
    CouponsSettings: require( './tabs/tab-settings/coupons_settings.vue'),
    ReferencesSettings: require( './tabs/tab-settings/references_settings.vue'),
    ShippingSettings: require( './tabs/tab-settings/shipping_settings.vue'),
    CartItemsSettings: require( './tabs/tab-settings/cart_items_settings.vue'),
};

try {
    components = Object.assign(components, require( './../../../pro_version/assets/js/components' ));
} catch (e) {}

module.exports = components;
