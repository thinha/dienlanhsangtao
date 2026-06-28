<template>
  <div>
    <b-modal id="addFee"
             ref="modal"
             :title="addFeeLabel"
             @shown="shown"
             size="lg"
             :noCloseOnBackdrop="modalDontCloseOnBackdropClick"
             :static="true"
             v-model="showModal"
    >
      <b-form inline @submit.stop.prevent="submit">

        <label class="mr-sm-2" for="feeName">{{ feeNameLabel }}</label>
        <b-form-input
          ref="feeName"
          type="text"
          class="mb-2 mr-sm-2 mb-sm-0"
          v-model.trim="elFeeName"
          id="feeName"
          required
        >
        </b-form-input>


        <label class="mr-sm-2" for="feeAmount">{{ feeAmountLabel }}</label>
        <div class="mb-sm-2">
          <b-form-radio-group buttons
                              button-variant="outline-primary"
                              class="mb-2 mr-sm-2 mb-sm-0"
                              v-model="elFeeType"
                              name="fee-type"
                              ref="group"
                              :options="[{html: this.currencySymbol, value: 'fixed'}, {text: '%', value: 'percent'}]">S
          </b-form-radio-group>
          <input
            type="number"
            class="mb-2 mr-sm-2 mb-sm-0"
            v-model.number="elFeeAmount"
            id="feeAmount"
            required
            ref="feeAmount"
            min=0
            step='0.01'
          >
          <span>{{ isFeeAmountIncludeTax ? feeAmountWithTaxLabel : feeAmountWithoutTaxLabel }}</span>
        </div>
        <b-button type="submit" v-show="false"></b-button>
      </b-form>
      <template v-slot:footer>
        <div>
          <b-button @click="close">{{ cancelLabel }}</b-button>
          <b-button @click="apply" variant="primary" :disabled="!isAllowedSubmit">{{ applyLabel }}</b-button>
        </div>
      </template>
    </b-modal>
  </div>
</template>

<script>

export default {
  props: {
    cancelLabel: {
      default: function () {
        return 'Cancel';
      }
    },
    applyLabel: {
      default: function () {
        return 'Apply';
      }
    },
    addFeeLabel: {
      default: function () {
        return 'Add Fee';
      }
    },
    feeNameLabel: {
      default: function () {
        return 'Fee name';
      }
    },
    feeAmountLabel: {
      default: function () {
        return 'Fee amount';
      }
    },
    feeAmountWithTaxLabel: {
      default: function () {
        return 'with tax';
      }
    },
    feeAmountWithoutTaxLabel: {
      default: function () {
        return 'without tax';
      }
    },
  },
  data: function () {
    return {
      elFeeName: '',
      elFeeAmount: '',
      elFeeType: '',
      showModal: false,
    };
  },
  computed: {
    feeName: function () {
      return this.getSettingsOption('default_fee_name');
    },
    feeAmount: function () {
      return this.getSettingsOption('default_fee_amount');
    },
    feeType: function () {
      return this.getSettingsOption('default_fee_type');
    },
    isValidFeeName: function () {
      return !!this.elFeeName.length;
    },
    isAllowToUseZeroAmount: function () {
      return this.getSettingsOption('allow_to_use_zero_amount');
    },
    isValidFeeAmount: function () {
      return this.isAllowToUseZeroAmount || parseFloat(this.elFeeAmount) !== 0.0;
    },
    isAllowedSubmit: function () {
      return this.isValidFeeName && this.isValidFeeAmount;
    },
    currencySymbol() {
      return this.$store.state.add_order.cart.order_currency && this.getSettingsOption('show_order_currency_selector') ? this.$store.state.add_order.cart.order_currency.symbol : this.$store.state.add_order.cart.wc_price_settings.currency_symbol;
    },
    isFeeAmountIncludeTax() {
      return this.$store.state.add_order.cart.wc_tax_settings.prices_include_tax;
    },
  },
  methods: {
    apply() {
      this.$store.commit('add_order/addFeeItem', {
        name: this.elFeeName,
        type: this.elFeeType,
        amount: this.elFeeAmount,
        original_amount: this.elFeeAmount,
        add_manually: true
      });
      this.close();
    },
    shown(e) {
      this.elFeeName = this.feeName;
      this.elFeeAmount = this.feeAmount;
      this.elFeeType = this.feeType;
      this.$refs.feeName.focus();
    },
    close() {
      this.showModal = false;
    },
    submit() {

      if (this.isAllowedSubmit) {
        this.apply();
        return;
      }

      if (!this.isValidFeeName) {
        this.$refs.feeName.focus();
        return;
      }

      if (!this.isValidFeeAmount) {
        this.$refs.feeAmount.focus();
        return;
      }
    },
  },
}
</script>
