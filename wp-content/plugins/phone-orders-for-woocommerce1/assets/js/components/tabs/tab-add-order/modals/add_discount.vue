<template>
  <div>
    <b-modal id="addDiscountModal"
             ref="modal"
             :title="addDiscountLabel"
             @shown="shown"
             :noCloseOnBackdrop="modalDontCloseOnBackdropClick"
             :static="true"
             v-model="showModal"
    >
      <b-form ref="discountForm" inline @submit.stop.prevent="apply">
      <div v-if="isAllowToEditCouponName" class="wpo-add-discount-name">
          <input type="text" name="coupon_name" v-model="elCouponName" :placeholder="couponNameLabel" :required="isAllowToEditCouponName" ref="couponName">
        </div>
        <b-form-radio-group buttons
                            button-variant="outline-primary"
                            class="mb-2 mr-sm-2 mb-sm-0"
                            v-model="elDiscountType"
                            name="discount-type"
                            ref="group"
                            :options="[{html: this.currencySymbol, value: 'fixed_cart'}, {text: '%', value: 'percent'}]">
          S
        </b-form-radio-group>

        <input
          type="number"
          class="mb-2 mr-sm-2 mb-sm-0"
          v-model.number="elDiscountValue"
          id="discountValue"
          required
          ref="autofocus"
          min=0
          step='0.01'
          :class="{'wpo-add-discount-value': isAllowToEditCouponName}"
        >

        <span>{{ isDiscountIncludeTax ? discountWithTaxLabel : discountWithoutTaxLabel }}</span>
      </b-form>
      <template v-slot:footer>
        <div>
          <b-button @click="cancel">{{ cancelLabel }}</b-button>
          <b-button @click="remove" variant="danger" :disabled="!discount">{{ removeLabel }}</b-button>
          <b-button @click="apply" variant="primary">{{ applyLabel }}</b-button>
        </div>
      </template>
    </b-modal>
  </div>
</template>

<style>
@media (max-width: 767px) {
  #addDiscountModal .modal-body input[type="number"] {
    max-width: 160px;
    margin-left: 10px;
    margin-right: 10px;
  }
}

.wpo-add-discount-name {
  margin-right: 10px;
}

.wpo-add-discount-value {
  width: 25%;
}
</style>

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
    removeLabel: {
      default: function () {
        return 'Remove';
      }
    },
    addDiscountLabel: {
      default: function () {
        return 'Add discount';
      }
    },
    discountValue: {
      default: function () {
        return 0;
      }
    },
    discountWithTaxLabel: {
      default: function () {
        return 'with tax';
      }
    },
    discountWithoutTaxLabel: {
      default: function () {
        return 'without tax';
      }
    },
    couponNameLabel: {
      default: function () {
        return 'Coupon Name';
      }
    },
  },
  data: function () {
    return {
      elDiscountType: '',
      elDiscountValue: this.discountValue,
      elCouponName: this.isAllowToEditCouponName ? this.defaultCouponName : '',
      showModal: false,
    };
  },
  computed: {
    discount() {
      return this.$store.state.add_order.cart.discount;
    },
    currencySymbol() {
      return this.$store.state.add_order.cart.order_currency && this.getSettingsOption('show_order_currency_selector') ? this.$store.state.add_order.cart.order_currency.symbol : this.$store.state.add_order.cart.wc_price_settings.currency_symbol;
    },
    isDiscountIncludeTax() {
      return this.$store.state.add_order.cart.wc_tax_settings.prices_include_tax;
    },
    isAllowToEditCouponName() {
      return this.getSettingsOption('allow_to_edit_coupon_name');
    },
    defaultCouponName() {
      return this.getSettingsOption('manual_coupon_title') ? this.getSettingsOption('manual_coupon_title') : '';
    },
    defaultDiscountType() {
      return this.getSettingsOption('default_discount_type');
    },
  },
  methods: {
    cancel() {
      this.close();
    },
    apply() {
      const form = this.$refs.discountForm.$el;

      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }

      var discount = {type: this.elDiscountType, amount: this.elDiscountValue, name: this.elCouponName};
      this.$root.bus.$emit('set-manual-discount', discount);
      this.$store.commit('add_order/setDiscount', discount);
      this.close();
    },
    remove() {
      this.$root.bus.$emit('set-manual-discount', null);
      this.$store.commit('add_order/setDiscount', null);
      this.close();
    },
    close() {
      this.showModal = false;
    },
    shown(e) {
      var discount = this.discount;
      this.elDiscountType = discount ? discount.type : this.defaultDiscountType;
      this.elDiscountValue = discount ? discount.amount : this.discountValue;
      if(this.isAllowToEditCouponName) {
        this.elCouponName = discount ? discount.name : this.defaultCouponName;
      } else {
        this.elCouponName = discount ? discount.name : '';
      }
      this.$nextTick(() => {
        if (this.isAllowToEditCouponName) {
          this.$refs.couponName.focus();
        } else {
          this.$refs.autofocus.focus();
        }
      });
    },
    enter() {
      console.log('enter');
    },
    submit() {
      console.log('submit');
    },
  },
}
</script>
