<template>
  <tr v-show="shown" :data-tab-key="tabKey">
    <td colspan=2>
      <table class="form-table">
        <tbody>
        <tr>
          <td colspan=2>
            <b>{{ title }}</b>
            <a style="display: inline-block; margin-left: 15px" :href="docLink" target="_blank" data-search-ignore>
              {{readDocsTitle}}
            </a>
          </td>
        </tr>

        <tr>
          <td>
            {{ showOrderDateTimeLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="tmpShowOrderDateTime"
                   name="show_order_date_time">
          </td>
        </tr>

        <tr>
          <td>
            {{ showOrderStatusLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="tmpShowOrderStatus" name="show_order_status">
          </td>
        </tr>

        <tr>
          <td>
            {{ showOrderCurrencySelectorLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="tmpShowOrderCurrencySelector"
                   name="show_order_currency_selector">
          </td>
        </tr>

        <tr>
          <td>
            {{ showPaymentMethodsLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="tmpShowPaymentMethods" name="show_payment_methods">
          </td>
        </tr>

        <tr>
          <td>
            {{ orderFieldsPositionLabel }}
          </td>
          <td class="phone-orders-woocommerce__radio">
            <label>
              <input type="radio" class="option" v-model="tmpOrderFieldsPosition" name="order_fields_position"
                     value="above_customer_details">
              {{ orderFieldsPositionAboveCustomerDetailsLabel }}
            </label>
            <label>
              <input type="radio" class="option" v-model="tmpOrderFieldsPosition" name="order_fields_position"
                     value="below_customer_details">
              {{ orderFieldsPositionBelowCustomerDetailsLabel }}
            </label>
          </td>
        </tr>

        <slot name="pro-layout-settings"></slot>

        </tbody>
      </table>
    </td>
  </tr>
</template>

<style>


</style>

<script>

export default {
  props: {
    title: {
      default: function () {
        return 'Layout';
      },
    },
    tabKey: {
      default: function () {
        return 'layoutSettings';
      },
    },
    showOrderDateTimeLabel: {
      default: function () {
        return 'Show order date/time';
      },
    },
    showOrderDateTime: {
      default: function () {
        return false;
      },
    },
    showOrderStatusLabel: {
      default: function () {
        return 'Show order status';
      },
    },
    readDocsTitle:{
      default: function () {
        return 'Read docs';
      },
    },
    docLink:{
      default: function () {
        return '';
      },
    },
    showOrderStatus: {
      default: function () {
        return false;
      },
    },
    showOrderCurrencySelectorLabel: {
      default: function () {
        return 'Show currency selector';
      },
    },
    showOrderCurrencySelector: {
      default: function () {
        return false;
      },
    },
    showPaymentMethodsLabel: {
      default: function () {
        return 'Show payment method';
      },
    },
    showPaymentMethods: {
      default: function () {
        return false;
      },
    },
    orderFieldsPositionLabel: {
      default: function () {
        return 'Order fields position';
      }
    },
    orderFieldsPositionAboveCustomerDetailsLabel: {
      default: function () {
        return 'above customer details';
      }
    },
    orderFieldsPositionBelowCustomerDetailsLabel: {
      default: function () {
        return 'below customer details';
      }
    },
    orderFieldsPosition: {
      default: function () {
        return 'below_customer_details';
      }
    },
  },
  mounted() {
    this.addSettingsTab(this.getTabsHeaders())
    this.setComponentsSettings(this.componentsSettings)
  },
  data() {
    return {
      tmpShowOrderDateTime: this.showOrderDateTime,
      tmpShowOrderStatus: this.showOrderStatus,
      tmpShowOrderCurrencySelector: this.showOrderCurrencySelector,
      tmpShowPaymentMethods: this.showPaymentMethods,
      tmpOrderFieldsPosition: this.orderFieldsPosition,
    };
  },
  watch: {
    componentsSettings() {
      this.setComponentsSettings(this.componentsSettings)
    },
  },
  computed: {
    shown() {

      if (this.getSearchMode()) {
        return this.getMatchedTabs().includes(this.tabKey);
      }

      return this.getSettingsCurrentTab() === this.tabKey;
    },
    componentsSettings() {
      return this.getSettings();
    },
  },
  methods: {
    getSettings() {

      var settings = {
        show_order_date_time: this.tmpShowOrderDateTime,
        show_order_status: this.tmpShowOrderStatus,
        show_order_currency_selector: this.tmpShowOrderCurrencySelector,
        show_payment_methods: this.tmpShowPaymentMethods,
        order_fields_position: this.tmpOrderFieldsPosition,
      };

      return settings;
    },
    getTabsHeaders() {
      return {
        key: this.tabKey,
        title: this.title,
        menu_order: 20,
      };
    },
    showOption(key) {
      this.shown = this.tabKey === key;
    },
  },
}
</script>
