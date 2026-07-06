<template>
  <tr v-show="shown" :data-tab-key="tabKey">
    <td colspan=2>
      <table class="form-table">
        <tbody>
        <tr>
          <td colspan=2>
            <b>{{ title }}</b>
            <a style="display: inline-block; margin-left: 15px" :href="docLink" target="_blank" data-search-ignore>
              {{readDocsTitle}}
            </a>
          </td>
        </tr>
        <tr>
          <td>
            {{ autoRecalculateLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="recalculate" name="auto_recalculate">
          </td>
        </tr>
        <tr>
          <td>
            {{ orderPaymentMethodLabel }}
          </td>
          <td>
            <multiselect
              :allow-empty="false"
              :hide-selected="true"
              :searchable="false"
              style="width: 100%;max-width: 800px;"
              label="title"
              v-model="paymentMethod"
              :options="orderPaymentMethodsList"
              track-by="value"
              :show-labels="false"
            >
              <template v-slot:noOptions>
                                    <span>
                                        <span v-html="noOptionsTitle"></span>
                                    </span>
              </template>
            </multiselect>
          </td>
        </tr>
        <tr>
          <td>
            {{ allowToCreateOrdersWithoutPaymentLabel }}<br>
          </td>
          <td>
            <input type="checkbox" class="option" v-model="elAllowToCreateOrdersWithoutPayment"
                   name="allow_to_create_orders_without_payment">
          </td>
        </tr>
        <tr>
          <td>
            {{ orderStatusLabel }}
          </td>
          <td>
            <multiselect
              :allow-empty="false"
              :hide-selected="true"
              :searchable="false"
              style="width: 100%;max-width: 800px;"
              label="title"
              v-model="statusOrder"
              :options="orderStatusesList"
              track-by="value"
              :show-labels="false"
            >
              <template v-slot:noOptions>
                                    <span>
                                        <span v-html="noOptionsTitle"></span>
                                    </span>
              </template>
            </multiselect>
          </td>
        </tr>
        <tr>
          <td>
            {{ switchCustomerInCartLabel }}<br>
            <i>{{ switchCustomerInCartLabelTip }}</i>
          </td>
          <td>
            <input type="checkbox" class="option" v-model="elSwitchCustomerInCart"
                   name="switch_customer_while_calc_cart">
          </td>
        </tr>
        <tr>
          <td>
            {{ googleMapAPIKeyLabel }}

            <div class="link-note">
              <a href="https://docs.algolplus.com/algol_phone_order/free-version/settings/common/"
                 target="_blank">{{ googleMapAPIKeyLinkLabel }}</a>
            </div>
          </td>
          <td>
                            <span class="block-input-check-map-api">
                                <input type="text" class="option" v-model.trim="mapAPIKey" name="google_map_api_key">
                                <span class="fa-icon green-color" v-show="mapAPIKeyIsValid === true"
                                      :title="validatedMapAPIKeySuccessTitle">
                                    <fa-icon icon="check-circle"/>
                                </span>
                                <span class="fa-icon red-color" v-show="mapAPIKeyIsValid === false"
                                      :title="validatedMapAPIKeyErrorTitle">
                                    <fa-icon icon="exclamation-circle"/>
                                </span>
                            </span>
            <button class="btn btn-secondary btn-sm btn-check-api-key" @click="validateMapAPIKey" v-show="!!mapAPIKey">
              {{ validateMapAPIKeyLabel }}
              <loader v-show="isChecking"></loader>
            </button>
          </td>
        </tr>
        <tr v-show="mapAPIErrorMsg != '' && mapAPIErrorMsg != undefined">
          <td>
          </td>
          <td>
            <p class="error-message__api">
              {{ mapAPIErrorMsg }}
            </p>
          </td>
        </tr>
        <tr>
          <td>
            {{ googleMapAPICountriesLabel }}
          </td>
          <td>
            <multiselect
              :allow-empty="true"
              :multiple="true"
              :hide-selected="true"
              :searchable="true"
              :max="mapAPICountryLimits"
              style="width: 100%;max-width: 800px;"
              label="title"
              id="google_map_api_countries_list"
              :options="googleMapAPICountriesList"
              v-model="elGoogleMapAPISelectedCountries"
              track-by="value"
              :show-labels="false"
              :placeholder="selectOptionLabel"
            >
              <template v-slot:noOptions>
                                    <span>
                                        <span v-html="noOptionsTitle"></span>
                                    </span>
              </template>
              <template v-slot:maxElements>
                {{ this.googleMapAPIMaximumCountriesMsg + ": " + this.mapAPICountryLimits }}
              </template>
            </multiselect>
          </td>
        </tr>
        <tr>
          <td>
          </td>
          <td>
            <input type="checkbox" class="option" v-model="elGoogleMapAPIHideRoutes" name="google_map_api_hide_routes">
            {{ googleMapAPIHideRoutesLabel }}
          </td>
        </tr>
        <tr>
          <td>
            {{ addressValidationUSPSAPILabel }}

            <div class="link-note">
              <a href="https://developers.usps.com/getting-started"
                 target="_blank">{{ addressValidationUSPSDocumentationLabel }}</a>
            </div>
          </td>
          <td>
            <table style="width: 100%">
              <tr>
                <td>
                  {{ addressValidationUSPSKeyLabel }}
                </td>
                <td>
                  <input type="text" class="option" v-model.trim="elAddressValidationUSPSKey"
                     name="address_validation_usps_key">
                </td>
              </tr>
              <tr>
                <td>
                  {{ addressValidationUSPSSecretLabel }}
                </td>
                <td>
                  <input type="text" class="option" v-model.trim="elAddressValidationUSPSSecret"
                     name="address_validation_usps_secret">
                </td>
              </tr>
              <tr v-show="!!elAddressValidationUSPSKey && !!elAddressValidationUSPSSecret">
                <td colspan="100%">
                  <button
                    class="btn btn-secondary btn-sm btn-check-api-key"
                    @click="refreshUSPSToken"
                    style="margin-right: 16px"
                  >
                    {{ refreshUSPSTokenLabel }}
                    <loader v-show="isRefreshingUSPS"></loader>
                  </button>
                  <span class="block-refresh-usps">
                    <span class="fa-icon green-color" v-show="USPSTokenRefreshed === true"
                          :title="refreshedUSPSTokenSuccessTitle">
                      <fa-icon icon="check-circle"/>
                    </span>
                    <span class="fa-icon red-color" v-show="USPSTokenRefreshed === false"
                          :title="refreshedUSPSTokenErrorTitle">
                      <fa-icon icon="exclamation-circle"/>
                    </span>
                  </span>
                  <span class="error-message__usps"  v-show="USPSTokenErrorMsg !== '' && USPSTokenErrorMsg !== undefined" >
                    {{ USPSTokenErrorMsg }}
                  </span>
                </td>
<!--                <td colspan="100%">-->
<!--                </td>-->
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td>
            {{ disableOrderEmailsLabel }}<br>
          </td>
          <td>
            <input type="checkbox" class="option" v-model="elDisableOrderEmails" name="disable_order_emails">
          </td>
        </tr>
        <slot name="pro-common-settings"></slot>
        </tbody>
      </table>
    </td>
  </tr>
</template>

<style>

#phone-orders-app .btn.btn-check-api-key {
  padding: 3px 12px;
}

.btn-check-api-key .v-spinner {
  display: inline-block;
  vertical-align: middle;
  height: 15px;
}

.btn-check-api-key .v-spinner .v-pulse {
  animation-duration: 1s !important;
  height: 10px !important;
  width: 10px !important;
}

.block-input-check-map-api .fa-icon.green-color, .block-refresh-usps .fa-icon.green-color {
  color: green;
}

.block-input-check-map-api .fa-icon.red-color, .block-refresh-usps .fa-icon.red-color {
  color: red;
}

.block-input-check-map-api .fa-icon {
  position: absolute;
  top: 0;
  right: 15px;
}

.block-input-check-map-api {
  position: relative;
}

.block-input-check-map-api .option {
  padding-right: 30px;
}

.block-refresh-usps {
  margin-right: 4px;
}

.link-note {
  margin-top: 5px;
  font-size: 13px;
}

.form-table td .error-message__api, .form-table td .error-message__usps {
  margin-top: 0;
  padding-top: 0;
  color: red;
}

.form-table td .error-message__api {
  padding-bottom: 10px;
}
</style>

<script>

import Multiselect from 'vue-multiselect';

var loader = require('vue-spinner/dist/vue-spinner.min').PulseLoader;

import {library} from '@fortawesome/fontawesome-svg-core';
import {faCheckCircle, faExclamationCircle} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon as FaIcon} from '@fortawesome/vue-fontawesome';

library.add(faCheckCircle, faExclamationCircle)

export default {
  props: {
    title: {
      default: function () {
        return 'Common';
      },
    },
    tabKey: {
      default: function () {
        return 'commonSettings';
      },
    },
    autoRecalculateLabel: {
      default: function () {
        return 'Automatically update Shipping/Taxes/Totals';
      },
    },
    readDocsTitle: {
      default: function () {
        return 'Read docs';
      },
    },
    docLink: {
      default: function () {
        return '';
      },
    },
    autoRecalculate: {
      default: function () {
        return false;
      },
    },
    orderPaymentMethodLabel: {
      default: function () {
        return 'Set payment method for created order';
      },
    },
    orderPaymentMethod: {
      default: function () {
        return '';
      },
    },
    orderPaymentMethodsList: {
      default: function () {
        return [];
      },
    },
    orderStatusLabel: {
      default: function () {
        return 'Set status for created order';
      },
    },
    orderStatus: {
      default: function () {
        return '';
      },
    },
    orderStatusesList: {
      default: function () {
        return [];
      },
    },
    googleMapAPIKeyLabel: {
      default: function () {
        return 'Google Map API Key';
      },
    },
    validateMapAPIKeyLabel: {
      default: function () {
        return 'Check';
      },
    },
    validatedMapAPIKeySuccessTitle: {
      default: function () {
        return 'Check';
      },
    },
    validatedMapAPIKeyErrorTitle: {
      default: function () {
        return 'Check';
      },
    },
    googleMapAPIKey: {
      default: function () {
        return '';
      },
    },
    googleMapAPIErrorMsg: {
      default: function () {
        return '';
      }
    },
    googleMapAPIKeyLinkLabel: {
      default: function () {
        return 'How to get api key';
      },
    },
    googleMapAPIHideRoutesLabel: {
      default: function () {
        return 'Hide results without house number';
      },
    },
    googleMapAPICountriesList: {
      default: function () {
        return [];
      }
    },
    googleMapAPISelectedCountries: {
      default: function () {
        return [];
      }
    },
    googleMapAPICountriesLabel: {
      default: function () {
        return 'Display autocomplete results only for selected countries (Google Map API)';
      },
    },
    googleMapAPIMaximumCountriesMsg: {
      default: function () {
        return 'The maximum number of countries that can be selected';
      },
    },
    googleMapAPIHideRoutes: {
      default: function () {
        return false;
      },
    },
    switchCustomerInCartLabel: {
      default: function () {
        return 'Switch customer during cart calculations';
      },
    },
    switchCustomerInCartLabelTip: {
      default: function () {
        return 'required by some pricing plugins';
      },
    },
    switchCustomerInCart: {
      default: function () {
        return false;
      },
    },
    disableOrderEmailsLabel: {
      default: function () {
        return "Don't send order emails";
      },
    },
    disableOrderEmails: {
      default: function () {
        return false;
      },
    },
    noOptionsTitle: {
      default: function () {
        return 'List is empty.';
      }
    },
    addressValidationUSPSAPILabel: {
      default: function () {
        return 'Address Validation (USPS)';
      }
    },
    addressValidationUSPSDocumentationLabel: {
      default: function () {
        return 'USPS documentation';
      },
    },
    addressValidationUSPSKeyLabel: {
      default: function () {
        return 'Consumer Key';
      }
    },
    addressValidationUSPSSecretLabel: {
      default: function () {
        return 'Consumer Secret';
      }
    },
    addressValidationUSPSKey: {
      default: function () {
        return '';
      }
    },
    addressValidationUSPSSecret: {
      default: function () {
        return '';
      }
    },
    refreshUSPSTokenLabel: {
      default: function () {
        return 'Refresh Token'
      }
    },
    refreshedUSPSTokenSuccessTitle: {
      default: function () {
        return 'Check';
      },
    },
    refreshedUSPSTokenErrorTitle: {
      default: function () {
        return 'Check';
      },
    },
    allowToCreateOrdersWithoutPaymentLabel: {
      default: function () {
        return 'Allow to create orders without payment';
      }
    },
    allowToCreateOrdersWithoutPayment: {
      default: function () {
        return false;
      }
    },
    selectOptionLabel: {
      default: function () {
        return 'Select option';
      }
    },
    unknownErrorLabel: {
      default: function () {
        return 'Unknown error';
      }
    }
  },
  mounted() {
    this.addSettingsTab(this.getTabsHeaders())
    this.setComponentsSettings(this.componentsSettings)
  },
  data() {
    return {
      recalculate: this.autoRecalculate,
      paymentMethod: this.getObjectByKeyValue(this.orderPaymentMethodsList, 'value', this.orderPaymentMethod),
      statusOrder: this.getObjectByKeyValue(this.orderStatusesList, 'value', this.orderStatus),
      mapAPIKey: this.googleMapAPIKey,
      mapAPIKeyIsValid: null,
      mapAPIErrorMsg: this.googleMapAPIErrorMsg,
      mapAPICountryLimits: 5,
      elGoogleMapAPIHideRoutes: this.googleMapAPIHideRoutes,
      elGoogleMapAPISelectedCountries: this.googleMapAPISelectedCountries,
      isChecking: false,
      elSwitchCustomerInCart: this.switchCustomerInCart,
      elDisableOrderEmails: this.disableOrderEmails,
      elAddressValidationUSPSKey: this.addressValidationUSPSKey,
      elAddressValidationUSPSSecret: this.addressValidationUSPSSecret,
      isRefreshingUSPS: false,
      USPSTokenRefreshed: null,
      USPSTokenErrorMsg: '',
      elAllowToCreateOrdersWithoutPayment: this.allowToCreateOrdersWithoutPayment,
    };
  },
  watch: {
    mapAPIKey() {
      if (this.mapAPIKey == '') {
        this.mapAPIErrorMsg = '';
      }
      this.mapAPIKeyIsValid = null;
    },
    componentsSettings() {
      this.setComponentsSettings(this.componentsSettings)
    },
  },
  computed: {
    shown() {

      if (this.getSearchMode()) {
        return this.getMatchedTabs().includes(this.tabKey);
      }

      return this.getSettingsCurrentTab() === this.tabKey;
    },
    componentsSettings() {
      return this.getSettings();
    },
  },
  methods: {
    getSettings() {

      var settings = {
        auto_recalculate: this.recalculate,
        order_payment_method: this.getKeyValueOfObject(this.paymentMethod, 'value'),
        order_status: this.getKeyValueOfObject(this.statusOrder, 'value'),
        google_map_api_key: this.mapAPIKey,
        google_map_api_hide_routes: this.elGoogleMapAPIHideRoutes,
        google_map_api_selected_countries: this.elGoogleMapAPISelectedCountries,
        switch_customer_while_calc_cart: this.elSwitchCustomerInCart,
        disable_order_emails: this.elDisableOrderEmails,
        address_validation_usps_key: this.elAddressValidationUSPSKey,
        address_validation_usps_secret: this.elAddressValidationUSPSSecret,
        allow_to_create_orders_without_payment: this.elAllowToCreateOrdersWithoutPayment,
      };

      return settings;
    },
    validateMapAPIKey() {

      this.isChecking = true;
      this.mapAPIKeyIsValid = null;
      this.mapAPIErrorMsg = '';
      var self = this;
      var successCallback = async () => {
        const self = this;

        try {
          const { AutocompleteSuggestion } = await google.maps.importLibrary('places');

          const request = {
            input: 'pizza near Syd',
            sessionToken: new google.maps.places.AutocompleteSessionToken(),
          };

          const { suggestions } = await google.maps.places.AutocompleteSuggestion.fetchAutocompleteSuggestions(request);

          self.mapAPIKeyIsValid = Array.isArray(suggestions) && suggestions.length > 0;
        } catch (error) {

          self.mapAPIErrorMsg = error.message || self.unknownErrorLabel;
          self.mapAPIKeyIsValid = false;
        } finally {
          self.isChecking = false;
        }
      };


      var errorCallback = function () {
        self.mapAPIKeyIsValid = false;
        self.isChecking = false;
        var oldConsoleErr = console.error;
        console.error = function () {
          self.mapAPIErrorMsg = arguments[0];
          return oldConsoleErr.apply(this, arguments);
        };
        console.error = oldConsoleErr;
      }

      this.registerGoogleMapJs(this.mapAPIKey, successCallback, errorCallback);
    },
    refreshUSPSToken () {
      this.isRefreshingUSPS = true;

      const handleResponse = async (res) => {
        const self = this;

        if (res.hasOwnProperty('data') && res.data.hasOwnProperty('success') && res.data.success) {
          self.USPSTokenErrorMsg = '';
          self.USPSTokenRefreshed = true;
        } else {
          self.USPSTokenErrorMsg = self.unknownErrorLabel;
          self.USPSTokenRefreshed = false;
        }

        self.isRefreshingUSPS = false;
      }

      this.getUSPSToken(this.elAddressValidationUSPSKey, this.elAddressValidationUSPSSecret, handleResponse)
    },
    getTabsHeaders() {
      return {
        key: this.tabKey,
        title: this.title,
        menu_order: 10,
      };
    },
    showOption(key) {
      this.shown = this.tabKey === key;
    },
  },
  components: {
    Multiselect,
    loader,
    FaIcon
  },
}
</script>
