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
            {{ showIconInOrdersListLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="tmpShowIconInOrdersList" name="show_icon_in_orders_list">
          </td>
        </tr>

        <slot name="pro-woocommerce-settings"></slot>

        </tbody>
      </table>
    </td>
  </tr>
</template>

<style>


</style>

<script>

export default {
  props: {
    title: {
      default: function () {
        return 'WooCommerce';
      },
    },
    tabKey: {
      default: function () {
        return 'woocommerceSettings';
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
    showIconInOrdersListLabel: {
      default: function () {
        return 'Show icon for phone orders in orders list';
      },
    },
    showIconInOrdersList: {
      default: function () {
        return false;
      },
    },
  },
  mounted() {
    this.addSettingsTab(this.getTabsHeaders())
    this.setComponentsSettings(this.componentsSettings)
  },
  data() {
    return {
      tmpShowIconInOrdersList: this.showIconInOrdersList,
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
    getSettings() {

      var settings = {
        show_icon_in_orders_list: this.tmpShowIconInOrdersList,
      };

      return settings;
    },
    getTabsHeaders() {
      return {
        key: this.tabKey,
        title: this.title,
        menu_order: 40,
      };
    },
    showOption(key) {
      this.shown = this.tabKey === key;
    },
  },
}
</script>
