<template>
    <b-row>
        <b-col cols="12" class="google-autocomplete-component">
            <div class="autocomplete-block alert alert-primary" v-show="isValidAPIKey">
                <b-form-input :placeholder="inputPlaceholder" ref="autocomplete_address" v-model="autocompleteInput" :key="key" autocomplete="off"/>
                <fa-icon icon="search-location" class="autocomplete-icon"/>
            </div>
            <b-alert show variant="warning" class="alert-autocomplete" v-show="!isValidAPIKey">
                {{ invalidMessage }} <fa-icon icon="exclamation-triangle" class="alert-icon"/>
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
            init() {

		if (!this.isValidAPIKey) {
		    return;
		}

                this.autocomplete = null;
                this.key          = +(new Date);

                this.$nextTick(() => {

		    var options = {types: ['geocode']};

		    if( typeof window.wpo_init_google_autcomplete === "function" ) {
			options = window.wpo_init_google_autcomplete(options);
		    }

                    var autocomplete = new google.maps.places.Autocomplete(
                        this.$refs.autocomplete_address.$el,
                        options
                    );

                    autocomplete.addListener('place_changed', () => {
                        this.onChanged(autocomplete.getPlace());
                    });

                    this.autocomplete = autocomplete;
                });
            },
            onChanged(place) {

                var componentForm = [
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
                        return [components.street_number.short_name, components.route.long_name]
                                    .filter((v) => typeof v !== 'undefined' && v.trim() !== '')
                                    .join(' ');
                    },
                    address_2: (components) => {
                        return '';
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

                        if ( this.$root.isStateAbbreviationExists(components.country.short_name, components.administrative_area_level_1.short_name) ) {
                            state = components.administrative_area_level_1.short_name;
                        } else if ( this.$root.isStateAbbreviationExists(components.country.short_name, components.administrative_area_level_2.short_name) ) {
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
                for (var j = 0; j < componentForm.length; j++) {
                    dataComponents[componentForm[j]] = {
                        'long_name': '',
                        'short_name': '',
                    };
                }

                // Get each component of the address from the place details
                // and fill the corresponding field on the form.
                for (var i = 0; i < place.address_components.length; i++) {
                    var addressType = place.address_components[i].types[0];
                    var val = {
                        'long_name': place.address_components[i]['long_name'],
                        'short_name': place.address_components[i]['short_name'],
                    };
                    dataComponents[addressType] = val;
                }

                var fields = {};

                for (let fieldKey in fillFieldsKeys) {
                    fields[fieldKey] = fillFieldsKeys[fieldKey](dataComponents);
                }

                var numeral = require("numeral");
                fields['lat'] = numeral(place.geometry.location.lat()).format("0.0000").toString();
                fields['lng'] = numeral(place.geometry.location.lng()).format("0.0000").toString();

		fields = typeof window[this.customGoogleAutocompleteJsCallback] === 'function' ? window[this.customGoogleAutocompleteJsCallback](fields) : fields;

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
    }
</script>
