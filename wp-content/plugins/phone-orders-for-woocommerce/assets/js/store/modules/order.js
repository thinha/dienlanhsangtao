var Vue = require('vue');

Vue.mixin({
    computed: {
        cartEnabled () {
            return this.$store.state.add_order.cart_enabled;
        },
        cartIsChanged () {
            var cart = this.$store.state.add_order.cart;
            return cart.items.length && !cart.order_id && !cart.edit_order_id && !cart.loaded_order_id && !cart.drafted_order_id
                ||
                cart.edit_order_id && this.getObjectHash(this.$store.state.add_order.cart) !== this.$store.state.add_order.stored_cart_hash;
        },
    },
    methods: {
        getStrHash (str) {

            var hash = 0, i, chr;

            if (str.length === 0) {
                return hash;
            }

            for (i = 0; i < str.length; i++) {
                chr   = str.charCodeAt(i);
                hash  = ((hash << 5) - hash) + chr;
                hash |= 0; // Convert to 32bit integer
            }

            return hash;
        },
        getObjectHash (object) {
            return this.getStrHash(JSON.stringify(object));
        },
        addProductItemToStore (item) {
            this.addProductItemsToStore([item]);
        },
        addProductItemsToStore (items) {
            this.getSettingsOption('add_product_to_top_of_the_cart') ?
                    this.$store.commit('add_order/addCartItemsToTop', items)
                :
                    this.$store.commit('add_order/addCartItemsToBottom', items);
        },
        getDefaultCustomFieldsValues (customFields, customValues) {

            var fields = {};

            customFields.forEach(function (field) {

                var val = '';

                switch (field.type) {
                    case 'hidden':
                    case 'text':
                        if ( field.value.length ) {
                            val = field.value.join('')
                        }
                        break;
                    case 'time':
                        if ( field.value.length ) {
                            val = field.value.join('')
                        }
                        break;
                    case 'checkbox':
                        if ( field.value.length && field.selected_values.length ) {
                            val = field.selected_values
                        }
                        break;
                    case 'radio':
                        if ( field.value.length && field.selected_values.length) {
                            val = field.selected_values[0]
                        }
                        break;
                    case 'select':
                        if ( field.value.length && field.selected_values.length) {
                            val = field.selected_values[0]
                        }
                        break;
                }

                if (customValues && typeof customValues[field.name] !== 'undefined') {
                    val = customValues[field.name];
                }

                fields[field.name] = val;
            });

            return fields;
        },
        getCustomFieldsList (customFields) {

            var fieldList = [];

            if (!customFields) {
                return fieldList;
            }

            customFields.split(/((\r?\n)|(\r\n?))/)
                .filter(function (v) { return v && v.trim(); })
                .forEach(function (v) {

                    var line = v.split('|');

                    if (line.length > 1) {
                        var value = '';
                        var selected_values = [];
                        var start_with = null;
                        if (typeof line[3] !== 'undefined') {
                            value = line.slice(3).filter(function (v) {
                                return v && v.trim();
                            });

                            value = value.map(function (vNew) {
                                let exploded = vNew.split("=");
                                if (!exploded) {
                                    return false;
                                }
                                let selectValue = "";
                                let selectLabel = "";

                                if (typeof exploded[0] !== 'undefined') {
                                    selectValue = exploded[0];
                                    if (typeof exploded[1] !== 'undefined') {
                                        selectLabel = exploded[1];
                                    } else {
                                        selectLabel = selectValue;
                                    }
                                }

                                if (selectValue.startsWith("^")) {
                                    vNew = vNew.slice(1);
                                    start_with = selectValue.slice(1);
                                }

                                if (selectValue.startsWith("*")) {
                                    vNew = vNew.slice(1);
                                    selected_values.push(selectValue.slice(1));
                                }

                                return vNew;
                            });
                        }


                        fieldList.push({
                            label: line[0],
                            name: line[1],
                            type: typeof line[2] !== 'undefined' ? line[2] : 'text',
                            required: line[0].startsWith('*'),
                            value: value,
                            selected_values: selected_values,
			    start_with: start_with,
                        });
                    }
                });

            return fieldList;
        },
        getCustomFieldsListSettingsRows (customFieldsListSettingsRows) {

            var settingsRows = [];

            if (!customFieldsListSettingsRows) {
                return settingsRows;
            }

            return customFieldsListSettingsRows.split(/((\r?\n)|(\r\n?))/)
                .filter(function (v) { return v && v.trim(); })
                .map(function (v) { return parseInt(v); });
        },
        getDefaultListItemCustomMetaFieldsList (customFields) {

            var fieldList = [];

            if (!customFields) {
                return fieldList;
            }

            customFields.split(/((\r?\n)|(\r\n?))/)
                .filter(function (v) { return v && v.trim(); })
                .forEach(function (v) {

                    var line = v.split('|');

                    if (line.length > 1 && line[0] && line[0].trim()) {
                        fieldList.push(line[0]);
                    }
                });

            return fieldList;
        },
        getItemCustomMetaFieldsList (itemCustomMetaFields) {

            var fieldList = [];

            if (!itemCustomMetaFields) {
                return fieldList;
            }

            itemCustomMetaFields.split(/((\r?\n)|(\r\n?))/)
                .filter(function (v) { return v && v.trim(); })
                .forEach(function (v) {
                    fieldList.push(v.trim());
                });

            return fieldList;
        },
        setDefaultCustomFieldsValues () {
            this.$store.commit(
                'add_order/setCartCustomFields',
                Object.assign({}, this.getDefaultCustomFieldsValues(
                    this.getCustomFieldsList(this.getSettingsOption('order_custom_fields'))
                ))
            );
        },
        setDefaultCustomFieldsValuesEx () {
            this.$store.commit(
                'add_order/setCartCustomFields',
                Object.assign({}, this.getDefaultCustomFieldsValues(
                    this.getCustomFieldsList(this.getSettingsOption('order_custom_fields')),
                    this.$store.state.add_order.cart.custom_fields_values
                ))
            );
        },
        updateStoredCartHash () {
            this.$store.commit(
                'add_order/setStoredCartHash',
                this.getObjectHash(this.$store.state.add_order.cart)
            );
        },
        updateCustomer(newCustomer) {
            this.$store.commit(
                'add_order/updateCustomer',
                newCustomer
            );

            if (typeof this.$store.state.add_order.cart.custom_fields !== 'undefined' && typeof newCustomer.custom_fields !== 'undefined' && this.getSettingsOption('replace_order_with_customer_custom_fields') ) {
                this.$store.commit(
                    'add_order/setCartCustomFields',
                    Object.assign({}, this.$store.state.add_order.cart.custom_fields, newCustomer.custom_fields)
                );
            }
        },
    }
});

var empty_cart = {
    items: [],
    coupons: [],
    discount: null,
    shipping: {
	total_html: "",
	packages: [],
    },
    fee: [],
    customer_note: '',
    private_note: '',
    customer: '',
    custom_fields: {},
    order_id: null,
    order_number: null,
    edit_order_id: null,
    edit_order_number: null,
    loaded_order_id: null,
    loaded_order_number: null,
    drafted_order_id: null,
    order_is_completed: false,
    custom_fields_values: [],
    payment_method: '',
    allow_refund_order: false,
    wc_price_settings: Object.assign({}, PhoneOrdersData.wc_price_settings),
    wc_tax_settings: Object.assign({}, PhoneOrdersData.wc_tax_settings),
    wc_measurements_settings: Object.assign({}, PhoneOrdersData.wc_measurements_settings),
};

const state = {
    cart: JSON.parse(JSON.stringify(empty_cart)),
    default_cart: JSON.parse(JSON.stringify(empty_cart)),
    loaded_order: null,
    cart_enabled: true,
    additional_params_product_search: {},
    deleted_items: [],
    calculated_deleted_items: [],
    out_of_stock_items: [],
    log_row_id: null,
    buttons_message: '',
    is_loading: false,
    is_loading_without_background: false,
    stored_cart_hash: '',
    cart_params_changed_by_backend: 0,
	force_cart_set: 0,
    unconditional_redirect: false,
    order_date_timestamp: 0,
    order_status: '',
    payment_gateways: [],
};

const mutations = {
	enableUnconditionalRedirect (state, enable ) {
		state.unconditional_redirect = enable;
	},
    updateCustomer (state, newCustomer) {
        var copiedCart      = Object.assign({}, state.cart);
        copiedCart.customer = newCustomer;
        // copiedCart.custom_fields = Object.assign({}, copiedCart.custom_fields, newCustomer.custom_fields);
        state.cart          = copiedCart;
    },
	setCustomerTaxExempt (state, newTaxExempt) {
		var copiedCart      = Object.assign({}, state.cart);
		var customer =  Object.assign({}, copiedCart.customer || {});
		customer.is_vat_exempt = newTaxExempt;
		copiedCart.customer = customer;

		state.cart          = copiedCart;
	},
	updateOrderDateTimestamp (state, newTimestamp) {
		this._vm.$set(state, 'order_date_timestamp', newTimestamp);
    },
    updateOrderStatus (state, newStatus) {
        this._vm.$set(state, 'order_status', newStatus);
    },
    addCartItemsToBottom (state, items) {
        var copiedCart      = Object.assign({}, state.cart);
        copiedCart.items    = [...copiedCart.items, ...items];
        state.cart          = copiedCart;
    },
    addCartItemsToTop (state, items) {
        var copiedCart      = Object.assign({}, state.cart);
        copiedCart.items    = [...items, ...copiedCart.items];
        state.cart          = copiedCart;
    },
    addFeeItem (state, item) {

        var found       = false;
        var copiedCart  = Object.assign({}, state.cart);
        var feeList     = [...copiedCart.fee];

        feeList.forEach(function (fee, index) {
           if (item.name === fee.name) {
               feeList[index] = item;
               found = true;
           }
        });

        if (!found) {
            feeList.push(item);
        }

        copiedCart.fee = feeList;
        state.cart     = copiedCart;
    },
    removeFeeItem (state, itemIndex) {
        var copiedCart      = Object.assign({}, state.cart);
        copiedCart.fee      = copiedCart.fee.filter(function (item, index) {
            return index !== itemIndex;
        });
        state.cart = copiedCart;
    },
    addCouponItem (state, item) {
        var copiedCart      = Object.assign({}, state.cart);
        copiedCart.coupons  = [...copiedCart.coupons, item];
        state.cart          = copiedCart;
    },
    removeCouponItem (state, itemIndex) {
        var copiedCart      = Object.assign({}, state.cart);
        copiedCart.coupons  = copiedCart.coupons.filter(function (item, index) {
            return index !== itemIndex;
        });
        state.cart = copiedCart;
    },
    setDiscount (state, discount) {
        var copiedCart      = Object.assign({}, state.cart);
        copiedCart.discount = discount;
        state.cart          = copiedCart;
    },
    setShipping (state, shipping) {
        var copiedCart      = Object.assign({}, state.cart);
        copiedCart.shipping = shipping;
        state.cart          = copiedCart;
    },
    setPackages(state, packages) {
        var copiedCart = Object.assign({}, state.cart);
        var copiedShipping = Object.assign({}, copiedCart.shipping);
        copiedShipping.packages = packages;
        copiedCart.shipping = copiedShipping;
        state.cart = copiedCart;
    },
    setCartCoupons (state, coupons) {
        var copiedCart      = Object.assign({}, state.cart);
        copiedCart.coupons  = coupons;
        state.cart          = copiedCart;
    },
    setCartFees (state, fees) {
        var copiedCart   = Object.assign({}, state.cart);
        copiedCart.fee   = fees;
        state.cart       = copiedCart;
    },
    setAdditionalParamsProductSearch (state, params) {
        state.additional_params_product_search = Object.assign({}, params);
    },
    updateCartItem (state, data) {

        var copiedCart   = Object.assign({}, state.cart);
        var items        = [...copiedCart.items];

        items.forEach(function (item, index) {
            if (item.key === data.key ) {
                items[index] = data.item;
            }
        });

        copiedCart.items = items;
        state.cart       = copiedCart;
    },
    removeCartItem (state, key) {
        var copiedCart      = Object.assign({}, state.cart);
        copiedCart.items    = copiedCart.items.filter(function (item, index) {
            return item.key !== key;
        });

        state.cart = copiedCart;
    },
    setCustomerNote (state, note) {
        var copiedCart           = Object.assign({}, state.cart);
        copiedCart.customer_note = note;
        state.cart               = copiedCart;
    },
    setPrivateNote (state, note) {
        var copiedCart           = Object.assign({}, state.cart);
        copiedCart.private_note  = note;
        state.cart               = copiedCart;
    },
    clearCart (state) {
        state.cart = Object.assign({}, state.cart, {
            items: [],
            coupons: [],
            discount: null,
            shipping: {
                total_html: "",
                packages: [],
            },
            fee: [],
            customer_note: '',
            private_note: '',
            custom_fields: {},
            order_id: null,
            order_number: null,
            edit_order_id: null,
            edit_order_number: null,
            loaded_order_id: null,
            loaded_order_number: null,
            drafted_order_id: null,
            order_is_completed: false,
            custom_fields_values: [],
	    allow_refund_order: false,
        });
    },
    clearCartAll (state) {
        state = Object.assign(state, {
            cart: {
                items: [],
                coupons: [],
                discount: null,
                shipping: {
                    total_html: "",
                    packages: [],
                },
                fee: [],
                customer_note: '',
                private_note: '',
                customer: '',
                custom_fields: {},
                order_id: null,
                order_number: null,
                loaded_order_id: null,
                loaded_order_number: null,
                edit_order_id: null,
                edit_order_number: null,
                drafted_order_id: null,
                order_is_completed: false,
                custom_fields_values: [],
                payment_method: '',
		allow_refund_order: false,
            },
            loaded_order: null,
            cart_enabled: true,
            additional_params_product_search: {},
            deleted_items: [],
            calculated_deleted_items: [],
            out_of_stock_items: [],
            log_row_id: null,
            buttons_message: '',
            is_loading: false,
            is_loading_without_background: false,
            stored_cart_hash: '',
            cart_params_changed_by_backend: 0,
	        force_cart_set: 0,
	        order_date_timestamp: 0,
            order_status: '',
            payment_gateways: [],
        });
    },
    setStateToDefault (state, new_state) {

	var cart = {
	    items: [],
	    coupons: [],
	    discount: null,
        shipping: {
            total_html: "",
            packages: [],
        },
	    fee: [],
	    customer_note: '',
	    private_note: '',
	    customer: '',
	    custom_fields: {},
	    order_id: null,
	    order_number: null,
	    loaded_order_id: null,
	    loaded_order_number: null,
	    edit_order_id: null,
	    edit_order_number: null,
	    drafted_order_id: null,
	    order_is_completed: false,
	    custom_fields_values: [],
	    payment_method: '',
	    allow_refund_order: false,
	};

	if(!new_state.cart) {
	    new_state.cart = {};
	}

	new_state.cart = Object.assign({}, cart, new_state.cart);

        state = Object.assign(state, {
            cart: cart,
            loaded_order: null,
            cart_enabled: true,
            additional_params_product_search: {},
            deleted_items: [],
            calculated_deleted_items: [],
            out_of_stock_items: [],
            log_row_id: null,
            buttons_message: '',
            is_loading: false,
            is_loading_without_background: false,
            stored_cart_hash: '',
            cart_params_changed_by_backend: 0,
	        force_cart_set: 0,
            order_date_timestamp: 0,
            order_status: '',
            payment_gateways: [],
        }, new_state);
    },
    setCartEnabled (state, enabled) {
        state.cart_enabled = enabled;
    },
    setCart (state, cart) {
        state.cart = Object.assign({}, state.cart, cart);
    },
    setState (state, state_object) {
        state = Object.assign(state, state_object);
    },
    setLoadedOrder (state, loaded_order) {
        state.loaded_order = loaded_order;
    },
    setLogRowID (state, log_row_id) {
        state.log_row_id = log_row_id;
    },
    setLogRowID (state, log_row_id) {
        state.log_row_id = log_row_id;
    },
	purgeCartOrder( state ) {
		var copiedCart = Object.assign( {}, state.cart );
		delete copiedCart.order_is_completed;
		delete copiedCart.order_id;
		delete copiedCart.order_number;
		delete copiedCart.order_payment_url;
		delete copiedCart.loaded_order_id;
		delete copiedCart.loaded_order_number;
		delete copiedCart.loaded_order;
		delete copiedCart.edit_order_id;
		delete copiedCart.edit_order_number;
		delete copiedCart.drafted_order_id;
		delete copiedCart.allow_refund_order;
		state.cart = copiedCart;
	},
    setCartOrderIsCompleted (state, is_completed) {
        var copiedCart                = Object.assign({}, state.cart);
        copiedCart.order_is_completed = is_completed;
        state.cart                    = copiedCart;
    },
    setCartOrderID (state, order_id) {
        var copiedCart      = Object.assign({}, state.cart);
        copiedCart.order_id = order_id;
        state.cart          = copiedCart;
    },
    setCartOrderNumber (state, order_number) {
        var copiedCart		= Object.assign({}, state.cart);
        copiedCart.order_number = order_number;
        state.cart		= copiedCart;
    },
    setCartEditOrderID (state, order_id) {
        var copiedCart              = Object.assign({}, state.cart);
        copiedCart.edit_order_id    = order_id;
        state.cart                  = copiedCart;
    },
    setCartEditOrderNumber (state, order_number) {
        var copiedCart			= Object.assign({}, state.cart);
        copiedCart.edit_order_number    = order_number;
        state.cart			= copiedCart;
    },
    setCartDraftedOrderID (state, order_id) {
        var copiedCart              = Object.assign({}, state.cart);
        copiedCart.drafted_order_id = order_id;
        state.cart                  = copiedCart;
    },
    setCartOrderPaymentUrl (state, url) {
        var copiedCart                  = Object.assign({}, state.cart);
        copiedCart.order_payment_url    = url;
        state.cart                      = copiedCart;
    },
    setDefaultCartCustomFields (state, fields) {
        var copiedCart           = Object.assign({}, state.cart);
        copiedCart.custom_fields = Object.assign({}, fields, state.cart.custom_fields);
        state.cart               = copiedCart;
    },
    setCartCustomFields (state, fields) {
        var copiedCart           = Object.assign({}, state.cart);
        copiedCart.custom_fields = Object.assign({}, fields);
        state.cart               = copiedCart;
    },
	setDefaultCustomerCustomFields (state, fields) {
		var copiedCart      = Object.assign({}, state.cart);
		var customer =  Object.assign({}, copiedCart.customer || {});
		customer.custom_fields =  Object.assign({}, fields, state.cart.customer.custom_fields);
		copiedCart.customer = customer;

		state.cart          = copiedCart;
	},
	setCustomerCustomFields (state, fields) {
		var copiedCart      = Object.assign({}, state.cart);
		var customer =  Object.assign({}, copiedCart.customer || {});
		customer.custom_fields =  Object.assign({}, fields);
		copiedCart.customer = customer;

		state.cart          = copiedCart;
	},
    setCartItems (state, items) {
        var copiedCart   = Object.assign({}, state.cart);
        copiedCart.items = [...items];
        state.cart       = copiedCart;
    },
    setButtonsMessage (state, message) {
        this._vm.$set(state, 'buttons_message', message);
    },
    setIsLoading (state, is_loading) {
        this._vm.$set(state, 'is_loading', is_loading);
    },
    setIsLoadingWithoutBackground (state, is_loading) {
        this._vm.$set(state, 'is_loading_without_background', is_loading);
    },
    setDeletedItems (state, items) {
        this._vm.$set(state, 'deleted_items', items);
    },
    setCalculatedDeletedItems (state, items) {
        this._vm.$set(state, 'calculated_deleted_items', items);
    },
    setOutOfStockItems (state, items) {
        this._vm.$set(state, 'out_of_stock_items', items);
    },
    setStoredCartHash (state, hash) {
        state.stored_cart_hash = hash;
    },
    setCartParamsChangedByBackend (state, value) {
        state.cart_params_changed_by_backend = value;
    },
    setForceCartSet (state, value) {
        state.force_cart_set = value;
    },
    updatePaymentMethod (state, method) {
        var copiedCart            = Object.assign({}, state.cart);
        copiedCart.payment_method = method;
        state.cart                = copiedCart;
    },
    updateAvailablePaymentGateways (state, methods_list) {
        this._vm.$set(state, 'payment_gateways', methods_list);
    },
    setCartAllowRefundOrder (state, allow_refund_order) {
        var copiedCart		      = Object.assign({}, state.cart);
        copiedCart.allow_refund_order = allow_refund_order;
        state.cart		      = copiedCart;
    },
    setPriceSettings (state, newPriceSettings) {
        var copiedCart		      = Object.assign({}, state.cart);
        copiedCart.wc_price_settings = Object.assign({}, newPriceSettings);
        state.cart		      = copiedCart;
    },
    setTaxSettings (state, newTaxSettings) {
        var copiedCart		      = Object.assign({}, state.cart);
        copiedCart.wc_tax_settings    = Object.assign({}, newTaxSettings);
        state.cart		      = copiedCart;
    },
    setMeasurementsSettings (state, newMeasurementsSettings) {
        var copiedCart			    = Object.assign({}, state.cart);
        copiedCart.wc_measurements_settings = Object.assign({}, newMeasurementsSettings);
        state.cart			    = copiedCart;
    },
};

module.exports = {
    namespaced: true,
    state,
    mutations
}