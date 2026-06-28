<template>
  <b-row>
    <b-col cols="12" class="google-autocomplete-component">
      <div class="autocomplete-block alert alert-primary"  v-show="isValidAPIKey">
        <div ref="autocomplete_address" style="display:none;"></div>
      </div>
      <b-alert show variant="warning" class="alert-autocomplete" v-show="!isValidAPIKey">
        {{ invalidMessage }}
        <fa-icon icon="exclamation-triangle" class="alert-icon"/>
      </b-alert>
    </b-col>
  </b-row>
</template>

<style>
.pac-container.pac-logo {
  z-index: 1000000;
}

.alert-autocomplete .alert-icon {
  float: right;
  margin-top: 4px;
}

.autocomplete-block {
  position: relative;
}

.autocomplete-block .autocomplete-icon {
  position: absolute;
  top: 22px;
  right: 25px;
  font-size: 20px;
  color: #aaa;
}

.autocomplete-block.alert {
  padding-right: 15px;
  padding-left: 15px;
}

.alert-autocomplete,
.autocomplete-block.alert {
  margin-bottom: 0;
}

.google-autocomplete-component {
  margin: 15px 0;
}
</style>

<script>

import {library} from '@fortawesome/fontawesome-svg-core';
import {faExclamationTriangle, faSearchLocation} from '@fortawesome/free-solid-svg-icons';
import {FontAwesomeIcon as FaIcon} from '@fortawesome/vue-fontawesome';

library.add(faExclamationTriangle, faSearchLocation)

export default {
  props: {
    inputPlaceholder: {
      default: function () {
        return 'Input your address';
      }
    },
    invalidMessage: {
      default: function () {
        return 'Please, enter valid Places API key at tab Settings';
      }
    },
    customGoogleAutocompleteJsCallback: {
      default: function () {
        return '';
      }
    },
  },
  created() {
    this.$root.bus.$on('google-map-autocomplete-ready', (data) => {
      this.isValidAPIKey = data.status;
      this.init();
    });
  },
  data() {
    return {
      autocompleteInput: '',
      autocomplete: null,
      isValidAPIKey: false,
      key: +(new Date),
    };
  },
  methods: {
    async init() {
      if (!this.isValidAPIKey) return;

      this.key = +(new Date);

      this.$nextTick(async () => {
        const inputWrapper = this.$refs.autocomplete_address?.$el || this.$refs.autocomplete_address;

        if (!inputWrapper) {
          console.error('Input element not found');
          return;
        }

        const placeAutocomplete = new google.maps.places.PlaceAutocompleteElement();

        placeAutocomplete.classList.add('form-control');
        placeAutocomplete.classList.add('pac-target-input');
        placeAutocomplete.style.padding = '0';
        placeAutocomplete.style.border = 'none';
        placeAutocomplete.placeholder = this.inputPlaceholder || 'Input your address';

        const selectedCountriesList = this.getSettingsOption?.('google_map_api_selected_countries');
        if (selectedCountriesList && selectedCountriesList.length) {
          placeAutocomplete.includedRegionCodes = selectedCountriesList.map(c => c.value);
        }

        inputWrapper.replaceWith(placeAutocomplete);
        this.autocomplete = placeAutocomplete;

        placeAutocomplete.addEventListener('gmp-select', async ({ placePrediction }) => {
          var place = placePrediction.toPlace();
          await place.fetchFields({ fields: ['displayName', 'formattedAddress', 'location', 'addressComponents'] });
          this.onChanged(place);
        });
      });
    },
    onChanged(place) {
      var fields = {};

      var UK_mask = /^([^,]+), ([^,]+), ([^,]+) ([A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}), (GB|UK)$/;
      var UK_mask_2 = /^([^,]+), ([^,]+) ([A-Z]{1,2}\d[A-Z\d]? ?\d[A-Z]{2}), (GB|UK)$/;

      var formattedAddressNew = place.formattedAddress || place.displayName;

      var countryComponent = place.addressComponents.find(c => c.types.includes('country'));
      var countryLong = countryComponent?.longText || '';
      var countryShort = countryComponent?.shortText || '';

      if (formattedAddressNew && countryLong && countryShort) {
        formattedAddressNew = formattedAddressNew.replace(countryLong, countryShort);
      }

      var UK_results = UK_mask.exec(formattedAddressNew);
      var UK_results_2 = UK_mask_2.exec(formattedAddressNew);

      if (UK_results) {
        fields['address_1'] = UK_results[1];
        fields['address_2'] = UK_results[2];
        fields['city'] = UK_results[3];
        fields['postcode'] = UK_results[4];
        fields['state'] = 'England';
        fields['country'] = 'GB';
      } else if (UK_results_2) {
        fields['address_1'] = UK_results_2[1];
        fields['address_2'] = '';
        fields['city'] = UK_results_2[2];
        fields['postcode'] = UK_results_2[3];
        fields['state'] = 'England';
        fields['country'] = 'GB';
      } else {
        //COMMON logic
        var componentForm = [
          'subpremise',
          'street_number',
          'route',
          'locality',
          'administrative_area_level_1',
          'administrative_area_level_2',
          'country',
          'postal_code',
          'postal_town',
          'postal_code_suffix',
          'sublocality_level_1',
        ];

        var fillFieldsKeys = {
          address_1: (components) => {
            var street_number = [(components.country.short_name === 'AU' ? components.subpremise.short_name : ''), components.street_number.short_name]
              .filter((v) => typeof v !== 'undefined' && v.trim() !== '')
              .join('/')
            return [street_number, components.route.long_name]
              .filter((v) => typeof v !== 'undefined' && v.trim() !== '')
              .join(' ');
          },
          address_2: (components) => {
            if (components.country.short_name === 'AU') {
              return ''
            }
            return [components.subpremise.short_name]
              .filter((v) => typeof v !== 'undefined' && v.trim() !== '')
              .join(' ');
          },
          city: (components) => {
            return components.locality.long_name || components.sublocality_level_1.long_name || components.postal_town.short_name;
          },
          postcode: (components) => {
            return [components.postal_code.short_name]
              .filter((v) => typeof v !== 'undefined' && v.trim() !== '')
              .join(' ');
          },
          country: (components) => {
            return components.country.short_name;
          },
          state: (components) => {
            var state = '';

            if (this.$root.isStateAbbreviationExists(components.country.short_name, components.administrative_area_level_1.short_name)) {
              state = components.administrative_area_level_1.short_name;
            } else if (this.$root.isStateAbbreviationExists(components.country.short_name, components.administrative_area_level_2.short_name)) {
              state = components.administrative_area_level_2.short_name;
            } else {
              state = components.administrative_area_level_1.long_name || components.administrative_area_level_2.long_name;
            }

            return state;
          },
          street_number: (components) => {
            return typeof components.street_number.short_name !== 'undefined' ? components.street_number.short_name : '';
          },
        };

        var dataComponents = {};

        // prefill with empty strings
        // no need to use many if !== 'undefined' checks
        for (let type of componentForm) {
          dataComponents[type] = { long_name: '', short_name: '' };
        }

        for (let component of place.addressComponents) {
          const long_name = component.longText;
          const short_name = component.shortText;
          const types = component.types;

          for (let type of types) {
            if (componentForm.includes(type)) {
              dataComponents[type] = { long_name, short_name };
            }
          }
        }


        for (let fieldKey in fillFieldsKeys) {
          fields[fieldKey] = fillFieldsKeys[fieldKey](dataComponents);
        }
      }//end IF

      var numeral = require("numeral");
      fields['lat'] = numeral(place.location.lat()).format("0.0000").toString();
      fields['lng'] = numeral(place.location.lng()).format("0.0000").toString();

      fields = typeof window[this.customGoogleAutocompleteJsCallback] === 'function' ? window[this.customGoogleAutocompleteJsCallback](fields, dataComponents, place) : fields;

      this.$emit('change', fields);

      this.clear();
    },
    clear() {
      this.autocompleteInput = '';
    },
  },
  components: {
    FaIcon,
  },
  emits: ['change']
}
</script>
