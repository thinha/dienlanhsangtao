<template>
  <div class="postbox disable-on-order">
        <span class="handlediv button-link custom-button-link">
            <a href="" class="clear-customer" v-show="customer"
               @click.prevent.stop="cartEnabled ? clearCustomer() : null" :class="{disabled: !cartEnabled}">&times;</a>
        </span>
    <h2>
	    <span :class="{'invalid-field': !isValidCustomerValue}">
		<span v-if="allowAddCustomers">{{ title }}</span>
		<span v-else>{{ titleOnlyFind }}</span>
	    </span>
    </h2>
    <div class="inside">
      <div id="search-customer-box">
        <div style="display: flex; justify-content:space-between;">
          <a v-if="allowAddCustomers && cartEnabled" href="#" @click.prevent.stop v-b-modal="'addCustomer'"
             :class="{disabled: !cartEnabled}">
            {{ createNewCustomerLabel }}
          </a>
          <a href="#" @click.prevent.stop v-else-if="allowAddCustomers" :class="{disabled: !cartEnabled}">
            {{ createNewCustomerLabel }}
          </a>
          <a v-if="allowToBrowseVisitors" href="#" @click.prevent.stop v-b-modal="'loadSessions'">{{browseVisitorsLabel}}</a>
        </div>

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
          @select="onChangeCustomer"
          :disabled="!cartEnabled"
          :options-limit="+customerSelectOptionsLimit"
          :show-labels="false"
          v-store-search-multiselect
          :class="{'empty-list': !customerList.length}"
        >
          <template v-slot:noResult>
            <span>{{ noResultLabel }}</span>
          </template>
          <template v-slot:option="props">
                        <span>
                            <span v-html="props.option.title"></span>
                        </span>
          </template>
          <template v-slot:noOptions>
                        <span>
                            <span v-html="noOptionsTitle"></span>
                        </span>
          </template>
        </multiselect>
      </div>
      <div class="orders-customer-links-block">
        <div v-show="showCustomersUrl" id="customer_urls">
          <a v-show="showProfileUrl" :href="profileUrl" target="_blank">{{ profileUrlTitle }}</a><br/>
          <a v-show="showOtherOrderUrl" :href="otherOrderUrl" target="_blank">{{ otherOrderUrlTitle }}</a>
        </div>
        <br/>
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
          <div class="billing-details" data-edit-address="billing"
               @click.prevent.stop="cartEnabled ? onClick('billing') : null"
               :class="{'invalid-field': !isValidCustomerValue && !isFilledRequiredFieldsBilling}">
            <h4>
              {{ billingDetailsLabel }}
              <a href="#" class="edit_address" @click.prevent.stop="cartEnabled ? onClick('billing') : null">Edit</a>
            </h4>
            <p>
              <span v-if="billingAddress" v-html="billingAddress" @click="onClickAddress"></span>
              <span v-else>
                                {{ shippingAddress ? billingAddressAsShippingMessage : emptyBillingAddressMessage }}
                            </span>
            </p>
          </div>
          <span v-show="! customerIsEmpty">
                        <slot name="tax-exempt"></slot>
                        <p v-show="!hideShippingSection">
                            <label>
                                <input type="checkbox" v-model="shipDifferent" v-bind:disabled="!cartEnabled"
                                       name="ship-different">
                                {{ shipDifferentLabel }}
                            </label>
                        </p>
                        <div v-show="!hideShippingSection && shipDifferent"
                             @click.prevent.stop="cartEnabled ? onClick('shipping') : null"
                             :class="{'invalid-field': !isValidCustomerValue && !isFilledRequiredFieldsShipping}">
                            <h4>
                                {{ shipDetailsLabel }}
                                <a href="#" class="edit_address"
                                   @click.prevent.stop="cartEnabled ? onClick('shipping') : null">Edit</a>
                            </h4>
                            <p>
                                <span v-if="shippingAddress" v-html="shippingAddress" @click="onClickAddress"></span>
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

#search-customer-box .empty-list .multiselect__select {
  display: none;
}
</style>

<script>

import Multiselect from 'vue-multiselect';
import ProFeatures from './pro_features.vue';

export default {
  props: {
    title: {
      default: function () {
        return 'Find or create a customer';
      }
    },
    titleOnlyFind: {
      default: function () {
        return 'Find a customer';
      }
    },
    createNewCustomerLabel: {
      default: function () {
        return 'New customer';
      }
    },
    browseVisitorsLabel: {
      default: function () {
        return 'Browse visitors';
      }
    },
    billingDetailsLabel: {
      default: function () {
        return 'Billing Details';
      }
    },
    shipDifferentLabel: {
      default: function () {
        return 'Ship to a different address?';
      }
    },
    shipDetailsLabel: {
      default: function () {
        return 'Shipping Details';
      }
    },
    emptyBillingAddressMessage: {
      default: function () {
        return 'No billing address was provided.';
      }
    },
    emptyShippingAddressMessage: {
      default: function () {
        return 'No shipping address was provided.';
      }
    },
    billingAddressAsShippingMessage: {
      default: function () {
        return 'Same as shipping address.';
      }
    },
    tabName: {
      default: function () {
        return 'add-order';
      }
    },
    proFeaturesSettings: {
      default: function () {
        return {};
      }
    },
    isProVersion: {
      default: function () {
        return false;
      }
    },
    requiredFieldsForPopUp: {
      default: function () {
        return {};
      }
    },
    selectCustomerPlaceholder: {
      default: function () {
        return 'Guest';
      }
    },
    profileUrlTitle: {
      default: function () {
        return 'Profile &rarr;';
      }
    },
    otherOrderUrlTitle: {
      default: function () {
        return 'View other orders &rarr;';
      }
    },
    customerAddressAdditionalKeys: {
      default: function () {
        return {};
      }
    },
    multiSelectSearchDelay: {
      default: function () {
        return 1000;
      }
    },
    noOptionsTitle: {
      default: function () {
        return 'List is empty.';
      }
    },
    orderHistoryCustomerLinkTitle: {
      default: function () {
        return 'Order history &rarr;';
      }
    },
    orderHistoryCustomerSummaryNoTransactionsTitle: {
      default: function () {
        return 'Orders';
      }
    },
    disableCustomerSearch: {
      default: function () {
        return false;
      }
    },
    noResultLabel: {
      default: function () {
        return 'Oops! No elements found. Consider changing the search query.';
      },
    },
    customerEmptyMessage: {
      default: function () {
        return 'Please, select/create customer';
      },
    },
    customerEmptyRequiredFields: {
      default: function () {
        return 'Please, fill empty required billing/shipping fields for customer';
      },
    },
  },
  mounted() {
    this.addCheckCartValidation({
      check_cart: this.checkCart,
      check_cart_message: this.getCheckCartMessage,
    })

    window.wpoCustomer = {
      setCustomer: this.setCustomer,
    }
  },
  data: function () {
    return {
      isLoading: false,
      customerList: [],
      quickCustomerList: [],
      lastRequestTimeoutID: null,
      checkedShipDifferentAddress: null,
      errorMessage: '',
      where: '',
      quickSearchCustomers: this.getSettingsOption('quick_search_customers'),
      searchInOrders: this.getSettingsOption('search_customer_in_orders'),
      allowToBrowseVisitors : this.getSettingsOption('allow_to_browse_visitors'),
    };
  },
  computed: {
    customer: {
      get() {
        return this.storedCustomer;
      },
      set() {
      },
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
    customerIsEmpty: function () {
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
      return (!this.isFrontend || this.getSettingsOption('show_link_view_other_orders')) && this.otherOrderUrl;
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

        if (!this.customer) {

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
    openPopupShipDifferentAddress() {
      return this.getSettingsOption('open_popup_ship_different_address');
    },
    customersSessionKey() {
      return this.getSettingsOption('cache_customers_session_key');
    },
    customerSelectOptionsLimit: function () {
      return this.getSettingsOption('number_of_customers_to_show');
    },
    hideShippingSection: function () {
      return this.getSettingsOption('hide_shipping_section');
    },
    allowAddCustomers() {
      return !this.getSettingsOption('disable_creating_customers');
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
      return this.where === '' || this.getIsValidCustomerValue(this.where);
    },
    isValidCustomerSubscription: function () {
      return this.customer && +this.customer.id || !this.$store.state.add_order.cart.items.filter((product) => {
        return !!product.is_subscribed;
      }).length;
    },
    isFilledRequiredFields: function () {
      var is_valid = this.isFilledRequiredFieldsBilling;
      if (this.shipDifferent) {
        is_valid = is_valid && this.isFilledRequiredFieldsShipping;
      }
      return is_valid;
    },
    isFilledRequiredFieldsBilling: function () {
      return this.checkFilledRequiredFields('billing');
    },
    isFilledRequiredFieldsShipping: function () {
      return this.checkFilledRequiredFields('shipping');
    },
    hideFieldsList() {
      return this.getSettingsOption('customer_hide_fields', []);
    },
    requiredFieldsList() {
      return this.getSettingsOption('customer_required_fields', []);
    },
  },
  created: function () {
    this.$root.bus.$on('update-customer', (newId) => {
      this.getCustomerByCustomerType(newId);
    });

    if(this.quickSearchCustomers) {
      this.getAllCustomers();
    }
  },
  watch: {
    showOrderHistoryCustomerSummarySettingsOption() {
      if (this.showOrderHistoryCustomerSummarySettingsOption && this.customer && +this.customer.id && !this.orderHistoryCustomerSummary) {
        this.getCustomerByCustomerType(this.customer.id);
      }
    },
    errorMessage(newVal) {
      if (newVal) {
        setTimeout(() => {
          this.errorMessage = '';
        }, 5000);
      }
    },
    isValidCustomerValue(newVal) {
      if (newVal) {
        this.where = '';
      }
    },
  },
  methods: {
    onClick($address_type) {
      let data = {};
      data.customer = this.customer;
      data.addressType = $address_type;
      data.fields = {};

      for (let $field_name in this.requiredFieldsForPopUp) {
        if (this.requiredFieldsForPopUp.hasOwnProperty($field_name)) {
          let $field = this.requiredFieldsForPopUp[$field_name];
          if (this.customer) {
            $field['value'] = typeof this.customer[$address_type + '_' + $field_name] !== 'undefined' ? this.customer[$address_type + '_' + $field_name] : this.customer[$field_name];
          } else {
            $field['value'] = '';
          }
          data.fields[$field_name] = $field;
        }
      }

      this.$root.bus.$emit('edit-customer-address', data);
    },
    createNewCustomer() {
      this.openModal('addCustomer');
    },
    loadLastSessions() {
      this.openModal('loadSessions');
    },
    clearCustomer() {
      this.storedCustomer = null;
    },
    customLabel(customer) {
      return (customer.id && customer.id !== '0') ?
        `${customer.billing_first_name} ${customer.billing_last_name} (#${customer.id} - ${customer.billing_email})`
        :
        this.selectCustomerPlaceholder;
    },
    onChangeCustomer(customer) {
      this.setCustomer(customer.value, customer.type);
    },
    getAllCustomers() {
      this.axios.get(this.url, {
        params: {
          action: 'phone-orders-for-woocommerce',
          method: 'get_all_customers',
          tab: this.tabName,
          is_frontend: this.isFrontend ? 1 : 0,
          nonce: this.nonce,
        }
      }).then((response) => {

        if (typeof callback === 'function') {
          callback(response);
        } else {
          this.quickCustomerList = response.data.data;
        }

        this.isLoading = false;
      }, () => {
        this.isLoading = false;
      });
    },
    filterQuickCustomerList(query) {
      var searchWords = query.toLowerCase().trim().split(/\s+/);

      var matchesCustomers = this.quickCustomerList.filter(customer => {
        var searchableFields = [
          customer.name.toLowerCase(),
          customer.email.toLowerCase(),
          customer.billing_address?.toLowerCase() || '',
          customer.shipping_address?.toLowerCase() || '',
          String(customer.id)
        ];

        return searchWords.every(word =>
          searchableFields.some(field => field.includes(word))
        );
      });

      var idMatches = [];
      var otherMatches = [];

      matchesCustomers.forEach(customer => {
        var idMatch = searchWords.some(word => String(customer.id).includes(word));
        if (idMatch) {
          idMatches.push(customer);
        } else {
          otherMatches.push(customer);
        }
      });

      var sortedMatches = [...idMatches, ...otherMatches];

      this.customerList = sortedMatches.map(customer => ({
        title: `${customer.name} (#${customer.id} &ndash; ${customer.email})`,
        value: customer.id,
        type: customer.type
      }));
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

      if (this.quickSearchCustomers) {
        this.filterQuickCustomerList(query);
      }

      if (this.searchInOrders || !this.quickSearchCustomers) {
        this.lastRequestTimeoutID = setTimeout(() => {
          this.axios.get(this.url, {
            params: {
              action: 'woocommerce_json_search_customers',
              wpo_find_customer: 1,
              wpo_cache_customers_key: this.customersSessionKey,
              security: this.search_customers_nonce,
              term: query,
              nonce: this.nonce,
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
      } else {
        this.isLoading = false;
      }
    },
    getCustomerByCustomerType(id, callback) {
      this.getCustomer(id, 'customer', callback);
    },
    getCustomer(id, type, callback) {

      this.axios.get(this.url, {
        params: {
          action: 'phone-orders-for-woocommerce',
          method: 'get_customer',
          tab: this.tabName,
          id: id,
          type: type,
          is_frontend: this.isFrontend ? 1 : 0,
          nonce: this.nonce,
        }
      }).then((response) => {

        if (typeof callback === 'function') {
          callback(response);
        } else {
          this.storedCustomer = response.data.data;
        }

        this.isLoading = false;
      }, () => {
        this.isLoading = false;
      });
    },
    setCustomer(id, type) {
      this.$store.commit('add_order/setIsLoading', true);

      this.axios.post(this.url, this.qs.stringify({
        action: 'phone-orders-for-woocommerce',
        method: 'set_customer',
        tab: this.tabName,
        id: id,
        type: type,
        cart: JSON.stringify(this.clearCartParam(this.$store.state.add_order.cart)),
        is_frontend: this.isFrontend ? 1 : 0,
        nonce: this.nonce,
      })).then((response) => {

        if (!response.data.success) {
          this.errorMessage = response.data.data;
          return;
        }

        this.updateCustomer(response.data.data.customer);

        this.$root.bus.$emit('customer-updated', response.data.data.customer);
        this.$root.bus.$emit('apply-recalculated-cart', response.data.data.cart);

        this.$store.commit('add_order/updateOrderCurrency', {
          code: response.data.data.cart.wc_price_settings.currency,
          symbol: response.data.data.cart.wc_price_settings.currency_symbol
        });

        this.isLoading = false;
        this.$store.commit('add_order/setIsLoading', false);
      }, () => {
        this.isLoading = false;
        this.$store.commit('add_order/setIsLoading', false);
      });
    },
    openOrderHistoryCustomer() {
      this.$root.bus.$emit('order-history-customer-open', this.customer);
    },
    checkCart: function (where, options = {}) {
      this.where = where;
      return this.getIsValidCustomerValue(where);
    },
    getCheckCartMessage: function () {
      if (!this.isValidCustomerSubscription) {
        return this.customerEmptyMessage;
      }
      if (!this.isFilledRequiredFields) {
        return this.customerEmptyRequiredFields;
      }
    },
    getIsValidCustomerValue(where) {
      if (!this.isValidCustomerSubscription) {
        return false;
      }
      if (!this.isFilledRequiredFields && ['cart', 'checkout', 'checkout_link'].indexOf(where) === -1) {
        return false;
      }
      return true;
    },
    checkFilledRequiredFields: function (addressType) {

      var visibilityFields = {
        email: addressType === 'billing',
        phone: addressType === 'billing' || this.getSettingsOption('use_shipping_phone', false),
        vat_number: addressType === 'billing' && this.getSettingsOption('support_field_vat', false),
        role: addressType === 'billing' && this.getSettingsOption('customer_show_role_field', false) && +this.customer.id,
      };

      this.hideFieldsList.forEach((hide_field) => {
        visibilityFields[hide_field] = false;
      });

      var emptyRequiredFieldsList = [];
      var fields = {};
      var valid = true;

      for (let $field_name in this.requiredFieldsForPopUp) {
        if (this.requiredFieldsForPopUp.hasOwnProperty($field_name)) {
          let $field = this.requiredFieldsForPopUp[$field_name];
          if (this.customer) {
            $field['value'] = typeof this.customer[addressType + '_' + $field_name] !== 'undefined' ? this.customer[addressType + '_' + $field_name] : this.customer[$field_name];
          } else {
            $field['value'] = '';
          }
          fields[$field_name] = $field;
        }
      }

      for (let $field in fields) {

        if (this.requiredFieldsList.indexOf($field) > -1 && (typeof visibilityFields[$field] === 'undefined' || visibilityFields[$field])) {
          var value = fields[$field].value !== null && typeof fields[$field].value === 'object' ? fields[$field].value.value : (typeof fields[$field].value === 'undefined' ? '' : fields[$field].value);
          if (value.trim() === '') {
            emptyRequiredFieldsList.push($field);
          }
        }
      }

      var customFields = this.getSettingsOption('customer_custom_fields') ? this.getCustomFieldsList(this.getSettingsOption('customer_custom_fields')) : [];

      var customFieldsAtTop = this.getSettingsOption('customer_custom_fields_at_top') ? this.getCustomFieldsList(this.getSettingsOption('customer_custom_fields_at_top')) : [];

      var customerCustomFields = this.customer ? this.customer.custom_fields : {};
      var customFieldsList = [...customFieldsAtTop, ...customFields];

      customFieldsList.forEach((field) => {
        if (field.required && (typeof customerCustomFields[field.name] === 'undefined' || customerCustomFields[field.name].trim() === '')) {
          emptyRequiredFieldsList.push(field);
        }
      });

      if (emptyRequiredFieldsList.length) {
        valid = false;
      }

      return valid;
    },
    onClickAddress(e) {
      if (e.target.tagName.toLowerCase() === 'a') {
        e.stopPropagation()
      }
    },
  },
  components: {
    Multiselect,
    ProFeatures,
  },
}
</script>
