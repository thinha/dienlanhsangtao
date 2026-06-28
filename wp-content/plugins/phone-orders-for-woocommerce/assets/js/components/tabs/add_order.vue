<template>
  <div class="phone-orders-woocommerce_tab-add-order phone-orders-woocommerce__tab">
    <div v-show="isLoading" class="tab-loader" :class="{'tab-loader-without-background': isLoadingWithoutBackground }">
      <loader></loader>
    </div>
    <div class="wrap" id="woo-phone-orders">
      <h1 class="screen-reader-text"></h1>
      <div id="poststuff">
        <slot name="find-order"></slot>
        <div id="phone-orders-errors">
          <div v-if="isShowMessages">
            <b-alert
              ref="errorAlert"
              :show="true"
              variant="danger"
              class="error-alert"
              dismissible
              fade
              @dismissed="clear"
            >
              <ul>
                <li v-for="item in deletedItems">
                  {{ item.name }}: {{ deletedItemLabel }}
                </li>
              </ul>
              <ul>
                <li v-for="item in outOfStockItems">
                  {{ item.name }}: {{ outOfStockItemLabel }}
                </li>
              </ul>
            </b-alert>
          </div>
        </div>
        <div id="post-body" class="metabox-holder columns-2">
          <div id="postbox-container-1" class="postbox-container">
            <div v-html="orderSidebarCustomHtml"></div>
            <div class="wpo-order-sidebar d-flex flex-wrap">
              <div class="meta-box-sortables" v-bind:class="{'order-5': !isOrderFieldsBelowCustomerDetails}">
                <slot name="find-or-create-customer"></slot>
              </div>
              <div class="meta-box-sortables order-1" v-show="showOrderDate">
                <slot name="order-date"></slot>
              </div>
              <div class="meta-box-sortables order-2" v-show="showOrderStatus">
                <slot name="order-status"></slot>
              </div>
              <div class="meta-box-sortables order-3" v-show="showOrderCurrencySelector">
                <slot name="order-currency-selector"></slot>
              </div>
              <div class="meta-box-sortables order-4" v-show="showPaymentMethods">
                <slot name="order-payment-method"></slot>
              </div>
            </div>
          </div>
          <slot name="order-details"></slot>
        </div>
      </div>
    </div>
    <div style="clear: both">
      <add-coupon-modal v-bind="addCouponModalSettings"></add-coupon-modal>
      <shipping-modal v-bind="shippingModalSettings"></shipping-modal>
      <add-custom-item-modal v-bind="addCustomItemModalSettings"></add-custom-item-modal>
      <add-discount-modal v-bind="addDiscountModalSettings"></add-discount-modal>
      <add-fee-modal v-bind="addFeeModalSettings"></add-fee-modal>
      <add-customer-modal v-bind="addCustomerModalSettings">
        <template v-slot:add-customer-address-modal-header="props">
          <slot name="add-customer-address-header" :modal="props.modal"></slot>
        </template>
        <template v-slot:add-customer-address-modal-footer="props">
          <slot name="add-customer-address" :modal="props.modal"></slot>
        </template>
      </add-customer-modal>
      <edit-address-modal v-bind="editAddressModalSettings">
        <template v-slot:edit-customer-address-modal-header="props">
          <slot name="edit-customer-address-header" :modal="props.modal"></slot>
        </template>
        <template v-slot:edit-customer-address-modal-footer="props">
          <slot name="edit-customer-address" :modal="props.modal"></slot>
        </template>
        <template v-slot:multi-addresses-select-slot="slotProps">
          <div>
            <slot name="multi-addresses-select" v-bind="slotProps"></slot>
          </div>
        </template>
        <template v-slot:multi-addresses-buttons-slot="slotProps">
                    <span>
                        <slot name="multi-addresses-buttons" v-bind="slotProps"></slot>
                    </span>
        </template>
      </edit-address-modal>
      <configure-product-modal v-bind="configureProductModalSettings"></configure-product-modal>
      <order-history-customer-modal v-bind="orderHistoryCustomerModalSettings"></order-history-customer-modal>
      <load-sessions-modal v-bind="loadSessionsModalSettings"></load-sessions-modal>
      <add-gift-card-modal v-bind="addGiftCardModalSettings"></add-gift-card-modal>
      <choose-gifts-modal v-bind="chooseGiftsModalSettings"></choose-gifts-modal>
      <product-description-modal v-bind="productDescriptionModalSettings"></product-description-modal>
      <product-history-modal v-bind="productHistoryModalSettings"></product-history-modal>
    </div>
  </div>
</template>

<style lang="css" src="./../../../css/wc-phone-orders.css"></style>
<style lang="css" src="./../../../../node_modules/vue-multiselect/dist/vue-multiselect.css"></style>

<style>

#phone-orders-app a.disabled {
  color: #5cb3ff;
  cursor: not-allowed;
}

#phone-orders-app [disabled] {
  cursor: not-allowed;
}

.phone-orders-woocommerce__tab {
  position: relative;
}

.phone-orders-woocommerce__tab .tab-loader {
  height: 100%;
  position: absolute;
  background-color: white;
  opacity: 0.7;
  width: 100%;
  z-index: 10;;
}

.phone-orders-woocommerce_tab-settings .tab-loader {
  margin: 10px -5px;
}

.phone-orders-woocommerce__tab .tab-loader .v-spinner {
  text-align: center;
  position: absolute;
  top: 50%;
  margin-top: 5px;
  left: 44%;
}

.phone-orders-woocommerce__tab .tab-loader.tab-loader-without-background {
  height: 50px;
  position: absolute;
  opacity: 0.7;
  width: 50px;
  z-index: 10;
  top: 50%;
  left: 44%;
}

.phone-orders-woocommerce__tab .tab-loader.tab-loader-without-background .v-spinner {
  margin-top: 5px;
  position: relative;
  top: 0;
  left: 0;
}

#phone-orders-app .multiselect__input,
#phone-orders-app .multiselect__input:focus,
#phone-orders-app .multiselect__input:hover {
  border: none;
  box-shadow: none;
}

#phone-orders-app .multiselect__tags {
  min-height: 30px;
  padding: 5px 40px 5px 8px;
}

#phone-orders-app .multiselect__select {
  height: 30px;
}

#phone-orders-app {
  font-size: 14px;
}

#phone-orders-app .btn {
  font-size: 13px;
  padding: 4px 12px;
}

#phone-orders-app .btn.redirect {
  background-color: #00a523;
  border-color: #00a523;
}

#phone-orders-app .order-details__title h2 {
  padding: 0;
}

#phone-orders-app .order-details__title {
  display: inline-block;
}

#phone-orders-app .handlediv.button-link {
  height: inherit;
}

#phone-orders-app .search_options .search_option .multiselect {
  display: inline-block;
}

#phone-orders-app .custom-header {
  font-size: 20px;
}

#phone-orders-app .multiselect__spinner {
  height: 30px;
}

#phone-orders-app .multiselect {
  min-height: 30px;
}

#phone-orders-app #addCustomer .multiselect__spinner,
#phone-orders-app #editAddress .multiselect__spinner {
  height: 40px;
}

#phone-orders-app #addCustomer .multiselect,
#phone-orders-app #editAddress .multiselect {
  min-height: 40px;
}

#phone-orders-app #addCustomer .multiselect__tags,
#phone-orders-app #editAddress .multiselect__tags {
  min-height: 40px;
  padding: 8px 40px 5px 8px;
}

#phone-orders-app #addCustomer .multiselect__select,
#phone-orders-app #editAddress .multiselect__select {
  height: 37px;
}

#phone-orders-app .multiselect .multiselect__single,
#phone-orders-app .multiselect .multiselect__input {
  margin-bottom: 0;
}

#phone-orders-app .multiselect,
#phone-orders-app .multiselect__input,
#phone-orders-app .multiselect__single {
  font-size: 14px;
}

.multiselect__clear {
  position: absolute;
  right: 38px;
  height: 40px;
  width: 40px;
  display: block;
  cursor: pointer;
  z-index: 1;
  top: -3px;
}

.multiselect__clear:before {
  transform: rotate(45deg);
}

.multiselect__clear:after {
  transform: rotate(-45deg);
}

.multiselect__clear:after, .multiselect__clear:before {
  content: "";
  display: block;
  position: absolute;
  width: 3px;
  height: 16px;
  background: #aaa;
  top: 12px;
  right: 4px;
}

#phone-orders-app .multiselect__option {
  min-height: 30px;
  padding: 7px;
}

#phone-orders-app .multiselect__option::after {
  line-height: 30px;
}

#phone-orders-app #woocommerce-order-items .wc-order__actions td {
  padding-bottom: 0;
}

#phone-orders-app .copy-order__button {
  margin-left: 10px;
  padding: 4px 30px;
}

#phone-orders-app .find-order-alert .alert {
  margin-bottom: 0;
}

#phone-orders-app .search_option label {
  padding: 5px 5px 5px 5px;
}

#phone-orders-app #woo-phone-orders .link-add-custom-item,
#phone-orders-app #woo-phone-orders .link-add-item-from-shop {
  padding-top: 0;
  display: inline-block;
}

#phone-orders-app #woo-phone-orders .link-add-item-from-shop {
  margin-right: 20px;
}

#phone-orders-app .save-to-customer-button__loader {
  display: inline-block;
}

#phone-orders-app .save-to-customer-button__loader .v-clip {
  width: 15px !important;
  height: 15px !important;
  vertical-align: middle;
}

#phone-orders-app .multiselect__single {
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}

#phone-orders-app #addCustomer .error-alert,
#phone-orders-app #editAddress .error-alert {
  display: inline-block;
  padding: 3px 12px;
  vertical-align: middle;
  margin-bottom: 0;
  margin-right: 10px;
}

#phone-orders-errors .error-alert ul {
  margin-bottom: 0;
}

#phone-orders-app .multiselect__option--highlight {
  background: #5897fb;
}

#phone-orders-app .multiselect__spinner::after,
#phone-orders-app .multiselect__spinner::before {
  border-color: #5897fb transparent transparent;
}

#phone-orders-app .multiselect .multiselect__input {
  padding: 0 0 0 5px;
  line-height: 20px;
  min-height: 20px;
}

.wpo-order-sidebar > .meta-box-sortables {
  width: 100%;
}

#phone-orders-app #woo-phone-orders .link-add-item-from-shop {
  margin-right: 0;
}

@media screen and (max-width: 782px) {

  #phone-orders-app .phone-orders-woocommerce_tab-settings .form-table td:first-child {
    width: auto;
  }

  #phone-orders-app .multiselect input {
    padding: 0px 5px;
    line-height: inherit;
  }

  #phone-orders-app .wp-list-table tr:not(.inline-edit-row):not(.no-items) td:not(.check-column) {
    display: table-cell;
  }

}

</style>

<script>

import addCouponModal from './tab-add-order/modals/add_coupon.vue';
import addCustomItemModal from './tab-add-order/modals/add_custom_item_modal.vue';
import addCustomerModal from './tab-add-order/modals/add_customer.vue';
import loadSessionsModal from './tab-add-order/modals/load_sessions.vue';
import addDiscountModal from './tab-add-order/modals/add_discount.vue';
import addFeeModal from './tab-add-order/modals/add_fee.vue';
import shippingModal from './tab-add-order/modals/shipping_modal.vue';
import editAddressModal from './tab-add-order/modals/edit_address.vue';
import configureProductModal from './tab-add-order/modals/configure_product.vue';
import orderHistoryCustomerModal from './tab-add-order/modals/order_history_customer.vue';
import addGiftCardModal from './tab-add-order/modals/add_gift_card.vue';
import chooseGiftsModal from './tab-add-order/modals/choose_gifts.vue';
import productDescriptionModal from './tab-add-order/modals/product_description.vue';
import productHistoryModal from './tab-add-order/modals/product_history_customer.vue';

var loader = require('vue-spinner/dist/vue-spinner.min').ClipLoader;

export default {
  created() {
    window.addEventListener('beforeunload', this.windowBeforeUnload);
  },
  destroyed() {
    window.removeEventListener('beforeunload', this.windowBeforeUnload);
  },
  mounted() {
    this.updateStoredCartHash();
  },
  props: {
    addCouponModalSettings: {
      default: function () {
        return {};
      }
    },
    addCustomItemModalSettings: {
      default: function () {
        return {};
      }
    },
    addCustomerModalSettings: {
      default: function () {
        return {};
      }
    },
    addDiscountModalSettings: {
      default: function () {
        return {};
      }
    },
    addFeeModalSettings: {
      default: function () {
        return {};
      }
    },
    shippingModalSettings: {
      default: function () {
        return {};
      }
    },
    editAddressModalSettings: {
      default: function () {
        return {};
      }
    },
    configureProductModalSettings: {
      default: function () {
        return {};
      }
    },
    orderHistoryCustomerModalSettings: {
      default: function () {
        return {};
      }
    },
    loadSessionsModalSettings: {
      default: function () {
        return {};
      }
    },
    chooseGiftsModalSettings: {
      default: function () {
        return {};
      }
    },
    deletedItemLabel: {
      default: function () {
        return '';
      }
    },
    outOfStockItemLabel: {
      default: function () {
        return '';
      }
    },
    orderSidebarCustomHtml: {
      default: function () {
        return '';
      }
    },
    addGiftCardModalSettings: {
      default: function () {
        return {};
      }
    },
    productDescriptionModalSettings: {
      default: function () {
        return {};
      }
    },
    productHistoryModalSettings: {
      default: function () {
        return {};
      }
    },
  },
  data: function () {
    return {
      deletedItems: [],
    };
  },
  watch: {
    storedDeletedItems(newVal) {
      this.deletedItems = [...newVal];
    },
    storedCalculatedDeletedItems(newVal) {
      this.deletedItems = [...this.deletedItems, ...newVal];
    },
  },
  computed: {
    isLoading() {
      return this.$store.state.add_order.is_loading;
    },
    isLoadingWithoutBackground() {
      return this.$store.state.add_order.is_loading_without_background;
    },
    outOfStockItems() {
      return this.$store.state.add_order.out_of_stock_items;
    },
    storedDeletedItems() {
      return this.$store.state.add_order.deleted_items;
    },
    storedCalculatedDeletedItems() {
      return this.$store.state.add_order.calculated_deleted_items;
    },
    isShowMessages() {
      return !!this.deletedItems.length || !!this.outOfStockItems.length;
    },
    showOrderDate() {
      return this.getSettingsOption('show_order_date_time');
    },
    showOrderStatus() {
      return this.getSettingsOption('show_order_status');
    },
    showOrderCurrencySelector() {
      return this.getSettingsOption('show_order_currency_selector');
    },
    showPaymentMethods() {
      return this.getSettingsOption('show_payment_methods');
    },
    isOrderFieldsBelowCustomerDetails() {
      return this.getSettingsOption('order_fields_position', 'below_customer_details') == 'below_customer_details';
    },
  },
  methods: {
    clear() {
      this.$store.commit('add_order/setDeletedItems', []);
      this.$store.commit('add_order/setOutOfStockItems', []);
    },
    windowBeforeUnload(e) {

      if (!this.cartIsChanged || this.$store.state.add_order.unconditional_redirect) {
        return undefined;
      }

      /*setTimeout(() => {
          window.location = this.$parent.href;
      }, 2500);*/

      (e || window.event).returnValue = false; //Gecko + IE

      return false; //Gecko + Webkit, Safari, Chrome etc.
    },
  },
  components: {
    addCouponModal,
    addCustomItemModal,
    addCustomerModal,
    loadSessionsModal,
    addDiscountModal,
    addFeeModal,
    shippingModal,
    editAddressModal,
    configureProductModal,
    orderHistoryCustomerModal,
    addGiftCardModal,
    chooseGiftsModal,
    productDescriptionModal,
    productHistoryModal,
    loader,
  },
}
</script>
