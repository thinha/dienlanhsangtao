<template>
    <div class="postbox disable-on-order">
        <h2>
            <span class="field-label" :class="{'required-field': isEmptyValue}">{{ title }} *</span>
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
                    @input="onChange"
                    :disabled="!cartEnabled"
            >
                <template slot="noOptions">
                    <span v-html="noOptionsTitle"></span>
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
		    default: function() {
			return 'List is empty.';
		    }
		},
	    },
	    data: function () {
		return {
		    paymentMethod: {},
		    paymentGateways: this.initialPaymentGateways,
		};
	    },
	    watch: {
		storedPaymentMethod( newVal, oldVal ) {
		    if (this.showPaymentMethods) {
			this.paymentMethod = this.getObjectByKeyValue(this.paymentGateways, 'value', newVal, this.getObjectByKeyValue(this.paymentGateways, 'value', ''));
		    } else {
			this.paymentMethod = this.getObjectByKeyValue(this.paymentGateways, 'value', this.orderPaymentMethodOption, this.getObjectByKeyValue(this.paymentGateways, 'value', ''));
			this.onChange();
		    }
		},
		storedPaymentGateways( newVal, oldVal ) {
		    this.paymentGateways = newVal;
		},
		orderPaymentMethodOption(newVal, oldVal) {
		    this.paymentMethod = this.getObjectByKeyValue(this.paymentGateways, 'value', newVal);
		    this.onChange();
		},
	    },
	    computed: {
		storedPaymentMethod: {
		    get: function () {
			    return this.$store.state.add_order.cart.payment_method;
		    },
		    set: function ( newVal ) {
			    this.$store.commit( 'add_order/updatePaymentMethod', newVal );
		    },
		},
		storedPaymentGateways () {
		    return this.$store.state.add_order.payment_gateways;
		},
		orderPaymentMethodOption () {
		    return this.getSettingsOption('order_payment_method');
		},
		showPaymentMethods () {
		    return this.getSettingsOption('show_payment_methods');
		},
		isEmptyValue() {
		    return !this.paymentMethod || !this.paymentMethod.value;
		},
	    },
	    methods: {
		onChange: function () {
		    this.storedPaymentMethod = this.getKeyValueOfObject(this.paymentMethod, 'value');
		},
		checkCart: function () {

		    if (!this.showPaymentMethods) {
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