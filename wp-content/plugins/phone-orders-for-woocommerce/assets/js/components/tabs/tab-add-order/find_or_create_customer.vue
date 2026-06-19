<template>
    <div class="postbox disable-on-order">
        <span class="handlediv button-link custom-button-link">
            <a href="" class="clear-customer" v-show="customer" @click.prevent.stop="cartEnabled ? clearCustomer() : null" :class="{disabled: !cartEnabled}">&times;</a>
        </span>
        <h2>
	    <span :class="{'invalid-field': !isValidCustomerValue}">
		<span v-if="allowAddCustomers">{{ title }}</span>
		<span v-else>{{ titleOnlyFind }}</span>
	    </span>
        </h2>
        <div class="inside">
            <div id="search-customer-box">
                <a v-if="allowAddCustomers" href="#" @click.prevent.stop="cartEnabled ? createNewCustomer() : null" :class="{disabled: !cartEnabled}">
                    {{ createNewCustomerLabel }}
                </a>
                <multiselect
                    style="width: 100%;"
                    label="title"
                    v-model="customer"
                    :options="customerList"
                    track-by="value"
                    id="search-ajax-customer"
                    :placeholder="selectCustomerPlaceholder"
                    :loading="isLoading"
                    :internal-search="false"
                    :show-no-results="true"
                    @search-change="asyncFind"
                    :hide-selected="false"
                    :searchable="true"
                    open-direction="bottom"
                    :custom-label="customLabel"
                    @input="onChangeCustomer"
                    :disabled="!cartEnabled"
                    :options-limit="+customerSelectOptionsLimit"
                    :show-labels="false"
		    v-store-search-multiselect
                >
                    <span slot="noResult">{{ noResultLabel }}</span>
                    <template slot="option" slot-scope="props">
                        <span v-html="props.option.title"></span>
                    </template>
                    <template slot="noOptions">
                        <span v-html="noOptionsTitle"></span>
                    </template>
                </multiselect>
            </div>
	    <div class="orders-customer-links-block">
		<div v-show="showCustomersUrl" id="customer_urls">
		    <a v-show="showProfileUrl" :href="profileUrl" target="_blank">{{profileUrlTitle}}</a><br/>
		    <a v-show="showOtherOrderUrl" :href="otherOrderUrl" target="_blank">{{otherOrderUrlTitle}}</a>
		</div><br/>
		<div v-if="showOrderHistoryCustomerSummary" class="orders-history-link">
		    <a href="#" @click.prevent="openOrderHistoryCustomer">
			{{ orderHistoryCustomerLinkTitle }}
		    </a><br/>
		    <span>
			{{ orderHistoryCustomerSummaryNoTransactionsTitle }}:
			{{ orderHistoryCustomerSummary.no_transactions }} |
		    </span>
		    <span>
			<span v-html="orderHistoryCustomerSummary.total_paid"></span>
		    </span>
		</div>
	    </div>
            <div>
                <div class="order_data_column phone-orders-customer-data-details">
                    <div class="billing-details" data-edit-address="billing" @click.prevent.stop="cartEnabled ? onClick('billing') : null">
                        <h4>
                            {{ billingDetailsLabel }}
                            <a href="#" class="edit_address" @click.prevent.stop="cartEnabled ? onClick('billing') : null">Edit</a>
                        </h4>
                        <p>
                            <span v-if="billingAddress" v-html="billingAddress"></span>
                            <span v-else>
                                {{ shippingAddress ? billingAddressAsShippingMessage : emptyBillingAddressMessage }}
                            </span>
                        </p>
                    </div>
                    <span v-show="! customerIsEmpty">
                        <slot name="tax-exempt"></slot>
                        <p v-show="!hideShippingSection">
                            <label>
                                <input type="checkbox" v-model="shipDifferent" v-bind:disabled="!cartEnabled">
                                {{ shipDifferentLabel }}
                            </label>
                        </p>
                        <div v-show="!hideShippingSection && shipDifferent" @click.prevent.stop="cartEnabled ? onClick('shipping') : null">
                            <h4>
                                {{ shipDetailsLabel }}
                                <a href="#" class="edit_address"
                                   @click.prevent.stop="cartEnabled ? onClick('shipping') : null">Edit</a>
                            </h4>
                            <p>
                                <span v-if="shippingAddress" v-html="shippingAddress"></span>
                                <span v-else>
                                    {{ emptyShippingAddressMessage }}
                                </span>
                            </p>
                        </div>
                    </span>
                </div>
            </div>
            <pro-features v-if="!isProVersion" v-bind="proFeaturesSettings"></pro-features>
	    <b-alert :show="!!errorMessage" fade variant="danger" class="wpo-customer-error">
		{{ errorMessage }}
	    </b-alert>
        </div>
        <slot name="save-to-customer"></slot>
    </div>
</template>

<style>
    #phone-orders-app .button-link.custom-button-link {
        text-decoration: none;
	display: block;
	float: right;
    }

    #search-customer-box .multiselect .multiselect__option {
        white-space: normal;
    }

    #customer_urls {
        text-align: right;
	display: inline-block;
    }

    .phone-orders-customer-data-details {
	margin-top: 12px;
    }

    .orders-history-link {
	text-align: right;
	display: inline-block;
    }

    .orders-customer-links-block {
	text-align: right;
    }

    .wpo-customer-error {
	margin-bottom: 0;
	padding: 2px 8px;
    }

    .postbox.disable-on-order .invalid-field {
        color: red;
    }

</style>

<script>

    import Multiselect from 'vue-multiselect';
    import ProFeatures from './pro_features.vue';

    export default {
        props: {
            title: {
                default: function() {
                    return 'Find or create a customer';
                }
            },
	        titleOnlyFind: {
                default: function() {
                    return 'Find a customer';
                }
            },
            createNewCustomerLabel: {
                default: function() {
                    return 'New customer';
                }
            },
            billingDetailsLabel: {
                default: function() {
                    return 'Billing Details';
                }
            },
            shipDifferentLabel: {
                default: function() {
                    return 'Ship to a different address?';
                }
            },
            shipDetailsLabel: {
                default: function() {
                    return 'Shipping Details';
                }
            },
            emptyBillingAddressMessage: {
                default: function() {
                    return 'No billing address was provided.';
                }
            },
            emptyShippingAddressMessage: {
                default: function() {
                    return 'No shipping address was provided.';
                }
            },
            billingAddressAsShippingMessage: {
                default: function() {
                    return 'Same as shipping address.';
                }
            },
            tabName: {
                default: function() {
                    return 'add-order';
                }
            },
            proFeaturesSettings: {
                default: function() {
                    return {};
                }
            },
            isProVersion: {
                default: function() {
                    return false;
                }
            },
            requiredFieldsForPopUp: {
                default: function() {
                    return {};
                }
            },
            selectCustomerPlaceholder: {
                default: function() {
                    return 'Guest';
                }
            },
	        profileUrlTitle: {
		        default: function() {
			        return 'Profile &rarr;';
		        }
            },
	        otherOrderUrlTitle: {
		        default: function() {
			        return 'View other orders &rarr;';
		        }
	        },
            customerAddressAdditionalKeys: {
		        default: function() {
			        return {};
		        }
	        },
            multiSelectSearchDelay: {
                default: function() {
                    return 1000;
                }
            },
            noOptionsTitle: {
                default: function() {
                    return 'List is empty.';
                }
            },
            orderHistoryCustomerLinkTitle: {
                default: function() {
                    return 'Order history &rarr;';
                }
            },
            orderHistoryCustomerSummaryNoTransactionsTitle: {
                default: function() {
                    return 'Orders';
                }
            },
            disableCustomerSearch: {
                default: function() {
                    return false;
                }
            },
            noResultLabel: {
                default: function() {
                    return 'Oops! No elements found. Consider changing the search query.';
                },
            },
            customerEmptyMessage: {
                default: function() {
                    return 'Please, select/create customer';
                },
            },
        },
        data: function () {
            return {
                isLoading: false,
                customerList: [],
		lastRequestTimeoutID: null,
		checkedShipDifferentAddress: null,
		errorMessage: '',
            };
        },
        computed: {
            customer: {
                get () {
                    return this.storedCustomer;
                },
                set () {},
            },
            storedCustomer: {
                get: function () {
                    return this.$store.state.add_order.cart.customer;
                },
                set: function (newVal) {

		    this.$root.bus.$emit('update-customer-request', {
			customer: newVal,
			params: {checked_ship_different_address: this.checkedShipDifferentAddress},
			callback: (response) => {
			    this.updateCustomer(response.data.data.customer);
			    this.$root.bus.$emit('customer-updated', response.data.data.customer);

			    if (typeof response.data.data.customer_last_order_payment_method !== 'undefined') {
				this.$store.commit('add_order/updatePaymentMethod', response.data.data.customer_last_order_payment_method);
			    }
			},
		    });

                },
            },
            customerIsEmpty: function() {
            	return this.customer === "" || this.customer === null;
            },
            billingAddress: function () {
                return this.customer ? this.customer.formatted_billing_address : '';
            },
            shippingAddress: function () {
                return this.customer ? this.customer.formatted_shipping_address : '';
            },
	    profileUrl: function () {
		    return this.customer ? this.customer.profile_url : '';
	    },
	    otherOrderUrl: function () {
		    return this.customer ? this.customer.other_order_url : '';
	    },
	    showProfileUrl: function () {
		    return this.customer && +this.customer.id && this.customer.show_profile_url;
	    },
	    showOtherOrderUrl: function () {
		    return !this.isFrontend && this.otherOrderUrl;
	    },
            shipDifferent: {
                get: function () {
                    return this.customer ? this.customer['ship_different_address'] : false;
                },
                set: function (newVal) {

		    if (newVal) {
			this.checkedShipDifferentAddress = 1;
			this.$root.bus.$once('customer-updated', (customer) => {

			    this.checkedShipDifferentAddress = null;

			    if (this.openPopupShipDifferentAddress) {
				this.$nextTick(() => {
				    this.onClick('shipping');
				})
			    }
			})
		    }

		    if ( ! this.customer ) {

			var customer = {
			    ship_different_address: newVal,
			    billing_city: this.getSettingsOption('default_city'),
			    billing_country: this.getSettingsOption('default_country'),
			    billing_state: this.getSettingsOption('default_state'),
			    billing_postcode: this.getSettingsOption('default_postcode'),
			};

			if (newVal) {
			    customer = Object.assign(customer, {
				shipping_city: this.getSettingsOption('default_city'),
				shipping_country: this.getSettingsOption('default_country'),
				shipping_state: this.getSettingsOption('default_state'),
				shipping_postcode: this.getSettingsOption('default_postcode'),
			    });
			}

			this.storedCustomer = customer;

                    } else {

			var customer = JSON.parse(JSON.stringify(this.storedCustomer));

			customer['ship_different_address'] = newVal;

			if (customer['ship_different_address']) {

			    var shipping_fields = ['address_1', 'address_2', 'city', 'company', 'country', 'first_name', 'last_name', 'postcode', 'state'];
                shipping_fields = shipping_fields.concat(this.addressAdditionalKeys);

			    shipping_fields.forEach((field) => {
				if (typeof this.storedCustomer['billing_' + field] !== 'undefined') {
				    customer['shipping_' + field] = this.storedCustomer['billing_' + field];
				}
			    });
			}

			this.storedCustomer = customer;
                    }
                },
            },
            openPopupShipDifferentAddress () {
                return this.getSettingsOption('open_popup_ship_different_address');
            },
            customersSessionKey () {
                return this.getSettingsOption('cache_customers_session_key');
            },
            customerSelectOptionsLimit: function () {
                return this.getSettingsOption('number_of_customers_to_show');
            },
            hideShippingSection: function () {
                return this.getSettingsOption('hide_shipping_section');
            },
	    allowAddCustomers () {
		    return ! this.getSettingsOption('disable_creating_customers');
	    },
	    showCustomersUrl() {
		return this.showProfileUrl || this.showOtherOrderUrl;
	    },
            addressAdditionalKeys() {
                var addressAdditionalKeys = [];
                for (let $key in this.customerAddressAdditionalKeys) {
                    if (this.customerAddressAdditionalKeys.hasOwnProperty($key)) {
                        addressAdditionalKeys.push($key);
                    }
                }

                return addressAdditionalKeys;
            },
	    isFrontend() {
		return typeof window.wpo_frontend !== 'undefined';
	    },
            orderHistoryCustomerSummary: function () {
                return this.customer ? this.customer.order_history_summary : null;
            },
            showOrderHistoryCustomerSummarySettingsOption: function () {
                return this.getSettingsOption('show_order_history_customer');
            },
            showOrderHistoryCustomerSummary: function () {
                return this.showOrderHistoryCustomerSummarySettingsOption && this.orderHistoryCustomerSummary;
            },
            isValidCustomerValue: function () {
		if (!this.isValidCustomerSubscription) {
		    return false;
		}
                return true;
            },
            isValidCustomerSubscription: function () {
		return this.customer && +this.customer.id || !this.$store.state.add_order.cart.items.filter((product) => { return !!product.is_subscribed; }).length;
            },
        },
	created: function () {
            this.$root.bus.$on('update-customer', (newId) => {
                    this.getCustomerByCustomerType(newId);
            });
        },
	watch: {
	    showOrderHistoryCustomerSummarySettingsOption() {
		if (this.showOrderHistoryCustomerSummarySettingsOption && this.customer && +this.customer.id && !this.orderHistoryCustomerSummary) {
		    this.getCustomerByCustomerType(this.customer.id);
		}
	    },
	    errorMessage (newVal) {
		if (newVal) {
		    setTimeout(() => { this.errorMessage = ''; }, 5000);
		}
	    },
	},
        methods: {
        	onClick ($address_type) {
		        let data = {};
		        data.customer = this.customer;
                data.addressType = $address_type;
                data.fields = {};

                for ( let $field_name in this.requiredFieldsForPopUp) {
                    if ( this.requiredFieldsForPopUp.hasOwnProperty( $field_name ) ) {
                        let $field = this.requiredFieldsForPopUp[$field_name];
	                    if ( this.customer ) {
		                    $field['value'] = typeof this.customer[$address_type + '_' + $field_name] !== 'undefined' ? this.customer[$address_type + '_' + $field_name] : this.customer[$field_name];
	                    } else {
		                    $field['value'] = '';
                        }
                        data.fields[$field_name] = $field;
                    }
                }

                this.$root.bus.$emit('edit-customer-address', data);
            },
            createNewCustomer () {
                this.openModal('addCustomer');
            },
            clearCustomer () {
                this.storedCustomer = null;
            },
            customLabel (customer) {
                return ( customer.id && customer.id !== '0' ) ?
                            `${customer.billing_first_name} ${customer.billing_last_name} (#${customer.id} - ${customer.billing_email})`
                        :
                            this.selectCustomerPlaceholder;
            },
            onChangeCustomer (customer) {
                this.setCustomer(customer.value, customer.type);
            },
            asyncFind(query) {

		if (this.disableCustomerSearch) {
		    return;
		}

                this.lastRequestTimeoutID && clearTimeout(this.lastRequestTimeoutID);

                if (!query && query !== null) {
                    this.isLoading = false;
                    this.lastRequestTimeoutID = null;
                    this.customerList = [];
                    return;
                }

                this.isLoading = true;

                this.lastRequestTimeoutID = setTimeout(() => {
                    this.axios.get(this.url, {
                        params: {
                            action: 'woocommerce_json_search_customers',
                            wpo_find_customer: 1,
                            wpo_cache_customers_key: this.customersSessionKey,
                            security: this.search_customers_nonce,
                            term: query,
                        }
                    }).then((response) => {

                        var customers = [];

                        for (let id in response.data) {
                            if (response.data.hasOwnProperty(id)) {
                                let item = response.data[id];
                                customers.push({title: item.title, value: item.id, type: item.type});
                            }
                        }

                        this.customerList = customers;

                        this.isLoading = false;

                    });
                }, this.multiSelectSearchDelay);
            },
            getCustomerByCustomerType (id, callback) {
                this.getCustomer(id, 'customer', callback);
            },
            getCustomer (id, type, callback) {

                this.axios.get(this.url, { params: {
                    action: 'phone-orders-for-woocommerce',
                    method: 'get_customer',
                    tab: this.tabName,
                    id: id,
                    type: type,
		    is_frontend: this.isFrontend ? 1 : 0,
                }}).then( ( response ) => {

                    if (typeof callback === 'function') {
                        callback(response);
                    } else {
                        this.storedCustomer = response.data.data;
                    }

                    this.isLoading  = false;
                }, () => {
                    this.isLoading = false;
                });
            },
            setCustomer (id, type ) {

		this.axios.post(this.url, this.qs.stringify({
                    action: 'phone-orders-for-woocommerce',
                    method: 'set_customer',
                    tab: this.tabName,
                    id: id,
                    type: type,
                    cart: JSON.stringify(this.$store.state.add_order.cart),
                    is_frontend: this.isFrontend ? 1 : 0,
		})).then( ( response ) => {

		    if ( ! response.data.success ) {
			this.errorMessage = response.data.data;
			return;
		    }

		    this.updateCustomer(response.data.data.customer);

		    this.$root.bus.$emit('customer-updated', response.data.data.customer);
		    this.$root.bus.$emit('apply-recalculated-cart', response.data.data.cart);

                    this.isLoading  = false;
                }, () => {
                    this.isLoading = false;
                });
            },
            openOrderHistoryCustomer() {
		this.$root.bus.$emit('order-history-customer-open', this.customer);
            },
	    checkCart: function () {
		return this.isValidCustomerValue;
	    },
	    getCheckCartMessage: function () {
		if (!this.isValidCustomerSubscription) {
		    return this.customerEmptyMessage;
		}
	    },
        },
        components: {
            Multiselect,
            ProFeatures,
        },
    }
</script>