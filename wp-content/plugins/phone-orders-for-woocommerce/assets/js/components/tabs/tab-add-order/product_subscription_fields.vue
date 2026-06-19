<template>
    <div class="product-item-subscription-field-list">
	<b-row class="product-item-subscription-field-list__field">
	    <b-col cols="4" class="product-item-subscription-field-list__field-label">
		<strong>{{ signUpFeeLabel }} (<span v-html="currencySymbol"></span>):</strong>
	    </b-col>
	    <b-col cols="8" class="product-item-subscription-field-list__field-value">
		<input
		    type="text"
		    class="wpo-sign-up-fee"
		    v-model.lazy="signUpFeeModel"
		    :disabled="!cartEnabled"
		    autocomplete="off"
		    placeholder="0"
		    size="6"
		/>
	    </b-col>
	</b-row>
	<b-row class="product-item-subscription-field-list__field">
	    <b-col cols="4" class="product-item-subscription-field-list__field-label">
		<strong>{{ paymentLabel }}:</strong>
	    </b-col>
	    <b-col cols="8" class="product-item-subscription-field-list__field-value">
		<select v-model="billingInterval" :disabled="!cartEnabled" @change="onUpdateBillingInterval">
		    <option value="" disabled selected>{{chooseOptionBillingIntervalPlaceholder}}</option>
		    <option v-for="option in paymentIntervalOptions" :value="option.value">{{option.title}}</option>
		</select>
		<select v-model="billingPeriod" :disabled="!cartEnabled" @change="onUpdateBillingPeriod">
		    <option value="" disabled selected>{{chooseOptionBillingPeriodPlaceholder}}</option>
		    <option v-for="option in paymentPeriodOptions" :value="option.value">{{option.title}}</option>
		</select>
	    </b-col>
	</b-row>
	<b-row class="product-item-subscription-field-list__field">
	    <b-col cols="4" class="product-item-subscription-field-list__field-label">
		<strong>{{ nextPaymentLabel }}:</strong>
	    </b-col>
	    <b-col cols="8" class="product-item-subscription-field-list__field-value">
		<datepicker
		    ref="datepicker"
		    v-model="nextPaymentDateModel"
		    :disabled="!cartEnabled"
		    format="yyyy-MM-dd"
		    :typeable="true"
		></datepicker>
		<input type="number"
		    @blur="onUpdateNextPaymentDateHour"
		    class="hour"
		    :placeholder="hourPlaceholder"
		    v-model="nextPaymentDateHour"
		    min="0"
		    max="23"
		    step="1"
		    pattern="([01]?[0-9]{1}|2[0-3]{1})"
		    :disabled="!cartEnabled"
		/>
		    :
		<input type="number"
		    @blur="onUpdateNextPaymentDateMinute"
		    class="minute"
		    :placeholder="minutePlaceholder"
		    min="0"
		    max="59"
		    step="1"
		    v-model="nextPaymentDateMinute"
		    pattern="[0-5]{1}[0-9]{1}"
		    :disabled="!cartEnabled"
		/>
	    </b-col>
	</b-row>
    </div>
</template>

<style>
    .product-item-subscription-field-list {
        margin-top: 10px;
    }

    .product-item-subscription-field-list__field-label {
	align-self: center;
	max-width: 130px;
	padding-right: 0;
    }

     #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items .product-item-subscription-field-list__field-value select,
    .product-item-subscription-field-list__field-value .vdp-datepicker {
	width: 48%;
	display: inline-block;
    }

    .product-item-subscription-field-list__field-value .vdp-datepicker input {
	max-width: 100%;
    }

    .product-item-subscription-field-list__field + .product-item-subscription-field-list__field {
	margin-top: 5px;
    }

    .product-item-subscription-field-list__field-value,
    .product-item-subscription-field-list__field-value .vdp-datepicker {
	position: static;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items .product-item-subscription-field-list__field-value input:disabled {
	color: #a0a5aa;
	background-color: #f7f7f7;
    }

    .product-item-subscription-field-list__field-value {
	padding-left: 0;
    }

    #woocommerce-order-items .woocommerce_order_items_wrapper table.woocommerce_order_items .product-item-subscription-field-list__field-value input {
	padding: 0 4px;
    }

</style>
<script>

    import Multiselect from 'vue-multiselect';
    import Datepicker from 'vuejs-datepicker';

    export default {
	mounted() {
	    this.$refs.datepicker.$children[0].$on('closeCalendar', this.onUpdatePaymentDate)
	},
        props: {
            fields: {
                default: function() {
                    return {};
                }
            },
            paymentLabel: {
                default: function() {
                    return 'Billing Schedule';
                }
            },
            paymentPeriodOptions: {
                default: function() {
                    return [];
                }
            },
            paymentIntervalOptions: {
                default: function() {
                    return [];
                }
            },
            nextPaymentLabel: {
                default: function() {
                    return 'Next Payment';
                }
            },
	    hourPlaceholder: {
		default: function () {
		    return 'h';
		}
	    },
	    minutePlaceholder: {
		default: function () {
		    return 'm';
		}
	    },
            timezoneOffset: {
                default: function() {
                    return 0;
                }
            },
	    chooseOptionBillingIntervalPlaceholder: {
		default: function () {
		    return 'Choose interval';
		}
	    },
	    chooseOptionBillingPeriodPlaceholder: {
		default: function () {
		    return 'Choose period';
		}
	    },
	    signUpFeeLabel: {
		default: function () {
		    return 'Sign-up Fee';
		}
	    },
        },
        data: function () {

	    var nextPaymentDate = this.calcTime(this.fields.next_payment_date_timestamp_utc);

            return {
                billingPeriod: this.fields.billing_period,
                billingPeriodChangedManually: !!this.fields.billing_period_changed_manually,

                billingInterval: this.fields.billing_interval,
                billingIntervalChangedManually: !!this.fields.billing_interval_changed_manually,

		nextPaymentDateModel: new Date(nextPaymentDate),

		nextPaymentDate: nextPaymentDate,
                nextPaymentDateTimestampUtcChangedManually: !!this.fields.next_payment_date_timestamp_utc_changed_manually,

		signUpFee: this.fields.sign_up_fee,
                signUpFeeChangedManually: !!this.fields.sign_up_fee_changed_manually,
            };
        },
	watch: {
	    nextPaymentDate(newVal, oldVal) {

		if (this.calcTimestamp(newVal) === this.calcTimestamp(oldVal)) {
		    return;
		}

		this.nextPaymentDateTimestampUtcChangedManually = true;
		this.updateItem();
	    },
	    signUpFee(newVal, oldVal) {
		this.signUpFeeChangedManually = true;
		this.updateItem();
	    },
	},
        computed: {
	        nextPaymentDateHour: {
		    get() {
			return this.nextPaymentDate.getHours() > 9 ? this.nextPaymentDate.getHours() : '0' + this.nextPaymentDate.getHours();
		    },
		    set(newVal) {}
		},
	        nextPaymentDateMinute: {
		    get() {
			return this.nextPaymentDate.getMinutes() > 9 ? this.nextPaymentDate.getMinutes() : '0' + this.nextPaymentDate.getMinutes();
		    },
		    set(newVal) {}
		},
		currencySymbol() {
		    return this.$store.state.add_order.cart.wc_price_settings.currency_symbol;
		},
		signUpFeeModel: {
		    get () {
			return this.$options.filters.formatPrice(this.signUpFee, this.precision);
		    },
		    set (newVal) {
			this.signUpFee = newVal;
		    },
		},
		precision () {
		    return this.getSettingsOption('item_price_precision');
		},
        },
        methods: {
            onUpdateBillingInterval () {
		this.billingIntervalChangedManually = true;
		this.updateItem();
            },
            onUpdateBillingPeriod () {
		this.billingPeriodChangedManually = true;
		this.updateItem();
            },
            onUpdatePaymentDate () {
		var date = new Date(this.nextPaymentDate)
		date.setFullYear(this.nextPaymentDateModel.getFullYear());
		date.setMonth(this.nextPaymentDateModel.getMonth());
		date.setDate(this.nextPaymentDateModel.getDate());
		this.nextPaymentDate = date;
            },
            onUpdateNextPaymentDateHour (e) {
		var date = new Date(this.nextPaymentDate)
		date.setHours(e.target.value);
		this.nextPaymentDate = date;
            },
            onUpdateNextPaymentDateMinute (e) {
		var date = new Date(this.nextPaymentDate)
		date.setMinutes(e.target.value);
		this.nextPaymentDate = date;
            },
	    updateItem() {
		this.$emit('update', {
		    billing_period: this.billingPeriod,
		    billing_period_changed_manually: this.billingPeriodChangedManually,
		    billing_interval: this.billingInterval,
		    billing_interval_changed_manually: this.billingIntervalChangedManually,
		    next_payment_date_timestamp_utc: this.calcTimestamp(this.nextPaymentDate),
		    next_payment_date_timestamp_utc_changed_manually: this.nextPaymentDateTimestampUtcChangedManually,
		    sign_up_fee: this.signUpFee,
		    sign_up_fee_changed_manually: this.signUpFeeChangedManually,
		});
	    },
	    calcTime( timestamp = null ) {
		// create Date object for current location
		let d = new Date();

		if ( ! timestamp ) {
			timestamp = Math.floor( d.getTime() / 1000 );
		}
		// convert to msec
		// subtract local time zone offset
		// get UTC time in msec
		let utc = timestamp * 1000 + (
			d.getTimezoneOffset() * 60000
		);

		// create new Date object for different city
		// using supplied offset
		let nd = new Date( utc + (
			3600000 * this.timezoneOffset
		) );

		return nd;
	    },
	    calcTimestamp( date = null ) {
		if ( ! date ) {
		    date = new Date();
		}

		return Math.floor( (
		    date.getTime() - date.getTimezoneOffset() * 60000 - 3600000 * this.timezoneOffset
		) / 1000 );
	    },
        },
        components: {
            Multiselect,
	    Datepicker,
        },
    }
</script>