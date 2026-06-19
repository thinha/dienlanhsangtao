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
                            {{ cacheReferencesHoursLabel }}
                        </td>
			<td>
                            <input type="hidden" name="cache_references_session_key" v-model="sessionKey">
                            <input type="hidden" name="cache_references_reset" id="cache_references_reset" v-model="cacheReferencesReset">
                            <input type="number" class="option_hours" v-model.number="timeout" id="cache_references_timeout" name="cache_references_timeout" min=0>
                            {{ hoursLabel }}
                            <span v-if="timeout">
                                <button id="cache_references_disable_button" @click="disableCache" class="btn btn-primary">
                                    {{ cacheReferencesDisableButtonLabel }}
                                </button>
                                <button id="cache_references_reset_button" @click="resetCache" class="btn btn-danger">
                                    {{ cacheReferencesResetButtonLabel }}
                                </button>
                            </span>
			</td>
                    </tr>
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
                    return 'References';
                },
            },
	    tabKey: {
                default: function() {
                    return 'referencesSettings';
                },
            },
            hoursLabel: {
                default: function() {
                    return 'hours';
                },
            },
            cacheReferencesHoursLabel: {
                default: function() {
                    return 'Caching locations/categories/tags';
                },
            },
            cacheReferencesDisableButtonLabel: {
                default: function() {
                    return 'Disable cache';
                },
            },
            cacheReferencesResetButtonLabel: {
                default: function() {
                    return 'Reset cache';
                },
            },
            cacheReferencesSessionKey: {
                default: function() {
                    return '';
                },
            },
            cacheReferencesTimeout: {
                default: function() {
                    return 0;
                },
            },
        },
        data () {
            return {
                sessionKey: this.cacheReferencesSessionKey,
                timeout: +this.cacheReferencesTimeout,
                cacheReferencesReset: 0,
		shown: false,
            };
        },
        methods: {
            disableCache () {
                this.timeout = 0;
                this.saveSettingsByEvent();
            },
            resetCache () {
                this.cacheReferencesReset = 1;
                this.saveSettingsByEvent();
            },
            getSettings () {
                return {
                    cache_references_session_key: this.sessionKey,
                    cache_references_timeout: this.timeout,
                    cache_references_reset: this.cacheReferencesReset,
                };
            },
            onSettingsSaved (settings) {
                this.sessionKey         = settings.cache_references_session_key;
                this.cacheReferencesReset  = settings.cache_references_reset;
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