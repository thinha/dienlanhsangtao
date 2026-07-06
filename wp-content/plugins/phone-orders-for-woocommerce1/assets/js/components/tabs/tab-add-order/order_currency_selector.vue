<template>
  <div class="postbox disable-on-order">
    <h2>
      <span>{{ title }}</span>
    </h2>
    <div class="order-currency-select">
      <multiselect
        :allow-empty="false"
        :hide-selected="false"
        :searchable="true"
        style="width: 100%;max-width: 800px;"
        label="title"
        name="order-currency-select"
        v-model="currencyOrder"
        :options="orderCurrenciesList"
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
.postbox.disable-on-order .order-currency-select {
  padding: 5px;
}
</style>


<script>

import Multiselect from 'vue-multiselect';

export default {
  props: {
    title: {
      default: function () {
        return 'Order currency';
      }
    },
    orderCurrenciesList: {
      default: function () {
        return [];
      },
    },
    orderDefaultCurrency: {
      default: function () {
        return {code: '', symbol: ''};
      },
    },
    noOptionsTitle: {
      default: function () {
        return 'List is empty.';
      }
    },
  },
  mounted() {
    this.onChange(this.currencyOrder);
  },
  data: function () {
    return {
      currencyOrder: this.getObjectByKeyValue(this.orderCurrenciesList, 'value', this.orderDefaultCurrency.code),
    };
  },
  watch: {
    storedOrderCurrency(newVal, oldVal) {
      this.currencyOrder = this.getObjectByKeyValue(this.orderCurrenciesList, 'value', newVal.code);
    },
  },
  computed: {
    storedOrderCurrency: {
      get: function () {
        return this.$store.state.add_order.cart.order_currency;
      },
      set: function (newVal) {
        this.$store.commit('add_order/updateOrderCurrency', newVal);
      },
    },
  },
  methods: {
    onChange: function (currencyOrder) {
      this.storedOrderCurrency = {
        code: this.getKeyValueOfObject(currencyOrder, 'value'),
        symbol: this.getKeyValueOfObject(currencyOrder, 'symbol')
      };
    },
  },
  components: {
    Multiselect,
  },
}
</script>
