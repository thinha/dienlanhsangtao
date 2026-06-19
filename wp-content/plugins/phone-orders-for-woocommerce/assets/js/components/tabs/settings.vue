<template>
    <div class="phone-orders-woocommerce_tab-settings phone-orders-woocommerce__tab">
        <div v-show="isRunRequest" class="tab-loader">
            <loader></loader>
        </div>
        <div class="tabs">
            <span v-for="(value, index) in localTabsHeaders">
                <a v-on:click="showSubTab(value.key)" v-bind:class="[ activatedTabKey === value.key ? 'active' : '' ]">{{value.title}}</a>
                <span v-if="index != localTabsHeaders.length - 1"> | </span>
            </span>
        </div>
        <table class="form-table">
            <tbody>
		<slot name="base-settings"></slot>
                <need-more-settings v-if="!isProVersion" v-bind="needMoreSettings"></need-more-settings>
                <slot name="pro-settings"></slot>
                <hr/>
            </tbody>
        </table>
        <p>
            <button type="submit" class="btn btn-primary" @click="saveSettings">
                {{ submitButtonTitle }}
            </button>
            <b-alert :show="requestStatus === true" fade variant="success" class="success-alert">
                {{ this.requestSuccessResultMessage }}
            </b-alert>
            <b-alert :show="requestStatus === false" fade variant="danger" class="error-alert">
                {{ this.requestErrorResultMessage }}
            </b-alert>
        </p>
    </div>
</template>

<style>

    #phone-orders-app .form-table td {
        padding: 5px 0;
    }

    #phone-orders-app .phone-orders-woocommerce_tab-settings .tabs a {
        line-height: 2;
        padding: .2em;
        text-decoration: none;
        cursor: pointer;
        color: #007bff;
    }

    #phone-orders-app .phone-orders-woocommerce_tab-settings .tabs a.active {
        font-weight: bold;
        color: #000;
    }

    #phone-orders-app .phone-orders-woocommerce_tab-settings .alert {
        display: inline-block;
        margin-left: 15px;
        padding: 0.25rem 1.25rem;
        margin-bottom: 0;
        vertical-align: middle;
    }
</style>

<script>

    var loader = require('vue-spinner/dist/vue-spinner.min').ClipLoader;

    import NeedMoreSettings from './tab-settings/need_more_settings.vue';

    export default {
        created () {
            this.$root.bus.$on('save-settings', this.saveSettings);
        },
        props: {
            submitButtonTitle: {
                default: function() {
                    return 'Save Changes';
                }
            },
            requestSuccessResultMessage: {
                default: function() {
                    return 'Settings have been updated';
                }
            },
            requestErrorResultMessage: {
                default: function() {
                    return 'Settings have not been updated';
                }
            },
            tabName: {
                default: function() {
                    return '';
                }
            },
            isProVersion: {
                default: function() {
                    return false;
                }
            },
            needMoreSettings: {
                default: function() {
                    return {};
                }
            },
        },
        mounted: function () {
            this.localTabsHeaders = this.getTabsHeaders();
	    this.showSubTab(this.localTabsHeaders[0].key);
        },
        data: function () {
            return {
                isRunRequest: false,
                requestStatus: null,
	            localTabsHeaders: [],
	            activatedTabKey: '',
            };
        },
        methods: {
	    getTabsHeaders: function (  ) {

		var headers = [];

		this.$children.forEach(function (child) {
			if ( typeof child.getTabsHeaders === 'function' ) {
			    headers = headers.concat(child.getTabsHeaders());
			}
		});

		return headers;
	    },
	    showSubTab: function (key) {

		this.activatedTabKey = key;

		this.$children.forEach(function (child) {
		    if ( typeof child.showOption === 'function' ) {
			child.showOption(key);
		    }
		});
            },
            getSettings: function () {

                var settings = {};

                this.$children.forEach(function (child) {
                    settings = Object.assign(settings, typeof child.getSettings === 'function' ? child.getSettings() : {});
                });

                return settings;
            },
            saveSettings: function () {

                this.isRunRequest = true;
                var settings      = this.getSettings();

                this.axios.post( this.url, this.qs.stringify({
                    tab: this.tabName,
                    _wpnonce: this.nonce,
                    action: 'phone-orders-for-woocommerce',
                    method: 'save_settings',
                    settings: JSON.stringify(settings),
                })).then( ( response ) => {

                    var _s = response.data.data.settings;

                    settings = Object.assign(settings, {
                        cache_customers_session_key: _s.cache_customers_session_key,
                        cache_products_session_key: _s.cache_products_session_key,
                        cache_orders_session_key: _s.cache_orders_session_key,
                        cache_coupons_session_key: _s.cache_coupons_session_key,
                        cache_references_session_key: _s.cache_references_session_key,
                        cache_customers_reset: 0,
                        cache_products_reset: 0,
                        cache_orders_reset: 0,
                        cache_coupons_reset: 0,
                        customer_custom_fields: _s.customer_custom_fields,
                        order_custom_fields: _s.order_custom_fields,
                    });

                    this.setAllSettings(settings);
                    this.$root.bus.$emit('settings-saved', settings);

                    this.isRunRequest  = false;
                    this.requestStatus = true;

                    setTimeout(() => {
                        this.requestStatus = null;
                    }, 3000);

                }, () => {

                    this.isRunRequest  = false;
                    this.requestStatus = false;

                    setTimeout(() => {
                        this.requestStatus = null;
                    }, 3000);
                });

            },
        },
        components: {
            loader,
            NeedMoreSettings,
        },
    }
</script>