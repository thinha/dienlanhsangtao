import TabAddOrder from './tabs/add_order.vue'
import FindOrCreateCustomer from './tabs/tab-add-order/find_or_create_customer.vue'
import OrderDate from './tabs/tab-add-order/order_date.vue'
import OrderStatus from './tabs/tab-add-order/order_status.vue'
import OrderCurrencySelector from './tabs/tab-add-order/order_currency_selector.vue'
import OrderPaymentMethod from './tabs/tab-add-order/order_payment_method.vue'
import TabSettings from './tabs/settings.vue'
import TabLog from './tabs/log.vue'
import TabHelp from './tabs/help.vue'
import OrderDetails from './tabs/tab-add-order/order_details.vue'
import BaseSettings from './tabs/tab-settings/settings.vue'
import CommonSettings from './tabs/tab-settings/common_settings.vue'
import InterfaceSettings from './tabs/tab-settings/interface_settings.vue'
import WoocommerceSettings from './tabs/tab-settings/woocommerce_settings.vue'
import TaxSettings from './tabs/tab-settings/tax_settings.vue'
import LayoutSettings from './tabs/tab-settings/layout_settings.vue'
import CouponsSettings from './tabs/tab-settings/coupons_settings.vue'
import ReferencesSettings from './tabs/tab-settings/references_settings.vue'
import ShippingSettings from './tabs/tab-settings/shipping_settings.vue'
import CartItemsSettings from './tabs/tab-settings/cart_items_settings.vue'
import TabTools from './tabs/tools.vue'

var components = {
    TabAddOrder: TabAddOrder,
    FindOrCreateCustomer: FindOrCreateCustomer,
    OrderDate: OrderDate,
    OrderStatus: OrderStatus,
    OrderCurrencySelector: OrderCurrencySelector,
    OrderPaymentMethod: OrderPaymentMethod,
    TabSettings: TabSettings,
    TabLog: TabLog,
    TabHelp: TabHelp,
    OrderDetails: OrderDetails,
    BaseSettings: BaseSettings,
    CommonSettings: CommonSettings,
    InterfaceSettings: InterfaceSettings,
    WoocommerceSettings: WoocommerceSettings,
    TaxSettings: TaxSettings,
    LayoutSettings: LayoutSettings,
    CouponsSettings: CouponsSettings,
    ReferencesSettings: ReferencesSettings,
    ShippingSettings: ShippingSettings,
    CartItemsSettings: CartItemsSettings,
    TabTools: TabTools,
};

try {
    components = Object.assign(components, require('./../../../pro_version/assets/js/components').default);
} catch (e) {
}

export default components;
