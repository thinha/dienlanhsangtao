<template>
    <tr v-show="shown">
        <td colspan=2>
            <table class="form-table">
                <tbody>
                    <tr>
                        <td colspan=2>
                            <b>{{ title }}</b>
                        </td>
                    </tr>
                    <tr>
			<td>
                            {{ cacheCouponSearchResultHoursLabel }}
                        </td>
			<td>
                            <input type="hidden" name="cache_coupons_session_key" v-model="sessionKey">
                            <input type="hidden" name="cache_coupons_reset" id="cache_coupons_reset" v-model="cacheCouponsReset">
                            <input type="number" class="option_hours" v-model.number="timeout" id="cache_coupons_timeout" name="cache_coupons_timeout" min=0>
                            {{ hoursLabel }}
                            <span v-if="timeout">
                                <button id="cache_coupons_disable_button" @click="disableCache" class="btn btn-primary">
                                    {{ cacheCouponsDisableButtonLabel }}
                                </button>
                                <button id="cache_coupons_reset_button" @click="resetCache" class="btn btn-danger">
                                    {{ cacheCouponsResetButtonLabel }}
                                </button>
                            </span>
			</td>
                    </tr>

                    <slot name="pro-coupons-settings"></slot>
                </tbody>
            </table>
        </td>
    </tr>
</template>

<script>
    export default {
        created () {
            this.$root.bus.$on('settings-saved', this.onSettingsSaved);
        },
        props: {
            title: {
                default: function() {
                    return 'Coupons';
                },
            },
	    tabKey: {
                default: function() {
                    return 'couponsSettings';
                },
            },
            hoursLabel: {
                default: function() {
                    return 'hours';
                },
            },
            cacheCouponSearchResultHoursLabel: {
                default: function() {
                    return 'Caching search results';
                },
            },
            cacheCouponsDisableButtonLabel: {
                default: function() {
                    return 'Disable cache';
                },
            },
            cacheCouponsResetButtonLabel: {
                default: function() {
                    return 'Reset cache';
                },
            },
            cacheCouponsSessionKey: {
                default: function() {
                    return '';
                },
            },
            cacheCouponsTimeout: {
                default: function() {
                    return 0;
                },
            },
        },
        data () {
            return {
                sessionKey: this.cacheCouponsSessionKey,
                timeout: +this.cacheCouponsTimeout,
                cacheCouponsReset: 0,
		shown: false,
            };
        },
        methods: {
            disableCache () {
                this.timeout = 0;
                this.saveSettingsByEvent();
            },
            resetCache () {
                this.cacheCouponsReset = 1;
                this.saveSettingsByEvent();
            },
            getSettings() {

                var settings = {
                    cache_coupons_session_key: this.sessionKey,
                    cache_coupons_timeout: this.timeout,
                    cache_coupons_reset: this.cacheCouponsReset,
                };

                var childsSettings = {};

                this.$children.forEach(function (child) {
                    if (typeof child.getSettings === 'function') {
                        childsSettings = Object.assign(childsSettings, child.getSettings());
                    }
                });

                return Object.assign(settings, childsSettings);
            },
            onSettingsSaved (settings) {
                this.sessionKey         = settings.cache_coupons_session_key;
                this.cacheCouponsReset  = settings.cache_coupons_reset;
            },
	    getTabsHeaders() {
                return {
		    key: this.tabKey,
		    title: this.title,
		};
            },
            showOption(key) {
                this.shown = this.tabKey === key;
            },
        },
    }
</script>