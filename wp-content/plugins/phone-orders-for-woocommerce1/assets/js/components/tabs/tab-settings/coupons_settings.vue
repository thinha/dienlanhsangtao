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
            {{ cacheCouponSearchResultHoursLabel }}
          </td>
          <td>
            <input type="hidden" name="cache_coupons_session_key" v-model="sessionKey">
            <input type="hidden" name="cache_coupons_reset" id="cache_coupons_reset" v-model="cacheCouponsReset">
            <input type="number" class="option_hours" v-model.number="timeout" id="cache_coupons_timeout"
                   name="cache_coupons_timeout" min=0>
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
  created() {
    this.$root.bus.$on('settings-saved', this.onSettingsSaved);
  },
  props: {
    title: {
      default: function () {
        return 'Coupons';
      },
    },
    tabKey: {
      default: function () {
        return 'couponsSettings';
      },
    },
    hoursLabel: {
      default: function () {
        return 'hours';
      },
    },
    readDocsTitle:{
      default: function () {
        return 'Read docs';
      },
    },
    docLink:{
      default: function () {
        return '';
      },
    },
    cacheCouponSearchResultHoursLabel: {
      default: function () {
        return 'Caching search results';
      },
    },
    cacheCouponsDisableButtonLabel: {
      default: function () {
        return 'Disable cache';
      },
    },
    cacheCouponsResetButtonLabel: {
      default: function () {
        return 'Reset cache';
      },
    },
    cacheCouponsSessionKey: {
      default: function () {
        return '';
      },
    },
    cacheCouponsTimeout: {
      default: function () {
        return 0;
      },
    },
  },
  mounted() {
    this.addSettingsTab(this.getTabsHeaders())
    this.setComponentsSettings(this.componentsSettings)
  },
  data() {
    return {
      sessionKey: this.cacheCouponsSessionKey,
      timeout: +this.cacheCouponsTimeout,
      cacheCouponsReset: 0,
    };
  },
  watch: {
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
    disableCache() {
      this.timeout = 0;
      this.saveSettingsByEvent();
    },
    resetCache() {
      this.cacheCouponsReset = 1;
      this.saveSettingsByEvent();
    },
    getSettings() {

      var settings = {
        cache_coupons_session_key: this.sessionKey,
        cache_coupons_timeout: this.timeout,
        cache_coupons_reset: this.cacheCouponsReset,
      };

      return settings;
    },
    onSettingsSaved(settings) {
      this.sessionKey = settings.cache_coupons_session_key;
      this.cacheCouponsReset = settings.cache_coupons_reset;
    },
    getTabsHeaders() {
      return {
        key: this.tabKey,
        title: this.title,
        menu_order: 140,
      };
    },
    showOption(key) {
      this.shown = this.tabKey === key;
    },
  },
}
</script>
