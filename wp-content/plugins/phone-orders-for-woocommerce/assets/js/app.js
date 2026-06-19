var Vue         = require( 'vue' );
var store       = require('./store');
var components  = require('./components');
var axios       = require('axios');
var qs          = require('qs');

//fix multiple Vue version on page, Bootstrap Vue uses window.Vue if it none empty
var oldVue = window.Vue;
window.Vue = undefined

var BootstrapVue = require('bootstrap-vue');

window.Vue = oldVue

Vue.use( BootstrapVue );

var VueClipboard = require('vue-clipboard2');

Vue.use(VueClipboard);

Vue.filter("formatPrice", require('./filters/price'));
Vue.filter("formatWcPrice", require('./filters/wc-price'));

Vue.directive("store-search-multiselect", require('./directives/store-search-multiselect'));

// Create a global mixin to expose strings, global config, and single backend resource.
Vue.mixin( {
    computed: {
        nonce: function() {
            return PhoneOrdersData.nonce;
        },
        edd_wpo_nonce: function() {
            return PhoneOrdersData.edd_wpo_nonce;
        },
        search_customers_nonce: function() {
            return PhoneOrdersData.search_customers_nonce;
        },
        url: function() {
            return PhoneOrdersData.ajax_url;
        },
        base_cart_url: function() {
            return PhoneOrdersData.base_cart_url;
        },
        base_admin_url: function() {
            return PhoneOrdersData.base_admin_url;
        },
        axios: function() {
            return axios;
        },
        qs: function() {
            return qs;
        },
	modalDontCloseOnBackdropClick: function() {
            return !!this.getSettingsOption('dont_close_popup_click_outside');
        },
    },
    methods: {
        getObjectByKeyValue (arrayOfObjects, key, value, defaultValue) {

            var found = typeof defaultValue !== 'undefined' ? defaultValue : null;

            arrayOfObjects.forEach(function (obj) {
                if (obj[key] === value) {
                    found = obj;
                }
            });

            return found;
        },
        getKeyValueOfObject (object, key) {
            return object && typeof object === 'object' ? object[key] : object;
        },
        openModal (modalID) {
            this.$root.$emit('bv::show::modal', modalID);
        },
        saveSettingsByEvent: function () {
            this.$root.bus.$emit('save-settings');
        },
        getWindowLocationHash: function() {
            return window.location.hash;
        },
        registerGoogleMapJs(key, successCallback, errorCallback) {

            document.querySelectorAll('script[src*="maps.google"]').forEach((script) => {
                script.remove();
            });

	    if (!key) {
		return;
	    }

            if (typeof google === 'object') {
                google.maps = false;
            }

            window.gm_authFailure = errorCallback;

            var scriptTag = document.createElement('script');
            scriptTag.src = 'https://maps.googleapis.com/maps/api/js?key='+ key +'&libraries=places&language=' + this.locale;

            scriptTag.onload             = successCallback;
            scriptTag.onreadystatechange = successCallback;
            scriptTag.async              = true;
            scriptTag.defer              = true;

            document.body.appendChild(scriptTag);
        },
	    removeGetParameter( parameterName ) {
		    var result = null,
			    clean_uri = null,
			    tmp = [];

		    location.search
		            .substr( 1 )
		            .split( "&" )
		            .forEach( function ( item ) {
			            tmp = item.split( "=" );
			            if ( tmp[0] === parameterName ) {
				            result = decodeURIComponent( tmp[1] );
				            clean_uri = window.location.toString().replace( "&" + tmp[0] + "=" + tmp[1], "");
				            clean_uri = clean_uri.replace( tmp[0] + "=" + tmp[1], "");
				            clean_uri = clean_uri.replace(/\?$/ig, "");
			            }
		            } );

		    if ( result && clean_uri ) {
			    window.history.replaceState({}, document.title, clean_uri)
            }
		    return result;
	    },
	    getParameter( parameterName ) {
		    var result = null,
			    tmp = [];

		    location.search
		            .substr( 1 )
		            .split( "&" )
		            .forEach( function ( item ) {
			            tmp = item.split( "=" );
			            if ( tmp[0] === parameterName ) {
				            result = decodeURIComponent( tmp[1] );
					    return result;
			            }
		            } );

		    return result;
	    },
        deepIsEqual(first, second, excludeKeys) {
            first = Object.assign({}, first);
            second = Object.assign({}, second);

	    if (!excludeKeys) {
		excludeKeys = [];
	    }

	    excludeKeys.forEach((fieldKey) => {
		if (typeof first[fieldKey] !== 'undefined') {
		    delete first[fieldKey];
		}
		if (typeof second[fieldKey] !== 'undefined') {
		    delete second[fieldKey];
		}
	    });

            // If first and second are the same type and have the same value
            // Useful if strings or other primitive types are compared
            if (first === second) return true;

            // Try a quick compare by seeing if the length of properties are the same
            let firstProps = Object.getOwnPropertyNames(first);
            let secondProps = Object.getOwnPropertyNames(second);

            // Check different amount of properties
            if (firstProps.length !== secondProps.length) return false;

            // Go through properties of first object
            for (var i = 0; i < firstProps.length; i++) {
                let prop = firstProps[i];
                // Check the type of property to perform different comparisons
                switch (typeof (first[prop])) {
                    // If it is an object, decend for deep compare
                    case 'object':
                        if (!this.deepIsEqual(first[prop], second[prop])) return false;
                        break;
                    case 'number':
                        // with JavaScript NaN != NaN so we need a special check
                        if (isNaN(first[prop]) && isNaN(second[prop])) break;
                    default:
                        if (first[prop] !== second[prop]) return false;
                }
            }
            return true;
        },
        isStateAbbreviationExists(countryAbbreviation, stateAbbreviation) {
            if (!countryAbbreviation || !stateAbbreviation) {
                return false;
            }

            if (typeof this.defaultStatesList[countryAbbreviation] !== 'undefined') {
                var states = this.defaultStatesList[countryAbbreviation];
                for (var index = 0; index < states.length; index++) {
                    if (typeof states[index].value !== 'undefined' && states[index].value === stateAbbreviation) {
                        return true;
                    }
                }
            }

            return false;
        },
	wcPrice(value, settings) {
	    return this.$options.filters.formatWcPrice(value, Object.assign({},  this.$store.state.add_order.cart.wc_price_settings, (settings || {})));
	},
	ucwordsAddress(str) {
	    var upperCase = ['N', 'S', 'E', 'W', 'NE', 'NW', 'SE', 'SW'];
	    return str.toLowerCase().split(' ').map(function ( s ) {
		if (upperCase.indexOf(s.toUpperCase()) > -1 || s.length < 2) {
		    return s.toUpperCase();
		}
		return s.charAt(0).toUpperCase() + s.slice(1);
	    } ).join(' ');
	},
	validateAddressByUSPS(address, successCallback, errorCallback) {

	    var usps_user_id = this.getSettingsOption('address_validation_service_api_key');

	    if (address.country !== 'US' || !usps_user_id) {
		successCallback(address);
		return;
	    }

	    var encodeHTML = function (string) {
		return string.replace(/&/g, '&amp;')
			   .replace(/</g, '&lt;')
			   .replace(/>/g, '&gt;')
			   .replace(/"/g, '&quot;')
			   .replace(/'/g, '&apos;');
	    };

	    var str = '';

	    str += '<ZipCodeLookupRequest USERID="' + encodeHTML(usps_user_id) + '"><Address ID="1">';
	    str += '<Address1>' + encodeHTML(address.street1.replace("#", '')) + '</Address1>';
	    str += '<Address2>' + encodeHTML(address.street2.replace("#", '')) + '</Address2>';
	    str += '<City>' + encodeHTML(address.city) + '</City>';
	    str += '<State>' + encodeHTML(address.state) + '</State>';
	    str += '<Zip5>' + encodeHTML(address.zip) + '</Zip5>';
	    str += '<Zip4></Zip4></Address></ZipCodeLookupRequest>';

	    this.axios.get('https://secure.shippingapis.com/ShippingAPI.dll', {params: {
		API: 'ZipCodeLookup',
		XML: str,
            }}).then( ( response ) => {

		var xmlDoc = null;

		if (window.DOMParser) {
		    var parser = new DOMParser();
		    xmlDoc = parser.parseFromString(response.data, "text/xml");
		}
		else if (window.ActiveXObject) { // Internet Explorer
		    xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
		    xmlDoc.async = false;
		    xmlDoc.loadXML(response.data);
		} else {
		    throw new Error("No XML parser found");
		}

		if (!xmlDoc) {
		    throw new Error("XML parse error");
		}

		if (xmlDoc.getElementsByTagName("Error").length) {
		    var error = PhoneOrdersData.usps_label + ': ' + xmlDoc.getElementsByTagName("Description")[0].childNodes[0].nodeValue;
		    errorCallback(error);
		    return;
		}

		var zip = xmlDoc.getElementsByTagName("Zip5")[0].childNodes[0].nodeValue;

		if (xmlDoc.getElementsByTagName("Zip4").length && xmlDoc.getElementsByTagName("Zip4")[0].childNodes.length) {
		    zip += '-' + xmlDoc.getElementsByTagName("Zip4")[0].childNodes[0].nodeValue;
		}

		var street1 = '';
		var street2 = '';

		if (xmlDoc.getElementsByTagName("Address1").length) {
		    street1 = xmlDoc.getElementsByTagName("Address1")[0].childNodes[0].nodeValue;
		}

        if (xmlDoc.getElementsByTagName("Address2").length) {
            street2 = xmlDoc.getElementsByTagName("Address2")[0].childNodes[0].nodeValue;
        }


		var validated_address = {
			// SWAP them!
		    street1: this.ucwordsAddress(street2),
		    street2: this.ucwordsAddress(street1),
		    city: this.ucwordsAddress(xmlDoc.getElementsByTagName("City")[0].childNodes[0].nodeValue),
		    state: xmlDoc.getElementsByTagName("State")[0].childNodes[0].nodeValue,
		    zip: zip,
		};

		successCallback(validated_address);

            }, (error) => {});
	},
    }
} );

axios.interceptors.response.use( function ( response ) {
	if ( response.data.unexpected_output ) {
		console.log( response.data.unexpected_output )
	}
	return response;
} );

// Main Vue instance that bootstraps the frontend.
new Vue( {
    el: '#phone-orders-app',
    data: {
        bus: new Vue({}),
        defaultCountriesList: [],
        defaultStatesList: [],
        locale: '',
    },
    created: function () {
        this.$root.$on('changed::tab', (instance, val, tab) => {

            if ( typeof tab['$children'][0] !== 'undefined') {
                if ( typeof tab['$children'][0].update !== 'undefined') {
                    tab['$children'][0].update();
                }
            }

            window.location = tab.href;
        });

        window.addEventListener('hashchange', () => {

            var hash = this.getWindowLocationHash();

            this.$refs.tabs.tabs.forEach((tab, index) => {
                if (tab.href === hash) {
                    this.$refs.tabs.setTab(index);
                }
            });
        });

        this.$root.bus.$on(['settings-loaded', 'settings-saved'], () => {
	    this.loadCountryAndStatesList();
            this.locale = this.$el.dataset['locale'];
        });

	this.$store.init(this);

        var initAutocomplete = () => {

            var self = this;

            this.registerGoogleMapJs(this.getSettingsOption('google_map_api_key'),  () => {
                var service = new google.maps.places.AutocompleteService();

                service.getQueryPredictions({input: 'pizza near Syd'}, function (predictions, status) {
                    var success = false;

                    if (status === google.maps.places.PlacesServiceStatus.OK) {
                        success = true;
                    } else if (status === google.maps.places.PlacesServiceStatus.REQUEST_DENIED) {
                        //alert( 'ERROR: Access denied' );
                    } else {
                        //alert( 'ERROR: Error occured accessing the API.' );
                    }

                    self.bus.$emit('google-map-autocomplete-ready', {status: success});
                });
            }, () => {
                self.bus.$emit('google-map-autocomplete-ready', {status: false});
            });
        };

        this.$root.bus.$on(['settings-saved'], initAutocomplete);
        document.addEventListener('DOMContentLoaded', initAutocomplete);
    },
    mounted () {
        this.loadAllSettings(JSON.parse(this.$el.dataset['allSettings']));
        this.bus.$emit('app-loaded');
    },
    watch: {
        cart: {
            handler: function (newVal, oldVal) {
                if (this.$store.state.add_order.force_cart_set ) {
	                this.$store.commit('add_order/setForceCartSet', 0);
	                this.$store.commit('add_order/setCartParamsChangedByBackend', 0);
                    return;
                }
                console.log('changed')

                let localOldVal = JSON.parse(JSON.stringify(oldVal));
                let localNewVal = JSON.parse(JSON.stringify(newVal));

                let listen = ['items', 'coupons', 'customer', 'discount', 'shipping','shippings', 'fee', 'payment_method', 'shipping_custom_price'];

//                let excludeItemObjectKeys = ['custom_meta_fields', 'loaded_product', 'rand', 'key', 'original_price', 'item_cost' ];
                let excludeItemObjectKeys = ['custom_meta_fields' ];

                let newValListen = {};

                for (let key in Object.assign({}, localNewVal)) {
                    if (listen.indexOf(key) !== -1) {
                        newValListen[key] = localNewVal[key];
                    }
                }

                let oldValListen = {};

                for (let key in Object.assign({}, localOldVal)) {
                    if (listen.indexOf(key) !== -1) {
                        oldValListen[key] = localOldVal[key];
                    }
                }

                oldValListen['items'].forEach((item) => {
                    excludeItemObjectKeys.forEach((key) => {
                        typeof item[key] !== 'undefined' && delete item[key];
                    });
                });

                newValListen['items'].forEach((item) => {
                    excludeItemObjectKeys.forEach((key) => {
                        typeof item[key] !== 'undefined' && delete item[key];
                    });
                });

                console.log(oldValListen, newValListen)

                console.log('change')

                if (JSON.stringify(newValListen) === JSON.stringify(oldValListen) || this.$store.state.add_order.cart_params_changed_by_backend) {
                    this.$store.commit('add_order/setCartParamsChangedByBackend', 0);
                    return;
                }

                console.log('emit')

                this.bus.$emit('recalculate-cart');
            },
            deep: true,
        },
    },
    computed: {
        cart: function () {
            return this.$store.state.add_order.cart;
        },
    },
    methods: {
        loadCountryAndStatesList () {
            this.axios.get(this.url, {params: {
                action: 'phone-orders-for-woocommerce',
                method: 'get_countries_and_states_list',
                tab: 'add-order',
                wpo_cache_references_key: this.getSettingsOption('cache_references_session_key'),
            }}).then( ( response ) => {
                this.defaultCountriesList = response.data.data.countries_list;
                this.defaultStatesList    = response.data.data.states_list;
            });
        },
        loadAllSettings(settings) {
            this.setAllSettings(settings);
            this.bus.$emit('settings-loaded');
        },
    },
    components,
    store,
} );

