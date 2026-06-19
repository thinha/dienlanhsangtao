<template>
    <div>
        <b-modal id="addDiscountModal"
                 ref="modal"
                 :title="addDiscountLabel"
                 @shown="shown"
		 :no-close-on-backdrop="modalDontCloseOnBackdropClick"
        >
            <b-form inline @submit.stop.prevent="apply">
                <b-form-radio-group buttons
                                    button-variant="outline-primary"
                                    class="mb-2 mr-sm-2 mb-sm-0"
                                    v-model="elDiscountType"
                                    name="discount-type"
                                    ref="group">
                    <b-form-radio value="fixed_cart"><span v-html="this.currencySymbol"></span></b-form-radio>
                    <b-form-radio value="percent">%</b-form-radio>
                </b-form-radio-group>

                <input
                    type="number"
                    class="mb-2 mr-sm-2 mb-sm-0"
                    v-model.number="elDiscountValue"
                    required
                    ref="autofocus"
                    min=0
                    step='0.01'
                >

		<span>{{ isDiscountIncludeTax ? discountWithTaxLabel : discountWithoutTaxLabel }}</span>
            </b-form>
            <div slot="modal-footer">
                <b-button @click="cancel">{{ cancelLabel }}</b-button>
                <b-button @click="remove" variant="danger" :disabled="!discount">{{ removeLabel }}</b-button>
                <b-button @click="apply" variant="primary">{{ applyLabel }}</b-button>
            </div>
        </b-modal>
    </div>
</template>

<style>
    @media (max-width:767px) {
	#addDiscountModal .modal-body input[type="number"] {
	    max-width: 160px;
	    margin-left: 10px;
	    margin-right: 10px;
	}
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
			discountType: {
				default: function () {
					return 'fixed_cart';
				}
			},
			discountValue: {
				default: function () {
					return 10;
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
		},
		data: function () {
			return {
                            elDiscountType: this.discountType,
                            elDiscountValue: this.discountValue,
			};
		},
                computed: {
                    discount () {
                        return this.$store.state.add_order.cart.discount;
                    },
                    currencySymbol() {
                        return this.$store.state.add_order.cart.wc_price_settings.currency_symbol;
                    },
                    isDiscountIncludeTax() {
                        return this.$store.state.add_order.cart.wc_tax_settings.prices_include_tax;
                    },
                },
		methods: {
			cancel() {
                            this.close();
			},
			apply() {
                            var discount = {type: this.elDiscountType, amount: this.elDiscountValue};
                            this.$root.bus.$emit('set-manual-discount', discount);
                            this.$store.commit('add_order/setDiscount', discount);
                            this.close();
			},
			remove() {
                            this.$root.bus.$emit('set-manual-discount', null);
                            this.$store.commit('add_order/setDiscount', null);
                            this.close();
			},
			close () {
                            this.$refs.modal.hide();
			},
			shown( e ) {
                            var discount            = this.discount;
                            this.elDiscountType     = discount ? discount.type : this.discountType;
                            this.elDiscountValue    = discount ? discount.amount : this.discountValue;
                            this.$refs.autofocus.focus();
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
