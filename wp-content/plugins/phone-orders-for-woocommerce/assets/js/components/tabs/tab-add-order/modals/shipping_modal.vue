<template>
  <div>
    <b-modal id="shippingModal"
             ref="modal"
             :title="shippingMethodLabel"
             @shown="shown"
             :noCloseOnBackdrop="modalDontCloseOnBackdropClick"
             :static="true"
             v-model="showModal"
    >
      <div id="shipping_method">
        <div v-if="shippingPackages.length > 1">
          <div v-if="! packageContents.length">{{ packageContentsLabel }}</div>
          <div class="package_content">
                        <span v-for="(item, index) in packageContents">
                            <span>{{ item.title }} x {{ item.quantity }}</span><span
                          v-if="index+1 < packageContents.length">, </span>
                        </span>
          </div>
        </div>
        <div v-if="! shippingMethods.length">{{ noShippingMethodsAvailableLabel }}</div>
        <ul>
          <li v-for="shippingMethod in shippingMethods">
            <label>
              <input type="radio"
                     :ref="'radio_button_' + shippingMethod.id"
                     :value="shippingMethod.id"
                     class="shipping_method"
                     v-model="elSelectedShippingMethodID"
                     :key="shippingMethod.id"
                     @keyup.enter="select(shippingMethod)"
                     name="shipping_method"
                     :disabled="isSuppressSelection"
              >
              <span v-if="allowEditShippingTitle && (elSelectedShippingMethodID === shippingMethod.id)">
				<input
          v-model="newShippingTitle"
          type="text"
          @change="enableCustomTitle()"
          :disabled="isSuppressSelection"
        >
			    </span>
              <span v-else>
				{{ shippingMethod.label }}
			    </span>
              <span v-if="isCustomPriceShipping(shippingMethod)">:
                                <span class="woocommerce-Price-amount amount">
                                    <span class="woocommerce-Price-currencySymbol" v-html="currencySymbol"/>
                                    <input
                                      type="number"
                                      :ref="'cost_input_' + shippingMethod.id"
                                      @keyup.enter="save"
                                      min="0"
                                      step='0.01'
                                      v-model.number="shippingMethod.cost"
                                      @focus="focusCustomPriceShipping(shippingMethod)"
                                      :disabled="isSuppressSelection"
                                    >
                                </span>
                            </span>

              <span v-else>:
				    <span v-html="wcPrice(shippingMethod.cost)"/>
				    <input v-if="allowEditShippingCost && (elSelectedShippingMethodID === shippingMethod.id)"
                   v-model="newShippingPrice"
                   type="number"
                   min="0"
                   step='0.01'
                   @change="enableCustomPrice()"
                   :disabled="isSuppressSelection"
                   class="shipping_cost"
            >
                            </span>
            </label>
          </li>
        </ul>

      </div>
      <template v-slot:footer>
        <div>
          <b-button @click="close">{{ cancelLabel }}</b-button>
          <b-button @click="remove" :disabled="!shipping" variant="danger">{{ removeLabel }}</b-button>
          <b-button @click="save" variant="primary" :disabled="!elSelectedShippingMethodID">{{ saveLabel }}
          </b-button>
        </div>
      </template>
    </b-modal>
  </div>
</template>

<style>

#shipping_method .package_content {
  margin: 10px 0;
}

.shipping_cost {
  width: 100px;
  margin-left: 10px;
}

</style>

<script>

export default {
  created() {
    this.$root.bus.$on('edit-shipping-package', (hash) => {
      this.packageHash = hash;
      let item = this.selectedPackage;

      this.elSelectedShippingMethodID = null;
      this.newShippingPriceApplied = false;
      this.newShippingPrice = null;
      this.newShippingTitleApplied = false;
      this.newShippingTitle = null;

      if (!!item.chosen_rate) {
        this.elSelectedShippingMethodID = !!item.chosen_rate.id ? item.chosen_rate.id : null;
        this.newShippingPrice = !!item.chosen_rate.cost ? item.chosen_rate.cost : 0;
        this.newShippingTitle = !!item.chosen_rate.label ? item.chosen_rate.label : null;
      }

      if (!!item.custom_price) {
        this.newShippingPriceApplied = item.custom_price.enabled ? item.custom_price.enabled : false;
        this.newShippingPrice = item.custom_price.cost ? item.custom_price.cost : this.newShippingPrice;
      }

      if (!!item.custom_title) {
        this.newShippingTitleApplied = item.custom_title.enabled ? item.custom_title.enabled : false;
        this.newShippingTitle = item.custom_title.title ? item.custom_title.title : this.newShippingTitle;
      }

      this.showModal = true;
    });
  },
  props: {
    shippingMethodLabel: {
      default: function () {
        return 'Shipping method';
      }
    },
    noShippingMethodsAvailableLabel: {
      default: function () {
        return 'No shipping methods available';
      }
    },
    cancelLabel: {
      default: function () {
        return 'Cancel';
      }
    },
    removeLabel: {
      default: function () {
        return 'Reset';
      }
    },
    saveLabel: {
      default: function () {
        return 'Save';
      }
    },
    tabName: {
      default: function () {
        return 'add-order';
      }
    },
    packageContentsLabel: {
      default: function () {
        return 'Package contents';
      }
    },
  },
  data: function () {
    return {
      packageHash: "",
      elSelectedShippingMethodID: null,
      customShippingPrice: null,
      newShippingPrice: null,
      newShippingPriceApplied: false,
      newShippingTitle: null,
      newShippingTitleApplied: false,
      showModal: false,
    };
  },
  computed: {
    shippingPackages: function () {
      return !!this.$store.state.add_order.cart.shipping.packages ? this.$store.state.add_order.cart.shipping.packages : [];
    },
    selectedPackage: function () {
      let selectedPackage = null;

      this.shippingPackages.forEach((shippingPackage) => {
        if (shippingPackage.hash === this.packageHash) {
          selectedPackage = shippingPackage;
        }
      });

      return selectedPackage;
    },
    shipping: function () {
      return !!this.selectedPackage && !!this.selectedPackage.chosen_rate ? this.selectedPackage.chosen_rate : {};
    },
    shippingMethods: function () {
      // custom shipping is using "shippingMethod.cost" variable in v-model
      // we must "clone" object to prevent edit cost in storage

      let rates = !!this.selectedPackage && !!this.selectedPackage.rates ? this.selectedPackage.rates : [];

      rates.forEach(function (item, index, rates) {
        rates[index] = Object.assign({}, item);
      });

      return rates;
    },
    packageContents: function () {
      return !!this.selectedPackage && !!this.selectedPackage.contents ? this.selectedPackage.contents : [];
    },
    autorecalculate: function () {
      return this.getSettingsOption('auto_recalculate');
    },
    allowEditShippingCost: function () {
      return this.getSettingsOption('allow_edit_shipping_cost');
    },
    allowEditShippingTitle: function () {
      return this.getSettingsOption('allow_edit_shipping_title');
    },
    currencySymbol() {
      return this.$store.state.add_order.cart.order_currency && this.getSettingsOption('show_order_currency_selector') ? this.$store.state.add_order.cart.order_currency.symbol : this.$store.state.add_order.cart.wc_price_settings.currency_symbol;
    },
    isSuppressSelection: function () {
      return false
      // return !! this.$store.state.add_order.cart.shipping.is_free_shipping_coupon_applied ? this.$store.state.add_order.cart.shipping.is_free_shipping_coupon_applied : false;
    },
  },
  watch: {
    elSelectedShippingMethodID(newVal) {
      if (newVal) {
        this.newShippingPrice = this.getObjectByKeyValue(this.shippingMethods, 'id', this.elSelectedShippingMethodID).cost;
      }
      if (!this.newShippingTitleApplied && newVal) {
        this.newShippingTitle = this.getObjectByKeyValue(this.shippingMethods, 'id', this.elSelectedShippingMethodID).label;
      }
    },
    allowEditShippingCost(newVal) {
      if (!newVal) {
        this.newShippingPriceApplied = false;
      }
    },
    allowEditShippingTitle(newVal) {
      if (!newVal) {
        this.newShippingTitleApplied = false;
      }
    }
  },
  methods: {
    shown() {
      if (this.shippingMethods.length) {
        if (this.shipping.id) {
          this.$refs['radio_button_' + this.shipping.id][0].focus();
        } else {
          this.$refs['radio_button_' + this.shippingMethods[0].id][0].focus();
        }
      }
    },
    select(shipping) {

      this.elSelectedShippingMethodID = shipping.id;

      if (!this.isCustomPriceShipping(shipping)) {
        this.save();
        return;
      }

      this.$refs['cost_input_' + shipping.id][0].focus();
    },
    pushPackage() {
      let newPackages = [];
      let chosenRate = this.getObjectByKeyValue(this.shippingMethods, 'id', this.elSelectedShippingMethodID);

      if (this.isCustomPriceShipping(chosenRate)) {
        chosenRate.price = this.newShippingPrice;
      }

      var customTitle = this.prepareCartShippingCustomTitle();

      if (customTitle.enabled) {
        chosenRate = Object.assign({}, chosenRate, {label: customTitle.title})
      }

      var customPrice = this.prepareCartShippingCustomPrice();

      if (customPrice.enabled) {
        chosenRate = Object.assign({}, chosenRate, {cost: +customPrice.cost, full_cost: +customPrice.cost})
      }

      let shippingPackage = Object.assign({}, this.selectedPackage, {
        chosen_rate: chosenRate,
        custom_price: customPrice,
        custom_title: customTitle,
      });

      this.shippingPackages.forEach(function (item) {
        if (item.hash === shippingPackage.hash) {
          item = shippingPackage;
        }
        newPackages.push(item);
      });

      this.$store.commit('add_order/setPackages', newPackages);
    },
    save() {
      this.pushPackage();
      this.close();
    },
    remove() {
      this.elSelectedShippingMethodID = null;
      this.newShippingPriceApplied = false;
      this.newShippingPrice = null;
      this.newShippingTitleApplied = false;
      this.newShippingTitle = null;

      this.pushPackage();
      this.close();
    },
    close() {
      this.showModal = false;
    },
    isCustomPriceShipping(shipping) {
      return shipping && shipping.id.startsWith('phone_orders_custom_price');
    },
    focusCustomPriceShipping(shipping) {
      this.elSelectedShippingMethodID = shipping.id;
    },
    enableCustomPrice() {
      this.newShippingPriceApplied = true;
    },
    enableCustomTitle() {
      this.newShippingTitleApplied = true;
    },
    prepareCartShippingCustomPrice() {
      return Object.assign({}, {
        'enabled': this.newShippingPriceApplied,
        'cost': this.newShippingPrice,
      });
    },
    prepareCartShippingCustomTitle() {
      return Object.assign({}, {
        'enabled': this.newShippingTitleApplied,
        'title': this.newShippingTitle,
      });
    },
  },
}
</script>
