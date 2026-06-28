<template>
  <div>
    <b-modal id="addCustomer"
             ref="modal"
             :title="addCustomerLabel"
             @shown="formToDefault"
             @hidden="hideModal"
             size="lg"
             :noCloseOnBackdrop="modalDontCloseOnBackdropClick"
             :static="true"
             v-model="showModal"
    >
      <!--required fields!!!-->

      <template v-slot:modal-title>
            <span>
            {{ addCustomerLabel }}
            <b-alert variant="info" :show="!!selectedOrder" class="wpo-add-customer-modal__order-customer">
                {{ selectedOrderMessage }} <a :href="selectedOrder && selectedOrder.loaded_order_url" target="_blank">#{{
                selectedOrder && selectedOrder.loaded_order_number
              }}</a>
            </b-alert>
            </span>
      </template>

      <b-form>

        <b-container v-show="showSelectExistingOrders">
          <multiselect
            :style="'width: 100%;margin-bottom: 15px;'"
            label="formated_output"
            v-model="order"
            :options="orderList"
            track-by="loaded_order_id"
            id="ajax-existing-orders"
            :placeholder="selectExistingOrdersPlaceholder"
            :loading="isLoading"
            :internal-search="false"
            :show-no-results="true"
            @search-change="asyncFindExistingOrders"
            :hide-selected="false"
            :searchable="true"
            open-direction="bottom"
            @select="selectOrder"
            :allow-empty="false"
            @open="openSelectOrder"
            :show-labels="false"
          >
            <template v-slot:noResult>
              <span>{{ noResultLabel }}</span>
            </template>
            <template v-slot:singleLabel="props">
                <span>
                    <span v-html="props.option.formated_output"></span>
                </span>
            </template>
            <template v-slot:option="props">
                <span>
                    <span v-html="props.option.formated_output"></span>
                </span>
            </template>
            <template v-slot:noOptions>
                <span>
                    <span v-html="noOptionsTitle"></span>
                </span>
            </template>
          </multiselect>
        </b-container>

        <b-container>
          <slot name="add-customer-address-modal-header" modal="addCustomerModal"></slot>
        </b-container>

        <b-container v-for="section in sectionFields" :key="section.label">
          <b-form-group>
            <template v-for="(group, groupKey) in section.groups">
              <google-autocomplete
                ref="google_autocomplete"
                :input-placeholder="autocompleteInputPlaceholder"
                :invalid-message="autocompleteInvalidMessage"
                :custom-google-autocomplete-js-callback="customGoogleAutocompleteJsCallback"
                @change="updateAddress"
                v-if="groupKey === 'address' && !!!initCustomAutocompleteFunction"
                v-show="!!googleAutocompleteAPIKey && !!group.fields.length"
              ></google-autocomplete>
              <custom-autocomplete
                ref="custom_autocomplete"
                :input-placeholder="initCustomAutocompletePlaceholder"
                :init-autocomplete-function="initCustomAutocompleteFunction"
                @change="updateAddress"
                v-if="groupKey === 'address' && !!initCustomAutocompleteFunction"
                v-show="!!group.fields.length"
              ></custom-autocomplete>

              <b-row>
                <b-col cols="12" md="6" v-for="field in group.fields" :key="field.key">
                                    <span>
                                        <label class="mr-sm-2" :for="field.key"
                                               :class="[ emptyRequiredFieldsList.indexOf(field.key) > -1 ? 'text-danger' : '' ]">{{
                                            requiredFieldsList.indexOf(field.key) > -1 ? field.label + ' *' : field.label
                                          }}</label>

                                        <div v-if="field.key === 'country'">
                                            <multiselect
                                              :ref="field.key"
                                              :allow-empty="false"
                                              :hide-selected="false"
                                              :searchable="true"
                                              label="title"
                                              id="country"
                                              v-model="field.value"
                                              :options="defaultCountryList"
                                              track-by="value"
                                              @update:model-value="onChangeDefaultCountry(field.key)"
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


                                        <div v-else-if="field.key === 'state'">
                                            <multiselect
                                              :ref="field.key"
                                              v-if="statesList.length"
                                              :allow-empty="false"
                                              :hide-selected="false"
                                              :searchable="true"
                                              label="title"
                                              id="state"
                                              v-model="field.value"
                                              :options="statesList"
                                              track-by="value"
                                              @update:model-value="onEnter(field.key)"
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
                                              type="text"
                                              class="mb-2 mr-sm-2 mb-sm-0"
                                              id="state"
                                              v-model="field.value"
                                              :ref="field.key"
                                              @keydown.enter="onEnter(field.key)"
                                            >
                                            </b-form-input>
                                        </div>

                                        <div v-else-if="field.key === 'role'">
                                            <multiselect
                                              :ref="field.key"
                                              :allow-empty="false"
                                              :hide-selected="false"
                                              label="title"
                                              id="role"
                                              v-model="field.value"
                                              :options="allowedRolesListCustomer"
                                              track-by="value"
                                              @update:model-value="onEnter(field.key)"
                                              :show-labels="false"
                                              :placeholder="selectPlaceholder"
                                            >
                                                <template v-slot:noOptions>
                                                    <span>
                                                        <span v-html="noOptionsTitle"></span>
                                                    </span>
                                                </template>
                                            </multiselect>
                                        </div>

                                        <div v-else-if="field.key === 'locale'">
                                            <multiselect
                                              :ref="field.key"
                                              :allow-empty="false"
                                              :hide-selected="false"
                                              label="title"
                                              id="locale"
                                              v-model="field.value"
                                              :options="languagesList"
                                              track-by="value"
                                              @update:model-value="onEnter(field.key)"
                                              :show-labels="false"
                                              :placeholder="selectPlaceholder"
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
                                              :ref="field.key"
                                              :id="field.key"
                                              autocomplete="off"
                                              type="text"
                                              class="mb-2 mr-sm-2 mb-sm-0"
                                              v-model="field.value"
                                              @keydown.enter="onEnter(field.key)"
                                            >
                                            </b-form-input>
                                        </div>
                                    </span>
                </b-col>
              </b-row>
            </template>
          </b-form-group>
        </b-container>
        <slot name="add-customer-address-modal-footer" modal="addCustomerModal"></slot>
      </b-form>

      <template v-slot:footer>
        <div style="display: flex; flex-direction: column; width: 100%">
          <b-alert :show="!!error" variant="danger" class="error-alert">{{ this.error }}</b-alert>
          <div style="margin-left: auto">
            <b-button @click="cancel()">{{ cancelLabel }}</b-button>
            <b-button @click="createCustomer()" variant="primary">{{ saveCustomerLabel }}</b-button>
          </div>
        </div>
      </template>
    </b-modal>
  </div>
</template>

<style>
.wpo-add-customer-modal__order-customer {
  margin-bottom: 0;
  padding: 2px 10px;
  font-size: 14px;
  display: inline-block;
  margin-left: 5px;
}
</style>

<script>

import Multiselect from 'vue-multiselect';
import GoogleAutocomplete from '../google-autocomplete.vue';
import CustomAutocomplete from '../custom-autocomplete.vue';

export default {
  props: {
    fieldsToShow: {
      type: Object,
      default: function () {
        return {}
      }
    },
    cancelLabel: {
      default: function () {
        return 'Cancel';
      }
    },
    saveCustomerLabel: {
      default: function () {
        return 'Save customer';
      }
    },
    addCustomerLabel: {
      default: function () {
        return 'New customer';
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
    rolesList: {
      default: function () {
        return [];
      },
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
    noOptionsTitle: {
      default: function () {
        return 'List is empty.';
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
    customGoogleAutocompleteJsCallback: {
      default: function () {
        return '';
      }
    },
    selectExistingOrdersPlaceholder: {
      default: function () {
        return 'Load customer details from order';
      }
    },
    noResultLabel: {
      default: function () {
        return 'Oops! No elements found. Consider changing the search query.';
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
    selectedOrderMessage: {
      default: function () {
        return 'Copied from order';
      }
    },
    languagesList: {
      default: function () {
        return [];
      },
    },
  },
  created: function () {
    this.$root.bus.$on('edit-customer-address-custom-fields-updated', (fields) => {
      this.customFields = fields;
    });
    this.$root.bus.$on('edit-customer-address-custom-fields-updated-at-top', (fields) => {
      this.customFieldsAtTop = fields;
    });
  },
  data: function () {
    return Object.assign({
      error: '',
      error_code: '',
      statesList: [],
      order: null,
      orderList: [],
      isLoading: false,
      lastRequestTimeoutID: null,
      orderCustomer: null,
      selectedOrder: null,
      emptyRequiredFieldsList: [],
      showModal: false,
    }, this.initForm());
  },
  computed: {
    sectionFields: function () {

      var sections = [];

      for (let section in this.fieldsToShow) {

        var groups = {
          personal: {
            keys: this.personalFields,
            fields: [],
          },
          address: {
            keys: this.addressFields,
            fields: [],
          },
        };

        for (let $fieldName in this.fieldsToShow[section].fields) {
          if (this.fieldsToShow[section].fields.hasOwnProperty($fieldName)) {
            for (let group in groups) {
              if (groups[group].keys.indexOf($fieldName) > -1 && this.fields[$fieldName].visibility) {
                groups[group].fields.push(Object.assign(this.fields[$fieldName], {key: $fieldName}));
              }
            }
          }
        }

        sections.push({
          label: this.fieldsToShow[section].label,
          groups: groups,
        });
      }

      return sections;
    },
    settingsData() {

      var visibility = {};

      visibility['password'] = this.getSettingsOption('newcustomer_show_password_field');
      visibility['username'] = this.getSettingsOption('newcustomer_show_username_field');
      visibility['role'] = this.getSettingsOption('newcustomer_show_role_field');
      visibility['locale'] = this.getSettingsOption('newcustomer_show_language_field');
      visibility['first_name'] = !this.getSettingsOption('newcustomer_hide_first_name');
      visibility['last_name'] = !this.getSettingsOption('newcustomer_hide_last_name');
      visibility['email'] = !this.getSettingsOption('newcustomer_hide_email');
      visibility['company'] = !this.getSettingsOption('newcustomer_hide_company');
      visibility['address_1'] = !this.getSettingsOption('newcustomer_hide_address_1');
      visibility['address_2'] = !this.getSettingsOption('newcustomer_hide_address_2');
      visibility['city'] = !this.getSettingsOption('newcustomer_hide_city');
      visibility['postcode'] = !this.getSettingsOption('newcustomer_hide_postcode');
      visibility['country'] = !this.getSettingsOption('newcustomer_hide_country');
      visibility['state'] = !this.getSettingsOption('newcustomer_hide_state');
      visibility['vat_number'] = this.getSettingsOption('support_field_vat');

      var defaultValues = {};

      defaultValues['city'] = this.getSettingsOption('default_city');
      defaultValues['postcode'] = this.getSettingsOption('default_postcode');
      defaultValues['country'] = this.getSettingsOption('default_country');
      defaultValues['state'] = this.getSettingsOption('default_state');
      defaultValues['role'] = this.getSettingsOption('default_role');
      defaultValues['locale'] = 'site-default';

      return {
        visibility,
        defaultValues,
      };
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
    addressFields() {
      var addressFields = ['country', 'company', 'address_1', 'address_2', 'city', 'state', 'postcode', 'phone'];
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
      return ['username', 'password', 'email', 'role', 'locale', 'first_name', 'last_name'];
    },
    doNotSubmitOnEnterLastField() {
      return this.getSettingsOption('do_not_submit_on_enter_last_field');
    },
    allowedRolesListCustomer() {

      var allowedRolesListCustomer = this.getSettingsOption('allowed_roles_new_customer', []);

      return this.rolesList.filter((role) => {
        return !allowedRolesListCustomer.length || allowedRolesListCustomer.indexOf(role.value) >= 0;
      });
    },
    customerCustomFields() {
      return this.orderCustomer ? this.orderCustomer.custom_fields : {};
    },
    showSelectExistingOrders() {
      return this.getSettingsOption('create_customer_base_on_existing_order');
    },
    requiredFieldsList() {
      return this.getSettingsOption('newcustomer_required_fields', []);
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
      $reactFields['customFields'] = [];
      $reactFields['customFieldsAtTop'] = [];
      $reactFields['fields'] = {};
      $reactFields['visibleFields'] = [];

      // copy without reference
      var $fieldsToShow = JSON.parse(JSON.stringify(this.fieldsToShow));

      for (let $container in $fieldsToShow) {
        if ($fieldsToShow.hasOwnProperty($container)) {
          let $fields = $fieldsToShow[$container]['fields'];
          for (let $field in $fields) {
            if ($fields.hasOwnProperty($field)) {
              $reactFields['fields'][$field] = $fields[$field];

              if (this.settingsData) {

                if (typeof this.settingsData.visibility[$field] !== 'undefined') {
                  $reactFields['fields'][$field]['visibility'] = this.settingsData.visibility[$field];
                }

                if (typeof this.settingsData.defaultValues[$field] !== 'undefined') {
                  $reactFields['fields'][$field]['value'] = this.settingsData.defaultValues[$field];
                }

              }

              if ($reactFields['fields'][$field]['visibility']) {
                $reactFields['visibleFields'].push($field);
              }
            }
          }

          if ($fields.hasOwnProperty('country')
            && typeof this.defaultCountryList !== 'undefined'
            && typeof this.defaultStatesList !== 'undefined'
          ) {
            var country = this.getObjectByKeyValue(this.defaultCountryList, 'value',
              $fields['country'].value);

            var statesList = this.defaultStatesList[$fields['country'].value] || [];

            this.statesList = statesList;

            $reactFields['fields']['country']['value'] = country || {value: ''};

            $reactFields['statesList'] = statesList;
          }

          if ($fields.hasOwnProperty('state')
            && typeof statesList !== 'undefined'
          ) {
            var state = null;

            if (statesList.length) {
              state = this.getObjectByKeyValue(statesList, 'value', $fields['state'].value);
            } else {
              state = $fields['state'].value;
            }

            $reactFields['fields']['state']['value'] = state;
          }

          if ($fields.hasOwnProperty('role')
            && typeof this.rolesList !== 'undefined'
          ) {
            var role = null;

            if (this.rolesList.length) {
              role = this.getObjectByKeyValue(this.rolesList, 'value', $fields['role'].value);
            } else {
              role = $fields['role'].value;
            }

            $reactFields['fields']['role']['value'] = role;
          }

          if ($fields.hasOwnProperty('locale')
            && typeof this.languagesList !== 'undefined'
          ) {
            var locale = null;

            if (this.languagesList.length) {
              locale = this.getObjectByKeyValue(this.languagesList, 'value', $fields['locale'].value);
            } else {
              locale = $fields['locale'].value;
            }

            $reactFields['fields']['locale']['value'] = locale;
          }
        }
      }

      return $reactFields;
    },
    formToDefault() {

      let $reactFields = this.initForm();

      this.fields = $reactFields.fields;
      this.visibleFields = $reactFields.visibleFields;
      this.customFields = $reactFields.customFields;
      this.customFieldsAtTop = $reactFields.customFieldsAtTop;

      this.$nextTick(() => {
        // add second $nextTick() because BModal::methods::focusFirst() set new focus
        this.$nextTick(() => {
          if (this.visibleFields.length) {
            this.$refs[this.visibleFields[0]][0].focus();
          }
        });
      });

      this.error = '';

      this.order = null;
      this.orderList = [];
      this.isLoading = false;
      this.lastRequestTimeoutID = null;
      this.orderCustomer = null;
      this.selectedOrder = null;
      this.emptyRequiredFieldsList = [];
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

          var value = typeof this.fields[$field].value === 'object' ? this.fields[$field].value.value : this.fields[$field].value;
          if (value.trim() === '') {
            this.emptyRequiredFieldsList.push($field);
          }
        }
      }

      if (this.emptyRequiredFieldsList.length) {
        valid = false;
      }

      this.getCreateCustomerValidation().forEach((validation) => {
        if (!!!validation()) {
          valid = false;
          return;
        }
      });

      return valid;
    },
    createCustomer() {
      let $newFields = {};

      if (!!!this.isCustomerValid()) {
        this.error = this.fillAllFieldsLabel;
        return;
      }

      for (let $field in this.fields) {
        if (this.fields.hasOwnProperty($field)) {
          if (this.fields[$field].hasOwnProperty('value')) {
            if (typeof this.fields[$field].value !== 'undefined' &&
              this.fields[$field].value !== null &&
              this.fields[$field].value.hasOwnProperty('value')) {
              $newFields[$field] = this.fields[$field].value.value; // for multiselect fields
            } else {
              $newFields[$field] = this.fields[$field].value;
            }
          }
        }
      }

      $newFields['custom_fields'] = Object.assign({}, this.customFieldsAtTop, this.customFields);

      this.validateAddressByUSPS({
        street1: $newFields.address_1,
        street2: $newFields.address_2,
        city: $newFields.city,
        state: $newFields.state,
        zip: $newFields.postcode,
        country: $newFields.country,
      }, (address) => {

        var validatedFields = {
          address_1: address.street1,
          address_2: address.street2,
          city: address.city,
          state: address.state,
          postcode: address.zip,
        };

        this.updateAddress(validatedFields);

        $newFields = Object.assign($newFields, validatedFields);

        let $args = {
          action: 'phone-orders-for-woocommerce',
          method: 'create_customer',
          _wp_http_referer: this.referrer,
          _wpnonce: this.nonce,
          tab: this.tabName,
          data: $newFields,
          order_customer: this.orderCustomer,
          nonce: this.nonce,
        };

        this.error = '';

        var formData = new FormData();
        this.buildFormData(formData, $args);

        this.axios.post(this.url, formData, {headers: {'Content-Type': 'multipart/form-data'}}).then((response) => {

          let $data = response.data.data;
          let newId = $data.id;

          if (response.data.success === true) {
            this.$root.bus.$emit('update-customer', newId);
            this.showModal = false;
          } else {
            this.error = $data.message;
            this.error_code = $data.code;
          }
        }, () => {
        });

      }, (error) => {
        this.error = error;
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
        this.createCustomer();
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
    selectOrder(option, id) {
      this.isLoading = true;
      this.axios.get(this.url, {
        params: {
          action: 'phone-orders-for-woocommerce',
          wpo_cache_orders_key: this.wpoCacheOrdersKey,
          method: 'get_customer',
          tab: this.tabName,
          type: 'order',
          id: option.loaded_order_id,
          nonce: this.nonce,
        }
      }).then((response) => {

        var customer = response.data.data;

        for (var fieldKey in this.fields) {

          var value = customer['billing_' + fieldKey];

          if (typeof value === 'undefined') {
            value = customer[fieldKey];
          }

          if (typeof value !== 'undefined') {

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
              if (this.rolesList.length) {
                value = this.getObjectByKeyValue(this.rolesList, 'value', value);
              }
            }

            this.fields[fieldKey]['value'] = value;
          }
        }

        this.orderCustomer = customer;
        this.selectedOrder = this.order;
        this.order = null;

        this.isLoading = false;
      });
    },
    openSelectOrder() {
      this.orderList = [];
    },
    asyncFindExistingOrders(query) {

      this.lastRequestTimeoutID && clearTimeout(this.lastRequestTimeoutID);

      if (!query) {
        this.isLoading = false;
        this.lastRequestTimeoutID = null;
        this.orderList = [];
        return;
      }

      this.isLoading = true;

      this.lastRequestTimeoutID = setTimeout(() => {
        this.axios.get(this.url, {
          params: {
            action: 'phone-orders-for-woocommerce',
            wpo_cache_orders_key: this.wpoCacheOrdersKey,
            method: 'find_orders_customers',
            tab: this.tabName,
            term: query,
            nonce: this.nonce,
          }
        }).then((response) => {
          this.orderList = response.data;
          this.isLoading = false;
        });
      }, this.multiSelectSearchDelay);
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
