<template>
  <div class="postbox disable-on-order">
    <h2>
      <span class="field-label" :class="{'required-field': isEmptyValue && !allowToCreateOrdersWithoutPayment}">{{
          title
        }} *</span>
    </h2>
    <div class="order-payment-method-select">
      <multiselect
        :allow-empty="false"
        :hide-selected="false"
        :searchable="false"
        style="width: 100%;max-width: 800px;"
        label="title"
        v-model="paymentMethod"
        :options="paymentGateways"
        track-by="value"
        :show-labels="false"
        @select="onChange"
        :disabled="!cartEnabled"
      >
        <template v-slot:noOptions>
                    <span>
                        <span v-html="noOptionsTitle"></span>
                    </span>
        </template>
      </multiselect>
    </div>
  </div>
</template>

<style>

.postbox.disable-on-order .order-payment-method-select {
  padding: 5px;
}

.postbox.disable-on-order .field-label.required-field {
  color: red;
}
</style>


<script>

import Multiselect from 'vue-multiselect';

export default {
  props: {
    title: {
      default: function () {
        return 'Payment method';
      }
    },
    initialPaymentGateways: {
      default: function () {
        return [];
      }
    },
    noOptionsTitle: {
      default: function () {
        return 'List is empty.';
      }
    },
  },
  mounted() {
    this.addCheckCartValidation({
      check_cart: this.checkCart,
    })
  },
  data: function () {
    return {
      paymentMethod: {},
      paymentGateways: this.initialPaymentGateways,
    };
  },
  watch: {
    storedPaymentMethod(newVal, oldVal) {
      if (this.showPaymentMethods) {
        this.paymentMethod = this.getObjectByKeyValue(this.paymentGateways, 'value', newVal, this.getObjectByKeyValue(this.paymentGateways, 'value', ''));
      } else {
        this.paymentMethod = {title: '', value: newVal};
        this.onChange(this.paymentMethod);
      }
    },
    storedPaymentGateways(newVal, oldVal) {

      this.paymentGateways = newVal;

      if (this.showPaymentMethods) {
        this.paymentMethod = this.getObjectByKeyValue(this.paymentGateways, 'value', this.storedPaymentMethod, this.getObjectByKeyValue(this.paymentGateways, 'value', ''));
      } else {
        this.paymentMethod = {title: '', value: this.storedPaymentMethod};
        this.onChange(this.paymentMethod);
      }
    },
    orderPaymentMethodOption(newVal, oldVal) {
      this.paymentMethod = this.getObjectByKeyValue(this.paymentGateways, 'value', newVal);
      this.onChange(this.paymentMethod);
    },
  },
  computed: {
    storedPaymentMethod: {
      get: function () {
        return this.$store.state.add_order.cart.payment_method;
      },
      set: function (newVal) {
        this.$store.commit('add_order/updatePaymentMethod', newVal);
      },
    },
    storedPaymentGateways() {
      return this.$store.state.add_order.payment_gateways;
    },
    orderPaymentMethodOption() {
      return this.getSettingsOption('order_payment_method');
    },
    showPaymentMethods() {
      return this.getSettingsOption('show_payment_methods');
    },
    isEmptyValue() {
      return !this.paymentMethod || !this.paymentMethod.value;
    },
    allowToCreateOrdersWithoutPayment() {
      return this.getSettingsOption('allow_to_create_orders_without_payment');
    },
  },
  methods: {
    onChange: function (paymentMethod) {
      this.storedPaymentMethod = this.getKeyValueOfObject(paymentMethod, 'value');
    },
    checkCart: function (where, options = {}) {

      if (options.autoPay) {
        return true;
      }

      if (!this.showPaymentMethods || this.allowToCreateOrdersWithoutPayment) {
        return true;
      }

      return !this.isEmptyValue;
    },
  },
  components: {
    Multiselect,
  },
}
</script>
