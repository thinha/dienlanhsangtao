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
            {{ allowEditShippingCostLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="elAllowEditShippingCost" name="allow_edit_shipping_cost">
          </td>
        </tr>
        <tr>
          <td>
            {{ allowEditShippingTitleLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="elAllowEditShippingTitle" name="allow_edit_shipping_title">
          </td>
        </tr>

        <tr>
          <td>
            {{ orderDefaultShippingMethodLabel }}
          </td>
          <td>
            <table style="width: 100%">
              <tr v-for="zone in orderShippingZonesList">
                <td>
                  {{ zone.title }}
                </td>
                <td>
                  <multiselect
                    :allow-empty="false"
                    :searchable="false"
                    style="width: 100%;max-width: 800px;"
                    label="title"
                    v-model="elOrderDefaultZonesShippingMethod[zone.id]"
                    :options="zone.shipping_methods"
                    track-by="value"
                    :show-labels="false"
                  >
                    <template v-slot:noOptions>
                        <span>
                            <span v-html="noOptionsTitle"></span>
                        </span>
                    </template>
                  </multiselect>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <tr>
          <td>
            {{ allowToCreateOrdersWithoutShippingLabel }}
          </td>
          <td>
            <input type="checkbox" class="option" v-model="elAllowToCreateOrdersWithoutShipping"
                   name="allow_to_create_orders_without_shipping">
          </td>
        </tr>

        <slot name="pro-shipping-settings"></slot>

        </tbody>
      </table>
    </td>
  </tr>
</template>

<style>


</style>

<script>

import Multiselect from 'vue-multiselect';

export default {
  props: {
    title: {
      default: function () {
        return 'Shipping';
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
    tabKey: {
      default: function () {
        return 'shippingSettings';
      },
    },
    allowEditShippingCostLabel: {
      default: function () {
        return 'Allow to edit shipping cost';
      },
    },
    allowEditShippingCost: {
      default: function () {
        return false;
      },
    },
    allowEditShippingTitleLabel: {
      default: function () {
        return 'Allow to edit shipping title';
      },
    },
    allowEditShippingTitle: {
      default: function () {
        return false;
      },
    },
    orderDefaultShippingMethodLabel: {
      default: function () {
        return 'Default shipping method';
      }
    },
    orderShippingZonesList: {
      default: function () {
        return [];
      }
    },
    orderDefaultZonesShippingMethod: {
      default: function () {
        return {};
      }
    },
    allowToCreateOrdersWithoutShippingLabel: {
      default: function () {
        return 'Allow to create orders without shipping';
      },
    },
    allowToCreateOrdersWithoutShipping: {
      default: function () {
        return false;
      },
    },
    noOptionsTitle: {
      default: function () {
        return 'List is empty.';
      }
    },
  },
  mounted() {
    this.addSettingsTab(this.getTabsHeaders())
    this.setComponentsSettings(this.componentsSettings)
  },
  data() {

    var orderDefaultZonesShippingMethod = {};

    this.orderShippingZonesList.forEach((zone) => {
      orderDefaultZonesShippingMethod[zone.id] = this.getObjectByKeyValue(zone.shipping_methods, 'value', this.orderDefaultZonesShippingMethod[zone.id] || '');
    });

    return {
      elAllowEditShippingCost: this.allowEditShippingCost,
      elAllowEditShippingTitle: this.allowEditShippingTitle,
      elOrderDefaultZonesShippingMethod: orderDefaultZonesShippingMethod,
      elAllowToCreateOrdersWithoutShipping: this.allowToCreateOrdersWithoutShipping,
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

      var orderDefaultZonesShippingMethod = {};

      for (var zoneID in this.elOrderDefaultZonesShippingMethod) {
        orderDefaultZonesShippingMethod[zoneID] = this.getKeyValueOfObject(this.elOrderDefaultZonesShippingMethod[zoneID], 'value');
      }

      var settings = {
        allow_edit_shipping_cost: this.elAllowEditShippingCost,
        allow_edit_shipping_title: this.elAllowEditShippingTitle,
        order_default_zones_shipping_method: orderDefaultZonesShippingMethod,
        allow_to_create_orders_without_shipping: this.elAllowToCreateOrdersWithoutShipping,
      };

      return settings;
    },
    getTabsHeaders() {
      return {
        key: this.tabKey,
        title: this.title,
        menu_order: 130,
      };
    },
    showOption(key) {
      this.shown = this.tabKey === key;
    },
  },
  components: {
    Multiselect,
  },
}
</script>
