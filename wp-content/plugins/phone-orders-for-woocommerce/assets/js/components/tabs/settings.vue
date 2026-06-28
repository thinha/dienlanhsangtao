<template>
  <div class="phone-orders-woocommerce_tab-settings phone-orders-woocommerce__tab">
    <div v-show="isRunRequest" class="tab-loader">
      <loader></loader>
    </div>
    <div class="pow-settings-search-wrapper">
      <div class="pow-settings-search">
        <span class="dashicons dashicons-search"></span>
        <input type="text" :placeholder="searchTitle" v-model="searchQuery" @input="onSearchInput">
        <span class="dashicons dashicons-dismiss" @click="clearSearch"></span>
      </div>
    </div>
    <div class="form-settings" ref="settingsContainer">
      <div class="tabs">
            <span v-for="(value, index) in localTabsHeaders">
                <a v-on:click="showSubTab(value.key)" v-bind:class="[ activatedTabKey === value.key ? 'active' : '' ]">{{ value.title }}</a>
            </span>
      </div>
      <table class="form-table">
        <tbody>
        <slot name="base-settings"></slot>
        <need-more-settings v-if="!isProVersion" v-bind="needMoreSettings"></need-more-settings>
        <slot name="pro-settings"></slot>
        </tbody>
      </table>
    </div>
    <hr/>
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

.pow-settings-search-wrapper {
  display: flex;
  justify-content: flex-end;
  margin-top: 0px;
  margin-bottom: -40px;
}

.tabs {
  display: grid;
  margin-right: 10px;
}

.tabs span {
  white-space: nowrap
}

.form-settings {
  display: flex;
  align-items: flex-start;
}

.form-settings .form-table b {
  font-size: 1.3em;
}

.form-settings .tabs {
  margin-top: 0.8rem;
}

.form-settings .form-table {
  margin-top: 0 !important;
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

#phone-orders-app .phone-orders-woocommerce_tab-settings .phone-orders-woocommerce__radio > * + * {
  margin-left: 1rem;
}

.pow-settings-search {
  position: relative;
}

.pow-settings-search input {
  padding: 0 30px;
}

.pow-settings-search .dashicons-search {
  position: absolute;
  top: 5px;
  left: 5px;
  color: #777;
}

.pow-settings-search .dashicons-dismiss {
  position: absolute;
  top: 7px;
  right: 5px;
  cursor: pointer;
  font-size: 15px;
  color: #777;
}

.pow-settings-search .hide {
  display: none;
}

.search-hidden {
  display: none !important;
}

.search-match {
  background: yellow;
  font-weight: bold;
}

</style>

<script>

var loader = require('vue-spinner/dist/vue-spinner.min').ClipLoader;

import NeedMoreSettings from './tab-settings/need_more_settings.vue';

export default {
  created() {
    this.$root.bus.$on('save-settings', this.saveSettings);
  },
  props: {
    submitButtonTitle: {
      default: function () {
        return 'Save Changes';
      }
    },
    searchTitle: {
      default: function () {
        return 'Search';
      }
    },
    requestSuccessResultMessage: {
      default: function () {
        return 'Settings have been updated';
      }
    },
    requestErrorResultMessage: {
      default: function () {
        return 'Settings have not been updated';
      }
    },
    tabName: {
      default: function () {
        return '';
      }
    },
    isProVersion: {
      default: function () {
        return false;
      }
    },
    needMoreSettings: {
      default: function () {
        return {};
      }
    },
  },
  mounted: function () {
    this.showSubTab(this.localTabsHeaders[0].key);
  },
  data: function () {
    return {
      isRunRequest: false,
      requestStatus: null,
      searchQuery: '',
      _searchDebounceTimer: null,
    };
  },
  computed: {
    localTabsHeaders() {
      return this.getSettingsTabs();
    },
    activatedTabKey() {
      return this.getSettingsCurrentTab();
    },
  },
  methods: {
    clearSearch() {
      this.searchQuery = '';

      this.setSearchMode(false);
      this.setMatchedTabs([]);

      this.performSearch();
    },
    onSearchInput() {
      clearTimeout(this._searchDebounceTimer);
      this._searchDebounceTimer = setTimeout(() => this.performSearch(), 250);
    },
    performSearch() {
      const q = (this.searchQuery || '').trim();
      const container = this.$el;

      if (!container) {
        return;
      }

      const escapeRegExp = (s) =>
        s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

      const clearHighlights = (root) => {
        root.querySelectorAll('.search-match').forEach(span => {
          span.outerHTML = span.textContent;
        });
      };

      const getSearchableText = (el) => {
        const clone = el.cloneNode(true);
        clone.querySelectorAll('[data-search-ignore]').forEach(node => {
          node.remove();
        });
        return clone.textContent || '';
      };

      container.querySelectorAll('tr').forEach(tr => {
        tr.classList.remove('search-hidden');
        clearHighlights(tr);
      });

      const rows = Array.from(
        container.querySelectorAll('[data-tab-key]')
      );

      if (!q) {

        this.setSearchMode(false);
        this.setMatchedTabs([]);

        if (this.localTabsHeaders.length) {
          this.showSubTab(this.localTabsHeaders[0].key);
        }

        return;
      }

      const testRe = new RegExp(escapeRegExp(q), 'i');
      const highlightRe = new RegExp(escapeRegExp(q), 'ig');

      const highlightInElement = (el) => {
        const walker = document.createTreeWalker(
          el,
          NodeFilter.SHOW_TEXT,
          null,
          false
        );

        const textNodes = [];
        let node;

        while ((node = walker.nextNode())) {
          if (node.parentElement?.closest('[data-search-ignore]')) {
            continue;
          }

          if (!node.nodeValue.trim()) {
            continue;
          }

          if (testRe.test(node.nodeValue)) {
            textNodes.push(node);
          }
        }

        textNodes.forEach(n => {
          const wrapper = document.createElement('span');

          wrapper.innerHTML = n.nodeValue.replace(
            highlightRe,
            '<span class="search-match">$&</span>'
          );

          n.parentNode.replaceChild(wrapper, n);
        });
      };

      const matchedTabs = new Set();

      rows.forEach(tr => {

        const text = getSearchableText(tr);

        if (testRe.test(text)) {

          highlightInElement(tr);

          if (tr.dataset.tabKey) {
            matchedTabs.add(tr.dataset.tabKey);
          }

        } else {

          tr.classList.add('search-hidden');
        }
      });

      container.querySelectorAll('tr:not([data-tab-key])').forEach(tr => {

        const isHeader = !!tr.querySelector('b');

        if (isHeader) {
          return;
        }

        if (!tr.querySelector('.search-match')) {
          tr.classList.add('search-hidden');
        }
      });

      const tabs = [...matchedTabs];

      if (tabs.length) {

        this.setMatchedTabs(tabs);
        this.setSearchMode(true);

      } else {

        this.setMatchedTabs([]);
        this.setSearchMode(false);
      }
    },
    getTabsHeaders: function () {

      var headers = [];

      this.$children.forEach(function (child) {
        if (typeof child.getTabsHeaders === 'function') {
          headers = headers.concat(child.getTabsHeaders());
        }
      });

      return headers;
    },
    showSubTab: function (key) {
      this.setSettingsCurrentTab(key)
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
      var settings = this.getComponentsSettings();

      this.axios.post(this.url, this.qs.stringify({
        tab: this.tabName,
        _wpnonce: this.nonce,
        action: 'phone-orders-for-woocommerce',
        method: 'save_settings',
        settings: JSON.stringify(settings),
        nonce: this.nonce,
      })).then((response) => {

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

        this.isRunRequest = false;
        this.requestStatus = true;

        setTimeout(() => {
          this.requestStatus = null;
        }, 3000);

      }, () => {

        this.isRunRequest = false;
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
