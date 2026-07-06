import {createApp} from 'vue'
import {store, mixins as store_mixins} from './store'
import components from './components'
import axios from 'axios'
import qs from 'qs'

import emitter from 'tiny-emitter/instance'

var busEvent = {
    $on: (...args) => emitter.on(...args),
    $once: (...args) => emitter.once(...args),
    $off: (...args) => emitter.off(...args),
    $emit: (...args) => emitter.emit(...args)
}

// Main Vue instance that bootstraps the frontend.
const app = createApp({
    data() {
        return {
            bus: busEvent,
            defaultCountriesList: [],
            defaultStatesList: [],
            locale: '',
        };
    },
    created: function () {

        /* this.$root.$on('changed::tab', (instance, val, tab) => {

             if ( typeof tab['$children'][0] !== 'undefined') {
                 if ( typeof tab['$children'][0].update !== 'undefined') {
                     tab['$children'][0].update();
                 }
             }

             window.location = tab.href;
         });*/

        var setActiveTab = () => {
            var hash = this.getWindowLocationHash();

            if (!this.$refs.tabs || !this.$refs.tabs.tabs) {
                return;
            }

            this.$refs.tabs.tabs.forEach((tab, index) => {
                if (tab.tab.props.href === hash) {
                    this.$refs.tabs.tabIndex = index;
                    this.$root.bus.$emit('open-tab', hash)
                }
            });
        }

        window.addEventListener('hashchange', () => {
            setActiveTab()
        });

        setTimeout(() => {
            setActiveTab()
        }, 0)

        var func = () => {
            this.loadCountryAndStatesList();
            this.locale = this.$el.parentNode.dataset['locale'];
        }

        this.$root.bus.$on('settings-loaded', func);
        this.$root.bus.$on('settings-saved', func);

        this.$store.init(this);

        var initAutocomplete = () => {
            var self = this;

            this.registerGoogleMapJs(this.getSettingsOption('google_map_api_key'), async () => {
                const { AutocompleteSuggestion } = await google.maps.importLibrary('places');

                let sessionToken = new google.maps.places.AutocompleteSessionToken();

                let request = {
                    input: 'pizza near Syd',
                    sessionToken: sessionToken,
                };

                try {
                    const { suggestions } = await google.maps.places.AutocompleteSuggestion.fetchAutocompleteSuggestions(request);

                    if (suggestions && suggestions.length > 0) {
                        self.bus.$emit('google-map-autocomplete-ready', { status: true });
                    } else {
                        self.bus.$emit('google-map-autocomplete-ready', { status: false });
                    }
                } catch (error) {
                    self.bus.$emit('google-map-autocomplete-ready', { status: false });
                }

            }, () => {
                self.bus.$emit('google-map-autocomplete-ready', {status: false});
            });
        };

        this.$root.bus.$on('settings-saved', initAutocomplete);
        document.addEventListener('DOMContentLoaded', initAutocomplete);

        this.setAllSettings(JSON.parse(window.wpo_settings));
    },
    mounted() {
        this.loadAllSettings(JSON.parse(this.$el.parentNode.dataset['allSettings']));
        this.bus.$emit('app-loaded');
    },
    watch: {
        cart: {
            handler: function (newVal, oldVal) {
                if (this.$store.state.add_order.force_cart_set) {
                    this.$store.commit('add_order/setForceCartSet', 0);
                    this.$store.commit('add_order/setCartParamsChangedByBackend', 0);
                    return;
                }
                console.log('changed')

                let localOldVal = JSON.parse(JSON.stringify(oldVal));
                let localNewVal = JSON.parse(JSON.stringify(newVal));

                let listen = ['items', 'coupons', 'customer', 'discount', 'shipping', 'shippings', 'fee', 'payment_method', 'shipping_custom_price', 'gift_card', 'order_currency', 'dont_apply_pricing_rules'];

                if (this.getSettingsOption('changing_custom_fields_forces_cart_update')) {
                    listen.push('custom_fields');
                }

//                let excludeItemObjectKeys = ['custom_meta_fields', 'loaded_product', 'rand', 'key', 'original_price', 'item_cost' ];
                let excludeItemObjectKeys = ['custom_name', 'original_price', 'removed_custom_meta_fields_keys'];

                if (this.getSettingsOption('dont_refresh_cart_item_item_meta')) {
                    excludeItemObjectKeys.push('custom_meta_fields');
                }

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
        loadCountryAndStatesList() {
            this.axios.get(this.url, {
                params: {
                    action: 'phone-orders-for-woocommerce',
                    method: 'get_countries_and_states_list',
                    tab: 'add-order',
                    wpo_cache_references_key: this.getSettingsOption('cache_references_session_key'),
                    nonce: this.nonce,
                }
            }).then((response) => {
                this.defaultCountriesList = response.data.data.countries_list;
                this.defaultStatesList = response.data.data.states_list;
            });
        },
        loadAllSettings(settings) {
            this.setAllSettings(settings);
            this.bus.$emit('settings-loaded');
        },
    },
    components,
});

app.use(store);

store_mixins.forEach((mixin) => {
    app.mixin(mixin)
})

//fix multiple Vue version on page, Bootstrap Vue uses window.Vue if it none empty
var oldVue = window.Vue;
window.Vue = undefined

//import BootstrapVue from 'bootstrap-vue'

window.Vue = oldVue

//app.use( BootstrapVue );

import BootstrapVue3 from 'bootstrap-vue-3'

// Optional, since every component import their Bootstrap functionality
// the following line is not necessary
// import 'bootstrap'

import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue-3/dist/bootstrap-vue-3.css'

app.use(BootstrapVue3)

import VueClipboard from 'vue3-clipboard'

app.use(VueClipboard, {
    autoSetContainer: true,
    appendToBody: true,
});

import storeSearchMultiselect from './directives/store-search-multiselect';

app.directive("store-search-multiselect", storeSearchMultiselect);

import checkDateDatetimepicker from './directives/check-date-datetimepicker';

app.directive("check-date-datetimepicker", checkDateDatetimepicker);

import numeral from "numeral"
import {add} from "lodash/math";

// Create a global mixin to expose strings, global config, and single backend resource.
app.mixin({
    computed: {
        nonce: function () {
            return PhoneOrdersData.nonce;
        },
        edd_wpo_nonce: function () {
            return PhoneOrdersData.edd_wpo_nonce;
        },
        search_customers_nonce: function () {
            return PhoneOrdersData.search_customers_nonce;
        },
        url: function () {
            return PhoneOrdersData.ajax_url;
        },
        base_cart_url: function () {
            return PhoneOrdersData.base_cart_url;
        },
        base_admin_url: function () {
            return PhoneOrdersData.base_admin_url;
        },
        axios: function () {
            return axios;
        },
        qs: function () {
            return qs;
        },
        modalDontCloseOnBackdropClick: function () {
            return !!this.getSettingsOption('dont_close_popup_click_outside');
        },
    },
    methods: {
        getObjectByKeyValue(arrayOfObjects, key, value, defaultValue) {

            var found = typeof defaultValue !== 'undefined' ? defaultValue : null;

            arrayOfObjects.forEach(function (obj) {
                if (obj[key] === value) {
                    found = obj;
                }
            });

            return found;
        },
        getKeyValueOfObject(object, key) {
            return object && typeof object === 'object' ? object[key] : object;
        },
        openModal(modalID) {
            console.log(this, this.$bvModal)
            this.$root.$emit('bv::show::modal', modalID);
        },
        saveSettingsByEvent: function () {
            this.$root.bus.$emit('save-settings');
        },
        getWindowLocationHash: function () {
            return window.location.hash;
        },
        clickTab(href) {

            //this.$root.bus.$emit('open-tab', href);

            this.$root.bus.$emit('changed::tab', href);

            window.location = href;
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

            scriptTag.innerHTML = `
                (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src='https://maps.' + c + 'apis.com/maps/api/js?'+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
                  key: "` + key + `",
                  v: "weekly",
                });
            `

            scriptTag.async = true;
            scriptTag.defer = true;

            document.body.appendChild(scriptTag);

            successCallback();
        },
        getUSPSToken(key, secret, handleResponse) {
            this.axios.post(this.url, this.qs.stringify({
                action: 'phone-orders-for-woocommerce',
                method: 'refresh_usps_token',
                tab: 'settings',
                nonce: this.nonce,
                credentials: {
                    client_id: key,
                    client_secret: secret
                }
            }))
                .then(handleResponse)
        },
        removeGetParameter(parameterName) {
            var result = null,
                clean_uri = null,
                tmp = [];

            location.search
                .substr(1)
                .split("&")
                .forEach(function (item) {
                    tmp = item.split("=");
                    if (tmp[0] === parameterName) {
                        result = decodeURIComponent(tmp[1]);
                        clean_uri = window.location.toString().replace("&" + tmp[0] + "=" + tmp[1], "");
                        clean_uri = clean_uri.replace(tmp[0] + "=" + tmp[1], "");
                        clean_uri = clean_uri.replace(/\?$/ig, "");
                    }
                });

            if (result && clean_uri) {
                window.history.replaceState({}, document.title, clean_uri)
            }
            return result;
        },
        getParameter(parameterName) {
            var result = null,
                tmp = [];

            location.search
                .substr(1)
                .split("&")
                .forEach(function (item) {
                    tmp = item.split("=");
                    if (tmp[0] === parameterName) {
                        result = decodeURIComponent(tmp[1]);
                        return result;
                    }
                });

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
        formatWcPrice(value, settings) {

            if (typeof value === 'undefined') {
                return value;
            }

            var price = numeral(value < 0 ? value * -1 : value)
                .format("0,0." + "0".repeat(settings.decimals));

            var tmp = price.split('.');

            price = tmp[0].replace(/,/g, settings.thousand_separator);

            if (tmp.length > 1) {
                price = price + settings.decimal_separator + tmp[1];
            }

            var formatted_price = (value < 0 ? '-' : '') +
                settings.price_format
                    .replace('%1$s', '<span class="woocommerce-Price-currencySymbol">' + settings.currency_symbol + '</span>')
                    .replace('%2$s', price);

            return '<span class="woocommerce-Price-amount amount">' + formatted_price + '</span>';
        },
        wcPrice(value, settings) {
            return this.formatWcPrice(value, Object.assign({}, this.$store.state.add_order.cart.wc_price_settings, (settings || {}), {currency_symbol: this.$store.state.add_order.cart.order_currency && this.getSettingsOption('show_order_currency_selector') ? this.$store.state.add_order.cart.order_currency.symbol : this.$store.state.add_order.cart.wc_price_settings.currency_symbol}));
        },
        ucwordsAddress(str) {
            var upperCase = ['N', 'S', 'E', 'W', 'NE', 'NW', 'SE', 'SW'];
            return str.toLowerCase().split(' ').map(function (s) {
                if (upperCase.indexOf(s.toUpperCase()) > -1 || s.length < 2) {
                    return s.toUpperCase();
                }
                return s.charAt(0).toUpperCase() + s.slice(1);
            }).join(' ');
        },
        validateAddressByUSPS(address, successCallback, errorCallback) {
            let usps_key = this.getSettingsOption("address_validation_usps_key");
            let usps_secret = this.getSettingsOption("address_validation_usps_secret");

            if (address.country !== 'US' || !usps_key || !usps_secret) {
                successCallback(address);
                return;
            }

            this.axios.post(this.url, this.qs.stringify({
                action: 'phone-orders-for-woocommerce',
                method: 'get_validated_address_usps',
                tab: 'add-order',
                nonce: this.nonce,
                address: address
            })).then(res => {
                let resData = res.data.data;

                if (res.data.success) {
                    let formattedZip = resData.ZIPCode;

                    if (resData.hasOwnProperty('ZIPPlus4') && resData.ZIPPlus4.length > 0) {
                        formattedZip += '-' + resData.ZIPPlus4
                    }

                    let validatedAddress = {
                        street1: this.ucwordsAddress(resData.streetAddress),
                        street2: this.ucwordsAddress(resData.secondaryAddress),
                        city: this.ucwordsAddress(resData.city),
                        state: resData.state,
                        zip: formattedZip
                    }

                    successCallback(validatedAddress)
                } else {
                    let regex = /failed with reason:(.+?)$/
                    if (regex.test(resData)) {
                        resData = regex.exec(resData)[1]
                        resData = resData.replaceAll(/[\[|\]:]/g, '')
                    }
                    let error = PhoneOrdersData.usps_label + ": " + resData
                    errorCallback(error);
                }
            });
        },
        clearCartParam(cart) {

            var cartData = Object.assign({}, cart);

            if (cartData.items) {
                cartData.items.forEach((item) => {
                    delete item.product_price_html;
                })
            }

            return cartData;
        },
        formatPrice(value, precision) {

            if (typeof value === 'undefined') {
                return value;
            }

            var _precision = typeof precision !== 'undefined' ? +precision : 2;
            return numeral(value).format("0." + "0".repeat(_precision));
        },
    },
});

axios.interceptors.response.use(function (response) {
    if (response.data.unexpected_output) {
        console.log(response.data.unexpected_output)
    }
    return response;
});

app.mount('#phone-orders-app');
