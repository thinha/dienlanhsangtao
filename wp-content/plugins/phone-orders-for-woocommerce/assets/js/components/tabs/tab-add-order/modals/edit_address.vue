<template>
  <div>
    <b-modal id="editAddress"
             ref="modal"
             :title="editAddressLabel"
             @shown="formLoad"
             @hidden="hideModal"
             size="lg"
             :noCloseOnBackdrop="modalDontCloseOnBackdropClick"
             :static="true"
             v-model="showModal"
    >
      <b-container>

        <slot
          name="multi-addresses-select-slot"
          :customer-group-fields="groupFields"
          :customer="customer"
          :address-type="addressType"
        ></slot>

        <slot name="edit-customer-address-modal-header" modal="editAddressModal"></slot>

        <b-row v-for="(group, groupKey) in groupFields" :key="groupKey">


          <b-col cols="12">

            <google-autocomplete
              ref="google_autocomplete"
              :input-placeholder="autocompleteInputPlaceholder"
              :invalid-message="autocompleteInvalidMessage"
              :custom-google-autocomplete-js-callback="customGoogleAutocompleteJsCallback"
              @change="updateAddress"
              v-if="groupKey === 'address' && !!!initCustomAutocompleteFunction"
              v-show="!!googleAutocompleteAPIKey && !!group.rows.length"
              style="margin-top: 1rem"
            ></google-autocomplete>

            <custom-autocomplete
              ref="custom_autocomplete"
              :input-placeholder="initCustomAutocompletePlaceholder"
              :init-autocomplete-function="initCustomAutocompleteFunction"
              @change="updateAddress"
              v-if="groupKey === 'address' && !!initCustomAutocompleteFunction"
              v-show="!!group.fields.length"
              style="margin-top: 1rem"
            ></custom-autocomplete>
            <b-row v-for="(row, index) in group.rows" :key="index">

              <b-col v-for="(loopField, numIndex) in row" :md="countCols" cols="12" :key="loopField.key">

				<span>

                                    <strong><label :for="loopField.key" class="mr-sm-2"
                                                   :class="[ emptyRequiredFieldsList.indexOf(loopField.key) > -1 ? 'text-danger' : '' ]">{{
                                        requiredFieldsList.indexOf(loopField.key) > -1 ? loopField.label + ' *' : loopField.label
                                      }}</label></strong>

				    <div v-if="loopField.key === 'country'">
					<multiselect
            :ref="loopField.key"
            :allow-empty="false"
            :hide-selected="true"
            :searchable="true"
            label="title"
            :id="loopField.key"
            v-model="loopField.value"
            :options="defaultCountryList"
            track-by="value"
            @update:model-value="onChangeDefaultCountry(loopField.key)"
            :show-labels="false"
            :placeholder="selectPlaceholder"
          >
                        <template v-slot:singleLabel="props">
                            <span>
                                <span v-html="props.option.title"></span>
                            </span>
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

				    <div v-else-if="loopField.key === 'state'">
					<multiselect
            :ref="loopField.key"
            v-if="statesList.length"
            :allow-empty="false"
            :hide-selected="true"
            :searchable="true"
            label="title"
            :id="loopField.key"
            v-model="loopField.value"
            :options="statesList"
            track-by="value"
            @update:model-value="onEnter(loopField.key)"
            :show-labels="false"
            :placeholder="selectPlaceholder"
          >
					    <template v-slot:singleLabel="props">
                            <span>
                                <span v-html="props.option.title"></span>
                            </span>
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
					<b-form-input
            v-else
            :ref="loopField.key"
            :id="loopField.key"
            type="text"
            autocomplete="off"
            class="mb-2 mr-sm-2 mb-sm-0"
            v-model="loopField.value"
            @keydown.enter="onEnter(loopField.key)"
          >
					</b-form-input>
				    </div>

				    <div v-else-if="loopField.key === 'role'">
					<multiselect
            :ref="loopField.key"
            :allow-empty="false"
            v-model="loopField.value"
            :id="loopField.key"
            :options="rolesList"
            @update:model-value="onEnter(loopField.key)"
            :placeholder="selectPlaceholder"
            :show-labels="false"
            label="title"
            track-by="value"
          >
                        <template v-slot:noOptions>
                            <span>
                                <span v-html="noOptionsTitle"></span>
                            </span>
                        </template>
					</multiselect>
				    </div>

				    <div v-else-if="loopField.key === 'locale'">
					<multiselect
            :ref="loopField.key"
            :allow-empty="false"
            :id="loopField.key"
            v-model="loopField.value"
            :options="languagesList"
            @update:model-value="onEnter(loopField.key)"
            :placeholder="selectPlaceholder"
            :show-labels="false"
            label="title"
            track-by="value"
          >
					    <template v-slot:noOptions>
                            <span>
                                <span v-html="noOptionsTitle"></span>
                            </span>
                        </template>
					</multiselect>
				    </div>

				    <div v-else>
					<b-form-input
            :ref="loopField.key"
            type="text"
            class="mb-2 mr-sm-2 mb-sm-0"
            autocomplete="off"
            :id="loopField.key"
            v-model="loopField.value"
            @keydown.enter="onEnter(loopField.key)"
            :readonly="readonlyFieldsList.indexOf(loopField.key) > -1"
          >
					</b-form-input>
				    </div>
				</span>
              </b-col>
            </b-row>
          </b-col>
        </b-row>
      </b-container>
      <slot name="edit-customer-address-modal-footer" modal="editAddressModal"></slot>
      <template v-slot:footer>
        <div style="width: 100%">
          <b-row>
            <b-col cols="12" md="3">
              <b-button class="wpo-copy-billing-address-btn" @click="copyFromBillingAddress"
                        v-if="this.addressType === 'shipping'">{{ copyFromBillingAddressLabel }}
              </b-button>
            </b-col>
            <b-col cols="12" md="9" style="width: 100%; display: flex; flex-direction: column">
              <b-alert :show="!!error" variant="danger" class="error-alert">{{ this.error }}</b-alert>
              <div style="margin-left: auto">
                <b-button @click="cancel()">{{ cancelLabel }}</b-button>
                <b-button @click="saveAddress()" variant="primary">{{ saveAddressLabel }}</b-button>
              </div>
              <slot
                name="multi-addresses-buttons-slot"
                :customer-group-fields="groupFields"
                :customer="customer"
                :address-type="addressType"
              ></slot>
            </b-col>
          </b-row>
        </div>
      </template>
    </b-modal>
  </div>
</template>

<style>
@media (max-width: 767px) {
  .wpo-copy-billing-address-btn {
    margin-bottom: 10px;
  }
}
</style>

<script>

import Multiselect from 'vue-multiselect';
import GoogleAutocomplete from '../google-autocomplete.vue';
import CustomAutocomplete from '../custom-autocomplete.vue';

export default {
  props: {
    cancelLabel: {
      default: function () {
        return 'Cancel';
      }
    },
    editAddressLabel: {
      default: function () {
        return 'Edit address';
      }
    },
    saveAddressLabel: {
      default: function () {
        return 'Done';
      }
    },
    tabName: {
      default: function () {
        return 'add-order';
      },
    },
    selectPlaceholder: {
      default: function () {
        return 'Select option';
      },
    },
    autocompleteInputPlaceholder: {
      default: function () {
        return null;
      },
    },
    autocompleteInvalidMessage: {
      default: function () {
        return null;
      },
    },
    noOptionsTitle: {
      default: function () {
        return 'List is empty.';
      }
    },
    personalFieldsOrder: {
      default: function () {
        return ['email', 'role', 'first_name', 'last_name'];
      }
    },
    addressFieldsOrder: {
      default: function () {
        return ['country', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'phone', 'locale'];
      }
    },
    countFieldsInRow: {
      default: function () {
        return 2;
      }
    },
    initCustomAutocompleteFunction: {
      default: function () {
        return null;
      }
    },
    initCustomAutocompletePlaceholder: {
      default: function () {
        return null;
      }
    },
    fillAllFieldsLabel: {
      default: function () {
        return 'Please fill out all required fields!';
      }
    },
    customerAddressAdditionalKeys: {
      default: function () {
        return {};
      }
    },
    customGoogleAutocompleteJsCallback: {
      default: function () {
        return '';
      }
    },
    copyFromBillingAddressLabel: {
      default: function () {
        return 'Copy from billing address';
      }
    },
    rolesList: {
      default: function () {
        return [];
      },
    },
    languagesList: {
      default: function () {
        return [];
      },
    },
    readonlyFields: {
      default: function () {
        return [];
      },
    },
  },
  created: function () {
    this.$root.bus.$on('edit-customer-address', (data) => {
      this.addressType = data.addressType;
      this.customer = data.customer;
      this.customFields = {};
      this.customFieldsAtTop = {};
      this.fieldsToShow = data.fields;
      this.showModal = true;
    });

    this.$root.bus.$on('edit-customer-address-custom-fields-updated', (fields) => {
      this.customFields = fields;
    });

    this.$root.bus.$on('edit-customer-address-custom-fields-updated-at-top', (fields) => {
      this.customFieldsAtTop = fields;
    });

    this.$root.bus.$on('update-customer-request', (data) => {
      this.updateCustomerRequest(data.customer, data.callback, data.params);
    });

    this.$root.bus.$on('edit-customer-update-address', (address) => {
      this.updateAddress(address);
    });
  },
  data: function () {
    let $react_fields = this.initForm();
    $react_fields['addressType'] = '';
    $react_fields['customer'] = {};
    $react_fields['fieldsToShow'] = {};
    $react_fields['customFields'] = {};
    $react_fields['customFieldsAtTop'] = {};
    $react_fields['error'] = '';
    $react_fields['error_code'] = '';
    $react_fields['emptyRequiredFieldsList'] = [];
    $react_fields['showModal'] = false;

    return $react_fields;
  },
  computed: {
    groupFields: function () {

      var groups = {
        personal: {
          keys: this.personalFields,
          fields: [],
          rows: [],
        },
        address: {
          keys: this.addressFields,
          fields: [],
          rows: [],
        },
      };

      for (let group in groups) {
        groups[group].keys.forEach((key) => {
          if (this.fields.hasOwnProperty(key) && this.fields[key].visibility) {
            groups[group].fields.push(Object.assign(this.fields[key], {key: key}));
          }
        })
      }

      let $elementsInRow = this.countFieldsInRow;

      for (let group in groups) {

        let $row = [];
        let $numIndex = 0;

        groups[group].fields.forEach(function (v, i) {

          if (v.key === 'email' || v.key === 'role') {

            $row.push(v);

            if (v.key === 'email' && groups[group].fields[i + 1] && groups[group].fields[i + 1].key === 'role') {
              return true;
            }

            groups[group].rows.push($row);

            $row = [];

            return true;
          }

          $row.push(v);

          if ($row.length < $elementsInRow) {
            return true;
          }

          groups[group].rows.push($row);

          $row = [];
        });

        if ($row.length) {
          groups[group].rows.push($row);
        }
      }

      return groups;
    },
    defaultCountryList() {
      return this.$root.defaultCountriesList;
    },
    defaultStatesList() {
      return this.$root.defaultStatesList;
    },
    googleAutocompleteAPIKey() {
      return this.getSettingsOption('google_map_api_key');
    },
    countCols() {
      return Math.floor(12 / this.countFieldsInRow);
    },
    addressFields() {
      var addressFields = this.addressFieldsOrder;
      return addressFields.concat(this.addressAdditionalKeys);
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
    personalFields() {
      return this.personalFieldsOrder;
    },
    doNotSubmitOnEnterLastField() {
      return this.getSettingsOption('do_not_submit_on_enter_last_field');
    },
    storedCustomer() {
      return this.$store.state.add_order.cart.customer;
    },
    customerCustomFields() {
      return this.storedCustomer ? this.storedCustomer.custom_fields : {};
    },
    hideFieldsList() {
      return this.getSettingsOption('customer_hide_fields', []);
    },
    requiredFieldsList() {
      return this.getSettingsOption('customer_required_fields', []);
    },
    customerFieldListWithoutBillingShippingPrefix() {
      return ['role', 'locale'];
    },
  },
  watch: {
    customerCustomFields(newVal, oldVal) {
      this.$root.bus.$emit('customer-custom-fields-at-top-set-custom-fields', {custom_fields: newVal});
      this.$root.bus.$emit('customer-custom-fields-set-custom-fields', {custom_fields: newVal});
    },
  },
  methods: {
    initForm($reactFields) {
      if (typeof $reactFields === 'undefined') {
        var $reactFields = {};
      }
      $reactFields['fields'] = {};
      $reactFields['visibleFields'] = [];
      $reactFields['readonlyFieldsList'] = [];

      if (typeof this.fieldsToShow === 'undefined') {
        return $reactFields;
      }

      // copy without reference
      var $fields = JSON.parse(JSON.stringify(this.fieldsToShow));

      var defaultValues = {
        city: this.getSettingsOption('default_city'),
        country: this.getSettingsOption('default_country'),
        state: this.getSettingsOption('default_state'),
        postcode: this.getSettingsOption('default_postcode'),
      };

      var visibilityFields = {
        email: this.addressType === 'billing',
        phone: this.addressType === 'billing' || this.getSettingsOption('use_shipping_phone', false),
        vat_number: this.addressType === 'billing' && this.getSettingsOption('support_field_vat', false),
        role: this.addressType === 'billing' && this.getSettingsOption('customer_show_role_field', false) && +this.storedCustomer.id,
        locale: this.addressType === 'billing' && this.getSettingsOption('customer_show_language_field', false) && +this.storedCustomer.id,
      };

      this.hideFieldsList.forEach((hide_field) => {
        visibilityFields[hide_field] = false;
      });

      var isEmptyCustomer = typeof this.storedCustomer.billing_first_name === 'undefined';

      for (let $field in $fields) {
        if ($fields.hasOwnProperty($field)) {
          $reactFields['fields'][$field] = $fields[$field];

          if (typeof $reactFields['fields'][$field]['visibility'] === 'undefined') {
            $reactFields['fields'][$field]['visibility'] = true;
          }

          if (typeof visibilityFields[$field] !== 'undefined') {
            $reactFields['fields'][$field]['visibility'] = visibilityFields[$field];
          }

          if (typeof defaultValues[$field] !== 'undefined' && isEmptyCustomer) {
            $reactFields['fields'][$field]['value'] = defaultValues[$field];
          }
        }

        if ($reactFields['fields'][$field]['visibility']) {
          $reactFields['visibleFields'].push($field);
        }
      }

      if ($fields.hasOwnProperty('country')
        && typeof this.defaultCountryList !== 'undefined'
        && typeof this.defaultStatesList !== 'undefined'
      ) {
        var country = this.getObjectByKeyValue(this.defaultCountryList, 'value',
          $fields['country'].value);

        var statesList = this.defaultStatesList[$fields['country'].value] || [];

        $reactFields['fields']['country']['value'] = country || {value: ''};

        //	if formToDefault() we need to update 'statesList'
        this.statesList = statesList;

        $reactFields['statesList'] = statesList;
      }

      if ($fields.hasOwnProperty('state')
        && typeof statesList !== 'undefined'
      ) {
        var state = null;

        if (statesList.length) {
          state = this.getObjectByKeyValue(statesList, 'value', $fields['state'].value, this.getObjectByKeyValue(statesList, 'value', ''));
        } else {
          state = $fields['state'].value;
        }

        $reactFields['fields']['state']['value'] = state;
      }

      if ($fields.hasOwnProperty('role')
        && typeof this.rolesList !== 'undefined'
      ) {
        var role = this.getObjectByKeyValue(this.rolesList, 'value',
          $fields['role'].value);

        $reactFields['fields']['role']['value'] = role;
      }

      if ($fields.hasOwnProperty('locale')
        && typeof this.languagesList !== 'undefined'
      ) {
        var locale = this.getObjectByKeyValue(this.languagesList, 'value',
          $fields['locale'].value);

        $reactFields['fields']['locale']['value'] = locale;
      }

      let readonlyFieldsList = [];

      for (let field in $reactFields['fields']) {
        if (this.readonlyFields.indexOf(field) > -1 && $reactFields['fields'][field]['value'] !== '') {
          readonlyFieldsList.push(field)
        }
      }

      $reactFields['readonlyFieldsList'] = readonlyFieldsList;

      return $reactFields;
    },
    formLoad() {

      let $reactFields = this.initForm();
      this.fields = $reactFields.fields;
      this.visibleFields = $reactFields.visibleFields;
      this.error = '';
      this.error_code = '';
      this.emptyRequiredFieldsList = [];
      this.readonlyFieldsList = $reactFields.readonlyFieldsList;

      this.$nextTick(() => {
        // add second $nextTick() because BModal::methods::focusFirst() set new focus
        this.$nextTick(() => {
          if (this.visibleFields.length) {
            this.$refs[this.visibleFields[0]][0].focus();
          }
        });
      });
    },
    hideModal() {
      if (typeof this.$refs.google_autocomplete !== 'undefined') {
        this.$refs.google_autocomplete.forEach((component) => {
          component.clear();
          component.init();
        });
      }
    },
    cancel() {
      this.showModal = false;
    },
    isCustomerValid() {
      var valid = true;

      this.emptyRequiredFieldsList = [];

      for (let $field in this.fields) {

        if (this.requiredFieldsList.indexOf($field) > -1 && this.fields[$field].visibility) {

          var value = this.fields[$field].value !== null && typeof this.fields[$field].value === 'object' ? this.fields[$field].value.value : this.fields[$field].value;
          if (value.trim() === '') {
            this.emptyRequiredFieldsList.push($field);
          }
        }
      }

      if (this.emptyRequiredFieldsList.length) {
        valid = false;
      }

      this.getEditCustomerValidation().forEach((validation) => {
        if (!!!validation()) {
          valid = false;
          return;
        }
      });

      return valid;
    },
    saveAddress() {

      if (!this.customer) {
        this.customer = {};
      }

      var customer = Object.assign({}, this.customer);

      if (!!!this.isCustomerValid()) {
        this.error = this.fillAllFieldsLabel;
        return;
      }

      for (let $field in this.fields) {
        if (this.fields.hasOwnProperty($field)) {

          var $fieldKey = this.customerFieldListWithoutBillingShippingPrefix.indexOf($field) > -1 ? $field : this.addressType + '_' + $field;

          if (this.fields[$field].value !== null && typeof this.fields[$field].value === 'object' && this.fields[$field].value.hasOwnProperty('value')) {
            customer[$fieldKey] = this.fields[$field].value.value; // for multiselect fields
          } else {
            customer[$fieldKey] = this.fields[$field].value;
          }
        }
      }

      customer['custom_fields'] = Object.assign({}, this.customFieldsAtTop, this.customFields);

      this.validateAddressByUSPS({
        street1: customer[this.addressType + '_address_1'],
        street2: customer[this.addressType + '_address_2'],
        city: customer[this.addressType + '_city'],
        state: customer[this.addressType + '_state'],
        zip: customer[this.addressType + '_postcode'],
        country: customer[this.addressType + '_country'],
      }, (address) => {

        var validatedFields = {
          address_1: address.street1,
          address_2: address.street2,
          city: address.city,
          state: address.state,
          postcode: address.zip,
        };

        this.updateAddress(validatedFields);

        for (let field in validatedFields) {
          customer[this.addressType + '_' + field] = validatedFields[field];
        }

        this.updateCustomerRequest(customer, (response) => {

          let $data = response.data.data;

          if (response.data.success === true) {
            this.updateCustomer($data.customer);
            this.showModal = false;
          } else {
            this.error = $data.message;
            this.error_code = $data.code;
          }
        });

      }, (error) => {
        this.error = error;
      });

    },
    updateCustomerRequest(customer, callback, params) {

      let $args = Object.assign({
        customer_data: customer,
        action: 'phone-orders-for-woocommerce',
        method: 'update_customer',
        _wp_http_referer: this.referrer,
        _wpnonce: this.nonce,
        tab: this.tabName,
        nonce: this.nonce,
      }, params || {});

      this.error = '';
      this.error_code = '';

      var formData = new FormData();
      this.buildFormData(formData, $args);

      this.axios.post(this.url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then((response) => {
        callback(response);
      }, () => {
      });
    },
    onChangeDefaultCountry: function (fieldKey) {
      this.fields.state.value = '';
      this.statesList = this.defaultStatesList[this.fields.country.value.value] || [];

      if (this.statesList.length) {
        this.fields.state.value = this.getObjectByKeyValue(this.statesList, 'value', this.fields.state.value);
      }

      this.$nextTick(() => {
        this.onEnter(fieldKey);
      })
    },
    onEnter: function (fieldKey) {

      var currentIndex = this.visibleFields.indexOf(fieldKey);
      var nextIndex = currentIndex + 1;

      if (typeof this.visibleFields[nextIndex] !== 'undefined') {

        if (typeof this.$refs[this.visibleFields[nextIndex]][0].activate === 'function') {
          this.$refs[this.visibleFields[nextIndex]][0].activate();
        } else {
          this.$refs[this.visibleFields[nextIndex]][0].focus();
        }

        return;
      }

      if (!this.doNotSubmitOnEnterLastField) {
        this.saveAddress();
      }

    },
    updateAddress: function (fields) {

      for (let fieldKey in fields) {
        if (this.fields[fieldKey]) {

          this.fields[fieldKey].value = null;

          if (this.visibleFields.indexOf(fieldKey) > -1 || fieldKey === 'country' && this.visibleFields.indexOf('state') > -1) {
            this.fields[fieldKey].value = fields[fieldKey];
            if (fieldKey === 'country') {
              this.fields[fieldKey].value = this.getObjectByKeyValue(
                this.defaultCountryList,
                'value',
                this.fields[fieldKey].value
              );
              this.statesList = this.defaultStatesList[this.fields.country.value.value] || [];
            }
          } else {
            // ignore visibility for additional customer fields
            if (this.addressAdditionalKeys.indexOf(fieldKey) !== -1) {
              this.fields[fieldKey].value = fields[fieldKey];
            }
          }
        }
      }

      if (this.statesList.length) {
        this.fields.state.value = this.getObjectByKeyValue(this.statesList, 'value', this.fields.state.value) || this.getObjectByKeyValue(this.statesList, 'title', this.fields.state.value);
      }
    },
    copyFromBillingAddress() {

      var skipFields = ['email'];

      if (!this.fields['phone']['visibility']) {
        skipFields.push('phone');
      }

      for (var fieldKey in this.fields) {

        if (skipFields.indexOf(fieldKey) === -1) {

          var $fieldKey = this.customerFieldListWithoutBillingShippingPrefix.indexOf(fieldKey) > -1 ? fieldKey : 'billing_' + fieldKey;

          var value = this.customer[$fieldKey];

          if (fieldKey === 'country'
            && typeof this.defaultCountryList !== 'undefined'
            && typeof this.defaultStatesList !== 'undefined'
          ) {
            value = this.getObjectByKeyValue(this.defaultCountryList, 'value', value);

            value = value || {value: ''};

            var statesList = this.defaultStatesList[value.value] || [];

            //	if formToDefault() we need to update 'statesList'
            this.statesList = statesList;
          }

          if (fieldKey === 'state'
            && typeof statesList !== 'undefined'
          ) {
            if (statesList.length) {
              value = this.getObjectByKeyValue(statesList, 'value', value);
            }
          }

          if (fieldKey === 'role'
            && typeof this.rolesList !== 'undefined'
          ) {
            value = this.getObjectByKeyValue(this.rolesList, 'value',
              value);
          }

          if (fieldKey === 'locale'
            && typeof this.languagesList !== 'undefined'
          ) {
            value = this.getObjectByKeyValue(this.languagesList, 'value',
              value);
          }

          this.fields[fieldKey]['value'] = value;
        }
      }

    },
    buildFormData(formData, data, parentKey) {
      if (data && typeof data === 'object' && !(data instanceof Date) && !(data instanceof File)) {
        Object.keys(data).forEach(key => {
          this.buildFormData(formData, data[key], parentKey ? `${parentKey}[${key}]` : key);
        });
      } else {
        const value = data == null ? '' : data;

        formData.append(parentKey, value);
      }
    },
  },
  components: {
    Multiselect,
    GoogleAutocomplete,
    CustomAutocomplete,
  },
}
</script>
